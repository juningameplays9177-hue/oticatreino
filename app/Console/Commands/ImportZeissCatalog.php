<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Console\Command;

class ImportZeissCatalog extends Command
{
    protected $signature = 'products:import-zeiss-catalog {--supplier=ZEISS} {--dry-run}';
    protected $description = 'Importa produtos do catálogo ZEISS para o banco de dados';

    public function handle()
    {
        $supplierName = $this->option('supplier') ?? 'ZEISS';
        $dryRun = $this->option('dry-run');

        $this->info("📄 Processando catálogo ZEISS...");

        // Extrair produtos do conteúdo fornecido
        $products = $this->extractZeissProducts();

        if (empty($products)) {
            $this->warn("⚠️  Nenhum produto encontrado!");
            return 1;
        }

        $this->info("✓ Encontrados " . count($products) . " produtos únicos");

        if ($dryRun) {
            $this->warn("🔍 Modo DRY-RUN - Nenhum dado será salvo");
            $this->displayProducts($products);
            return 0;
        }

        // Buscar ou criar tipo de produto (Lentes)
        $productType = ProductType::where('code_prefix', 'L')->first();
        if (!$productType) {
            $productType = ProductType::first();
            if (!$productType) {
                $this->error("Nenhum tipo de produto encontrado! Execute ProductTypesSeeder primeiro.");
                return 1;
            }
        }

        // Buscar ou criar fornecedor
        $supplier = Supplier::firstOrCreate(
            ['trade_name' => $supplierName],
            [
                'trade_name' => $supplierName,
                'legal_name' => $supplierName,
                'is_active' => true,
            ]
        );

        // Buscar ou criar marca ZEISS
        $brand = Brand::firstOrCreate(
            ['name' => 'ZEISS'],
            ['name' => 'ZEISS']
        );

        // Buscar lojas
        $stores = Store::where('active', true)->get();
        if ($stores->isEmpty()) {
            $firstStore = Store::first();
            if (!$firstStore) {
                $firstStore = Store::create([
                    'name' => 'Loja Padrão',
                    'code' => '001',
                    'active' => true,
                ]);
                $this->line("  ⚠️  Criada loja padrão: {$firstStore->name}");
            }
            $stores = collect([$firstStore]);
        }

        $this->info("📦 Iniciando cadastro de produtos...");
        $this->newLine();

        $bar = $this->output->createProgressBar(count($products));
        $bar->start();

        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        foreach ($products as $productData) {
            try {
                // Validar dados mínimos
                if (empty($productData['name']) || empty($productData['price']) || $productData['price'] <= 0) {
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }

                // Criar nome único incluindo índice e tratamento
                $uniqueName = $productData['name'];
                if (!empty($productData['index'])) {
                    $uniqueName .= ' - Índice ' . $productData['index'];
                }
                if (!empty($productData['treatment'])) {
                    $uniqueName .= ' - ' . $productData['treatment'];
                }

                // Verificar se produto já existe
                $existingProduct = Product::where('name', $uniqueName)
                    ->where('supplier_id', $supplier->id)
                    ->first();

                if ($existingProduct) {
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }

                // Gerar código do produto
                $ref = Product::generateRef($productType->id);

                // Criar produto
                $product = Product::create([
                    'ref' => $ref,
                    'name' => $uniqueName,
                    'description' => $productData['description'] ?? $uniqueName,
                    'product_type_id' => $productType->id,
                    'brand_id' => $brand->id,
                    'supplier_id' => $supplier->id,
                    'control_stock' => false,
                    'sell_only_with_os' => true,
                    'archived' => false, // Importante: produtos devem estar ativos para aparecer no PDV
                    'unit' => 'PAR',
                ]);

                // Criar preços
                $price = $productData['price'];
                $cost = $price * 0.5; // Custo estimado em 50% do preço

                foreach ($stores as $store) {
                    ProductPrice::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'store_id' => $store->id,
                        ],
                        [
                            'price' => $price,
                            'cost' => $cost,
                        ]
                    );
                }

                $successCount++;

            } catch (\Exception $e) {
                $errorCount++;
                $this->newLine();
                $this->error("Erro: " . ($productData['name'] ?? 'Desconhecido') . " - " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Importação concluída!");
        $this->table(
            ['Status', 'Quantidade'],
            [
                ['✅ Cadastrados', $successCount],
                ['⚠️  Ignorados', $skippedCount],
                ['❌ Erros', $errorCount],
                ['📊 Total processado', count($products)],
            ]
        );

        return 0;
    }

    protected function extractZeissProducts(): array
    {
        $products = [];
        
        // Primeiro, adicionar produtos exatos do catálogo
        $exactProducts = $this->getExactProducts();
        $products = array_merge($products, $exactProducts);
        
        // Padrões de produtos encontrados no catálogo com preços base
        $productPatterns = [
            // SmartLife Individual 3
            ['name' => 'ZEISS SmartLife Individual 3', 'base_price' => 2559, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza', 'Polarizada']],
            ['name' => 'ZEISS SmartLife', 'base_price' => 2269, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza', 'Polarizada']],
            ['name' => 'ZEISS Light 2', 'base_price' => 1780, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza', 'Polarizada']],
            ['name' => 'ZEISS SmartLife Young', 'base_price' => 2258, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza', 'Polarizada']],
            ['name' => 'ZEISS MyoCare', 'base_price' => 1999, 'variations' => ['Poli', '1.6', '1.67']],
            ['name' => 'ZEISS ClearView', 'base_price' => 990, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza']],
            ['name' => 'ZEISS ClassicPlus', 'base_price' => 279, 'variations' => ['Blueguard']],
            ['name' => 'ZEISS SmartLife Digital Individual 3', 'base_price' => 2213, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza', 'Polarizada']],
            ['name' => 'ZEISS Individual DriveSafe', 'base_price' => 2140, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza', 'Polarizada']],
            ['name' => 'ZEISS Individual Sport', 'base_price' => 2510, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza', 'Polarizada']],
            ['name' => 'ZEISS EnergizeMe', 'base_price' => 1454, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza']],
            
            // Multifocais
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'base_price' => 10989, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza', 'Polarizada']],
            ['name' => 'ZEISS Progressive SmartLife Superb', 'base_price' => 7280, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza', 'Polarizada']],
            ['name' => 'ZEISS Progressive SmartLife Plus', 'base_price' => 6280, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza', 'Polarizada']],
            ['name' => 'ZEISS Progressive SmartLife Pure', 'base_price' => 5380, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza', 'Polarizada']],
            ['name' => 'ZEISS Progressive SmartLife Essential', 'base_price' => 4480, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza', 'Polarizada']],
            ['name' => 'ZEISS Progressive Light 2 3Dv', 'base_price' => 4089, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza', 'Polarizada']],
            ['name' => 'ZEISS Progressive Light 2 3D', 'base_price' => 3339, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza', 'Polarizada']],
            ['name' => 'ZEISS Progressive Light 2 D', 'base_price' => 2639, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza', 'Polarizada']],
            ['name' => 'ZEISS Progressive GT2', 'base_price' => 1989, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza']],
            ['name' => 'ZEISS Progressive ClassicPlus', 'base_price' => 1589, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza']],
            ['name' => 'ZEISS OfficeLens Individual', 'base_price' => 3880, 'variations' => ['Blueguard']],
            ['name' => 'ZEISS Progressive Individual DriveSafe', 'base_price' => 4990, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza', 'Polarizada']],
            ['name' => 'ZEISS Progressive Individual Sport', 'base_price' => 4754, 'variations' => ['Blueguard', 'PhotoFusion X Cinza', 'PhotoFusion X Extra Dark Cinza', 'Polarizada']],
        ];

        // Índices de refração
        $indices = ['1.5', 'Poli', '1.6', '1.67', '1.74'];
        
        // Tratamentos antirreflexo
        $treatments = [
            'DuraVision Gold UV' => 0,
            'DuraVision Platinum UV' => 0,
            'DuraVision Silver UV' => 0,
            'DuraVision Chrome UV' => 0,
        ];

        // Processar cada padrão de produto
        foreach ($productPatterns as $pattern) {
            $baseName = $pattern['name'];
            $basePrice = $pattern['base_price'];
            
            // Para cada índice
            foreach ($indices as $index) {
                // Para cada variação
                foreach ($pattern['variations'] as $variation) {
                    // Calcular preço baseado no índice (ajustar conforme necessário)
                    $priceMultiplier = 1.0;
                    if ($index === '1.6') $priceMultiplier = 1.1;
                    elseif ($index === '1.67') $priceMultiplier = 1.3;
                    elseif ($index === '1.74') $priceMultiplier = 1.5;
                    elseif ($index === 'Poli') $priceMultiplier = 1.05;
                    
                    // Ajustar preço para variações
                    $variationMultiplier = 1.0;
                    if (stripos($variation, 'PhotoFusion') !== false) $variationMultiplier = 1.4;
                    elseif (stripos($variation, 'Polarizada') !== false) $variationMultiplier = 1.3;
                    
                    $price = round($basePrice * $priceMultiplier * $variationMultiplier, 2);
                    
                    $products[] = [
                        'name' => $baseName,
                        'index' => $index,
                        'treatment' => $variation,
                        'price' => $price,
                        'description' => "{$baseName} - Índice {$index} - {$variation}",
                    ];
                }
            }
        }

        // Produtos já foram adicionados no início

        // Remover duplicados
        $uniqueProducts = [];
        $seen = [];
        
        foreach ($products as $product) {
            $key = $product['name'] . '_' . ($product['index'] ?? '') . '_' . ($product['treatment'] ?? '');
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueProducts[] = $product;
            }
        }

        return $uniqueProducts;
    }

    protected function getExactProducts(): array
    {
        // Produtos com preços exatos extraídos do catálogo ZEISS
        // Baseado nas tabelas de preços do catálogo fornecido
        $products = [
            // Colorações
            ['name' => 'Coloração Padrão FARB ZEISS', 'index' => '1.50', 'treatment' => 'Padrão', 'price' => 240.00],
            ['name' => 'Coloração Padrão FARB ZEISS', 'index' => '1.60', 'treatment' => 'Padrão', 'price' => 240.00],
            ['name' => 'Coloração Padrão FARB ZEISS', 'index' => '1.67', 'treatment' => 'Padrão', 'price' => 240.00],
            ['name' => 'Coloração Degradê Padrão FARB ZEISS', 'index' => '1.5', 'treatment' => 'Degradê', 'price' => 270.00],
            ['name' => 'Coloração Degradê Padrão FARB ZEISS', 'index' => '1.60', 'treatment' => 'Degradê', 'price' => 270.00],
            ['name' => 'Coloração Degradê Padrão FARB ZEISS', 'index' => '1.67', 'treatment' => 'Degradê', 'price' => 270.00],
            ['name' => 'Coloração com Amostra', 'index' => '1.5', 'treatment' => 'Amostra', 'price' => 450.00],
            ['name' => 'Coloração com Amostra', 'index' => '1.60', 'treatment' => 'Amostra', 'price' => 450.00],
            ['name' => 'Coloração com Amostra', 'index' => '1.67', 'treatment' => 'Amostra', 'price' => 450.00],
            ['name' => 'Coloração Dégradê com Amostra', 'index' => '1.5', 'treatment' => 'Dégradê Amostra', 'price' => 490.00],
            ['name' => 'Coloração Dégradê com Amostra', 'index' => '1.60', 'treatment' => 'Dégradê Amostra', 'price' => 490.00],
            ['name' => 'Coloração Dégradê com Amostra', 'index' => '1.67', 'treatment' => 'Dégradê Amostra', 'price' => 490.00],
            
            // Tratamentos especiais
            ['name' => 'ZEISS SKYLET', 'index' => '1.50', 'treatment' => 'SKYLET', 'price' => 490.00],
            ['name' => 'Filtro Medicinal', 'index' => '1.50', 'treatment' => 'Filtro Medicinal', 'price' => 1290.00],
            
            // Tratamentos antirreflexo
            ['name' => 'Tratamento DuraVision Gold UV', 'index' => '', 'treatment' => 'DuraVision Gold UV', 'price' => 1290.00],
            ['name' => 'Tratamento DuraVision DriveSafe', 'index' => '', 'treatment' => 'DuraVision DriveSafe', 'price' => 1150.00],
            ['name' => 'Tratamento DuraVision Platinum UV', 'index' => '', 'treatment' => 'DuraVision Platinum UV', 'price' => 1150.00],
            ['name' => 'Tratamento DuraVision Silver UV', 'index' => '', 'treatment' => 'DuraVision Silver UV', 'price' => 890.00],
            ['name' => 'Tratamento DuraVision Chrome UV', 'index' => '', 'treatment' => 'DuraVision Chrome UV', 'price' => 400.00],
            
            // SmartLife Individual 3 - Preços reais do catálogo
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 2559.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 2419.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 2159.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 1669.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 2919.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 2779.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 2519.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 2029.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.6', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 3139.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.6', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 2999.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.6', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 2739.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.6', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 2249.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.67', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 3940.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.67', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 4299.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.67', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 4039.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.67', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 3549.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.74', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 4749.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.74', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 4609.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.74', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 4349.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.74', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 3859.00],
            
            // SmartLife Individual 3 PhotoFusion X
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.5', 'treatment' => 'PhotoFusion X Cinza DuraVision Gold UV', 'price' => 3649.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.5', 'treatment' => 'PhotoFusion X Cinza DuraVision Platinum UV', 'price' => 3509.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.5', 'treatment' => 'PhotoFusion X Cinza DuraVision Silver UV', 'price' => 3249.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.5', 'treatment' => 'PhotoFusion X Cinza DuraVision Chrome UV', 'price' => 2759.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => 'Poli', 'treatment' => 'PhotoFusion X Cinza DuraVision Gold UV', 'price' => 4009.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => 'Poli', 'treatment' => 'PhotoFusion X Cinza DuraVision Platinum UV', 'price' => 3869.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => 'Poli', 'treatment' => 'PhotoFusion X Cinza DuraVision Silver UV', 'price' => 3609.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => 'Poli', 'treatment' => 'PhotoFusion X Cinza DuraVision Chrome UV', 'price' => 3119.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.6', 'treatment' => 'PhotoFusion X Cinza DuraVision Gold UV', 'price' => 4229.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.6', 'treatment' => 'PhotoFusion X Cinza DuraVision Platinum UV', 'price' => 4089.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.6', 'treatment' => 'PhotoFusion X Cinza DuraVision Silver UV', 'price' => 3829.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.6', 'treatment' => 'PhotoFusion X Cinza DuraVision Chrome UV', 'price' => 3339.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.67', 'treatment' => 'PhotoFusion X Cinza DuraVision Gold UV', 'price' => 5529.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.67', 'treatment' => 'PhotoFusion X Cinza DuraVision Platinum UV', 'price' => 5389.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.67', 'treatment' => 'PhotoFusion X Cinza DuraVision Silver UV', 'price' => 5129.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.67', 'treatment' => 'PhotoFusion X Cinza DuraVision Chrome UV', 'price' => 4639.00],
            
            // SmartLife Individual 3 Polarizada
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.5', 'treatment' => 'Polarizada DuraVision Gold UV', 'price' => 3399.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.5', 'treatment' => 'Polarizada DuraVision Platinum UV', 'price' => 3259.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.5', 'treatment' => 'Polarizada DuraVision Silver UV', 'price' => 2999.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.5', 'treatment' => 'Polarizada DuraVision Chrome UV', 'price' => 2509.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.6', 'treatment' => 'Polarizada DuraVision Gold UV', 'price' => 3979.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.6', 'treatment' => 'Polarizada DuraVision Platinum UV', 'price' => 3839.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.6', 'treatment' => 'Polarizada DuraVision Silver UV', 'price' => 3579.00],
            ['name' => 'ZEISS SmartLife Individual 3', 'index' => '1.6', 'treatment' => 'Polarizada DuraVision Chrome UV', 'price' => 3089.00],
            
            // SmartLife (padrão)
            ['name' => 'ZEISS SmartLife', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 2269.00],
            ['name' => 'ZEISS SmartLife', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 2129.00],
            ['name' => 'ZEISS SmartLife', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 1869.00],
            ['name' => 'ZEISS SmartLife', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 1379.00],
            ['name' => 'ZEISS SmartLife', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 2629.00],
            ['name' => 'ZEISS SmartLife', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 2489.00],
            ['name' => 'ZEISS SmartLife', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 2229.00],
            ['name' => 'ZEISS SmartLife', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 1739.00],
            ['name' => 'ZEISS SmartLife', 'index' => '1.6', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 2849.00],
            ['name' => 'ZEISS SmartLife', 'index' => '1.6', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 2709.00],
            ['name' => 'ZEISS SmartLife', 'index' => '1.6', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 2449.00],
            ['name' => 'ZEISS SmartLife', 'index' => '1.6', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 1959.00],
            ['name' => 'ZEISS SmartLife', 'index' => '1.67', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 4149.00],
            ['name' => 'ZEISS SmartLife', 'index' => '1.67', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 4009.00],
            ['name' => 'ZEISS SmartLife', 'index' => '1.67', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 3749.00],
            ['name' => 'ZEISS SmartLife', 'index' => '1.67', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 3259.00],
            ['name' => 'ZEISS SmartLife', 'index' => '1.74', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 4459.00],
            ['name' => 'ZEISS SmartLife', 'index' => '1.74', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 4319.00],
            ['name' => 'ZEISS SmartLife', 'index' => '1.74', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 4059.00],
            ['name' => 'ZEISS SmartLife', 'index' => '1.74', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 3569.00],
            
            // Light 2
            ['name' => 'ZEISS Light 2', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 1780.00],
            ['name' => 'ZEISS Light 2', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 1640.00],
            ['name' => 'ZEISS Light 2', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 1380.00],
            ['name' => 'ZEISS Light 2', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 890.00],
            ['name' => 'ZEISS Light 2', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 2140.00],
            ['name' => 'ZEISS Light 2', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 2000.00],
            ['name' => 'ZEISS Light 2', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 1740.00],
            ['name' => 'ZEISS Light 2', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 1250.00],
            ['name' => 'ZEISS Light 2', 'index' => '1.6', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 2360.00],
            ['name' => 'ZEISS Light 2', 'index' => '1.6', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 2220.00],
            ['name' => 'ZEISS Light 2', 'index' => '1.6', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 1960.00],
            ['name' => 'ZEISS Light 2', 'index' => '1.6', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 1470.00],
            ['name' => 'ZEISS Light 2', 'index' => '1.67', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 3660.00],
            ['name' => 'ZEISS Light 2', 'index' => '1.67', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 3520.00],
            ['name' => 'ZEISS Light 2', 'index' => '1.67', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 3260.00],
            ['name' => 'ZEISS Light 2', 'index' => '1.67', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 2770.00],
            ['name' => 'ZEISS Light 2', 'index' => '1.74', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 3970.00],
            ['name' => 'ZEISS Light 2', 'index' => '1.74', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 3830.00],
            ['name' => 'ZEISS Light 2', 'index' => '1.74', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 3570.00],
            ['name' => 'ZEISS Light 2', 'index' => '1.74', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 3080.00],
            
            // ClearView
            ['name' => 'ZEISS ClearView', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 990.00],
            ['name' => 'ZEISS ClearView', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 890.00],
            ['name' => 'ZEISS ClearView', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 699.00],
            ['name' => 'ZEISS ClearView', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 489.00],
            ['name' => 'ZEISS ClearView', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 1252.00],
            ['name' => 'ZEISS ClearView', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 1149.00],
            ['name' => 'ZEISS ClearView', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 886.00],
            ['name' => 'ZEISS ClearView', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 699.00],
            ['name' => 'ZEISS ClearView', 'index' => '1.6', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 1357.00],
            ['name' => 'ZEISS ClearView', 'index' => '1.6', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 1269.00],
            ['name' => 'ZEISS ClearView', 'index' => '1.6', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 1090.00],
            ['name' => 'ZEISS ClearView', 'index' => '1.6', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 899.00],
            ['name' => 'ZEISS ClearView', 'index' => '1.67', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 1672.00],
            ['name' => 'ZEISS ClearView', 'index' => '1.67', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 1605.00],
            ['name' => 'ZEISS ClearView', 'index' => '1.67', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 1420.00],
            ['name' => 'ZEISS ClearView', 'index' => '1.74', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 2292.00],
            ['name' => 'ZEISS ClearView', 'index' => '1.74', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 2255.00],
            
            // ClassicPlus
            ['name' => 'ZEISS ClassicPlus', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 279.00],
            ['name' => 'ZEISS ClassicPlus', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 399.00],
            
            // MyoCare
            ['name' => 'ZEISS MyoCare', 'index' => 'Poli', 'treatment' => 'DuraVision Platinum UV', 'price' => 1999.00],
            ['name' => 'ZEISS MyoCare', 'index' => '1.6', 'treatment' => 'DuraVision Platinum UV', 'price' => 2399.00],
            ['name' => 'ZEISS MyoCare', 'index' => '1.67', 'treatment' => 'DuraVision Platinum UV', 'price' => 2699.00],
            ['name' => 'ZEISS MyoCare S', 'index' => 'Poli', 'treatment' => 'DuraVision Platinum UV', 'price' => 1999.00],
            ['name' => 'ZEISS MyoCare S', 'index' => '1.6', 'treatment' => 'DuraVision Platinum UV', 'price' => 2399.00],
            ['name' => 'ZEISS MyoCare S', 'index' => '1.67', 'treatment' => 'DuraVision Platinum UV', 'price' => 2699.00],
            
            // Multifocais - Progressive SmartLife Individual 3
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 10989.00],
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 10849.00],
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 10589.00],
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'index' => '1.5', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 10099.00],
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 11579.00],
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 11439.00],
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 11179.00],
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'index' => 'Poli', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 10689.00],
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'index' => '1.6', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 12239.00],
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'index' => '1.6', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 12099.00],
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'index' => '1.6', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 11839.00],
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'index' => '1.6', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 11349.00],
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'index' => '1.67', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 13879.00],
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'index' => '1.67', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 13739.00],
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'index' => '1.67', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 13479.00],
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'index' => '1.67', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 12989.00],
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'index' => '1.74', 'treatment' => 'Blueguard DuraVision Gold UV', 'price' => 15479.00],
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'index' => '1.74', 'treatment' => 'Blueguard DuraVision Platinum UV', 'price' => 15339.00],
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'index' => '1.74', 'treatment' => 'Blueguard DuraVision Silver UV', 'price' => 15079.00],
            ['name' => 'ZEISS Progressive SmartLife Individual 3', 'index' => '1.74', 'treatment' => 'Blueguard DuraVision Chrome UV', 'price' => 14589.00],
        ];
        
        return $products;
    }

    protected function displayProducts(array $products): void
    {
        $this->newLine();
        $this->info("Produtos encontrados (primeiros 30):");
        $this->newLine();

        $displayProducts = array_slice($products, 0, 30);

        $tableData = [];
        foreach ($displayProducts as $product) {
            $name = substr($product['name'] ?? '-', 0, 40);
            if (!empty($product['index'])) {
                $name .= ' (' . $product['index'] . ')';
            }
            $tableData[] = [
                $name,
                $product['treatment'] ?? '-',
                'R$ ' . number_format($product['price'] ?? 0, 2, ',', '.'),
            ];
        }

        $this->table(
            ['Nome', 'Tratamento', 'Preço'],
            $tableData
        );

        if (count($products) > 30) {
            $this->line("... e mais " . (count($products) - 30) . " produtos");
        }
    }
}

