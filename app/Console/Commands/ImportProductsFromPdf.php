<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Smalot\PdfParser\Parser;

class ImportProductsFromPdf extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:import-pdf 
                            {file : Caminho do arquivo PDF}
                            {--supplier= : Nome do fornecedor padrão}
                            {--dry-run : Executar sem salvar no banco de dados}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa produtos de uma tabela de preços em PDF para o banco de dados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        $supplierName = $this->option('supplier') ?? 'Fornecedor Padrão';
        $dryRun = $this->option('dry-run');

        if (!file_exists($filePath)) {
            $this->error("Arquivo não encontrado: {$filePath}");
            return 1;
        }

        $this->info("📄 Processando PDF: {$filePath}");
        
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            
            $this->info("✓ PDF carregado com sucesso");
            $this->line("Total de páginas: " . count($pdf->getPages()));
            
            // Salvar texto extraído em arquivo temporário para análise
            $tempFile = storage_path('logs/pdf_extracted_text.txt');
            file_put_contents($tempFile, $text);
            $this->line("Texto extraído salvo em: {$tempFile}");
            
            // Processar o texto e extrair produtos
            $products = $this->extractProducts($text);
            
            if (empty($products)) {
                $this->warn("⚠️  Nenhum produto encontrado no PDF.");
                $this->line("Verifique o arquivo de texto extraído em: {$tempFile}");
                $this->line("Você pode precisar ajustar a lógica de extração conforme o formato do PDF.");
                return 1;
            }
            
            $this->info("✓ Encontrados " . count($products) . " produtos");
            
            if ($dryRun) {
                $this->warn("🔍 Modo DRY-RUN - Nenhum dado será salvo");
                $this->displayProducts($products);
                return 0;
            }
            
            // Buscar ou criar tipo de produto padrão
            $productType = ProductType::where('code_prefix', 'P')->first();
            if (!$productType) {
                $productType = ProductType::first();
                if (!$productType) {
                    $this->error("Nenhum tipo de produto encontrado. Execute ProductTypesSeeder primeiro.");
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
            
            // Buscar lojas
            $stores = Store::where('active', true)->get();
            if ($stores->isEmpty()) {
                $this->warn("⚠️  Nenhuma loja ativa encontrada. Preços serão criados sem loja específica.");
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
                    
                    // Verificar se produto já existe (por nome ou código)
                    $existingProduct = null;
                    if (!empty($productData['ref'])) {
                        $existingProduct = Product::where('ref', $productData['ref'])->first();
                    }
                    if (!$existingProduct && !empty($productData['ean13'])) {
                        $existingProduct = Product::where('ean13', $productData['ean13'])->first();
                    }
                    if (!$existingProduct) {
                        $existingProduct = Product::where('name', $productData['name'])->first();
                    }
                    
                    if ($existingProduct) {
                        $skippedCount++;
                        $bar->advance();
                        continue;
                    }
                    
                    // Criar ou buscar marca
                    $brand = null;
                    if (!empty($productData['brand'])) {
                        $brand = Brand::firstOrCreate(
                            ['name' => $productData['brand']],
                            ['name' => $productData['brand']]
                        );
                    }
                    
                    // Gerar código do produto
                    $ref = $productData['ref'] ?? Product::generateRef($productType->id);
                    
                    // Criar produto
                    $product = Product::create([
                        'ref' => $ref,
                        'ean13' => $productData['ean13'] ?? null,
                        'name' => $productData['name'],
                        'description' => $productData['description'] ?? $productData['name'],
                        'product_type_id' => $productType->id,
                        'brand_id' => $brand?->id,
                        'supplier_id' => $supplier->id,
                        'color' => $productData['color'] ?? null,
                        'size' => $productData['size'] ?? null,
                        'shape' => $productData['shape'] ?? null,
                        'control_stock' => $productData['control_stock'] ?? true,
                        'sell_only_with_os' => $productData['sell_only_with_os'] ?? false,
                        'unit' => $productData['unit'] ?? 'UN',
                    ]);
                    
                    // Criar preços
                    $price = $productData['price'] ?? 0;
                    $cost = $productData['cost'] ?? 0;
                    
                    if ($stores->isNotEmpty()) {
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
                    } else {
                        // Se não houver lojas, criar uma loja padrão ou usar a primeira disponível
                        $firstStore = Store::first();
                        if (!$firstStore) {
                            // Criar loja padrão se não existir nenhuma
                            $firstStore = Store::create([
                                'name' => 'Loja Padrão',
                                'code' => '001',
                                'active' => true,
                            ]);
                            $this->line("  ⚠️  Criada loja padrão: {$firstStore->name}");
                        }
                        
                        ProductPrice::updateOrCreate(
                            [
                                'product_id' => $product->id,
                                'store_id' => $firstStore->id,
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
                    $this->error("Erro ao cadastrar produto: " . ($productData['name'] ?? 'Desconhecido'));
                    $this->error("  " . $e->getMessage());
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
                    ['⚠️  Ignorados (já existem)', $skippedCount],
                    ['❌ Erros', $errorCount],
                    ['📊 Total processado', count($products)],
                ]
            );
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Erro ao processar PDF: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
    
    /**
     * Extrai produtos do texto do PDF
     * Esta função precisa ser ajustada conforme o formato específico do PDF
     */
    protected function extractProducts(string $text): array
    {
        $products = [];
        $lines = explode("\n", $text);
        
        // Remover linhas vazias e limpar
        $lines = array_filter(array_map('trim', $lines), function($line) {
            return !empty($line) && strlen($line) > 3;
        });
        
        $currentProduct = null;
        $inTable = false;
        $headerFound = false;
        
        foreach ($lines as $lineIndex => $line) {
            // Detectar início da tabela (procura por cabeçalhos comuns)
            if (!$headerFound && (
                stripos($line, 'código') !== false ||
                stripos($line, 'produto') !== false ||
                stripos($line, 'descrição') !== false ||
                stripos($line, 'preço') !== false ||
                stripos($line, 'marca') !== false
            )) {
                $headerFound = true;
                $inTable = true;
                continue;
            }
            
            if (!$inTable) {
                continue;
            }
            
            // Tentar identificar linhas de produto
            // Padrões comuns em tabelas de preços:
            // - Código | Nome | Marca | Preço
            // - Código Nome Marca Preço
            // - Nome - Marca - Preço
            
            // Dividir linha por espaços múltiplos ou tabs
            $parts = preg_split('/\s{2,}|\t/', $line);
            $parts = array_filter(array_map('trim', $parts));
            
            if (count($parts) < 2) {
                // Tentar dividir por outros separadores
                $parts = preg_split('/\s*\|\s*|;|,/', $line);
                $parts = array_filter(array_map('trim', $parts));
            }
            
            // Se a linha tem pelo menos 2 partes, pode ser um produto
            if (count($parts) >= 2) {
                $product = $this->parseProductLine($parts, $line);
                
                if ($product && !empty($product['name'])) {
                    $products[] = $product;
                }
            }
        }
        
        // Se não encontrou produtos com o método acima, tentar método alternativo
        if (empty($products)) {
            $products = $this->extractProductsAlternative($text);
        }
        
        return $products;
    }
    
    /**
     * Parse uma linha de produto
     */
    protected function parseProductLine(array $parts, string $originalLine): ?array
    {
        $product = [
            'ref' => null,
            'ean13' => null,
            'name' => null,
            'brand' => null,
            'price' => 0,
            'cost' => 0,
            'color' => null,
            'size' => null,
            'shape' => null,
        ];
        
        // Tentar identificar código (geralmente primeiro campo, alfanumérico curto)
        foreach ($parts as $index => $part) {
            // Código geralmente tem 3-20 caracteres alfanuméricos
            if (preg_match('/^[A-Z0-9\-]{3,20}$/i', $part) && empty($product['ref'])) {
                $product['ref'] = $part;
                unset($parts[$index]);
                break;
            }
        }
        
        // Tentar identificar EAN13 (13 dígitos)
        foreach ($parts as $index => $part) {
            if (preg_match('/^\d{13}$/', $part)) {
                $product['ean13'] = $part;
                unset($parts[$index]);
                break;
            }
        }
        
        // Tentar identificar preço (número com vírgula ou ponto decimal)
        foreach ($parts as $index => $part) {
            $priceStr = preg_replace('/[^\d,.]/', '', $part);
            if (preg_match('/^\d+[,.]?\d*$/', $priceStr) && floatval(str_replace(',', '.', $priceStr)) > 0) {
                $price = floatval(str_replace(',', '.', $priceStr));
                // Se o preço parece razoável (entre 1 e 100000)
                if ($price >= 1 && $price <= 100000) {
                    $product['price'] = $price;
                    // Estimar custo como 50% do preço (ajustar conforme necessário)
                    $product['cost'] = $price * 0.5;
                    unset($parts[$index]);
                    break;
                }
            }
        }
        
        // Restante são nome, marca, etc.
        $remaining = array_values($parts);
        
        if (count($remaining) >= 1) {
            // Primeiro campo geralmente é o nome
            $product['name'] = $remaining[0];
            
            // Segundo campo pode ser marca
            if (count($remaining) >= 2) {
                $product['brand'] = $remaining[1];
            }
            
            // Se nome está muito curto, pode estar dividido
            if (strlen($product['name']) < 5 && count($remaining) > 1) {
                $product['name'] = implode(' ', array_slice($remaining, 0, min(3, count($remaining))));
            }
        }
        
        // Se não conseguiu identificar nome, usar a linha original
        if (empty($product['name'])) {
            $product['name'] = trim($originalLine);
        }
        
        return $product;
    }
    
    /**
     * Método alternativo de extração (linha por linha simples)
     */
    protected function extractProductsAlternative(string $text): array
    {
        $products = [];
        $lines = explode("\n", $text);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Ignorar linhas muito curtas ou que parecem cabeçalhos
            if (strlen($line) < 5 || 
                stripos($line, 'página') !== false ||
                stripos($line, 'total') !== false ||
                stripos($line, 'código') !== false && stripos($line, 'descrição') !== false) {
                continue;
            }
            
            // Tentar extrair preço da linha
            preg_match_all('/R\$\s*(\d+[.,]\d{2})|(\d+[.,]\d{2})/i', $line, $matches);
            $price = 0;
            if (!empty($matches[0])) {
                $priceStr = str_replace(['R$', ' '], '', $matches[0][0]);
                $price = floatval(str_replace(',', '.', $priceStr));
            }
            
            // Se tem preço ou parece um produto (linha com texto significativo)
            if ($price > 0 || (strlen($line) > 10 && preg_match('/[A-Za-z]{3,}/', $line))) {
                $product = [
                    'name' => $line,
                    'price' => $price,
                    'cost' => $price > 0 ? $price * 0.5 : 0,
                ];
                
                // Tentar extrair código se houver
                if (preg_match('/\b([A-Z0-9\-]{3,15})\b/i', $line, $codeMatch)) {
                    $product['ref'] = $codeMatch[1];
                }
                
                $products[] = $product;
            }
        }
        
        return $products;
    }
    
    /**
     * Exibe produtos encontrados (modo dry-run)
     */
    protected function displayProducts(array $products): void
    {
        $this->newLine();
        $this->info("Produtos encontrados (primeiros 20):");
        $this->newLine();
        
        $displayProducts = array_slice($products, 0, 20);
        
        $tableData = [];
        foreach ($displayProducts as $product) {
            $tableData[] = [
                $product['ref'] ?? '-',
                substr($product['name'] ?? '-', 0, 40),
                $product['brand'] ?? '-',
                'R$ ' . number_format($product['price'] ?? 0, 2, ',', '.'),
            ];
        }
        
        $this->table(
            ['Código', 'Nome', 'Marca', 'Preço'],
            $tableData
        );
        
        if (count($products) > 20) {
            $this->line("... e mais " . (count($products) - 20) . " produtos");
        }
    }
}

