<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductPrice;
use Illuminate\Console\Command;

class CleanDuplicateLenses extends Command
{
    protected $signature = 'lenses:clean-duplicates';
    protected $description = 'Remove lentes com código antigo (formato longo) mantendo apenas as com código sequencial (L001, L002, etc.)';

    public function handle()
    {
        $this->info('=== Limpeza de Lentes Duplicadas ===');
        $this->newLine();

        // Buscar tipo de produto Lente
        $lensType = ProductType::where('code_prefix', 'L')->first();

        if (!$lensType) {
            $this->error('Tipo de produto "Lente" não encontrado!');
            return 1;
        }

        $this->info("Tipo de produto encontrado: {$lensType->name} (ID: {$lensType->id})");
        $this->newLine();

        // Buscar todas as lentes
        $allLenses = Product::where('product_type_id', $lensType->id)->get();

        $this->info("Total de lentes encontradas: {$allLenses->count()}");
        $this->newLine();

        // Identificar lentes com código antigo (formato longo)
        $oldFormatLenses = $allLenses->filter(function ($lens) {
            // Código antigo: qualquer coisa que NÃO seja L001, L002, etc.
            return !preg_match('/^L\d{3}$/', $lens->ref);
        });

        // Identificar lentes com código sequencial (L001, L002, etc.)
        $newFormatLenses = $allLenses->filter(function ($lens) {
            return preg_match('/^L\d{3}$/', $lens->ref);
        });

        $this->info("Lentes com código antigo (formato longo): {$oldFormatLenses->count()}");
        $this->info("Lentes com código sequencial (L001, L002, etc.): {$newFormatLenses->count()}");
        $this->newLine();

        if ($oldFormatLenses->isEmpty()) {
            $this->info('✅ Nenhuma lente com código antigo encontrada. Nada para limpar!');
            return 0;
        }

        $this->warn('=== Lentes que serão removidas ===');
        foreach ($oldFormatLenses as $lens) {
            $this->line("- {$lens->ref}: {$lens->name}");
        }

        $this->newLine();

        // Confirmar antes de deletar
        if (!$this->confirm("⚠️  ATENÇÃO: Isso irá deletar {$oldFormatLenses->count()} lentes com código antigo! Deseja continuar?", false)) {
            $this->warn('❌ Operação cancelada pelo usuário.');
            return 0;
        }

        $this->newLine();
        $this->info('=== Removendo lentes duplicadas... ===');

        $deletedCount = 0;
        $deletedPrices = 0;

        foreach ($oldFormatLenses as $lens) {
            // Deletar preços associados
            $prices = ProductPrice::where('product_id', $lens->id)->get();
            foreach ($prices as $price) {
                $price->delete();
                $deletedPrices++;
            }
            
            // Deletar produto
            $lens->delete();
            $deletedCount++;
            $this->line("✓ Removida: {$lens->ref} - {$lens->name}");
        }

        $this->newLine();
        $this->info('=== Resumo ===');
        $this->info("Lentes removidas: {$deletedCount}");
        $this->info("Preços removidos: {$deletedPrices}");
        $this->info("Lentes restantes (código sequencial): {$newFormatLenses->count()}");
        $this->newLine();
        $this->info('✅ Limpeza concluída com sucesso!');

        return 0;
    }
}

