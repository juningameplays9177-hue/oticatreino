<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Brand;
use Illuminate\Console\Command;

class FixZeissProductsArchived extends Command
{
    protected $signature = 'products:fix-zeiss-archived';
    protected $description = 'Corrige produtos ZEISS que podem estar arquivados ou sem o campo archived definido';

    public function handle()
    {
        $this->info("🔧 Corrigindo produtos ZEISS...");

        // Buscar marca ZEISS
        $brand = Brand::where('name', 'ZEISS')->first();

        if (!$brand) {
            $this->error("Marca ZEISS não encontrada!");
            return 1;
        }

        // Buscar todos os produtos ZEISS
        $products = Product::where('brand_id', $brand->id)->get();

        $this->info("Encontrados {$products->count()} produtos ZEISS");

        $updated = 0;
        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            // Se archived for null ou true, definir como false
            if (is_null($product->archived) || $product->archived === true) {
                $product->update(['archived' => false]);
                $updated++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Correção concluída!");
        $this->info("   Produtos atualizados: {$updated}");
        $this->info("   Produtos já corretos: " . ($products->count() - $updated));

        return 0;
    }
}

