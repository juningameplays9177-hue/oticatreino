<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductPrice;
use App\Models\Store;
use App\Models\Brand;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PriceTableProductsSeeder extends Seeder
{
    /**
     * Seeder para cadastrar produtos de uma tabela de preços
     * 
     * Para usar este seeder:
     * 1. Preencha o array $products com os dados dos produtos do PDF
     * 2. Execute: php artisan db:seed --class=PriceTableProductsSeeder
     */
    public function run(): void
    {
        $this->command->info('=== Cadastro de Produtos da Tabela de Preços ===');
        
        // Buscar tipo de produto padrão (ou criar se não existir)
        $productType = ProductType::where('code_prefix', 'P')->first();
        
        if (!$productType) {
            $this->command->error('Tipo de produto padrão não encontrado! Execute ProductTypesSeeder primeiro.');
            return;
        }

        // Buscar todas as lojas para cadastrar preços
        $stores = Store::all();
        if ($stores->isEmpty()) {
            $this->command->warn('Nenhuma loja encontrada. Os preços serão cadastrados sem loja específica.');
        }

        $counter = 0;

        // ============================================
        // PREENCHA AQUI OS PRODUTOS DO PDF
        // ============================================
        // Formato esperado:
        // [
        //     'name' => 'Nome do Produto',
        //     'description' => 'Descrição completa (opcional)',
        //     'price' => 100.00, // Preço padrão
        //     'cost' => 50.00, // Custo (opcional)
        //     'brand' => 'Nome da Marca', // Será criada se não existir
        //     'supplier' => 'Nome do Fornecedor', // Será criado se não existir
        //     'control_stock' => true, // true ou false
        //     'color' => 'Cor do produto (opcional)',
        //     'sell_only_with_os' => false, // true para produtos que só vendem com OS
        // ]
        
        $products = [
            // Exemplo:
            // [
            //     'name' => 'Óculos de Sol Modelo X',
            //     'description' => 'Óculos de sol com proteção UV',
            //     'price' => 150.00,
            //     'cost' => 75.00,
            //     'brand' => 'Marca Exemplo',
            //     'supplier' => 'Fornecedor Exemplo',
            //     'control_stock' => true,
            //     'color' => 'Preto',
            //     'sell_only_with_os' => false,
            // ],
        ];

        if (empty($products)) {
            $this->command->warn('⚠️  Nenhum produto definido no array $products!');
            $this->command->info('Por favor, preencha o array $products com os dados dos produtos do PDF.');
            return;
        }

        $this->command->info("Processando " . count($products) . " produtos...");

        foreach ($products as $productData) {
            try {
                // Criar ou buscar marca
                $brand = null;
                if (!empty($productData['brand'])) {
                    $brand = Brand::firstOrCreate(
                        ['name' => $productData['brand']],
                        ['name' => $productData['brand']]
                    );
                }

                // Criar ou buscar fornecedor
                $supplier = null;
                if (!empty($productData['supplier'])) {
                    $supplier = Supplier::firstOrCreate(
                        ['trade_name' => $productData['supplier']],
                        [
                            'trade_name' => $productData['supplier'],
                            'legal_name' => $productData['supplier'],
                            'is_active' => true,
                        ]
                    );
                }

                // Gerar código do produto
                $ref = Product::generateRef($productType->id);

                // Criar produto
                $product = Product::create([
                    'ref' => $ref,
                    'name' => $productData['name'],
                    'description' => $productData['description'] ?? $productData['name'],
                    'product_type_id' => $productType->id,
                    'brand_id' => $brand?->id,
                    'supplier_id' => $supplier?->id,
                    'control_stock' => $productData['control_stock'] ?? true,
                    'color' => $productData['color'] ?? null,
                    'sell_only_with_os' => $productData['sell_only_with_os'] ?? false,
                    'is_active' => true,
                ]);

                // Criar preços para cada loja
                if ($stores->isNotEmpty()) {
                    foreach ($stores as $store) {
                        ProductPrice::create([
                            'product_id' => $product->id,
                            'store_id' => $store->id,
                            'price' => $productData['price'] ?? 0,
                            'cost' => $productData['cost'] ?? 0,
                        ]);
                    }
                } else {
                    // Se não houver lojas, criar preço sem loja específica
                    ProductPrice::create([
                        'product_id' => $product->id,
                        'store_id' => null,
                        'price' => $productData['price'] ?? 0,
                        'cost' => $productData['cost'] ?? 0,
                    ]);
                }

                $counter++;
                $this->command->line("✓ {$ref}: {$product->name}");

            } catch (\Exception $e) {
                $this->command->error("✗ Erro ao cadastrar produto: {$productData['name']} - {$e->getMessage()}");
            }
        }

        $this->command->info("\n✅ Cadastro concluído! {$counter} produtos cadastrados.");
    }
}

