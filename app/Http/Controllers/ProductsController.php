<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use App\Models\ProductGroup;
use App\Models\ProductSubgroup;
use App\Models\Supplier;
use App\Models\Store;
use App\Models\ProductPrice;
use App\Models\ProductStock;
use App\Models\ProductColor;
use App\Models\ProductSize;
use App\Models\ProductShape;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Traits\HasStoreFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class ProductsController extends Controller
{
    use HasStoreFilter;
    
    public function index(Request $request)
    {
        $query = Product::with(['brand', 'group', 'subgroup', 'supplier', 'images']);
        
        // Produtos são compartilhados entre lojas, mas podemos filtrar preços/estoques por loja depois
        // Não filtrar produtos aqui, apenas aplicar filtro de loja nos preços/estoques quando necessário

        // Busca livre
        if ($request->filled('q')) {
            $query->search($request->q);
        }

        // Filtros
        $filters = $request->only([
            'group_id', 'subgroup_id', 'brand_id', 'supplier_id',
            'archived_mode', 'has_photos', 'showcase_enabled', 'from', 'to'
        ]);

        // Período
        if ($request->filled('period')) {
            $period = $request->period;
            $now = Carbon::now();
            switch ($period) {
                case 'hoje':
                    $filters['from'] = $now->startOfDay()->format('Y-m-d');
                    $filters['to'] = $now->endOfDay()->format('Y-m-d');
                    break;
                case 'ontem':
                    $filters['from'] = $now->copy()->subDay()->startOfDay()->format('Y-m-d');
                    $filters['to'] = $now->copy()->subDay()->endOfDay()->format('Y-m-d');
                    break;
                case 'esta_semana':
                    $filters['from'] = $now->startOfWeek()->format('Y-m-d');
                    $filters['to'] = $now->endOfWeek()->format('Y-m-d');
                    break;
                case 'ultimos_7':
                    $filters['from'] = $now->copy()->subDays(7)->format('Y-m-d');
                    $filters['to'] = $now->format('Y-m-d');
                    break;
                case 'ultimos_30':
                    $filters['from'] = $now->copy()->subDays(30)->format('Y-m-d');
                    $filters['to'] = $now->format('Y-m-d');
                    break;
                case 'este_mes':
                    $filters['from'] = $now->startOfMonth()->format('Y-m-d');
                    $filters['to'] = $now->endOfMonth()->format('Y-m-d');
                    break;
                case 'mes_anterior':
                    $filters['from'] = $now->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                    $filters['to'] = $now->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                    break;
                case 'este_ano':
                    $filters['from'] = $now->startOfYear()->format('Y-m-d');
                    $filters['to'] = $now->endOfYear()->format('Y-m-d');
                    break;
            }
        }

        $query->filters($filters);

        // Ordenação
        $sort = $request->get('sort', 'created_desc');
        switch ($sort) {
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'created_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'created_desc':
                $query->orderBy('created_at', 'desc');
                break;
            case 'price_asc':
                $query->leftJoin('product_prices', 'products.id', '=', 'product_prices.product_id')
                    ->select('products.*')
                    ->orderBy('product_prices.price', 'asc');
                break;
            case 'price_desc':
                $query->leftJoin('product_prices', 'products.id', '=', 'product_prices.product_id')
                    ->select('products.*')
                    ->orderBy('product_prices.price', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $products = $query->paginate(50)->withQueryString();

        // Dados para filtros
        $groups = ProductGroup::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $suppliers = Supplier::orderBy('trade_name')->orderBy('legal_name')->get();

        return view('products.index', compact('products', 'groups', 'brands', 'suppliers'));
    }

    public function create()
    {
        $brands = Brand::orderBy('name')->get();
        $groups = ProductGroup::orderBy('name')->get();
        $suppliers = Supplier::orderBy('trade_name')->orderBy('legal_name')->get();
        $productTypes = \App\Models\ProductType::where('is_active', true)->orderBy('name')->get();
        
        // Buscar cores, tamanhos e formatos
        $colors = collect([]);
        $sizes = collect([]);
        $shapes = collect([]);
        
        try {
            if (Schema::hasTable('product_colors')) {
                $colors = ProductColor::where('is_active', true)->orderBy('name')->get();
            }
            if (Schema::hasTable('product_sizes')) {
                $sizes = ProductSize::where('is_active', true)->orderBy('order')->orderBy('name')->get();
            }
            if (Schema::hasTable('product_shapes')) {
                $shapes = ProductShape::where('is_active', true)->orderBy('name')->get();
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao buscar cores/tamanhos/formatos: ' . $e->getMessage());
        }
        
        // Buscar lojas ativas
        try {
            // Verificar se a tabela stores existe
            if (Schema::hasTable('stores')) {
                $stores = Store::where('active', true)->orderBy('name')->get();
                
                // Se não houver lojas, criar automaticamente baseado nas empresas ativas
                if ($stores->isEmpty() && Schema::hasTable('companies')) {
                    try {
                        $companies = \App\Models\Company::where('is_active', true)->get();
                        foreach ($companies as $company) {
                            // Verificar se já existe uma loja com o nome da empresa
                            $storeName = $company->trade_name ?: $company->legal_name;
                            if (empty($storeName)) {
                                continue; // Pular se não houver nome
                            }
                            
                            $existingStore = Store::where('name', $storeName)->first();
                            if (!$existingStore) {
                                // Gerar código e sigla baseado no slug
                                $codeAndAbbrev = Store::generateCodeAndAbbreviation($company->slug, $company->id);
                                
                                Store::create([
                                    'name' => $storeName,
                                    'code' => $codeAndAbbrev['code'],
                                    'abbreviation' => $codeAndAbbrev['abbreviation'],
                                    'company_id' => $company->id,
                                    'active' => true,
                                ]);
                            }
                        }
                        // Buscar novamente após criar
                        $stores = Store::where('active', true)->orderBy('name')->get();
                    } catch (\Exception $e) {
                        // Se houver erro ao criar lojas, continuar sem lojas
                        Log::warning('Erro ao criar lojas automaticamente: ' . $e->getMessage());
                        $stores = collect([]);
                    }
                }
            } else {
                $stores = collect([]);
            }
        } catch (\Exception $e) {
            // Se houver erro ao buscar lojas, usar coleção vazia
            Log::error('Erro ao buscar lojas: ' . $e->getMessage());
            $stores = collect([]);
        }

        return view('products.create', compact('brands', 'groups', 'suppliers', 'stores', 'colors', 'sizes', 'shapes', 'productTypes'));
    }

    public function store(StoreProductRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            
            $isService = ($data['item_type'] ?? 'PRODUTO') === 'SERVICO';
            
            // Se for serviço e não tiver descrição, usar o nome do tipo de produto
            if ($isService && empty($data['description'])) {
                $productType = \App\Models\ProductType::find($data['product_type_id']);
                if ($productType) {
                    $data['description'] = $productType->name;
                }
            }
            
            // Se for serviço e não tiver unidade, definir como UN
            if ($isService && empty($data['unit'])) {
                $data['unit'] = 'UN';
            }

            // Usar descrição como nome se nome não for fornecido
            if (empty($data['name']) && !empty($data['description'])) {
                // Pegar primeiras palavras da descrição como nome (máximo 190 caracteres)
                $data['name'] = mb_substr(strip_tags($data['description']), 0, 190);
            }

            // Gerar ref sequencial automaticamente baseado no tipo de produto
            $data['ref'] = Product::generateRef($data['product_type_id'] ?? null);

            // Gerar label_code se vazio
            if (empty($data['label_code'])) {
                $data['label_code'] = Product::generateLabelCode();
            }

            // Criar produto
            $product = Product::create($data);

            // Salvar preços e estoques por loja (se houver lojas e dados de preços)
            // Serviços não têm preço fixo nem estoque
            $isService = ($data['item_type'] ?? 'PRODUTO') === 'SERVICO';
            
            if (!$isService && $request->has('prices') && is_array($request->prices)) {
                foreach ($request->prices as $storeId => $priceData) {
                    // Converter valores de formato brasileiro para decimal
                    $cost = $this->parseBrazilianNumber($priceData['cost'] ?? '0');
                    $margin = $this->parseBrazilianNumber($priceData['margin_percent'] ?? '0');
                    $price = $this->parseBrazilianNumber($priceData['price'] ?? '0');
                    
                    // Só salvar se houver custo ou preço definido
                    if ($cost > 0 || $price > 0) {
                        // Calcular preço se margin_percent > 0 e price vazio
                        if ($margin > 0 && $price == 0 && $cost > 0) {
                            $calculatedPrice = round($cost * (1 + $margin / 100), 2);
                        } else {
                            $calculatedPrice = $price;
                        }

                        ProductPrice::updateOrCreate(
                            [
                                'product_id' => $product->id,
                                'store_id' => $storeId,
                            ],
                            [
                                'cost' => $cost,
                                'margin_percent' => $margin,
                                'price' => $calculatedPrice,
                            ]
                        );
                    }

                    // Salvar estoque sempre (quantidade será salva automaticamente)
                    $qty = intval($priceData['qty'] ?? 0);
                    if ($qty > 0 || isset($priceData['qty'])) {
                        ProductStock::updateOrCreate(
                            [
                                'product_id' => $product->id,
                                'store_id' => $storeId,
                            ],
                            [
                                'qty' => $qty,
                            ]
                        );
                    }
                }
            } elseif ($isService) {
                // Remover preços e estoques existentes se mudou para serviço
                ProductPrice::where('product_id', $product->id)->delete();
                ProductStock::where('product_id', $product->id)->delete();
            }

            // Upload de imagens
            if ($request->hasFile('images')) {
                $this->storeImages($product, $request->file('images'));
            }

            DB::commit();

            return redirect()->route('products.index')
                ->with('success', 'Produto criado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao criar produto: ' . $e->getMessage());
        }
    }

    public function edit(Product $product)
    {
        $product->load(['brand', 'group', 'subgroup', 'supplier', 'prices.store', 'stocks.store', 'images']);
        $brands = Brand::orderBy('name')->get();
        $groups = ProductGroup::orderBy('name')->get();
        $suppliers = Supplier::orderBy('trade_name')->orderBy('legal_name')->get();
        $productTypes = \App\Models\ProductType::where('is_active', true)->orderBy('name')->get();
        
        // Buscar cores, tamanhos e formatos
        $colors = collect([]);
        $sizes = collect([]);
        $shapes = collect([]);
        
        try {
            if (Schema::hasTable('product_colors')) {
                $colors = ProductColor::where('is_active', true)->orderBy('name')->get();
            }
            if (Schema::hasTable('product_sizes')) {
                $sizes = ProductSize::where('is_active', true)->orderBy('order')->orderBy('name')->get();
            }
            if (Schema::hasTable('product_shapes')) {
                $shapes = ProductShape::where('is_active', true)->orderBy('name')->get();
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao buscar cores/tamanhos/formatos: ' . $e->getMessage());
        }
        
        // Buscar lojas ativas
        try {
            // Verificar se a tabela stores existe
            if (Schema::hasTable('stores')) {
                $stores = Store::where('active', true)->orderBy('name')->get();
                
                // Se não houver lojas, criar automaticamente baseado nas empresas ativas
                if ($stores->isEmpty() && Schema::hasTable('companies')) {
                    try {
                        $companies = \App\Models\Company::where('is_active', true)->get();
                        foreach ($companies as $company) {
                            // Verificar se já existe uma loja com o nome da empresa
                            $storeName = $company->trade_name ?: $company->legal_name;
                            if (empty($storeName)) {
                                continue; // Pular se não houver nome
                            }
                            
                            $existingStore = Store::where('name', $storeName)->first();
                            if (!$existingStore) {
                                // Gerar código e sigla baseado no slug
                                $codeAndAbbrev = Store::generateCodeAndAbbreviation($company->slug, $company->id);
                                
                                Store::create([
                                    'name' => $storeName,
                                    'code' => $codeAndAbbrev['code'],
                                    'abbreviation' => $codeAndAbbrev['abbreviation'],
                                    'company_id' => $company->id,
                                    'active' => true,
                                ]);
                            }
                        }
                        // Buscar novamente após criar
                        $stores = Store::where('active', true)->orderBy('name')->get();
                    } catch (\Exception $e) {
                        // Se houver erro ao criar lojas, continuar sem lojas
                        Log::warning('Erro ao criar lojas automaticamente: ' . $e->getMessage());
                        $stores = collect([]);
                    }
                }
            } else {
                $stores = collect([]);
            }
        } catch (\Exception $e) {
            // Se houver erro ao buscar lojas, usar coleção vazia
            Log::error('Erro ao buscar lojas: ' . $e->getMessage());
            $stores = collect([]);
        }

        // Carregar preços e estoques por loja
        $pricesByStore = [];
        $stocksByStore = [];
        foreach ($stores as $store) {
            $price = $product->prices->where('store_id', $store->id)->first();
            $stock = $product->stocks->where('store_id', $store->id)->first();
            $pricesByStore[$store->id] = $price;
            $stocksByStore[$store->id] = $stock;
        }

        return view('products.edit', compact('product', 'brands', 'groups', 'suppliers', 'stores', 'pricesByStore', 'stocksByStore', 'productTypes', 'colors', 'sizes', 'shapes'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            
            $isService = ($data['item_type'] ?? $product->item_type ?? 'PRODUTO') === 'SERVICO';
            
            // Se for serviço e não tiver descrição, usar o nome do tipo de produto
            if ($isService && empty($data['description'])) {
                $productType = \App\Models\ProductType::find($data['product_type_id'] ?? $product->product_type_id);
                if ($productType) {
                    $data['description'] = $productType->name;
                }
            }
            
            // Se for serviço e não tiver unidade, definir como UN
            if ($isService && empty($data['unit'])) {
                $data['unit'] = 'UN';
            }

            // Usar descrição como nome se nome não for fornecido
            if (empty($data['name']) && !empty($data['description'])) {
                // Pegar primeiras palavras da descrição como nome (máximo 190 caracteres)
                $data['name'] = mb_substr(strip_tags($data['description']), 0, 190);
            }

            // Atualizar produto
            $product->update($data);

            // Salvar preços e estoques por loja
            // Serviços não têm preço fixo nem estoque
            $isService = ($data['item_type'] ?? $product->item_type ?? 'PRODUTO') === 'SERVICO';
            
            if (!$isService && $request->has('prices')) {
                foreach ($request->prices as $storeId => $priceData) {
                    $cost = floatval($priceData['cost'] ?? 0);
                    $margin = floatval($priceData['margin_percent'] ?? 0);
                    $price = floatval($priceData['price'] ?? 0);
                    
                    // Calcular preço se margin_percent > 0 e price vazio
                    if ($margin > 0 && $price == 0) {
                        $calculatedPrice = round($cost * (1 + $margin / 100), 2);
                    } else {
                        $calculatedPrice = $price;
                    }

                        ProductPrice::updateOrCreate(
                            [
                                'product_id' => $product->id,
                                'store_id' => $storeId,
                            ],
                            [
                                'cost' => $cost,
                                'margin_percent' => $margin,
                                'price' => $calculatedPrice,
                            ]
                        );

                        // Salvar estoque se control_stock = true
                        if ($product->control_stock) {
                            ProductStock::updateOrCreate(
                                [
                                    'product_id' => $product->id,
                                    'store_id' => $storeId,
                                ],
                                [
                                    'qty' => intval($priceData['qty'] ?? 0),
                                ]
                            );
                        } else {
                        // Se não controla estoque, remover estoques
                        ProductStock::where('product_id', $product->id)
                            ->where('store_id', $storeId)
                            ->delete();
                    }
                }
            } elseif ($isService) {
                // Remover preços e estoques existentes se mudou para serviço
                ProductPrice::where('product_id', $product->id)->delete();
                ProductStock::where('product_id', $product->id)->delete();
            }

            // Upload de novas imagens
            if ($request->hasFile('images')) {
                $this->storeImages($product, $request->file('images'));
            }

            DB::commit();

            return redirect()->route('products.index')
                ->with('success', 'Produto atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao atualizar produto: ' . $e->getMessage());
        }
    }

    public function destroy(Product $product)
    {
        // Arquivar/desarquivar
        $product->archived = !$product->archived;
        $product->save();

        $message = $product->archived ? 'Produto arquivado com sucesso!' : 'Produto desarquivado com sucesso!';

        return redirect()->route('products.index')
            ->with('success', $message);
    }

    private function storeImages(Product $product, array $images)
    {
        $existingPositions = $product->images()->pluck('position')->toArray();
        $availablePositions = array_diff([1, 2, 3, 4, 5], $existingPositions);

        foreach ($images as $index => $image) {
            if (count($existingPositions) >= 5) {
                break; // Limite de 5 imagens
            }

            // Encontrar próxima posição disponível
            $position = !empty($availablePositions) ? min($availablePositions) : count($existingPositions) + 1;
            $availablePositions = array_diff($availablePositions, [$position]);

            // Salvar arquivo
            $path = $image->store("products/{$product->id}", 'public');
            
            // Criar registro
            $product->images()->create([
                'path' => $path,
                'position' => $position,
            ]);

            $existingPositions[] = $position;
        }
    }

    /**
     * Converte número em formato brasileiro (1.234,56) para float
     */
    private function parseBrazilianNumber($value)
    {
        if (empty($value)) return 0;
        // Remove pontos (separadores de milhar) e substitui vírgula por ponto
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        return floatval($value);
    }
}

