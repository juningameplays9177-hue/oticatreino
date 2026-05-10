<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class CleanDatabaseSeeder extends Seeder
{
    /**
     * Limpa o banco de dados mantendo apenas:
     * - Usuário admin@hospitaldosoculos.com
     * - Todos os produtos e suas relações
     */
    public function run(): void
    {
        $this->command->warn('========================================');
        $this->command->warn('LIMPEZA DE BANCO DE DADOS');
        $this->command->warn('========================================');
        $this->command->warn('');
        $this->command->warn('Este seeder irá:');
        $this->command->warn('- Manter apenas o usuário admin@hospitaldosoculos.com');
        $this->command->warn('- Manter todos os produtos e suas relações');
        $this->command->warn('- DELETAR todos os outros dados');
        $this->command->warn('');

        if (!$this->command->confirm('Deseja continuar?', false)) {
            $this->command->info('Operação cancelada.');
            return;
        }

        $this->command->info('Iniciando limpeza...');
        $this->command->newLine();

        try {
            // Desabilitar verificação de foreign keys temporariamente
            // IMPORTANTE: Fazer isso ANTES de iniciar a transação
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            DB::beginTransaction();

            // 1. Verificar se o usuário admin existe
            $adminUser = User::where('email', 'admin@hospitaldosoculos.com')->first();
            
            if (!$adminUser) {
                throw new \Exception("Usuário admin@hospitaldosoculos.com não encontrado!");
            }
            
            $adminId = $adminUser->id;
            $this->command->info("✓ Usuário admin encontrado (ID: {$adminId})");

            // 2. Limpar tabelas relacionadas a vendas e transações
            // Ordem: primeiro tabelas que referenciam outras, depois as referenciadas
            $tablesToClean = [
                // Pagamentos e movimentações (referenciam outras tabelas)
                'receivable_payments',
                'payable_payments',
                'cash_movements',
                'transactions',
                
                // Financeiro (referenciam vendas, clientes, etc)
                'receivables',
                'payables',
                'cash_sessions',
                'accounts',
                'finance_categories',
                'cost_centers',
                
                // Itens e relacionamentos
                'sale_items',
                'service_order_items',
                'service_order_prescription',
                'service_order_status_history',
                'service_order_images',
                
                // Vendas e OS (podem referenciar clientes, produtos, etc)
                'sales',
                'service_orders',
                
                // Clientes e relacionamentos
                'client_phones',
                'client_emails',
                'client_refs',
                'clients',
                
                // Outros relacionamentos
                'supplier_contacts',
                'supplier_representatives',
                'employees',
                'suppliers',
                'companies',
                'stores',
                'store_counters',
                'prescriptions',
            ];

            foreach ($tablesToClean as $table) {
                if (Schema::hasTable($table)) {
                    $count = DB::table($table)->count();
                    // Usar DELETE ao invés de TRUNCATE para evitar problemas com foreign keys
                    DB::table($table)->delete();
                    $this->command->info("✓ Tabela '{$table}' limpa ({$count} registros removidos)");
                }
            }

            // 3. Limpar usuários (exceto admin)
            $usersCount = User::where('email', '!=', 'admin@hospitaldosoculos.com')->count();
            User::where('email', '!=', 'admin@hospitaldosoculos.com')->delete();
            $this->command->info("✓ Usuários removidos ({$usersCount} usuários, exceto admin)");

            // 4. Limpar sessões
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->truncate();
                $this->command->info('✓ Sessões limpas');
            }

            // 5. Limpar tokens de reset de senha
            if (Schema::hasTable('password_reset_tokens')) {
                DB::table('password_reset_tokens')->truncate();
                $this->command->info('✓ Tokens de reset de senha limpos');
            }

            // 6. Resetar contadores de loja (se a tabela for recriada)
            if (Schema::hasTable('store_counters')) {
                // Não truncar, apenas resetar valores se necessário
                $this->command->info('✓ Contadores de loja serão resetados quando necessário');
            }

            // 7. Limpar cache
            if (Schema::hasTable('cache')) {
                DB::table('cache')->delete();
                $this->command->info('✓ Cache limpo');
            }

            DB::commit();
            
            // Reabilitar verificação de foreign keys APÓS o commit
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->command->newLine();
            $this->command->info('========================================');
            $this->command->info('LIMPEZA CONCLUÍDA COM SUCESSO!');
            $this->command->info('========================================');
            $this->command->newLine();
            
            $this->command->info('Mantidos:');
            $this->command->info('- Usuário: admin@hospitaldosoculos.com');
            $this->command->info('- Produtos e suas relações (grupos, subgrupos, marcas, preços, estoque, imagens)');
            $this->command->info('- Tabelas de referência (product_types, product_groups, product_subgroups, brands)');
            $this->command->newLine();
            
            $this->command->warn('Removidos:');
            $this->command->warn('- Todos os outros usuários');
            $this->command->warn('- Todas as vendas e itens de venda');
            $this->command->warn('- Todas as ordens de serviço');
            $this->command->warn('- Todos os clientes');
            $this->command->warn('- Todas as transações financeiras');
            $this->command->warn('- Todas as empresas e lojas');
            $this->command->warn('- Todos os funcionários e fornecedores');
            $this->command->warn('- Todas as sessões e tokens');

        } catch (\Exception $e) {
            // Tentar fazer rollback se houver transação ativa
            try {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }
            } catch (\Exception $e3) {
                // Ignorar erro no rollback
            }
            
            // Reabilitar foreign keys mesmo em caso de erro
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } catch (\Exception $e2) {
                // Ignorar erro ao reabilitar
            }
            
            $this->command->error('❌ ERRO: ' . $e->getMessage());
            $this->command->error('Operação revertida.');
            throw $e;
        }
    }
}

