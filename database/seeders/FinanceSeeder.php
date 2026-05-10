<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Store;
use App\Models\Finance\Account;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\CostCenter;
use App\Models\Finance\CashSession;
use Illuminate\Database\Seeder;

class FinanceSeeder extends Seeder
{
    /**
     * Seed dados iniciais do módulo financeiro
     */
    public function run(): void
    {
        $companies = Company::all();
        
        foreach ($companies as $company) {
            $stores = Store::where('company_id', $company->id)->get();
            
            // Criar contas padrão por loja
            foreach ($stores as $store) {
                // Caixa da Loja
                Account::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'store_id' => $store->id,
                        'name' => "Caixa {$store->name}",
                    ],
                    [
                        'type' => 'cash',
                        'opening_balance' => 0,
                        'is_active' => true,
                    ]
                );

                // Banco Principal
                Account::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'store_id' => $store->id,
                        'name' => "Banco Principal - {$store->name}",
                    ],
                    [
                        'type' => 'bank',
                        'bank_name' => 'Banco do Brasil',
                        'opening_balance' => 0,
                        'is_active' => true,
                    ]
                );

                // Gateway Cartão
                Account::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'store_id' => $store->id,
                        'name' => "Gateway Cartão - {$store->name}",
                    ],
                    [
                        'type' => 'credit_gateway',
                        'opening_balance' => 0,
                        'is_active' => true,
                    ]
                );

                // Gateway PIX
                Account::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'store_id' => $store->id,
                        'name' => "Gateway PIX - {$store->name}",
                    ],
                    [
                        'type' => 'credit_gateway',
                        'opening_balance' => 0,
                        'is_active' => true,
                    ]
                );
            }

            // Criar contas corporativas (sem store_id)
            Account::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'store_id' => null,
                    'name' => 'Banco Corporativo',
                ],
                [
                    'type' => 'bank',
                    'bank_name' => 'Banco do Brasil',
                    'opening_balance' => 0,
                    'is_active' => true,
                ]
            );

            // Criar categorias essenciais
            $revenueCategory = FinanceCategory::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => 'Receita Vendas',
                ],
                [
                    'nature' => 'revenue',
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            $cmvCategory = FinanceCategory::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => 'CMV',
                ],
                [
                    'nature' => 'expense',
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            FinanceCategory::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => 'Tarifas Gateway',
                ],
                [
                    'nature' => 'expense',
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            FinanceCategory::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => 'Despesas Operacionais',
                ],
                [
                    'nature' => 'expense',
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            $cashBreakCategory = FinanceCategory::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => 'Quebra de Caixa',
                ],
                [
                    'nature' => 'expense',
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            FinanceCategory::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => 'Transferências',
                ],
                [
                    'nature' => 'asset',
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            // Criar centro de custo padrão
            CostCenter::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => 'Geral',
                ],
                [
                    'is_active' => true,
                ]
            );

            // Criar sessão de caixa de exemplo (apenas para desenvolvimento)
            if (app()->environment('local')) {
                $cashAccount = Account::where('company_id', $company->id)
                    ->where('type', 'cash')
                    ->first();

                if ($cashAccount && $stores->isNotEmpty()) {
                    $store = $stores->first();
                    $user = \App\Models\User::where('company_id', $company->id)->first();
                    
                    if ($user) {
                        CashSession::firstOrCreate(
                            [
                                'company_id' => $company->id,
                                'store_id' => $store->id,
                                'account_id' => $cashAccount->id,
                                'status' => 'open',
                            ],
                            [
                                'opened_by' => $user->id,
                                'opened_at' => now(),
                                'opening_amount' => 100.00,
                            ]
                        );
                    }
                }
            }
        }
    }
}

