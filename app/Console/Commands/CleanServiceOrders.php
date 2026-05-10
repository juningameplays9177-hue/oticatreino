<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\ServiceOrder;

class CleanServiceOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'os:clean {--force : Força a limpeza sem confirmação}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpa todas as Ordens de Serviço (OS) do banco de dados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->warn('========================================');
        $this->warn('LIMPEZA DE ORDENS DE SERVIÇO (OS)');
        $this->warn('========================================');
        $this->warn('');
        $this->warn('Este comando irá DELETAR:');
        $this->warn('- Todas as Ordens de Serviço');
        $this->warn('- Todos os itens das OS');
        $this->warn('- Todas as receitas vinculadas às OS');
        $this->warn('- Todo o histórico de status das OS');
        $this->warn('- Todas as imagens das OS');
        $this->warn('');
        $this->warn('⚠️  ATENÇÃO: Esta ação NÃO pode ser desfeita!');
        $this->warn('');

        // Contar OS existentes
        $osCount = ServiceOrder::count();
        $this->info("Total de OS encontradas: {$osCount}");

        if ($osCount === 0) {
            $this->info('✅ Não há OS para limpar.');
            return 0;
        }

        // Confirmar se não usar --force
        if (!$this->option('force')) {
            if (!$this->confirm('Deseja continuar e DELETAR todas as OS?', false)) {
                $this->info('Operação cancelada.');
                return 0;
            }
        }

        $this->info('Iniciando limpeza...');
        $this->newLine();

        try {
            DB::beginTransaction();

            // Desabilitar verificação de foreign keys temporariamente
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Contar registros antes de deletar
            $itemsCount = DB::table('service_order_items')->count();
            $prescriptionsCount = DB::table('service_order_prescription')->count();
            $statusHistoryCount = DB::table('service_order_status_history')->count();
            $imagesCount = DB::table('service_order_images')->count();

            // Deletar em ordem (respeitando foreign keys)
            DB::table('service_order_images')->delete();
            $this->info("✓ Imagens de OS removidas ({$imagesCount} registros)");

            DB::table('service_order_status_history')->delete();
            $this->info("✓ Histórico de status removido ({$statusHistoryCount} registros)");

            DB::table('service_order_prescription')->delete();
            $this->info("✓ Receitas removidas ({$prescriptionsCount} registros)");

            DB::table('service_order_items')->delete();
            $this->info("✓ Itens de OS removidos ({$itemsCount} registros)");

            DB::table('service_orders')->delete();
            $this->info("✓ Ordens de Serviço removidas ({$osCount} registros)");

            // Resetar contadores de OS
            DB::table('store_counters')->update([
                'current_os' => 0,
                'current_co' => 0,
            ]);
            $this->info('✓ Contadores de OS resetados');

            // Reabilitar verificação de foreign keys
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            DB::commit();

            $this->newLine();
            $this->info('========================================');
            $this->info('✅ LIMPEZA CONCLUÍDA COM SUCESSO!');
            $this->info('========================================');
            $this->newLine();
            $this->info("Total de registros removidos:");
            $this->info("- OS: {$osCount}");
            $this->info("- Itens: {$itemsCount}");
            $this->info("- Receitas: {$prescriptionsCount}");
            $this->info("- Histórico: {$statusHistoryCount}");
            $this->info("- Imagens: {$imagesCount}");

            return 0;
        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->error('❌ ERRO: ' . $e->getMessage());
            $this->error('Operação revertida.');
            return 1;
        }
    }
}

