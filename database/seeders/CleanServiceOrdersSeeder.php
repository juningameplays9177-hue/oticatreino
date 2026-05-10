<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ServiceOrder;

class CleanServiceOrdersSeeder extends Seeder
{
    /**
     * Limpa todas as Ordens de Serviço (OS) do banco de dados
     */
    public function run(): void
    {
        $this->command->warn('========================================');
        $this->command->warn('LIMPEZA DE ORDENS DE SERVIÇO (OS)');
        $this->command->warn('========================================');
        $this->command->warn('');
        $this->command->warn('Este seeder irá DELETAR:');
        $this->command->warn('- Todas as Ordens de Serviço');
        $this->command->warn('- Todos os itens das OS');
        $this->command->warn('- Todas as receitas vinculadas às OS');
        $this->command->warn('- Todo o histórico de status das OS');
        $this->command->warn('- Todas as imagens das OS');
        $this->command->warn('');
        $this->command->warn('⚠️  ATENÇÃO: Esta ação NÃO pode ser desfeita!');
        $this->command->warn('');

        // Contar OS existentes
        $osCount = ServiceOrder::count();
        $this->command->info("Total de OS encontradas: {$osCount}");

        if ($osCount === 0) {
            $this->command->info('✅ Não há OS para limpar.');
            return;
        }

        // Confirmar
        if (!$this->command->confirm('Deseja continuar e DELETAR todas as OS?', false)) {
            $this->command->info('Operação cancelada.');
            return;
        }

        $this->command->info('Iniciando limpeza...');
        $this->command->newLine();

        try {
            // Desabilitar verificação de foreign keys temporariamente
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            DB::beginTransaction();

            // Contar registros antes de deletar
            $itemsCount = DB::table('service_order_items')->count();
            $prescriptionsCount = DB::table('service_order_prescription')->count();
            $statusHistoryCount = DB::table('service_order_status_history')->count();
            $imagesCount = DB::table('service_order_images')->count();

            // Deletar em ordem (respeitando foreign keys)
            DB::table('service_order_images')->delete();
            $this->command->info("✓ Imagens de OS removidas ({$imagesCount} registros)");

            DB::table('service_order_status_history')->delete();
            $this->command->info("✓ Histórico de status removido ({$statusHistoryCount} registros)");

            DB::table('service_order_prescription')->delete();
            $this->command->info("✓ Receitas removidas ({$prescriptionsCount} registros)");

            DB::table('service_order_items')->delete();
            $this->command->info("✓ Itens de OS removidos ({$itemsCount} registros)");

            DB::table('service_orders')->delete();
            $this->command->info("✓ Ordens de Serviço removidas ({$osCount} registros)");

            // Resetar contadores de OS
            DB::table('store_counters')->update([
                'current_os' => 0,
                'current_co' => 0,
            ]);
            $this->command->info('✓ Contadores de OS resetados');

            DB::commit();
            
            // Reabilitar verificação de foreign keys
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->command->newLine();
            $this->command->info('========================================');
            $this->command->info('✅ LIMPEZA CONCLUÍDA COM SUCESSO!');
            $this->command->info('========================================');
            $this->command->newLine();
            $this->command->info("Total de registros removidos:");
            $this->command->info("- OS: {$osCount}");
            $this->command->info("- Itens: {$itemsCount}");
            $this->command->info("- Receitas: {$prescriptionsCount}");
            $this->command->info("- Histórico: {$statusHistoryCount}");
            $this->command->info("- Imagens: {$imagesCount}");

        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->command->error('❌ ERRO: ' . $e->getMessage());
            $this->command->error('Operação revertida.');
            throw $e;
        }
    }
}

