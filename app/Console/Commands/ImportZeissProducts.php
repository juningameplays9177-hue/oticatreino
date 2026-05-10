<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Console\Command;

class ImportZeissProducts extends Command
{
    protected $signature = 'products:import-zeiss {--supplier=ZEISS} {--dry-run}';
    protected $description = 'Importa produtos ZEISS do catálogo para o banco de dados';

    public function handle()
    {
        $supplierName = $this->option('supplier') ?? 'ZEISS';
        $dryRun = $this->option('dry-run');

        $this->info("📄 Processando produtos ZEISS...");

        // Ler o arquivo de texto do PDF (você pode salvar o conteúdo em um arquivo)
        $pdfContent = $this->getPdfContent();
        
        if (empty($pdfContent)) {
            $this->error("Conteúdo do PDF não encontrado!");
            return 1;
        }

        // Extrair produtos
        $products = $this->extractProducts($pdfContent);

        if (empty($products)) {
            $this->warn("⚠️  Nenhum produto encontrado!");
            return 1;
        }

        $this->info("✓ Encontrados " . count($products) . " produtos");

        if ($dryRun) {
            $this->warn("🔍 Modo DRY-RUN - Nenhum dado será salvo");
            $this->displayProducts($products);
            return 0;
        }

        // Buscar ou criar tipo de produto padrão
        $productType = ProductType::where('code_prefix', 'L')->first();
        if (!$productType) {
            $productType = ProductType::first();
            if (!$productType) {
                $this->error("Nenhum tipo de produto encontrado!");
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
                if (empty($productData['name'])) {
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }

                // Verificar se produto já existe
                $existingProduct = Product::where('name', $productData['name'])
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
                    'name' => $productData['name'],
                    'description' => $productData['description'] ?? $productData['name'],
                    'product_type_id' => $productType->id,
                    'brand_id' => $brand->id,
                    'supplier_id' => $supplier->id,
                    'control_stock' => false,
                    'sell_only_with_os' => true,
                    'unit' => 'PAR',
                ]);

                // Criar preços
                $price = $productData['price'] ?? 0;
                $cost = $price > 0 ? $price * 0.5 : 0;

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
                ['📊 Total', count($products)],
            ]
        );

        return 0;
    }

    protected function getPdfContent(): string
    {
        // Você pode salvar o conteúdo do PDF em um arquivo de texto
        $textFile = storage_path('logs/zeiss_catalog.txt');
        
        if (file_exists($textFile)) {
            return file_get_contents($textFile);
        }

        // Se não existir, retornar conteúdo vazio
        return '';
    }

    protected function extractProducts(string $content): array
    {
        $products = [];
        $lines = explode("\n", $content);

        $currentProduct = null;
        $inPriceTable = false;

        foreach ($lines as $line) {
            $line = trim($line);

            // Detectar início de tabela de preços
            if (stripos($line, 'PRODUTO') !== false && 
                (stripos($line, 'TRATAMENTOS') !== false || stripos($line, 'VALOR') !== false)) {
                $inPriceTable = true;
                continue;
            }

            // Detectar produtos ZEISS
            if (stripos($line, 'ZEISS') !== false && strlen($line) > 10) {
                // Limpar linha e extrair nome do produto
                $productName = $this->cleanProductName($line);
                
                if (!empty($productName) && strlen($productName) > 5) {
                    $currentProduct = [
                        'name' => $productName,
                        'description' => $productName,
                        'price' => 0,
                    ];
                }
            }

            // Detectar preços (valores monetários)
            if ($currentProduct && preg_match('/\b(\d{1,3}(?:\.\d{3})*(?:,\d{2})?)\b/', $line, $matches)) {
                $priceStr = str_replace(['.', ','], ['', '.'], $matches[1]);
                $price = floatval($priceStr);
                
                // Validar se é um preço razoável (entre 100 e 50000)
                if ($price >= 100 && $price <= 50000) {
                    $currentProduct['price'] = $price;
                    
                    // Adicionar produto à lista
                    if (!empty($currentProduct['name'])) {
                        $products[] = $currentProduct;
                    }
                    
                    $currentProduct = null;
                }
            }

            // Detectar linhas com múltiplos valores (tabelas de preços)
            if ($inPriceTable && preg_match_all('/\b(\d{1,3}(?:\.\d{3})*(?:,\d{2})?)\b/', $line, $allMatches)) {
                foreach ($allMatches[1] as $priceMatch) {
                    $priceStr = str_replace(['.', ','], ['', '.'], $priceMatch);
                    $price = floatval($priceStr);
                    
                    if ($price >= 100 && $price <= 50000 && $currentProduct) {
                        $currentProduct['price'] = $price;
                        
                        if (!empty($currentProduct['name'])) {
                            $products[] = $currentProduct;
                        }
                        
                        // Criar novo produto base para próximos preços
                        $currentProduct = [
                            'name' => $currentProduct['name'],
                            'description' => $currentProduct['description'],
                            'price' => 0,
                        ];
                    }
                }
            }
        }

        // Remover duplicados
        $uniqueProducts = [];
        $seen = [];
        
        foreach ($products as $product) {
            $key = $product['name'] . '_' . $product['price'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueProducts[] = $product;
            }
        }

        return $uniqueProducts;
    }

    protected function cleanProductName(string $line): string
    {
        // Remover caracteres especiais e limpar
        $name = preg_replace('/\s+/', ' ', $line);
        $name = trim($name);
        
        // Remover prefixos comuns
        $name = preg_replace('/^(ZEISS\s+)?(Visão\s+Simples\s+)?(Progressive\s+)?/i', '', $name);
        $name = preg_replace('/\s+(Blueguard|PhotoFusion|Polarizada|Cinza|Marrom|Verde).*$/i', '', $name);
        $name = preg_replace('/\s+\d+[.,]\d+.*$/', '', $name); // Remove valores numéricos no final
        
        return trim($name);
    }

    protected function displayProducts(array $products): void
    {
        $this->newLine();
        $this->info("Produtos encontrados (primeiros 30):");
        $this->newLine();

        $displayProducts = array_slice($products, 0, 30);

        $tableData = [];
        foreach ($displayProducts as $product) {
            $tableData[] = [
                substr($product['name'] ?? '-', 0, 50),
                'R$ ' . number_format($product['price'] ?? 0, 2, ',', '.'),
            ];
        }

        $this->table(
            ['Nome', 'Preço'],
            $tableData
        );

        if (count($products) > 30) {
            $this->line("... e mais " . (count($products) - 30) . " produtos");
        }
    }
}

