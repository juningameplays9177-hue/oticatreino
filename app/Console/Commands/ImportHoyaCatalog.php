<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Console\Command;

class ImportHoyaCatalog extends Command
{
    protected $signature = 'products:import-hoya-catalog {--supplier=HOYA} {--dry-run}';
    protected $description = 'Importa produtos do catálogo HOYA para o banco de dados';

    public function handle()
    {
        $supplierName = $this->option('supplier') ?? 'HOYA';
        $dryRun = $this->option('dry-run');

        $this->info("📄 Processando catálogo HOYA...");

        // Extrair produtos do conteúdo fornecido
        $products = $this->extractHoyaProducts();

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

        // Buscar ou criar marca HOYA
        $brand = Brand::firstOrCreate(
            ['name' => 'HOYA'],
            ['name' => 'HOYA']
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

        // Buscar último código antes do loop para garantir sequência única
        $prefix = $productType->code_prefix ?? 'L';
        
        // Tentar encontrar último produto com 3 ou 4 dígitos
        $lastProduct = Product::where('ref', 'like', $prefix . '%')
            ->whereRaw('LENGTH(ref) IN (?, ?)', [strlen($prefix) + 3, strlen($prefix) + 4])
            ->orderByRaw('CAST(SUBSTRING(ref, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->first();
        
        $lastNumber = 0;
        $digits = 3; // Padrão: 3 dígitos (L001)
        
        if ($lastProduct && $lastProduct->ref) {
            $lastNumber = (int) substr($lastProduct->ref, strlen($prefix));
            // Se o último número tem 4 dígitos ou mais, usar 4 dígitos
            if ($lastNumber >= 1000) {
                $digits = 4;
            }
        }

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

                // Gerar código único sequencial
                $lastNumber++;
                // Se passar de 999, usar 4 dígitos
                if ($lastNumber > 999 && $digits == 3) {
                    $digits = 4;
                }
                $ref = $prefix . str_pad($lastNumber, $digits, '0', STR_PAD_LEFT);
                
                // Verificar se código já existe (segurança extra)
                while (Product::where('ref', $ref)->exists()) {
                    $lastNumber++;
                    if ($lastNumber > 999 && $digits == 3) {
                        $digits = 4;
                    }
                    $ref = $prefix . str_pad($lastNumber, $digits, '0', STR_PAD_LEFT);
                }

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

    protected function extractHoyaProducts(): array
    {
        $products = [];
        
        // Primeiro, adicionar produtos exatos do catálogo
        $exactProducts = $this->getExactProducts();
        $products = array_merge($products, $exactProducts);

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
        // Produtos com preços exatos extraídos do catálogo HOYA
        $products = [
            
            // MiYOSMART - Lentes Prontas
            ['name' => 'HOYA MiYOSMART', 'index' => 'Poli', 'treatment' => 'Antirreflexo MiYOSMART Incolor', 'price' => 2099.00],
            ['name' => 'HOYA MiYOSMART', 'index' => 'Poli', 'treatment' => 'Antirreflexo MiYOSMART Fotossensível Clear', 'price' => 2099.00],
            ['name' => 'HOYA MiYOSMART', 'index' => 'Poli', 'treatment' => 'Antirreflexo MiYOSMART Polarizado', 'price' => 2099.00],
            ['name' => 'HOYA MiYOSMART', 'index' => 'Poli', 'treatment' => 'Antirreflexo MiYOSMART Chameleon', 'price' => 2099.00],
            ['name' => 'HOYA MiYOSMART', 'index' => 'Poli', 'treatment' => 'Antirreflexo MiYOSMART Sunbird', 'price' => 2099.00],
            
            // Nulux iDentity V+ - Visão Simples Surfaçadas
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.50', 'treatment' => 'Hi-Vision Meiryo', 'price' => 4549.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.50', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 4049.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.50', 'treatment' => 'No-Risk BlueControl', 'price' => 3159.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.50', 'treatment' => 'Hi-Vision Hard', 'price' => 2699.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.53', 'treatment' => 'Hi-Vision Meiryo', 'price' => 4349.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 3849.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.53', 'treatment' => 'No-Risk BlueControl', 'price' => 2959.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.53', 'treatment' => 'Hi-Vision Hard', 'price' => 2499.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.59', 'treatment' => 'Hi-Vision Meiryo', 'price' => 3599.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.59', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 2709.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.59', 'treatment' => 'No-Risk BlueControl', 'price' => 2249.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.59', 'treatment' => 'Hi-Vision Hard', 'price' => 1899.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.60', 'treatment' => 'Hi-Vision Meiryo', 'price' => 5249.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 3899.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.60', 'treatment' => 'No-Risk BlueControl', 'price' => 3349.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.60', 'treatment' => 'Hi-Vision Hard', 'price' => 3099.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.67', 'treatment' => 'Hi-Vision Meiryo', 'price' => 5449.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.67', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 4099.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.67', 'treatment' => 'No-Risk BlueControl', 'price' => 3749.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.67', 'treatment' => 'Hi-Vision Hard', 'price' => 3099.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.74', 'treatment' => 'Hi-Vision Meiryo', 'price' => 4639.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.74', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 3889.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.74', 'treatment' => 'No-Risk BlueControl', 'price' => 2999.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.74', 'treatment' => 'Hi-Vision Hard', 'price' => 2539.00],
            
            // Nulux iDentity V+ Sensity 2
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 5149.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 4649.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.50', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 3759.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 5049.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 4549.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.53', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 3659.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.59', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 4799.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.59', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 3449.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.59', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 3099.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 4799.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 3449.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.60', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 3099.00],
            
            // Nulux TrueForm - Visão Simples Surfaçadas
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.50', 'treatment' => 'Hi-Vision Meiryo', 'price' => 3509.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.50', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 2709.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.50', 'treatment' => 'No-Risk BlueControl', 'price' => 2249.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.50', 'treatment' => 'Hi-Vision Hard', 'price' => 1899.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.53', 'treatment' => 'Hi-Vision Meiryo', 'price' => 3309.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 2509.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.53', 'treatment' => 'No-Risk BlueControl', 'price' => 2049.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.53', 'treatment' => 'Hi-Vision Hard', 'price' => 1699.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.59', 'treatment' => 'Hi-Vision Meiryo', 'price' => 3059.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.59', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 2259.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.59', 'treatment' => 'No-Risk BlueControl', 'price' => 1799.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.59', 'treatment' => 'Hi-Vision Hard', 'price' => 1449.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.60', 'treatment' => 'Hi-Vision Meiryo', 'price' => 4709.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 3449.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.60', 'treatment' => 'No-Risk BlueControl', 'price' => 3099.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.67', 'treatment' => 'Hi-Vision Meiryo', 'price' => 4509.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.67', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 3249.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.67', 'treatment' => 'No-Risk BlueControl', 'price' => 2899.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.67', 'treatment' => 'Hi-Vision Hard', 'price' => 2639.00],
            
            // Nulux TrueForm Sensity 2
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 3639.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 2889.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.50', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 2539.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 3509.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 2709.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.53', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 2189.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.59', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 3439.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.59', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 2379.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.59', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 2029.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 3639.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 2639.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.60', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 1739.00],
            
            // Hilux Surfaçadas - Visão Simples Esféricas
            ['name' => 'HOYA Hilux', 'index' => '1.50', 'treatment' => 'Hi-Vision Meiryo', 'price' => 1949.00],
            ['name' => 'HOYA Hilux', 'index' => '1.50', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 1949.00],
            ['name' => 'HOYA Hilux', 'index' => '1.50', 'treatment' => 'No-Risk BlueControl', 'price' => 1599.00],
            ['name' => 'HOYA Hilux', 'index' => '1.50', 'treatment' => 'Hi-Vision Hard', 'price' => 949.00],
            ['name' => 'HOYA Hilux', 'index' => '1.53', 'treatment' => 'Hi-Vision Meiryo', 'price' => 1749.00],
            ['name' => 'HOYA Hilux', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 1399.00],
            ['name' => 'HOYA Hilux', 'index' => '1.53', 'treatment' => 'No-Risk BlueControl', 'price' => 1499.00],
            ['name' => 'HOYA Hilux', 'index' => '1.53', 'treatment' => 'Hi-Vision Hard', 'price' => 1149.00],
            ['name' => 'HOYA Hilux', 'index' => '1.59', 'treatment' => 'Hi-Vision Meiryo', 'price' => 849.00],
            ['name' => 'HOYA Hilux', 'index' => '1.59', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 849.00],
            ['name' => 'HOYA Hilux', 'index' => '1.59', 'treatment' => 'No-Risk BlueControl', 'price' => 499.00],
            ['name' => 'HOYA Hilux', 'index' => '1.60', 'treatment' => 'Hi-Vision Meiryo', 'price' => 2949.00],
            ['name' => 'HOYA Hilux', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 2599.00],
            ['name' => 'HOYA Hilux', 'index' => '1.60', 'treatment' => 'No-Risk BlueControl', 'price' => 2149.00],
            ['name' => 'HOYA Hilux', 'index' => '1.60', 'treatment' => 'Hi-Vision Hard', 'price' => 1849.00],
            ['name' => 'HOYA Hilux', 'index' => '1.67', 'treatment' => 'Hi-Vision Meiryo', 'price' => 2549.00],
            ['name' => 'HOYA Hilux', 'index' => '1.67', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 2199.00],
            ['name' => 'HOYA Hilux', 'index' => '1.67', 'treatment' => 'No-Risk BlueControl', 'price' => 1949.00],
            ['name' => 'HOYA Hilux', 'index' => '1.67', 'treatment' => 'Hi-Vision Hard', 'price' => 1649.00],
            
            // Nulux Lentes Prontas - Asféricas
            ['name' => 'HOYA Nulux Lentes Prontas', 'index' => '1.53', 'treatment' => 'Hi-Vision Meiryo', 'price' => 2199.00],
            ['name' => 'HOYA Nulux Lentes Prontas', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 1599.00],
            ['name' => 'HOYA Nulux Lentes Prontas', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife UVControl', 'price' => 1379.00],
            ['name' => 'HOYA Nulux Lentes Prontas', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife CleanExtra', 'price' => 1499.00],
            ['name' => 'HOYA Nulux Lentes Prontas', 'index' => '1.60', 'treatment' => 'Hi-Vision Meiryo', 'price' => 1979.00],
            ['name' => 'HOYA Nulux Lentes Prontas', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 1499.00],
            ['name' => 'HOYA Nulux Lentes Prontas', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife UVControl', 'price' => 1199.00],
            ['name' => 'HOYA Nulux Lentes Prontas', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife CleanExtra', 'price' => 979.00],
            ['name' => 'HOYA Nulux Lentes Prontas', 'index' => '1.67', 'treatment' => 'Hi-Vision Meiryo', 'price' => 2079.00],
            ['name' => 'HOYA Nulux Lentes Prontas', 'index' => '1.67', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 679.00],
            
            // Nulux Lentes Prontas Sensity 2
            ['name' => 'HOYA Nulux Lentes Prontas', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision LongLife UVControl', 'price' => 2079.00],
            ['name' => 'HOYA Nulux Lentes Prontas', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision LongLife UVControl', 'price' => 1979.00],
            ['name' => 'HOYA Nulux Lentes Prontas', 'index' => '1.67', 'treatment' => 'Sensity 2 Hi-Vision LongLife UVControl', 'price' => 2079.00],
            
            // Hilux Lentes Prontas - Esféricas
            ['name' => 'HOYA Hilux Lentes Prontas', 'index' => '1.50', 'treatment' => 'Hi-Vision Meiryo', 'price' => 1099.00],
            ['name' => 'HOYA Hilux Lentes Prontas', 'index' => '1.50', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 899.00],
            ['name' => 'HOYA Hilux Lentes Prontas', 'index' => '1.50', 'treatment' => 'Hi-Vision LongLife UVControl', 'price' => 999.00],
            ['name' => 'HOYA Hilux Lentes Prontas', 'index' => '1.50', 'treatment' => 'Hi-Vision LongLife CleanExtra', 'price' => 799.00],
            ['name' => 'HOYA Hilux Lentes Prontas', 'index' => '1.50', 'treatment' => 'Hi-Vision Aqua', 'price' => 439.00],
            ['name' => 'HOYA Hilux Lentes Prontas', 'index' => '1.53', 'treatment' => 'Hi-Vision Meiryo', 'price' => 999.00],
            ['name' => 'HOYA Hilux Lentes Prontas', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 799.00],
            ['name' => 'HOYA Hilux Lentes Prontas', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife UVControl', 'price' => 799.00],
            ['name' => 'HOYA Hilux Lentes Prontas', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife CleanExtra', 'price' => 349.00],
            ['name' => 'HOYA Hilux Lentes Prontas', 'index' => '1.53', 'treatment' => 'Hi-Vision Aqua', 'price' => 349.00],
            ['name' => 'HOYA Hilux Lentes Prontas', 'index' => '1.59', 'treatment' => 'Hi-Vision Meiryo', 'price' => 219.00],
            ['name' => 'HOYA Hilux Lentes Prontas', 'index' => '1.60', 'treatment' => 'Hi-Vision Meiryo', 'price' => 1699.00],
            ['name' => 'HOYA Hilux Lentes Prontas', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 1099.00],
            ['name' => 'HOYA Hilux Lentes Prontas', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife UVControl', 'price' => 599.00],
            
            // Pentax Lentes Prontas
            ['name' => 'HOYA Pentax Blue', 'index' => '1.59', 'treatment' => 'Pentax Blue Incolor', 'price' => 899.00],
            ['name' => 'HOYA Pentax Blue', 'index' => '1.59', 'treatment' => 'Pentax Blue Fotossensível', 'price' => 559.00],
            ['name' => 'HOYA Pentax Blue', 'index' => '1.67', 'treatment' => 'Pentax Blue Incolor', 'price' => 399.00],
            ['name' => 'HOYA Pentax Blue', 'index' => '1.67', 'treatment' => 'Pentax Blue Fotossensível', 'price' => 1019.00],
            ['name' => 'HOYA Pentax Photo Grey', 'index' => '1.56', 'treatment' => 'Pentax Photo Grey Fotossensível', 'price' => 399.00],
            
            // Hoyalux iD MySelf - Progressivas Premium+
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.50', 'treatment' => 'Hi-Vision Meiryo', 'price' => 14509.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.50', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 13009.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.50', 'treatment' => 'No-Risk BlueControl', 'price' => 11209.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.50', 'treatment' => 'Hi-Vision Hard', 'price' => 10749.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.53', 'treatment' => 'Hi-Vision Meiryo', 'price' => 14309.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 12809.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.53', 'treatment' => 'No-Risk BlueControl', 'price' => 11009.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.53', 'treatment' => 'Hi-Vision Hard', 'price' => 10549.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.60', 'treatment' => 'Hi-Vision Meiryo', 'price' => 14409.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 12609.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.60', 'treatment' => 'No-Risk BlueControl', 'price' => 12149.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.60', 'treatment' => 'Hi-Vision Hard', 'price' => 11799.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.67', 'treatment' => 'Hi-Vision Meiryo', 'price' => 14209.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.67', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 12409.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.67', 'treatment' => 'No-Risk BlueControl', 'price' => 11949.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.67', 'treatment' => 'Hi-Vision Hard', 'price' => 11599.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.74', 'treatment' => 'Hi-Vision Meiryo', 'price' => 14599.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.74', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 13099.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.74', 'treatment' => 'No-Risk BlueControl', 'price' => 11299.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.74', 'treatment' => 'Hi-Vision Hard', 'price' => 10839.00],
            
            // Hoyalux iD MyStyle V+ - Progressivas Premium+
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.50', 'treatment' => 'Hi-Vision Meiryo', 'price' => 14099.00],
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.50', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 12599.00],
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.50', 'treatment' => 'No-Risk BlueControl', 'price' => 10799.00],
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.50', 'treatment' => 'Hi-Vision Hard', 'price' => 10339.00],
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.53', 'treatment' => 'Hi-Vision Meiryo', 'price' => 13899.00],
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 12399.00],
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.53', 'treatment' => 'No-Risk BlueControl', 'price' => 10599.00],
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.53', 'treatment' => 'Hi-Vision Hard', 'price' => 10139.00],
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.60', 'treatment' => 'Hi-Vision Meiryo', 'price' => 14189.00],
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 12689.00],
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.60', 'treatment' => 'No-Risk BlueControl', 'price' => 10889.00],
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.60', 'treatment' => 'Hi-Vision Hard', 'price' => 10429.00],
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.67', 'treatment' => 'Hi-Vision Meiryo', 'price' => 13999.00],
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.67', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 12199.00],
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.67', 'treatment' => 'No-Risk BlueControl', 'price' => 11739.00],
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.67', 'treatment' => 'Hi-Vision Hard', 'price' => 11189.00],
            
            // Hoyalux iD MyStyle V+ Sensity 2
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 13799.00],
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 12199.00],
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 13759.00],
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 11759.00],
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 13799.00],
            ['name' => 'HOYA Hoyalux iD MyStyle V+', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 11799.00],
            
            // Hoyalux iD LifeStyle 4i - Progressivas Premium
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.50', 'treatment' => 'Hi-Vision Meiryo', 'price' => 9659.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.50', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 8159.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.50', 'treatment' => 'No-Risk BlueControl', 'price' => 6359.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.50', 'treatment' => 'Hi-Vision Hard', 'price' => 5899.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.53', 'treatment' => 'Hi-Vision Meiryo', 'price' => 9459.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 7959.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.53', 'treatment' => 'No-Risk BlueControl', 'price' => 6159.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.53', 'treatment' => 'Hi-Vision Hard', 'price' => 5699.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.60', 'treatment' => 'Hi-Vision Meiryo', 'price' => 7709.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 5909.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.60', 'treatment' => 'No-Risk BlueControl', 'price' => 5449.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.60', 'treatment' => 'Hi-Vision Hard', 'price' => 5099.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.67', 'treatment' => 'Hi-Vision Meiryo', 'price' => 9749.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.67', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 7999.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.67', 'treatment' => 'No-Risk BlueControl', 'price' => 6199.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.67', 'treatment' => 'Hi-Vision Hard', 'price' => 5739.00],
            
            // Hoyalux iD LifeStyle 4i Sensity 2
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 9759.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 7609.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.50', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 7199.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 9559.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 7759.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.53', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 7149.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 9359.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 7309.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.60', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 6699.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.67', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 8709.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.67', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 7059.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4i', 'index' => '1.67', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 6249.00],
            
            // Hoyalux iD LifeStyle 4 - Progressivas Premium
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.50', 'treatment' => 'Hi-Vision Meiryo', 'price' => 9509.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.50', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 8009.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.50', 'treatment' => 'No-Risk BlueControl', 'price' => 6209.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.50', 'treatment' => 'Hi-Vision Hard', 'price' => 5749.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.53', 'treatment' => 'Hi-Vision Meiryo', 'price' => 9309.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 7809.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.53', 'treatment' => 'No-Risk BlueControl', 'price' => 6009.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.53', 'treatment' => 'Hi-Vision Hard', 'price' => 5549.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.60', 'treatment' => 'Hi-Vision Meiryo', 'price' => 9599.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 7849.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.60', 'treatment' => 'No-Risk BlueControl', 'price' => 6049.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.60', 'treatment' => 'Hi-Vision Hard', 'price' => 5589.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.67', 'treatment' => 'Hi-Vision Meiryo', 'price' => 9409.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.67', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 7609.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.67', 'treatment' => 'No-Risk BlueControl', 'price' => 6949.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.67', 'treatment' => 'Hi-Vision Hard', 'price' => 6499.00],
            
            // Hoyalux Balansis - Progressivas Advanced
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.50', 'treatment' => 'Hi-Vision Meiryo', 'price' => 7159.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.50', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 5359.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.50', 'treatment' => 'No-Risk BlueControl', 'price' => 4899.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.50', 'treatment' => 'Hi-Vision Hard', 'price' => 4549.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.53', 'treatment' => 'Hi-Vision Meiryo', 'price' => 6959.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 5159.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.53', 'treatment' => 'No-Risk BlueControl', 'price' => 4699.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.53', 'treatment' => 'Hi-Vision Hard', 'price' => 4349.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.60', 'treatment' => 'Hi-Vision Meiryo', 'price' => 6709.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 4909.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.60', 'treatment' => 'No-Risk BlueControl', 'price' => 4449.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.60', 'treatment' => 'Hi-Vision Hard', 'price' => 4099.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.67', 'treatment' => 'Hi-Vision Meiryo', 'price' => 8559.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.67', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 6299.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.67', 'treatment' => 'No-Risk BlueControl', 'price' => 5949.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.67', 'treatment' => 'Hi-Vision Hard', 'price' => 5749.00],
            
            // Hoyalux Daynamic - Progressivas Advanced
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.50', 'treatment' => 'Hi-Vision Meiryo', 'price' => 5999.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.50', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 4199.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.50', 'treatment' => 'No-Risk BlueControl', 'price' => 3739.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.50', 'treatment' => 'Hi-Vision Hard', 'price' => 3389.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.53', 'treatment' => 'Hi-Vision Meiryo', 'price' => 5799.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 3999.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.53', 'treatment' => 'No-Risk BlueControl', 'price' => 3539.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.53', 'treatment' => 'Hi-Vision Hard', 'price' => 3189.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.60', 'treatment' => 'Hi-Vision Meiryo', 'price' => 5549.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 3749.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.60', 'treatment' => 'No-Risk BlueControl', 'price' => 3289.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.60', 'treatment' => 'Hi-Vision Hard', 'price' => 2899.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.67', 'treatment' => 'Hi-Vision Meiryo', 'price' => 3909.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.67', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 2109.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.67', 'treatment' => 'No-Risk BlueControl', 'price' => 1649.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.67', 'treatment' => 'Hi-Vision Hard', 'price' => 1299.00],
            
            // Hoyalux Daynamic Sensity 2
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 7399.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 5139.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.50', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 4789.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 7199.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 4939.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.53', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 4589.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 6949.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 4689.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.60', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 4339.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.67', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 6199.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.67', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 3939.00],
            ['name' => 'HOYA Hoyalux Daynamic', 'index' => '1.67', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 3589.00],
            
            // HOYA Argos - Progressivas Standard
            ['name' => 'HOYA Argos', 'index' => '1.50', 'treatment' => 'Hi-Vision Meiryo', 'price' => 4959.00],
            ['name' => 'HOYA Argos', 'index' => '1.50', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 3159.00],
            ['name' => 'HOYA Argos', 'index' => '1.50', 'treatment' => 'No-Risk BlueControl', 'price' => 2699.00],
            ['name' => 'HOYA Argos', 'index' => '1.50', 'treatment' => 'Hi-Vision Hard', 'price' => 2349.00],
            ['name' => 'HOYA Argos', 'index' => '1.53', 'treatment' => 'Hi-Vision Meiryo', 'price' => 4759.00],
            ['name' => 'HOYA Argos', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 2959.00],
            ['name' => 'HOYA Argos', 'index' => '1.53', 'treatment' => 'No-Risk BlueControl', 'price' => 2499.00],
            ['name' => 'HOYA Argos', 'index' => '1.53', 'treatment' => 'Hi-Vision Hard', 'price' => 2149.00],
            ['name' => 'HOYA Argos', 'index' => '1.59', 'treatment' => 'Hi-Vision Meiryo', 'price' => 4509.00],
            ['name' => 'HOYA Argos', 'index' => '1.59', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 2709.00],
            ['name' => 'HOYA Argos', 'index' => '1.59', 'treatment' => 'No-Risk BlueControl', 'price' => 2249.00],
            ['name' => 'HOYA Argos', 'index' => '1.59', 'treatment' => 'Hi-Vision Hard', 'price' => 1899.00],
            ['name' => 'HOYA Argos', 'index' => '1.60', 'treatment' => 'Hi-Vision Meiryo', 'price' => 3909.00],
            ['name' => 'HOYA Argos', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 2109.00],
            ['name' => 'HOYA Argos', 'index' => '1.60', 'treatment' => 'No-Risk BlueControl', 'price' => 1649.00],
            ['name' => 'HOYA Argos', 'index' => '1.60', 'treatment' => 'Hi-Vision Hard', 'price' => 1299.00],
            
            // HOYA Amplitude - Progressivas Standard
            ['name' => 'HOYA Amplitude', 'index' => '1.50', 'treatment' => 'No-Risk BlueControl', 'price' => 1249.00],
            ['name' => 'HOYA Amplitude', 'index' => '1.50', 'treatment' => 'Hi-Vision Hard', 'price' => 1149.00],
            ['name' => 'HOYA Amplitude', 'index' => '1.53', 'treatment' => 'No-Risk BlueControl', 'price' => 1049.00],
            ['name' => 'HOYA Amplitude', 'index' => '1.53', 'treatment' => 'Hi-Vision Hard', 'price' => 949.00],
            ['name' => 'HOYA Amplitude', 'index' => '1.59', 'treatment' => 'No-Risk BlueControl', 'price' => 799.00],
            ['name' => 'HOYA Amplitude', 'index' => '1.60', 'treatment' => 'No-Risk BlueControl', 'price' => 1439.00],
            ['name' => 'HOYA Amplitude', 'index' => '1.60', 'treatment' => 'Hi-Vision Hard', 'price' => 1089.00],
            ['name' => 'HOYA Amplitude', 'index' => '1.67', 'treatment' => 'No-Risk BlueControl', 'price' => 1749.00],
            ['name' => 'HOYA Amplitude', 'index' => '1.67', 'treatment' => 'Hi-Vision Hard', 'price' => 1399.00],
            
            // Hoyalux iD WorkStyle 3 - Ocupacionais
            ['name' => 'HOYA Hoyalux iD WorkStyle 3', 'index' => '1.50', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 7499.00],
            ['name' => 'HOYA Hoyalux iD WorkStyle 3', 'index' => '1.50', 'treatment' => 'No-Risk BlueControl', 'price' => 5999.00],
            ['name' => 'HOYA Hoyalux iD WorkStyle 3', 'index' => '1.50', 'treatment' => 'Hi-Vision Hard', 'price' => 4199.00],
            ['name' => 'HOYA Hoyalux iD WorkStyle 3', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 5749.00],
            ['name' => 'HOYA Hoyalux iD WorkStyle 3', 'index' => '1.53', 'treatment' => 'No-Risk BlueControl', 'price' => 3949.00],
            ['name' => 'HOYA Hoyalux iD WorkStyle 3', 'index' => '1.53', 'treatment' => 'Hi-Vision Hard', 'price' => 3489.00],
            ['name' => 'HOYA Hoyalux iD WorkStyle 3', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 7789.00],
            ['name' => 'HOYA Hoyalux iD WorkStyle 3', 'index' => '1.60', 'treatment' => 'No-Risk BlueControl', 'price' => 6039.00],
            ['name' => 'HOYA Hoyalux iD WorkStyle 3', 'index' => '1.60', 'treatment' => 'Hi-Vision Hard', 'price' => 4239.00],
            ['name' => 'HOYA Hoyalux iD WorkStyle 3', 'index' => '1.67', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 3139.00],
            
            // WorkSmart Room - Ocupacionais
            ['name' => 'HOYA WorkSmart Room', 'index' => '1.50', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 2779.00],
            ['name' => 'HOYA WorkSmart Room', 'index' => '1.50', 'treatment' => 'No-Risk BlueControl', 'price' => 2319.00],
            ['name' => 'HOYA WorkSmart Room', 'index' => '1.50', 'treatment' => 'Hi-Vision Hard', 'price' => 1969.00],
            ['name' => 'HOYA WorkSmart Room', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 2529.00],
            ['name' => 'HOYA WorkSmart Room', 'index' => '1.53', 'treatment' => 'No-Risk BlueControl', 'price' => 2069.00],
            ['name' => 'HOYA WorkSmart Room', 'index' => '1.53', 'treatment' => 'Hi-Vision Hard', 'price' => 1719.00],
            ['name' => 'HOYA WorkSmart Room', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 2819.00],
            ['name' => 'HOYA WorkSmart Room', 'index' => '1.60', 'treatment' => 'No-Risk BlueControl', 'price' => 2359.00],
            ['name' => 'HOYA WorkSmart Room', 'index' => '1.60', 'treatment' => 'Hi-Vision Hard', 'price' => 2009.00],
            
            // HOYA SYNC III - Especiais
            ['name' => 'HOYA SYNC III', 'index' => '1.50', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 3709.00],
            ['name' => 'HOYA SYNC III', 'index' => '1.50', 'treatment' => 'No-Risk BlueControl', 'price' => 3359.00],
            ['name' => 'HOYA SYNC III', 'index' => '1.50', 'treatment' => 'Hi-Vision Hard', 'price' => 2559.00],
            ['name' => 'HOYA SYNC III', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 3109.00],
            ['name' => 'HOYA SYNC III', 'index' => '1.53', 'treatment' => 'No-Risk BlueControl', 'price' => 2309.00],
            ['name' => 'HOYA SYNC III', 'index' => '1.53', 'treatment' => 'Hi-Vision Hard', 'price' => 1849.00],
            ['name' => 'HOYA SYNC III', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 3999.00],
            ['name' => 'HOYA SYNC III', 'index' => '1.60', 'treatment' => 'No-Risk BlueControl', 'price' => 3399.00],
            ['name' => 'HOYA SYNC III', 'index' => '1.60', 'treatment' => 'Hi-Vision Hard', 'price' => 2599.00],
            ['name' => 'HOYA SYNC III', 'index' => '1.67', 'treatment' => 'Hi-Vision LongLife BlueControl', 'price' => 4359.00],
            ['name' => 'HOYA SYNC III', 'index' => '1.67', 'treatment' => 'No-Risk BlueControl', 'price' => 3099.00],
            ['name' => 'HOYA SYNC III', 'index' => '1.67', 'treatment' => 'Hi-Vision Hard', 'price' => 2749.00],
            
            // HOYA SYNC III Sensity 2
            ['name' => 'HOYA SYNC III', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 4109.00],
            ['name' => 'HOYA SYNC III', 'index' => '1.50', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 2849.00],
            ['name' => 'HOYA SYNC III', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision Hard', 'price' => 2499.00],
            ['name' => 'HOYA SYNC III', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 3649.00],
            ['name' => 'HOYA SYNC III', 'index' => '1.53', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 2649.00],
            ['name' => 'HOYA SYNC III', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 2099.00],
            ['name' => 'HOYA SYNC III', 'index' => '1.60', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 1799.00],
            ['name' => 'HOYA SYNC III', 'index' => '1.67', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 2139.00],
            ['name' => 'HOYA SYNC III', 'index' => '1.67', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 1789.00],
            
            // Hoyalux EnRoute - Progressivas Especiais
            ['name' => 'HOYA Hoyalux EnRoute', 'index' => '1.50', 'treatment' => 'Antirreflexo EnRoute', 'price' => 6699.00],
            ['name' => 'HOYA Hoyalux EnRoute', 'index' => '1.60', 'treatment' => 'Antirreflexo EnRoute', 'price' => 4899.00],
            
            // Nulux Enroute - Visão Simples Especiais
            ['name' => 'HOYA Nulux EnRoute', 'index' => '1.50', 'treatment' => 'Antirreflexo EnRoute', 'price' => 3090.00],
            ['name' => 'HOYA Nulux EnRoute', 'index' => '1.60', 'treatment' => 'Antirreflexo EnRoute', 'price' => 2290.00],
            
            // Hoyalux Sportive - Progressivas Especiais
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.50', 'treatment' => 'Hi-Vision LongLife BlueControl Curva 6', 'price' => 6869.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.50', 'treatment' => 'Hi-Vision LongLife BlueControl Curva 8', 'price' => 5069.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.50', 'treatment' => 'No-Risk BlueControl Curva 6', 'price' => 4609.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.50', 'treatment' => 'No-Risk BlueControl Curva 8', 'price' => 4259.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.50', 'treatment' => 'Hi-Vision Hard Curva 6', 'price' => 3909.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.50', 'treatment' => 'Hi-Vision Hard Curva 8', 'price' => 3649.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife BlueControl Curva 6', 'price' => 6619.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife BlueControl Curva 8', 'price' => 4819.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.53', 'treatment' => 'No-Risk BlueControl Curva 6', 'price' => 4359.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.53', 'treatment' => 'No-Risk BlueControl Curva 8', 'price' => 4009.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife BlueControl Curva 6', 'price' => 6209.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife BlueControl Curva 8', 'price' => 4409.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.60', 'treatment' => 'No-Risk BlueControl Curva 6', 'price' => 3949.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.60', 'treatment' => 'No-Risk BlueControl Curva 8', 'price' => 3599.00],
            
            // Nulux Sportive - Visão Simples Especiais
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.50', 'treatment' => 'Hi-Vision LongLife BlueControl Curva 6', 'price' => 3689.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.50', 'treatment' => 'Hi-Vision LongLife BlueControl Curva 8', 'price' => 2899.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.50', 'treatment' => 'No-Risk BlueControl Curva 6', 'price' => 2439.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.50', 'treatment' => 'No-Risk BlueControl Curva 8', 'price' => 2089.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.50', 'treatment' => 'Hi-Vision Hard Curva 6', 'price' => 1839.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.50', 'treatment' => 'Hi-Vision Hard Curva 8', 'price' => 1299.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife BlueControl Curva 6', 'price' => 3439.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.53', 'treatment' => 'Hi-Vision LongLife BlueControl Curva 8', 'price' => 2649.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.53', 'treatment' => 'No-Risk BlueControl Curva 6', 'price' => 2189.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.53', 'treatment' => 'No-Risk BlueControl Curva 8', 'price' => 1839.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife BlueControl Curva 6', 'price' => 2899.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.60', 'treatment' => 'Hi-Vision LongLife BlueControl Curva 8', 'price' => 2109.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.60', 'treatment' => 'No-Risk BlueControl Curva 6', 'price' => 1649.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.60', 'treatment' => 'No-Risk BlueControl Curva 8', 'price' => 1299.00],
            
            // Adicionar mais produtos com variações de Sensity 2 e outros tratamentos
            // Nulux iDentity V+ com variações adicionais
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.50', 'treatment' => 'Sensity Shine Light Mirror', 'price' => 5149.00],
            ['name' => 'HOYA Nulux iDentity V+', 'index' => '1.60', 'treatment' => 'Sensity Shine Light Mirror', 'price' => 4799.00],
            
            // Nulux TrueForm com variações adicionais
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.67', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 3639.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.67', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 2639.00],
            ['name' => 'HOYA Nulux TrueForm', 'index' => '1.67', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 1739.00],
            
            // Hilux com Sensity 2
            ['name' => 'HOYA Hilux', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 2949.00],
            ['name' => 'HOYA Hilux', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 2749.00],
            ['name' => 'HOYA Hilux', 'index' => '1.50', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 2499.00],
            ['name' => 'HOYA Hilux', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 2549.00],
            ['name' => 'HOYA Hilux', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 2299.00],
            ['name' => 'HOYA Hilux', 'index' => '1.53', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 1999.00],
            ['name' => 'HOYA Hilux', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 2549.00],
            ['name' => 'HOYA Hilux', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 2199.00],
            ['name' => 'HOYA Hilux', 'index' => '1.60', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 1949.00],
            
            // Hoyalux iD MySelf Sensity 2
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 14409.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 12609.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 14209.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 12409.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 14409.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 12609.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.67', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 14209.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.67', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 12409.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.74', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 14599.00],
            ['name' => 'HOYA Hoyalux iD MySelf', 'index' => '1.74', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 13099.00],
            
            // Hoyalux iD LifeStyle 4 Sensity 2
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 9599.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 7849.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.50', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 6049.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 9409.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 7609.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.53', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 6949.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 9359.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 7309.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.60', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 6699.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.67', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 8709.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.67', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 7059.00],
            ['name' => 'HOYA Hoyalux iD LifeStyle 4', 'index' => '1.67', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 6249.00],
            
            // Hoyalux Balansis Sensity 2
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 6909.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 5109.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.50', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 4649.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 6759.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 4959.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.53', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 4449.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 6509.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 4709.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.60', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 4249.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.67', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 8359.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.67', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 6099.00],
            ['name' => 'HOYA Hoyalux Balansis', 'index' => '1.67', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 5749.00],
            
            // Argos Sensity 2
            ['name' => 'HOYA Argos', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 4799.00],
            ['name' => 'HOYA Argos', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 2999.00],
            ['name' => 'HOYA Argos', 'index' => '1.50', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 2539.00],
            ['name' => 'HOYA Argos', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 4599.00],
            ['name' => 'HOYA Argos', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 2799.00],
            ['name' => 'HOYA Argos', 'index' => '1.53', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 2339.00],
            ['name' => 'HOYA Argos', 'index' => '1.59', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 4349.00],
            ['name' => 'HOYA Argos', 'index' => '1.59', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 2549.00],
            ['name' => 'HOYA Argos', 'index' => '1.59', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 2089.00],
            ['name' => 'HOYA Argos', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision Meiryo', 'price' => 3749.00],
            ['name' => 'HOYA Argos', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 1949.00],
            ['name' => 'HOYA Argos', 'index' => '1.60', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 1489.00],
            
            // Amplitude Sensity 2
            ['name' => 'HOYA Amplitude', 'index' => '1.50', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 1249.00],
            ['name' => 'HOYA Amplitude', 'index' => '1.53', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 1049.00],
            ['name' => 'HOYA Amplitude', 'index' => '1.60', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 1439.00],
            ['name' => 'HOYA Amplitude', 'index' => '1.67', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 1749.00],
            
            // WorkStyle 3 Sensity 2
            ['name' => 'HOYA Hoyalux iD WorkStyle 3', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 7499.00],
            ['name' => 'HOYA Hoyalux iD WorkStyle 3', 'index' => '1.50', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 5999.00],
            ['name' => 'HOYA Hoyalux iD WorkStyle 3', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 5749.00],
            ['name' => 'HOYA Hoyalux iD WorkStyle 3', 'index' => '1.53', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 3949.00],
            ['name' => 'HOYA Hoyalux iD WorkStyle 3', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 7789.00],
            ['name' => 'HOYA Hoyalux iD WorkStyle 3', 'index' => '1.60', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 6039.00],
            ['name' => 'HOYA Hoyalux iD WorkStyle 3', 'index' => '1.67', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 3139.00],
            
            // WorkSmart Room Sensity 2
            ['name' => 'HOYA WorkSmart Room', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 2779.00],
            ['name' => 'HOYA WorkSmart Room', 'index' => '1.50', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 2319.00],
            ['name' => 'HOYA WorkSmart Room', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 2529.00],
            ['name' => 'HOYA WorkSmart Room', 'index' => '1.53', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 2069.00],
            ['name' => 'HOYA WorkSmart Room', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl', 'price' => 2819.00],
            ['name' => 'HOYA WorkSmart Room', 'index' => '1.60', 'treatment' => 'Sensity 2 No-Risk BlueControl', 'price' => 2359.00],
            
            // Hoyalux Sportive Sensity 2
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl Curva 6', 'price' => 6909.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl Curva 8', 'price' => 5109.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.50', 'treatment' => 'Sensity 2 No-Risk BlueControl Curva 6', 'price' => 4649.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.50', 'treatment' => 'Sensity 2 No-Risk BlueControl Curva 8', 'price' => 4299.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl Curva 6', 'price' => 6759.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl Curva 8', 'price' => 4959.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl Curva 6', 'price' => 6509.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl Curva 8', 'price' => 4709.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.60', 'treatment' => 'Sensity 2 No-Risk BlueControl Curva 6', 'price' => 4249.00],
            ['name' => 'HOYA Hoyalux Sportive', 'index' => '1.60', 'treatment' => 'Sensity 2 No-Risk BlueControl Curva 8', 'price' => 3899.00],
            
            // Nulux Sportive Sensity 2
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl Curva 6', 'price' => 3729.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.50', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl Curva 8', 'price' => 2939.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.50', 'treatment' => 'Sensity 2 No-Risk BlueControl Curva 6', 'price' => 2479.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.50', 'treatment' => 'Sensity 2 No-Risk BlueControl Curva 8', 'price' => 2129.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl Curva 6', 'price' => 3479.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.53', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl Curva 8', 'price' => 2689.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl Curva 6', 'price' => 2939.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.60', 'treatment' => 'Sensity 2 Hi-Vision LongLife BlueControl Curva 8', 'price' => 2149.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.60', 'treatment' => 'Sensity 2 No-Risk BlueControl Curva 6', 'price' => 1689.00],
            ['name' => 'HOYA Nulux Sportive', 'index' => '1.60', 'treatment' => 'Sensity 2 No-Risk BlueControl Curva 8', 'price' => 1339.00],
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

