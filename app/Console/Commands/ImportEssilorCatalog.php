<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Console\Command;

class ImportEssilorCatalog extends Command
{
    protected $signature = 'products:import-essilor-catalog {--supplier=Essilor LabRio} {--dry-run}';
    protected $description = 'Importa produtos do catálogo Essilor/LabRio para o banco de dados';

    public function handle()
    {
        $supplierName = $this->option('supplier') ?? 'Essilor LabRio';
        $dryRun = $this->option('dry-run');

        $this->info("📄 Processando catálogo Essilor/LabRio...");

        // Extrair produtos do conteúdo fornecido
        $products = $this->extractEssilorProducts();

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

        // Buscar ou criar marca Essilor
        $brand = Brand::firstOrCreate(
            ['name' => 'Essilor'],
            ['name' => 'Essilor']
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
                    'archived' => false,
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

    protected function extractEssilorProducts(): array
    {
        $products = [];
        
        // Adicionar produtos exatos do catálogo Essilor
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
        // Produtos Essilor/LabRio extraídos do catálogo
        $products = [
            
            // VARILUX XR SERIES - Design
            ['name' => 'Varilux XR Design', 'index' => 'Airwear', 'treatment' => 'Crizal Prevencia', 'price' => 3794.00],
            ['name' => 'Varilux XR Design', 'index' => 'Airwear', 'treatment' => 'Crizal Sapphire HR', 'price' => 3580.00],
            ['name' => 'Varilux XR Design', 'index' => 'Airwear', 'treatment' => 'Crizal Rock', 'price' => 3336.00],
            ['name' => 'Varilux XR Design', 'index' => 'Airwear', 'treatment' => 'Crizal Easy Pro', 'price' => 3122.00],
            ['name' => 'Varilux XR Design', 'index' => 'Orma', 'treatment' => 'Crizal Prevencia', 'price' => 3914.00],
            ['name' => 'Varilux XR Design', 'index' => 'Orma', 'treatment' => 'Crizal Sapphire HR', 'price' => 3700.00],
            ['name' => 'Varilux XR Design', 'index' => 'Orma', 'treatment' => 'Crizal Rock', 'price' => 3462.00],
            ['name' => 'Varilux XR Design', 'index' => 'Orma', 'treatment' => 'Crizal Easy Pro', 'price' => 3248.00],
            ['name' => 'Varilux XR Design', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Prevencia', 'price' => 4743.00],
            ['name' => 'Varilux XR Design', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Sapphire HR', 'price' => 4294.00],
            ['name' => 'Varilux XR Design', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Rock', 'price' => 4080.00],
            ['name' => 'Varilux XR Design', 'index' => 'Stylis 1.74', 'treatment' => 'Crizal Prevencia', 'price' => 4794.00],
            ['name' => 'Varilux XR Design', 'index' => 'Stylis 1.74', 'treatment' => 'Crizal Sapphire HR', 'price' => 5254.00],
            
            // VARILUX XR SERIES - Design com Transitions Gen S
            ['name' => 'Varilux XR Design', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 3652.00],
            ['name' => 'Varilux XR Design', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 3249.00],
            ['name' => 'Varilux XR Design', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Rock', 'price' => 3125.00],
            ['name' => 'Varilux XR Design', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 3528.00],
            ['name' => 'Varilux XR Design', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 3533.00],
            ['name' => 'Varilux XR Design', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Rock', 'price' => 3657.00],
            
            // VARILUX XR SERIES - Track Lite
            ['name' => 'Varilux XR Track Lite', 'index' => 'Airwear', 'treatment' => 'Crizal Prevencia', 'price' => 2526.00],
            ['name' => 'Varilux XR Track Lite', 'index' => 'Airwear', 'treatment' => 'Crizal Sapphire HR', 'price' => 2312.00],
            ['name' => 'Varilux XR Track Lite', 'index' => 'Airwear', 'treatment' => 'Crizal Rock', 'price' => 2052.00],
            ['name' => 'Varilux XR Track Lite', 'index' => 'Airwear', 'treatment' => 'Crizal Easy Pro', 'price' => 1838.00],
            ['name' => 'Varilux XR Track Lite', 'index' => 'Orma', 'treatment' => 'Crizal Prevencia', 'price' => 2664.00],
            ['name' => 'Varilux XR Track Lite', 'index' => 'Orma', 'treatment' => 'Crizal Sapphire HR', 'price' => 2450.00],
            ['name' => 'Varilux XR Track Lite', 'index' => 'Orma', 'treatment' => 'Crizal Rock', 'price' => 2175.00],
            ['name' => 'Varilux XR Track Lite', 'index' => 'Orma', 'treatment' => 'Crizal Easy Pro', 'price' => 1961.00],
            ['name' => 'Varilux XR Track Lite', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Prevencia', 'price' => 3477.00],
            ['name' => 'Varilux XR Track Lite', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Sapphire HR', 'price' => 3005.00],
            ['name' => 'Varilux XR Track Lite', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Rock', 'price' => 2791.00],
            
            // VARILUX XR SERIES - Track Lite com Transitions
            ['name' => 'Varilux XR Track Lite', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 2511.00],
            ['name' => 'Varilux XR Track Lite', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 2344.00],
            ['name' => 'Varilux XR Track Lite', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Rock', 'price' => 2128.00],
            ['name' => 'Varilux XR Track Lite', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 2649.00],
            ['name' => 'Varilux XR Track Lite', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 2482.00],
            ['name' => 'Varilux XR Track Lite', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Rock', 'price' => 2266.00],
            
            // VARILUX XR SERIES - Track
            ['name' => 'Varilux XR Track', 'index' => 'Airwear', 'treatment' => 'Crizal Prevencia', 'price' => 2332.00],
            ['name' => 'Varilux XR Track', 'index' => 'Airwear', 'treatment' => 'Crizal Sapphire HR', 'price' => 2118.00],
            ['name' => 'Varilux XR Track', 'index' => 'Airwear', 'treatment' => 'Crizal Rock', 'price' => 1875.00],
            ['name' => 'Varilux XR Track', 'index' => 'Airwear', 'treatment' => 'Crizal Easy Pro', 'price' => 1661.00],
            ['name' => 'Varilux XR Track', 'index' => 'Orma', 'treatment' => 'Crizal Prevencia', 'price' => 2457.00],
            ['name' => 'Varilux XR Track', 'index' => 'Orma', 'treatment' => 'Crizal Sapphire HR', 'price' => 2243.00],
            ['name' => 'Varilux XR Track', 'index' => 'Orma', 'treatment' => 'Crizal Rock', 'price' => 2000.00],
            ['name' => 'Varilux XR Track', 'index' => 'Orma', 'treatment' => 'Crizal Easy Pro', 'price' => 1786.00],
            ['name' => 'Varilux XR Track', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Prevencia', 'price' => 3283.00],
            ['name' => 'Varilux XR Track', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Sapphire HR', 'price' => 2829.00],
            ['name' => 'Varilux XR Track', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Rock', 'price' => 2615.00],
            
            // VARILUX XR SERIES - Track com Transitions
            ['name' => 'Varilux XR Track', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 2472.00],
            ['name' => 'Varilux XR Track', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 2305.00],
            ['name' => 'Varilux XR Track', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Rock', 'price' => 2089.00],
            ['name' => 'Varilux XR Track', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 2560.00],
            ['name' => 'Varilux XR Track', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 2393.00],
            ['name' => 'Varilux XR Track', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Rock', 'price' => 2177.00],
            
            // VARILUX XR SERIES - Pro
            ['name' => 'Varilux XR Pro', 'index' => 'Airwear', 'treatment' => 'Crizal Prevencia', 'price' => 2143.00],
            ['name' => 'Varilux XR Pro', 'index' => 'Airwear', 'treatment' => 'Crizal Sapphire HR', 'price' => 2511.00],
            ['name' => 'Varilux XR Pro', 'index' => 'Airwear', 'treatment' => 'Crizal Rock', 'price' => 2344.00],
            ['name' => 'Varilux XR Pro', 'index' => 'Airwear', 'treatment' => 'Crizal Easy Pro', 'price' => 2128.00],
            ['name' => 'Varilux XR Pro', 'index' => 'Orma', 'treatment' => 'Crizal Prevencia', 'price' => 1848.00],
            ['name' => 'Varilux XR Pro', 'index' => 'Orma', 'treatment' => 'Crizal Sapphire HR', 'price' => 1681.00],
            ['name' => 'Varilux XR Pro', 'index' => 'Orma', 'treatment' => 'Crizal Rock', 'price' => 1465.00],
            ['name' => 'Varilux XR Pro', 'index' => 'Orma', 'treatment' => 'Crizal Easy Pro', 'price' => 1634.00],
            
            // VARILUX PHYSIO EXTENSEE
            ['name' => 'Varilux Physio Extensee', 'index' => 'Airwear', 'treatment' => 'Crizal Prevencia', 'price' => 1902.00],
            ['name' => 'Varilux Physio Extensee', 'index' => 'Airwear', 'treatment' => 'Crizal Sapphire HR', 'price' => 1735.00],
            ['name' => 'Varilux Physio Extensee', 'index' => 'Airwear', 'treatment' => 'Crizal Rock', 'price' => 1519.00],
            ['name' => 'Varilux Physio Extensee', 'index' => 'Airwear', 'treatment' => 'Crizal Easy Pro', 'price' => 1688.00],
            ['name' => 'Varilux Physio Extensee', 'index' => 'Orma', 'treatment' => 'Crizal Prevencia', 'price' => 1992.00],
            ['name' => 'Varilux Physio Extensee', 'index' => 'Orma', 'treatment' => 'Crizal Sapphire HR', 'price' => 1825.00],
            ['name' => 'Varilux Physio Extensee', 'index' => 'Orma', 'treatment' => 'Crizal Rock', 'price' => 1609.00],
            ['name' => 'Varilux Physio Extensee', 'index' => 'Orma', 'treatment' => 'Crizal Easy Pro', 'price' => 1778.00],
            ['name' => 'Varilux Physio Extensee', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Prevencia', 'price' => 2648.00],
            ['name' => 'Varilux Physio Extensee', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Sapphire HR', 'price' => 2481.00],
            ['name' => 'Varilux Physio Extensee', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Rock', 'price' => 2265.00],
            ['name' => 'Varilux Physio Extensee', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Easy Pro', 'price' => 2434.00],
            
            // VARILUX PHYSIO EXTENSEE com Transitions
            ['name' => 'Varilux Physio Extensee', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 2151.00],
            ['name' => 'Varilux Physio Extensee', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 1984.00],
            ['name' => 'Varilux Physio Extensee', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Rock', 'price' => 1768.00],
            ['name' => 'Varilux Physio Extensee', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 2487.00],
            ['name' => 'Varilux Physio Extensee', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 2320.00],
            ['name' => 'Varilux Physio Extensee', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Rock', 'price' => 2104.00],
            ['name' => 'Varilux Physio Extensee', 'index' => 'Stylis 1.74', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 3842.00],
            ['name' => 'Varilux Physio Extensee', 'index' => 'Stylis 1.74', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 3675.00],
            ['name' => 'Varilux Physio Extensee', 'index' => 'Stylis 1.74', 'treatment' => 'Transitions Gen S Cinza Crizal Rock', 'price' => 3459.00],
            
            // VARILUX COMFORT
            ['name' => 'Varilux Comfort', 'index' => 'Airwear', 'treatment' => 'Crizal Prevencia', 'price' => 1414.00],
            ['name' => 'Varilux Comfort', 'index' => 'Airwear', 'treatment' => 'Crizal Sapphire HR', 'price' => 1247.00],
            ['name' => 'Varilux Comfort', 'index' => 'Airwear', 'treatment' => 'Crizal Rock', 'price' => 1031.00],
            ['name' => 'Varilux Comfort', 'index' => 'Airwear', 'treatment' => 'Crizal Easy Pro', 'price' => 1200.00],
            ['name' => 'Varilux Comfort', 'index' => 'Orma', 'treatment' => 'Crizal Prevencia', 'price' => 1501.00],
            ['name' => 'Varilux Comfort', 'index' => 'Orma', 'treatment' => 'Crizal Sapphire HR', 'price' => 1334.00],
            ['name' => 'Varilux Comfort', 'index' => 'Orma', 'treatment' => 'Crizal Rock', 'price' => 1118.00],
            ['name' => 'Varilux Comfort', 'index' => 'Orma', 'treatment' => 'Crizal Easy Pro', 'price' => 1287.00],
            ['name' => 'Varilux Comfort', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Prevencia', 'price' => 2138.00],
            ['name' => 'Varilux Comfort', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Sapphire HR', 'price' => 1971.00],
            ['name' => 'Varilux Comfort', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Rock', 'price' => 1755.00],
            ['name' => 'Varilux Comfort', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Easy Pro', 'price' => 1924.00],
            
            // VARILUX COMFORT com Transitions
            ['name' => 'Varilux Comfort', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 1571.00],
            ['name' => 'Varilux Comfort', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 1404.00],
            ['name' => 'Varilux Comfort', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Rock', 'price' => 1188.00],
            ['name' => 'Varilux Comfort', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 1982.00],
            ['name' => 'Varilux Comfort', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 1815.00],
            ['name' => 'Varilux Comfort', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Rock', 'price' => 1599.00],
            
            // VARILUX LIBERTY
            ['name' => 'Varilux Liberty 3.0', 'index' => 'Airwear', 'treatment' => 'Crizal Prevencia', 'price' => 1031.00],
            ['name' => 'Varilux Liberty 3.0', 'index' => 'Airwear', 'treatment' => 'Crizal Sapphire HR', 'price' => 864.00],
            ['name' => 'Varilux Liberty 3.0', 'index' => 'Airwear', 'treatment' => 'Crizal Rock', 'price' => 648.00],
            ['name' => 'Varilux Liberty 3.0', 'index' => 'Airwear', 'treatment' => 'Crizal Easy Pro', 'price' => 817.00],
            ['name' => 'Varilux Liberty 3.0', 'index' => 'Orma', 'treatment' => 'Crizal Prevencia', 'price' => 1138.00],
            ['name' => 'Varilux Liberty 3.0', 'index' => 'Orma', 'treatment' => 'Crizal Sapphire HR', 'price' => 971.00],
            ['name' => 'Varilux Liberty 3.0', 'index' => 'Orma', 'treatment' => 'Crizal Rock', 'price' => 755.00],
            ['name' => 'Varilux Liberty 3.0', 'index' => 'Orma', 'treatment' => 'Crizal Easy Pro', 'price' => 924.00],
            ['name' => 'Varilux Liberty 3.0', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Prevencia', 'price' => 1870.00],
            ['name' => 'Varilux Liberty 3.0', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Sapphire HR', 'price' => 1703.00],
            ['name' => 'Varilux Liberty 3.0', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Rock', 'price' => 1487.00],
            ['name' => 'Varilux Liberty 3.0', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Easy Pro', 'price' => 1656.00],
            
            // VARILUX LIBERTY com Transitions
            ['name' => 'Varilux Liberty 3.0', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 1224.00],
            ['name' => 'Varilux Liberty 3.0', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 1057.00],
            ['name' => 'Varilux Liberty 3.0', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Rock', 'price' => 841.00],
            ['name' => 'Varilux Liberty 3.0', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 1679.00],
            ['name' => 'Varilux Liberty 3.0', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 1512.00],
            ['name' => 'Varilux Liberty 3.0', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Rock', 'price' => 1296.00],
            
            // VARILUX ACTIVITIES - Digitime
            ['name' => 'Varilux Digitime Near', 'index' => 'Airwear', 'treatment' => 'Crizal Prevencia', 'price' => 1142.00],
            ['name' => 'Varilux Digitime Near', 'index' => 'Airwear', 'treatment' => 'Crizal Sapphire HR', 'price' => 978.00],
            ['name' => 'Varilux Digitime Near', 'index' => 'Airwear', 'treatment' => 'Crizal Rock', 'price' => 975.00],
            ['name' => 'Varilux Digitime Near', 'index' => 'Airwear', 'treatment' => 'Crizal Easy Pro', 'price' => 811.00],
            ['name' => 'Varilux Digitime Near', 'index' => 'Orma', 'treatment' => 'Crizal Prevencia', 'price' => 2221.00],
            ['name' => 'Varilux Digitime Near', 'index' => 'Orma', 'treatment' => 'Crizal Sapphire HR', 'price' => 2221.00],
            ['name' => 'Varilux Digitime Near', 'index' => 'Orma', 'treatment' => 'Crizal Rock', 'price' => 2054.00],
            ['name' => 'Varilux Digitime Near', 'index' => 'Orma', 'treatment' => 'Crizal Easy Pro', 'price' => 1838.00],
            
            ['name' => 'Varilux Digitime Mid', 'index' => 'Airwear', 'treatment' => 'Crizal Prevencia', 'price' => 1142.00],
            ['name' => 'Varilux Digitime Mid', 'index' => 'Airwear', 'treatment' => 'Crizal Sapphire HR', 'price' => 978.00],
            ['name' => 'Varilux Digitime Mid', 'index' => 'Airwear', 'treatment' => 'Crizal Rock', 'price' => 759.00],
            ['name' => 'Varilux Digitime Mid', 'index' => 'Airwear', 'treatment' => 'Crizal Easy Pro', 'price' => 595.00],
            ['name' => 'Varilux Digitime Mid', 'index' => 'Orma', 'treatment' => 'Crizal Prevencia', 'price' => 2221.00],
            ['name' => 'Varilux Digitime Mid', 'index' => 'Orma', 'treatment' => 'Crizal Sapphire HR', 'price' => 2221.00],
            ['name' => 'Varilux Digitime Mid', 'index' => 'Orma', 'treatment' => 'Crizal Rock', 'price' => 1838.00],
            ['name' => 'Varilux Digitime Mid', 'index' => 'Orma', 'treatment' => 'Crizal Easy Pro', 'price' => 928.00],
            
            // VARILUX ACTIVITIES - Roadpilot
            ['name' => 'Varilux Roadpilot', 'index' => 'Airwear', 'treatment' => 'Crizal Prevencia', 'price' => 910.00],
            ['name' => 'Varilux Roadpilot', 'index' => 'Airwear', 'treatment' => 'Crizal Sapphire HR', 'price' => 1490.00],
            ['name' => 'Varilux Roadpilot', 'index' => 'Airwear', 'treatment' => 'Crizal Rock', 'price' => 741.00],
            ['name' => 'Varilux Roadpilot', 'index' => 'Airwear', 'treatment' => 'Crizal Easy Pro', 'price' => 1321.00],
            ['name' => 'Varilux Roadpilot', 'index' => 'Orma', 'treatment' => 'Crizal Prevencia', 'price' => 957.00],
            ['name' => 'Varilux Roadpilot', 'index' => 'Orma', 'treatment' => 'Crizal Sapphire HR', 'price' => 1537.00],
            ['name' => 'Varilux Roadpilot', 'index' => 'Orma', 'treatment' => 'Crizal Rock', 'price' => 1124.00],
            ['name' => 'Varilux Roadpilot', 'index' => 'Orma', 'treatment' => 'Crizal Easy Pro', 'price' => 1704.00],
            
            // VARILUX ACTIVITIES - Sport
            ['name' => 'Varilux Sport', 'index' => 'Airwear', 'treatment' => 'Crizal Prevencia', 'price' => 1142.00],
            ['name' => 'Varilux Sport', 'index' => 'Airwear', 'treatment' => 'Crizal Sapphire HR', 'price' => 978.00],
            ['name' => 'Varilux Sport', 'index' => 'Airwear', 'treatment' => 'Crizal Rock', 'price' => 759.00],
            ['name' => 'Varilux Sport', 'index' => 'Airwear', 'treatment' => 'Crizal Easy Pro', 'price' => 595.00],
            ['name' => 'Varilux Sport', 'index' => 'Orma', 'treatment' => 'Crizal Prevencia', 'price' => 2221.00],
            ['name' => 'Varilux Sport', 'index' => 'Orma', 'treatment' => 'Crizal Sapphire HR', 'price' => 2221.00],
            ['name' => 'Varilux Sport', 'index' => 'Orma', 'treatment' => 'Crizal Rock', 'price' => 1838.00],
            ['name' => 'Varilux Sport', 'index' => 'Orma', 'treatment' => 'Crizal Easy Pro', 'price' => 928.00],
            
            // EYEZEN BOOST
            ['name' => 'Eyezen Boost', 'index' => 'Airwear', 'treatment' => 'Crizal Prevencia 0.4', 'price' => 633.00],
            ['name' => 'Eyezen Boost', 'index' => 'Airwear', 'treatment' => 'Crizal Sapphire HR 0.4', 'price' => 1180.00],
            ['name' => 'Eyezen Boost', 'index' => 'Airwear', 'treatment' => 'Crizal Rock 0.4', 'price' => 724.00],
            ['name' => 'Eyezen Boost', 'index' => 'Airwear', 'treatment' => 'Crizal Easy Pro 0.4', 'price' => 1764.00],
            ['name' => 'Eyezen Boost', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Prevencia 0.4', 'price' => 1405.00],
            ['name' => 'Eyezen Boost', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Sapphire HR 0.4', 'price' => 2025.00],
            ['name' => 'Eyezen Boost', 'index' => 'Stylis 1.74', 'treatment' => 'Crizal Prevencia 0.4', 'price' => 847.00],
            ['name' => 'Eyezen Boost', 'index' => 'Stylis 1.74', 'treatment' => 'Crizal Sapphire HR 0.4', 'price' => 1394.00],
            
            ['name' => 'Eyezen Boost', 'index' => 'Airwear', 'treatment' => 'Crizal Prevencia 0.6', 'price' => 633.00],
            ['name' => 'Eyezen Boost', 'index' => 'Airwear', 'treatment' => 'Crizal Sapphire HR 0.6', 'price' => 1180.00],
            ['name' => 'Eyezen Boost', 'index' => 'Airwear', 'treatment' => 'Crizal Rock 0.6', 'price' => 724.00],
            ['name' => 'Eyezen Boost', 'index' => 'Airwear', 'treatment' => 'Crizal Easy Pro 0.6', 'price' => 1764.00],
            ['name' => 'Eyezen Boost', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Prevencia 0.6', 'price' => 1405.00],
            ['name' => 'Eyezen Boost', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Sapphire HR 0.6', 'price' => 2025.00],
            
            ['name' => 'Eyezen Boost', 'index' => 'Airwear', 'treatment' => 'Crizal Prevencia 0.85', 'price' => 633.00],
            ['name' => 'Eyezen Boost', 'index' => 'Airwear', 'treatment' => 'Crizal Sapphire HR 0.85', 'price' => 1180.00],
            ['name' => 'Eyezen Boost', 'index' => 'Airwear', 'treatment' => 'Crizal Rock 0.85', 'price' => 724.00],
            ['name' => 'Eyezen Boost', 'index' => 'Airwear', 'treatment' => 'Crizal Easy Pro 0.85', 'price' => 1764.00],
            ['name' => 'Eyezen Boost', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Prevencia 0.85', 'price' => 1405.00],
            ['name' => 'Eyezen Boost', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Sapphire HR 0.85', 'price' => 2025.00],
            
            // EYEZEN BOOST com Transitions
            ['name' => 'Eyezen Boost', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia 0.4', 'price' => 1370.00],
            ['name' => 'Eyezen Boost', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR 0.4', 'price' => 1203.00],
            ['name' => 'Eyezen Boost', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Rock 0.4', 'price' => 987.00],
            ['name' => 'Eyezen Boost', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia 0.4', 'price' => 1848.00],
            ['name' => 'Eyezen Boost', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR 0.4', 'price' => 1681.00],
            ['name' => 'Eyezen Boost', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Rock 0.4', 'price' => 1465.00],
            
            // EYEZEN START
            ['name' => 'Eyezen Start', 'index' => 'Airwear', 'treatment' => 'Crizal Prevencia', 'price' => 590.00],
            ['name' => 'Eyezen Start', 'index' => 'Airwear', 'treatment' => 'Crizal Sapphire HR', 'price' => 1100.00],
            ['name' => 'Eyezen Start', 'index' => 'Airwear', 'treatment' => 'Crizal Rock', 'price' => 1180.00],
            ['name' => 'Eyezen Start', 'index' => 'Airwear', 'treatment' => 'Crizal Easy Pro', 'price' => 718.00],
            ['name' => 'Eyezen Start', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Prevencia', 'price' => 1625.00],
            ['name' => 'Eyezen Start', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Sapphire HR', 'price' => 1681.00],
            ['name' => 'Eyezen Start', 'index' => 'Stylis 1.74', 'treatment' => 'Crizal Prevencia', 'price' => 1400.00],
            ['name' => 'Eyezen Start', 'index' => 'Stylis 1.74', 'treatment' => 'Crizal Sapphire HR', 'price' => 2021.00],
            
            // EYEZEN START com Transitions
            ['name' => 'Eyezen Start', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 1147.00],
            ['name' => 'Eyezen Start', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 1227.00],
            ['name' => 'Eyezen Start', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Rock', 'price' => 771.00],
            ['name' => 'Eyezen Start', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 1672.00],
            ['name' => 'Eyezen Start', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 1728.00],
            ['name' => 'Eyezen Start', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Rock', 'price' => 1447.00],
            
            // STELLEST
            ['name' => 'Essilor Stellest', 'index' => 'Airwear', 'treatment' => 'Crizal Rock', 'price' => 999.00],
            ['name' => 'Essilor Stellest Sun', 'index' => 'Airwear', 'treatment' => 'Crizal Sun XProtect', 'price' => 1099.00],
            
            // LENTES PRONTAS ESSILOR
            ['name' => 'Eyezen Start Stock', 'index' => 'Stylis 1.60', 'treatment' => 'Crizal Sapphire HR', 'price' => 621.00],
            ['name' => 'Eyezen Start Stock', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Sapphire HR', 'price' => 560.00],
            ['name' => 'Eyezen Start Stock', 'index' => 'Stylis 1.74', 'treatment' => 'Crizal Sapphire HR', 'price' => 332.00],
            ['name' => 'Lente Pronta Essilor', 'index' => 'Airwear', 'treatment' => 'Crizal Easy Pro', 'price' => 296.00],
            ['name' => 'Lente Pronta Essilor', 'index' => 'Airwear', 'treatment' => 'Crizal Sapphire HR', 'price' => 386.00],
            ['name' => 'Lente Pronta Essilor', 'index' => 'Airwear', 'treatment' => 'Crizal Rock', 'price' => 850.00],
            ['name' => 'Lente Pronta Essilor', 'index' => 'Orma', 'treatment' => 'Crizal Rock', 'price' => 211.00],
            ['name' => 'Lente Pronta Essilor', 'index' => 'Orma', 'treatment' => 'Crizal Sapphire HR', 'price' => 440.00],
            ['name' => 'Lente Pronta Essilor', 'index' => 'Orma', 'treatment' => 'Crizal Easy Pro', 'price' => 252.00],
            ['name' => 'Lente Pronta Essilor', 'index' => 'Orma', 'treatment' => 'Crizal Easy Pro Transitions Gen S Cinza', 'price' => 296.00],
            ['name' => 'Lente Pronta Essilor', 'index' => 'Orma', 'treatment' => 'Crizal Prevencia', 'price' => 393.00],
            ['name' => 'Lente Pronta Essilor', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza', 'price' => 401.00],
            
            // LENTES SURFAÇADAS ESSILOR
            ['name' => 'Lente Essilor Surfaçada', 'index' => 'Airwear', 'treatment' => 'Crizal Prevencia', 'price' => 754.00],
            ['name' => 'Lente Essilor Surfaçada', 'index' => 'Airwear', 'treatment' => 'Crizal Sapphire HR', 'price' => 754.00],
            ['name' => 'Lente Essilor Surfaçada', 'index' => 'Airwear', 'treatment' => 'Crizal Rock', 'price' => 587.00],
            ['name' => 'Lente Essilor Surfaçada', 'index' => 'Airwear', 'treatment' => 'Crizal Easy Pro', 'price' => 371.00],
            ['name' => 'Lente Essilor Surfaçada', 'index' => 'Orma', 'treatment' => 'Crizal Prevencia', 'price' => 766.00],
            ['name' => 'Lente Essilor Surfaçada', 'index' => 'Orma', 'treatment' => 'Crizal Sapphire HR', 'price' => 766.00],
            ['name' => 'Lente Essilor Surfaçada', 'index' => 'Orma', 'treatment' => 'Crizal Rock', 'price' => 599.00],
            ['name' => 'Lente Essilor Surfaçada', 'index' => 'Orma', 'treatment' => 'Crizal Easy Pro', 'price' => 383.00],
            ['name' => 'Lente Essilor Surfaçada', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Prevencia', 'price' => 909.00],
            ['name' => 'Lente Essilor Surfaçada', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Sapphire HR', 'price' => 909.00],
            ['name' => 'Lente Essilor Surfaçada', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Rock', 'price' => 742.00],
            ['name' => 'Lente Essilor Surfaçada', 'index' => 'Stylis 1.67', 'treatment' => 'Crizal Easy Pro', 'price' => 526.00],
            ['name' => 'Lente Essilor Surfaçada', 'index' => 'Stylis 1.74', 'treatment' => 'Crizal Prevencia', 'price' => 1251.00],
            ['name' => 'Lente Essilor Surfaçada', 'index' => 'Stylis 1.74', 'treatment' => 'Crizal Sapphire HR', 'price' => 1251.00],
            ['name' => 'Lente Essilor Surfaçada', 'index' => 'Stylis 1.74', 'treatment' => 'Crizal Rock', 'price' => 1084.00],
            ['name' => 'Lente Essilor Surfaçada', 'index' => 'Stylis 1.74', 'treatment' => 'Crizal Easy Pro', 'price' => 868.00],
            
            // LENTES SURFAÇADAS ESSILOR com Transitions
            ['name' => 'Lente Essilor Surfaçada', 'index' => 'Airwear', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 540.00],
            ['name' => 'Lente Essilor Surfaçada', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 552.00],
            ['name' => 'Lente Essilor Surfaçada', 'index' => 'Stylis 1.67', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 695.00],
            ['name' => 'Lente Essilor Surfaçada', 'index' => 'Stylis 1.74', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 1037.00],
            
            // LENTES SOLARES XPERIO
            ['name' => 'Xperio', 'index' => 'Orma', 'treatment' => 'Crizal Prevencia', 'price' => 816.00],
            ['name' => 'Xperio', 'index' => 'Orma', 'treatment' => 'Crizal Sapphire HR', 'price' => 821.00],
            ['name' => 'Xperio', 'index' => 'Airwear', 'treatment' => 'Crizal Prevencia', 'price' => 920.00],
            ['name' => 'Xperio', 'index' => 'Airwear', 'treatment' => 'Crizal Sapphire HR', 'price' => 925.00],
            
            // LENTES KODAK - Unique Infinite
            ['name' => 'KODAK Unique Infinite', 'index' => '1.50', 'treatment' => 'Crizal Prevencia', 'price' => 1119.00],
            ['name' => 'KODAK Unique Infinite', 'index' => '1.50', 'treatment' => 'Crizal Sapphire HR', 'price' => 952.00],
            ['name' => 'KODAK Unique Infinite', 'index' => '1.50', 'treatment' => 'Crizal Rock', 'price' => 736.00],
            ['name' => 'KODAK Unique Infinite', 'index' => '1.50', 'treatment' => 'Crizal Easy Pro', 'price' => 905.00],
            ['name' => 'KODAK Unique Infinite', 'index' => 'Poly', 'treatment' => 'Crizal Prevencia', 'price' => 1235.00],
            ['name' => 'KODAK Unique Infinite', 'index' => 'Poly', 'treatment' => 'Crizal Sapphire HR', 'price' => 1068.00],
            ['name' => 'KODAK Unique Infinite', 'index' => 'Poly', 'treatment' => 'Crizal Rock', 'price' => 852.00],
            ['name' => 'KODAK Unique Infinite', 'index' => 'Poly', 'treatment' => 'Crizal Easy Pro', 'price' => 1021.00],
            ['name' => 'KODAK Unique Infinite', 'index' => '1.67', 'treatment' => 'Crizal Prevencia', 'price' => 1876.00],
            ['name' => 'KODAK Unique Infinite', 'index' => '1.67', 'treatment' => 'Crizal Sapphire HR', 'price' => 1709.00],
            ['name' => 'KODAK Unique Infinite', 'index' => '1.67', 'treatment' => 'Crizal Rock', 'price' => 1493.00],
            ['name' => 'KODAK Unique Infinite', 'index' => '1.67', 'treatment' => 'Crizal Easy Pro', 'price' => 1662.00],
            
            // KODAK Unique Infinite com Transitions
            ['name' => 'KODAK Unique Infinite', 'index' => '1.50', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 1660.00],
            ['name' => 'KODAK Unique Infinite', 'index' => '1.50', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 1493.00],
            ['name' => 'KODAK Unique Infinite', 'index' => '1.50', 'treatment' => 'Transitions Gen S Cinza Crizal Rock', 'price' => 1277.00],
            ['name' => 'KODAK Unique Infinite', 'index' => 'Poly', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 2376.00],
            ['name' => 'KODAK Unique Infinite', 'index' => 'Poly', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 2209.00],
            ['name' => 'KODAK Unique Infinite', 'index' => 'Poly', 'treatment' => 'Transitions Gen S Cinza Crizal Rock', 'price' => 1993.00],
            
            // LENTES KODAK - Precise UHD
            ['name' => 'KODAK Precise UHD', 'index' => '1.50', 'treatment' => 'Crizal Prevencia', 'price' => 893.00],
            ['name' => 'KODAK Precise UHD', 'index' => '1.50', 'treatment' => 'Crizal Sapphire HR', 'price' => 893.00],
            ['name' => 'KODAK Precise UHD', 'index' => '1.50', 'treatment' => 'Crizal Rock', 'price' => 726.00],
            ['name' => 'KODAK Precise UHD', 'index' => '1.50', 'treatment' => 'Crizal Easy Pro', 'price' => 510.00],
            ['name' => 'KODAK Precise UHD', 'index' => 'Poly', 'treatment' => 'Crizal Prevencia', 'price' => 1393.00],
            ['name' => 'KODAK Precise UHD', 'index' => 'Poly', 'treatment' => 'Crizal Sapphire HR', 'price' => 1393.00],
            ['name' => 'KODAK Precise UHD', 'index' => 'Poly', 'treatment' => 'Crizal Rock', 'price' => 1226.00],
            ['name' => 'KODAK Precise UHD', 'index' => 'Poly', 'treatment' => 'Crizal Easy Pro', 'price' => 1010.00],
            ['name' => 'KODAK Precise UHD', 'index' => '1.67', 'treatment' => 'Crizal Prevencia', 'price' => 1509.00],
            ['name' => 'KODAK Precise UHD', 'index' => '1.67', 'treatment' => 'Crizal Sapphire HR', 'price' => 1509.00],
            ['name' => 'KODAK Precise UHD', 'index' => '1.67', 'treatment' => 'Crizal Rock', 'price' => 1342.00],
            ['name' => 'KODAK Precise UHD', 'index' => '1.67', 'treatment' => 'Crizal Easy Pro', 'price' => 1126.00],
            
            // KODAK Precise UHD com Transitions
            ['name' => 'KODAK Precise UHD', 'index' => '1.50', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 1009.00],
            ['name' => 'KODAK Precise UHD', 'index' => '1.50', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 842.00],
            ['name' => 'KODAK Precise UHD', 'index' => 'Poly', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 1509.00],
            ['name' => 'KODAK Precise UHD', 'index' => 'Poly', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 1342.00],
            
            // LENTES KODAK - Network UHD
            ['name' => 'KODAK Network UHD', 'index' => '1.50', 'treatment' => 'Crizal Prevencia', 'price' => 1003.00],
            ['name' => 'KODAK Network UHD', 'index' => '1.50', 'treatment' => 'Crizal Sapphire HR', 'price' => 836.00],
            ['name' => 'KODAK Network UHD', 'index' => '1.50', 'treatment' => 'Crizal Rock', 'price' => 620.00],
            ['name' => 'KODAK Network UHD', 'index' => '1.50', 'treatment' => 'Crizal Easy Pro', 'price' => 789.00],
            ['name' => 'KODAK Network UHD', 'index' => 'Poly', 'treatment' => 'Crizal Prevencia', 'price' => 1119.00],
            ['name' => 'KODAK Network UHD', 'index' => 'Poly', 'treatment' => 'Crizal Sapphire HR', 'price' => 952.00],
            ['name' => 'KODAK Network UHD', 'index' => 'Poly', 'treatment' => 'Crizal Rock', 'price' => 736.00],
            ['name' => 'KODAK Network UHD', 'index' => 'Poly', 'treatment' => 'Crizal Easy Pro', 'price' => 905.00],
            ['name' => 'KODAK Network UHD', 'index' => '1.67', 'treatment' => 'Crizal Prevencia', 'price' => 1503.00],
            ['name' => 'KODAK Network UHD', 'index' => '1.67', 'treatment' => 'Crizal Sapphire HR', 'price' => 1503.00],
            ['name' => 'KODAK Network UHD', 'index' => '1.67', 'treatment' => 'Crizal Rock', 'price' => 1336.00],
            ['name' => 'KODAK Network UHD', 'index' => '1.67', 'treatment' => 'Crizal Easy Pro', 'price' => 1120.00],
            
            // KODAK Network UHD com Transitions
            ['name' => 'KODAK Network UHD', 'index' => '1.50', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 1558.00],
            ['name' => 'KODAK Network UHD', 'index' => '1.50', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 1391.00],
            ['name' => 'KODAK Network UHD', 'index' => 'Poly', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 1673.00],
            ['name' => 'KODAK Network UHD', 'index' => 'Poly', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 1506.00],
            
            // LENTES KODAK - Unique UHD
            ['name' => 'KODAK Unique UHD', 'index' => '1.50', 'treatment' => 'Crizal Prevencia', 'price' => 1058.00],
            ['name' => 'KODAK Unique UHD', 'index' => '1.50', 'treatment' => 'Crizal Sapphire HR', 'price' => 891.00],
            ['name' => 'KODAK Unique UHD', 'index' => '1.50', 'treatment' => 'Crizal Rock', 'price' => 675.00],
            ['name' => 'KODAK Unique UHD', 'index' => '1.50', 'treatment' => 'Crizal Easy Pro', 'price' => 844.00],
            ['name' => 'KODAK Unique UHD', 'index' => 'Poly', 'treatment' => 'Crizal Prevencia', 'price' => 1173.00],
            ['name' => 'KODAK Unique UHD', 'index' => 'Poly', 'treatment' => 'Crizal Sapphire HR', 'price' => 1006.00],
            ['name' => 'KODAK Unique UHD', 'index' => 'Poly', 'treatment' => 'Crizal Rock', 'price' => 790.00],
            ['name' => 'KODAK Unique UHD', 'index' => 'Poly', 'treatment' => 'Crizal Easy Pro', 'price' => 959.00],
            ['name' => 'KODAK Unique UHD', 'index' => '1.67', 'treatment' => 'Crizal Prevencia', 'price' => 1814.00],
            ['name' => 'KODAK Unique UHD', 'index' => '1.67', 'treatment' => 'Crizal Sapphire HR', 'price' => 1647.00],
            ['name' => 'KODAK Unique UHD', 'index' => '1.67', 'treatment' => 'Crizal Rock', 'price' => 1431.00],
            ['name' => 'KODAK Unique UHD', 'index' => '1.67', 'treatment' => 'Crizal Easy Pro', 'price' => 1600.00],
            
            // KODAK Unique UHD com Transitions
            ['name' => 'KODAK Unique UHD', 'index' => '1.50', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 1558.00],
            ['name' => 'KODAK Unique UHD', 'index' => '1.50', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 1391.00],
            ['name' => 'KODAK Unique UHD', 'index' => 'Poly', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 1673.00],
            ['name' => 'KODAK Unique UHD', 'index' => 'Poly', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 1506.00],
            
            // LENTES KODAK - Single
            ['name' => 'KODAK Single', 'index' => 'Poly', 'treatment' => 'Crizal Prevencia', 'price' => 818.00],
            ['name' => 'KODAK Single', 'index' => 'Poly', 'treatment' => 'Crizal Sapphire HR', 'price' => 818.00],
            ['name' => 'KODAK Single', 'index' => 'Poly', 'treatment' => 'Crizal Rock', 'price' => 651.00],
            ['name' => 'KODAK Single', 'index' => 'Poly', 'treatment' => 'Crizal Easy Pro', 'price' => 435.00],
            ['name' => 'KODAK Single', 'index' => '1.50', 'treatment' => 'Crizal Prevencia', 'price' => 833.00],
            ['name' => 'KODAK Single', 'index' => '1.50', 'treatment' => 'Crizal Sapphire HR', 'price' => 833.00],
            ['name' => 'KODAK Single', 'index' => '1.50', 'treatment' => 'Crizal Rock', 'price' => 666.00],
            ['name' => 'KODAK Single', 'index' => '1.50', 'treatment' => 'Crizal Easy Pro', 'price' => 450.00],
            ['name' => 'KODAK Single', 'index' => '1.67', 'treatment' => 'Crizal Prevencia', 'price' => 1434.00],
            ['name' => 'KODAK Single', 'index' => '1.67', 'treatment' => 'Crizal Sapphire HR', 'price' => 1434.00],
            ['name' => 'KODAK Single', 'index' => '1.67', 'treatment' => 'Crizal Rock', 'price' => 1267.00],
            ['name' => 'KODAK Single', 'index' => '1.67', 'treatment' => 'Crizal Easy Pro', 'price' => 1051.00],
            
            // KODAK Single com Transitions
            ['name' => 'KODAK Single', 'index' => 'Poly', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 1318.00],
            ['name' => 'KODAK Single', 'index' => 'Poly', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 1151.00],
            ['name' => 'KODAK Single', 'index' => '1.50', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 1358.00],
            ['name' => 'KODAK Single', 'index' => '1.50', 'treatment' => 'Transitions Gen S Cinza Crizal Sapphire HR', 'price' => 1191.00],
            
            // LENTES KODAK - SoftWear
            ['name' => 'KODAK SoftWear', 'index' => '1.50', 'treatment' => 'Crizal Prevencia', 'price' => 861.00],
            ['name' => 'KODAK SoftWear', 'index' => '1.50', 'treatment' => 'Crizal Sapphire HR', 'price' => 861.00],
            ['name' => 'KODAK SoftWear', 'index' => '1.50', 'treatment' => 'Crizal Rock', 'price' => 694.00],
            ['name' => 'KODAK SoftWear', 'index' => '1.50', 'treatment' => 'Crizal Easy Pro', 'price' => 478.00],
            ['name' => 'KODAK SoftWear', 'index' => 'Poly', 'treatment' => 'Crizal Prevencia', 'price' => 976.00],
            ['name' => 'KODAK SoftWear', 'index' => 'Poly', 'treatment' => 'Crizal Sapphire HR', 'price' => 976.00],
            ['name' => 'KODAK SoftWear', 'index' => 'Poly', 'treatment' => 'Crizal Rock', 'price' => 809.00],
            ['name' => 'KODAK SoftWear', 'index' => 'Poly', 'treatment' => 'Crizal Easy Pro', 'price' => 593.00],
            ['name' => 'KODAK SoftWear', 'index' => '1.67', 'treatment' => 'Crizal Prevencia', 'price' => 1617.00],
            ['name' => 'KODAK SoftWear', 'index' => '1.67', 'treatment' => 'Crizal Sapphire HR', 'price' => 1617.00],
            ['name' => 'KODAK SoftWear', 'index' => '1.67', 'treatment' => 'Crizal Rock', 'price' => 1450.00],
            ['name' => 'KODAK SoftWear', 'index' => '1.67', 'treatment' => 'Crizal Easy Pro', 'price' => 1234.00],
            
            // LENTES KODAK - City (Lentes Prontas)
            ['name' => 'KODAK City', 'index' => 'Poly', 'treatment' => 'Crizal Prevencia', 'price' => 228.00],
            ['name' => 'KODAK City', 'index' => '1.56', 'treatment' => 'Crizal Prevencia', 'price' => 156.00],
            ['name' => 'KODAK City', 'index' => '1.67', 'treatment' => 'Crizal Prevencia', 'price' => 403.00],
            ['name' => 'KODAK City', 'index' => '1.50', 'treatment' => 'Transitions Gen 8 Cinza', 'price' => 342.00],
            
            // LENTES KODAK - Blue (Lentes Prontas)
            ['name' => 'KODAK Blue', 'index' => 'Poly', 'treatment' => 'Crizal Prevencia', 'price' => 138.00],
            ['name' => 'KODAK Blue', 'index' => '1.56', 'treatment' => 'Crizal Prevencia', 'price' => 90.00],
            ['name' => 'KODAK Blue', 'index' => '1.67', 'treatment' => 'Crizal Prevencia', 'price' => 302.00],
            
            // LENTES KODAK - Intro (Lentes Prontas)
            ['name' => 'KODAK Intro', 'index' => 'Poly', 'treatment' => 'Crizal Prevencia', 'price' => 114.00],
            ['name' => 'KODAK Intro', 'index' => '1.56', 'treatment' => 'Crizal Prevencia', 'price' => 47.00],
            
            // ESPACE
            ['name' => 'Espace Plus Digital', 'index' => 'Orma', 'treatment' => 'Crizal Prevencia', 'price' => 815.00],
            ['name' => 'Espace Plus Digital', 'index' => 'Orma', 'treatment' => 'Crizal Sapphire HR', 'price' => 815.00],
            ['name' => 'Espace Plus Digital', 'index' => 'Orma', 'treatment' => 'Crizal Rock', 'price' => 648.00],
            ['name' => 'Espace Plus Digital', 'index' => 'Orma', 'treatment' => 'Crizal Easy Pro', 'price' => 432.00],
            ['name' => 'Espace Plus Digital', 'index' => 'Poly', 'treatment' => 'Crizal Prevencia', 'price' => 1289.00],
            ['name' => 'Espace Plus Digital', 'index' => 'Poly', 'treatment' => 'Crizal Sapphire HR', 'price' => 1289.00],
            ['name' => 'Espace Plus Digital', 'index' => 'Poly', 'treatment' => 'Crizal Rock', 'price' => 1122.00],
            ['name' => 'Espace Plus Digital', 'index' => 'Poly', 'treatment' => 'Crizal Easy Pro', 'price' => 906.00],
            
            // ESPACE com Transitions
            ['name' => 'Espace Plus Digital', 'index' => 'Orma', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 601.00],
            ['name' => 'Espace Plus Digital', 'index' => 'Poly', 'treatment' => 'Transitions Gen S Cinza Crizal Prevencia', 'price' => 1075.00],
            
            // OXFORD/ESSENCIAL/EOS - Multifocal
            ['name' => 'Oxford HD Freedom 2.0', 'index' => 'Resina 1.50', 'treatment' => 'Sem AR', 'price' => 1220.00],
            ['name' => 'Oxford HD Freedom 2.0', 'index' => 'Resina 1.50', 'treatment' => 'Blue Filter', 'price' => 2668.00],
            ['name' => 'Essencial HD', 'index' => 'Resina 1.50', 'treatment' => 'Sem AR', 'price' => 813.00],
            ['name' => 'Essencial HD', 'index' => 'Resina 1.50', 'treatment' => 'Blue Filter', 'price' => 852.00],
            ['name' => 'Essencial HD Minimus', 'index' => 'Resina 1.50', 'treatment' => 'Sem AR', 'price' => 2441.00],
            ['name' => 'Essencial HD Progress', 'index' => 'Resina 1.50', 'treatment' => 'Sem AR', 'price' => 520.00],
            ['name' => 'Essencial Minimus LITE', 'index' => 'Resina 1.50', 'treatment' => 'Sem AR', 'price' => 558.00],
            ['name' => 'Essencial Progress LITE', 'index' => 'Resina 1.50', 'treatment' => 'Sem AR', 'price' => 1328.00],
            
            // OXFORD/ESSENCIAL/EOS - Visão Simples
            ['name' => 'EOS HD', 'index' => 'Resina 1.50', 'treatment' => 'Sem AR', 'price' => 368.00],
            ['name' => 'EOS HD', 'index' => 'Resina 1.50', 'treatment' => 'Blue Filter', 'price' => 744.00],
            ['name' => 'EOS HD Blend', 'index' => 'Resina 1.50', 'treatment' => 'Sem AR', 'price' => 881.00],
            ['name' => 'EOS Tradicional', 'index' => 'Resina 1.50', 'treatment' => 'Sem AR', 'price' => 435.00],
            ['name' => 'EOS Tradicional', 'index' => 'Resina 1.50', 'treatment' => 'Blue Filter', 'price' => 1101.00],
            
            // LENTES PRONTAS OXFORD/EOS
            ['name' => 'Lente Pronta EOS', 'index' => 'Resina 1.50', 'treatment' => 'Incolor', 'price' => 41.00],
            ['name' => 'Lente Pronta EOS', 'index' => 'Resina 1.56', 'treatment' => 'AR + Photo', 'price' => 43.00],
            ['name' => 'Lente Pronta EOS', 'index' => 'Poly', 'treatment' => 'Incolor', 'price' => 47.00],
            ['name' => 'Lente Pronta EOS', 'index' => 'Poly', 'treatment' => 'AR Residual Verde', 'price' => 47.00],
            ['name' => 'Lente Pronta EOS', 'index' => 'Poly', 'treatment' => 'AR + Blue Residual Verde', 'price' => 63.00],
            ['name' => 'Lente Pronta EOS', 'index' => 'Resina 1.60', 'treatment' => 'AR', 'price' => 97.00],
            ['name' => 'Lente Pronta EOS', 'index' => 'Resina 1.67', 'treatment' => 'AR Residual Verde', 'price' => 30.00],
            ['name' => 'Lente Pronta EOS', 'index' => 'Resina 1.56', 'treatment' => 'AR + Blue Residual Azul', 'price' => 66.00],
            ['name' => 'Lente Pronta EOS', 'index' => 'Resina 1.56', 'treatment' => 'AR Residual Verde', 'price' => 106.00],
            ['name' => 'Lente Pronta EOS', 'index' => 'Resina 1.56', 'treatment' => 'AR + Blue Residual Verde', 'price' => 146.00],
            ['name' => 'Lente Pronta EOS', 'index' => 'Resina 1.67', 'treatment' => 'AR + Blue', 'price' => 151.00],
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

