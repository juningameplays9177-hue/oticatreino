<?php

namespace Database\Seeders;

use App\Models\Finance\FinanceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinanceCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar todas as empresas ou usar empresa padrão (ID 1)
        $companies = DB::table('companies')->pluck('id')->toArray();
        if (empty($companies)) {
            $companies = [1]; // Fallback para empresa padrão
        }

        foreach ($companies as $companyId) {
            $this->seedCategoriesForCompany($companyId);
        }
    }
    
    /**
     * Recria categorias para uma empresa específica (força recriação)
     */
    public function recreateForCompany(int $companyId): void
    {
        $this->seedCategoriesForCompany($companyId, true);
    }

    protected function seedCategoriesForCompany(int $companyId, bool $force = false): void
    {
        // Helper para output (funciona tanto via artisan quanto via script)
        $output = function($message, $type = 'info') {
            if ($this->command) {
                if ($type === 'warn') {
                    $this->command->warn($message);
                } else {
                    $this->command->info($message);
                }
            } else {
                echo $message . "\n";
            }
        };
        
        // Verificar se já existem categorias para esta empresa
        $existing = FinanceCategory::where('company_id', $companyId)
            ->where('is_system', true)
            ->count();
        
        if ($existing > 0 && !$force) {
            $output("⚠️  Categorias já existem para empresa {$companyId} ({$existing} categorias encontradas).");
            $output("   Se desejar recriar, delete as categorias existentes primeiro.");
            
            // Verificar se tem todas as subcategorias (deve ter pelo menos 78 = 10 pais + 68 filhos)
            if ($existing < 78) {
                $output("   ⚠️  Aviso: Esperado 78 categorias, mas encontrado apenas {$existing}.", 'warn');
                $output("   Considere recriar as categorias para garantir que todas as subcategorias estejam presentes.", 'warn');
            }
            return;
        }
        
        if ($force && $existing > 0) {
            // Deletar categorias existentes se force = true
            FinanceCategory::where('company_id', $companyId)
                ->where('is_system', true)
                ->delete();
            $output("🗑️  Removidas {$existing} categorias antigas da empresa {$companyId}.");
        }
        
        $output("📦 Criando categorias para empresa {$companyId}...");

        // 1. Compras e Fornecedores
        $comprasFornecedores = FinanceCategory::create([
            'company_id' => $companyId,
            'name' => 'Compras e Fornecedores',
            'nature' => 'expense',
            'is_system' => true,
            'is_active' => true,
        ]);

        $comprasSubcategories = [
            'Combustivel Entrega',
            'Compra de Matéria Prima',
            'Compra de Mercadoria para Revenda',
            'Compra de Serviços Terceiros',
            'Correio Entrega',
            'Embalagem',
            'Frete de Compra',
            'Frete de Entrega',
        ];

        foreach ($comprasSubcategories as $sub) {
            FinanceCategory::create([
                'company_id' => $companyId,
                'parent_id' => $comprasFornecedores->id,
                'name' => $sub,
                'nature' => 'expense',
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        // 2. Despesas Administrativas
        $despesasAdmin = FinanceCategory::create([
            'company_id' => $companyId,
            'name' => 'Despesas Administrativas',
            'nature' => 'expense',
            'is_system' => true,
            'is_active' => true,
        ]);

        $adminSubcategories = [
            'Advogados',
            'Água',
            'Aluguel',
            'Combustível',
            'Condomínio',
            'Contabilidade',
            'Energia elétrica',
            'IPTU',
            'Limpeza',
            'Manutenção de imobilizado',
        ];

        foreach ($adminSubcategories as $sub) {
            FinanceCategory::create([
                'company_id' => $companyId,
                'parent_id' => $despesasAdmin->id,
                'name' => $sub,
                'nature' => 'expense',
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        // 3. Marketing e propaganda
        $marketing = FinanceCategory::create([
            'company_id' => $companyId,
            'name' => 'Marketing e propaganda',
            'nature' => 'expense',
            'is_system' => true,
            'is_active' => true,
        ]);

        $marketingSubcategories = [
            'Caixinha / Premiação / Bonus',
            'Marketing Digital',
            'Merchandising',
        ];

        foreach ($marketingSubcategories as $sub) {
            FinanceCategory::create([
                'company_id' => $companyId,
                'parent_id' => $marketing->id,
                'name' => $sub,
                'nature' => 'expense',
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        // 4. Despesas com pessoal
        $despesasPessoal = FinanceCategory::create([
            'company_id' => $companyId,
            'name' => 'Despesas com pessoal',
            'nature' => 'expense',
            'is_system' => true,
            'is_active' => true,
        ]);

        $pessoalSubcategories = [
            '13o Salário',
            'Adiantamento',
            'Assistência Médica',
            'Férias',
            'FGTS',
            'INSS',
            'IRRF',
            'Outros Benefícios',
            'Pensão Alimentícia',
            'PLR',
            'Pró-labore',
            'Recisões',
            'Salários',
            'Seguro de Vida',
            'Vale Refeição',
            'Vale Transporte',
        ];

        foreach ($pessoalSubcategories as $sub) {
            FinanceCategory::create([
                'company_id' => $companyId,
                'parent_id' => $despesasPessoal->id,
                'name' => $sub,
                'nature' => 'expense',
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        // 5. Despesas com Sócios
        $despesasSocios = FinanceCategory::create([
            'company_id' => $companyId,
            'name' => 'Despesas com Sócios',
            'nature' => 'expense',
            'is_system' => true,
            'is_active' => true,
        ]);

        $sociosSubcategories = [
            'Sócio 1',
            'Sócio 2',
        ];

        foreach ($sociosSubcategories as $sub) {
            FinanceCategory::create([
                'company_id' => $companyId,
                'parent_id' => $despesasSocios->id,
                'name' => $sub,
                'nature' => 'expense',
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        // 6. Despesas de venda
        $despesasVenda = FinanceCategory::create([
            'company_id' => $companyId,
            'name' => 'Despesas de venda',
            'nature' => 'expense',
            'is_system' => true,
            'is_active' => true,
        ]);

        $vendaSubcategories = [
            'Bonificações',
            'Cheque Devolvido',
            'Comissões',
            'Despesas de viagem',
            'Market Place',
        ];

        foreach ($vendaSubcategories as $sub) {
            FinanceCategory::create([
                'company_id' => $companyId,
                'parent_id' => $despesasVenda->id,
                'name' => $sub,
                'nature' => 'expense',
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        // 7. Despesas Financeiras
        $despesasFinanceiras = FinanceCategory::create([
            'company_id' => $companyId,
            'name' => 'Despesas Financeiras',
            'nature' => 'expense',
            'is_system' => true,
            'is_active' => true,
        ]);

        $financeirasSubcategories = [
            'Empréstimos',
            'Juros sobre empréstimos',
            'Multas',
            'Tarifas bancárias',
        ];

        foreach ($financeirasSubcategories as $sub) {
            FinanceCategory::create([
                'company_id' => $companyId,
                'parent_id' => $despesasFinanceiras->id,
                'name' => $sub,
                'nature' => 'expense',
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        // 8. Devoluções
        $devolucoes = FinanceCategory::create([
            'company_id' => $companyId,
            'name' => 'Devoluções',
            'nature' => 'expense',
            'is_system' => true,
            'is_active' => true,
        ]);

        $devolucoesSubcategories = [
            'Devolução de Serviços Prestados',
            'Devolução de Venda de Mercadoria',
            'Estornos',
        ];

        foreach ($devolucoesSubcategories as $sub) {
            FinanceCategory::create([
                'company_id' => $companyId,
                'parent_id' => $devolucoes->id,
                'name' => $sub,
                'nature' => 'expense',
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        // 9. Impostos e Taxas
        $impostos = FinanceCategory::create([
            'company_id' => $companyId,
            'name' => 'Impostos e Taxas',
            'nature' => 'expense',
            'is_system' => true,
            'is_active' => true,
        ]);

        $impostosSubcategories = [
            'COFINS',
            'Contribuição Social',
            'DARM RIO',
            'DAS (Simples Nacional)',
            'ICMS',
            'IOF',
            'IPI',
            'IRPJ',
            'ISS',
            'PIS',
            'ST',
        ];

        foreach ($impostosSubcategories as $sub) {
            FinanceCategory::create([
                'company_id' => $companyId,
                'parent_id' => $impostos->id,
                'name' => $sub,
                'nature' => 'expense',
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        // 10. Investimentos
        $investimentos = FinanceCategory::create([
            'company_id' => $companyId,
            'name' => 'Investimentos',
            'nature' => 'expense',
            'is_system' => true,
            'is_active' => true,
        ]);

        $investimentosSubcategories = [
            'Aplicação financeiras',
            'Equipamentos de informática',
            'Instalações',
            'Máquinas e Equipamentos',
            'Móveis e Utensílios',
            'Veículos',
        ];

        foreach ($investimentosSubcategories as $sub) {
            FinanceCategory::create([
                'company_id' => $companyId,
                'parent_id' => $investimentos->id,
                'name' => $sub,
                'nature' => 'expense',
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        // ========== CATEGORIAS DE RECEITA ==========
        
        // 1. Vendas de Produtos
        $vendasProdutos = FinanceCategory::create([
            'company_id' => $companyId,
            'name' => 'Vendas de Produtos',
            'nature' => 'revenue',
            'is_system' => true,
            'is_active' => true,
        ]);

        $vendasProdutosSub = [
            'Vendas Varejo Loja Física',
            'Vendas E-commerce',
            'Vendas Atacado',
            'Vendas – Promoções / Queima de estoque',
        ];

        foreach ($vendasProdutosSub as $sub) {
            FinanceCategory::create([
                'company_id' => $companyId,
                'parent_id' => $vendasProdutos->id,
                'name' => $sub,
                'nature' => 'revenue',
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        // 2. Vendas de Serviços
        $vendasServicos = FinanceCategory::create([
            'company_id' => $companyId,
            'name' => 'Vendas de Serviços',
            'nature' => 'revenue',
            'is_system' => true,
            'is_active' => true,
        ]);

        $vendasServicosSub = [
            'Serviços Técnicos',
            'Manutenção / Ajuste',
            'Consultoria / Atendimento Especial',
        ];

        foreach ($vendasServicosSub as $sub) {
            FinanceCategory::create([
                'company_id' => $companyId,
                'parent_id' => $vendasServicos->id,
                'name' => $sub,
                'nature' => 'revenue',
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        // 3. Receitas Financeiras
        $receitasFinanceiras = FinanceCategory::create([
            'company_id' => $companyId,
            'name' => 'Receitas Financeiras',
            'nature' => 'revenue',
            'is_system' => true,
            'is_active' => true,
        ]);

        $receitasFinanceirasSub = [
            'Juros de atraso',
            'Multas por atraso',
            'Descontos obtidos de fornecedores',
        ];

        foreach ($receitasFinanceirasSub as $sub) {
            FinanceCategory::create([
                'company_id' => $companyId,
                'parent_id' => $receitasFinanceiras->id,
                'name' => $sub,
                'nature' => 'revenue',
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        // 4. Descontos Concedidos (Receita negativa)
        $descontosConcedidos = FinanceCategory::create([
            'company_id' => $companyId,
            'name' => 'Descontos Concedidos',
            'nature' => 'revenue', // Negativa, mas ainda é receita
            'is_system' => true,
            'is_active' => true,
        ]);

        $descontosConcedidosSub = [
            'Desconto comercial na venda',
            'Desconto por antecipação de pagamento',
            'Perdão de dívida / abatimento',
        ];

        foreach ($descontosConcedidosSub as $sub) {
            FinanceCategory::create([
                'company_id' => $companyId,
                'parent_id' => $descontosConcedidos->id,
                'name' => $sub,
                'nature' => 'revenue',
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        // 5. Outras Receitas Operacionais
        $outrasReceitas = FinanceCategory::create([
            'company_id' => $companyId,
            'name' => 'Outras Receitas Operacionais',
            'nature' => 'revenue',
            'is_system' => true,
            'is_active' => true,
        ]);

        $outrasReceitasSub = [
            'Bonificações',
            'Reembolso de despesas',
            'Taxas cobradas de clientes',
        ];

        foreach ($outrasReceitasSub as $sub) {
            FinanceCategory::create([
                'company_id' => $companyId,
                'parent_id' => $outrasReceitas->id,
                'name' => $sub,
                'nature' => 'revenue',
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        // 6. Receitas Não Operacionais
        $receitasNaoOperacionais = FinanceCategory::create([
            'company_id' => $companyId,
            'name' => 'Receitas Não Operacionais',
            'nature' => 'revenue',
            'is_system' => true,
            'is_active' => true,
        ]);

        $receitasNaoOperacionaisSub = [
            'Venda de Ativos',
            'Indenizações',
            'Receitas não recorrentes',
        ];

        foreach ($receitasNaoOperacionaisSub as $sub) {
            FinanceCategory::create([
                'company_id' => $companyId,
                'parent_id' => $receitasNaoOperacionais->id,
                'name' => $sub,
                'nature' => 'revenue',
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        // Contar total de categorias criadas
        $totalCategories = FinanceCategory::where('company_id', $companyId)
            ->where('is_system', true)
            ->count();
        $parentCount = FinanceCategory::where('company_id', $companyId)
            ->where('is_system', true)
            ->whereNull('parent_id')
            ->count();
        $childCount = $totalCategories - $parentCount;

        // Helper para output
        if ($this->command) {
            $this->command->info("✅ Categorias financeiras criadas para empresa {$companyId}!");
            $this->command->info("   ✅ Criadas {$totalCategories} categorias ({$parentCount} pais + {$childCount} filhos)");
            $this->command->info("   ✅ Categorias completas!");
        } else {
            echo "✅ Categorias financeiras criadas para empresa {$companyId}!\n";
            echo "   ✅ Criadas {$totalCategories} categorias ({$parentCount} pais + {$childCount} filhos)\n";
            echo "   ✅ Categorias completas!\n";
        }
    }
}

