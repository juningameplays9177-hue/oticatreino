<?php

namespace App\Services\Finance;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Finance\Account;
use App\Models\Finance\Receivable;
use App\Models\Finance\Transaction;
use App\Models\Finance\FinanceCategory;
use App\Services\Finance\StockCostService;
use Illuminate\Support\Facades\DB;

class SaleSettlementService
{
    protected StockCostService $stockCostService;

    public function __construct(StockCostService $stockCostService)
    {
        $this->stockCostService = $stockCostService;
    }

    /**
     * Liquida uma venda (cria transactions, receivables, etc)
     */
    public function settleSale(Sale $sale, array $payments): void
    {
        DB::transaction(function () use ($sale, $payments) {
            // 1. Calcular e baixar estoque (COGS)
            $totalCost = $this->stockCostService->consumeForSale($sale);
            $sale->update(['total_cost' => $totalCost]);

            // 2. Criar lançamentos contábeis para cada pagamento
            $paymentSummary = [];
            
            foreach ($payments as $payment) {
                $this->processPayment($sale, $payment);
                $paymentSummary[] = [
                    'method' => $payment['method'],
                    'amount' => $payment['amount'],
                    'installments' => $payment['installments'] ?? null,
                ];
            }

            // 3. Lançar COGS
            $this->recordCogs($sale, $totalCost);

            // 4. Atualizar payment_summary
            $sale->update(['payment_summary' => $paymentSummary]);
        });
    }

    /**
     * Processa um pagamento individual
     */
    protected function processPayment(Sale $sale, array $payment): void
    {
        $method = $payment['method'];
        $amount = $payment['amount'];
        $accountId = $payment['account_id'] ?? null;
        $installments = $payment['installments'] ?? 1;
        $balance = $payment['balance'] ?? null; // Saldo para pagamento "sinal"

        // Criar SalePayment
        $salePayment = SalePayment::create([
            'sale_id' => $sale->id,
            'method' => $method,
            'account_id' => $accountId,
            'amount' => $amount,
            'paid_at' => now(),
            'gateway_fee_amount' => $payment['gateway_fee_amount'] ?? 0,
            'installments' => $installments > 1 ? $installments : null,
            'card_brand' => $payment['card_brand'] ?? null,
            'auth_code' => $payment['auth_code'] ?? null,
        ]);

        // Se tiver balance (saldo de sinal), processar sinal à vista e criar recebível vinculado à OS
        if ($balance && $balance > 0 && $sale->service_order_id) {
            // Processar o sinal como pagamento à vista usando o método informado
            $this->createCashTransaction($sale, $method, $amount, $accountId);
            
            // Criar recebível para o saldo vinculado à OS
            $this->createOsReceivable($sale, $balance);
        }
        // Se for método "sinal" explicitamente
        elseif ($method === 'sinal' && $sale->service_order_id) {
            // Processar o sinal como pagamento à vista usando o método de pagamento do sinal
            $sinalMethod = $payment['sinal_method'] ?? 'money';
            $this->createCashTransaction($sale, $sinalMethod, $amount, $accountId);
            
            // Criar recebível para o saldo se houver
            if ($balance && $balance > 0) {
                $this->createOsReceivable($sale, $balance);
            }
        }
        // Se parcelado ou boleto, criar receivables
        elseif ($installments > 1 || $method === 'boleto' || $method === 'boleto_installment') {
            $installmentsCount = ($method === 'boleto' || $method === 'boleto_installment') ? ($installments > 1 ? $installments : 1) : $installments;
            $this->createReceivables($sale, $amount, $installmentsCount, $method);
        } else {
            // Pagamento à vista - criar transaction
            $this->createCashTransaction($sale, $method, $amount, $accountId);
        }
    }

    /**
     * Cria recebível vinculado à OS para saldo de pagamento "Sinal"
     */
    protected function createOsReceivable(Sale $sale, float $balanceAmount): void
    {
        if (!$sale->service_order_id) {
            return; // Não criar se não houver OS vinculada
        }

        $serviceOrder = \App\Models\ServiceOrder::find($sale->service_order_id);
        if (!$serviceOrder) {
            return;
        }

        // Buscar categoria de receita padrão
        $revenueCategory = $this->getRevenueCategory($sale->company_id);
        
        // Obter data de trabalho (para lançamentos retroativos)
        $workDate = \App\Helpers\WorkDateHelper::getWorkDate();
        
        // Data de vencimento: data de entrega da OS ou 30 dias a partir da data de trabalho
        $dueDate = $serviceOrder->delivery_date 
            ? \Carbon\Carbon::parse($serviceOrder->delivery_date)
            : $workDate->copy()->addDays(30);

        \App\Models\Finance\Receivable::create([
            'company_id' => $sale->company_id,
            'store_id' => $sale->store_id,
            'customer_id' => $sale->customer_id,
            'sale_id' => $sale->id,
            'os_id' => $sale->service_order_id,
            'issue_date' => $workDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'original_amount' => $balanceAmount,
            'balance_amount' => $balanceAmount,
            'status' => 'open',
            'method' => 'money', // Saldo será pago em dinheiro na entrega
            'billing_type' => 'crediario',
            'category_id' => $revenueCategory->id,
            'document_no' => $serviceOrder->os_number . '-SALDO',
            'note' => "Saldo de pagamento sinal - OS {$serviceOrder->os_number}",
        ]);
    }

    /**
     * Cria receivables para venda parcelada ou boleto
     */
    protected function createReceivables(Sale $sale, float $totalAmount, int $installments, string $method): void
    {
        // Obter data de trabalho (para lançamentos retroativos)
        $workDate = \App\Helpers\WorkDateHelper::getWorkDate();
        
        $amountPerInstallment = $totalAmount / $installments;
        $dueDate = $workDate->copy();
        
        // Buscar categoria de receita padrão
        $revenueCategory = $this->getRevenueCategory($sale->company_id);
        
        // Determinar billing_type baseado no método
        $billingType = match($method) {
            'boleto', 'boleto_installment' => 'boleto',
            'card_credit' => 'cartao_prazo',
            default => 'crediario',
        };

        $firstReceivable = null;
        
        for ($i = 1; $i <= $installments; $i++) {
            if ($i > 1) {
                $dueDate = $dueDate->copy()->addDays(30); // 30 dias entre parcelas
            }

            $receivable = Receivable::create([
                'company_id' => $sale->company_id,
                'store_id' => $sale->store_id,
                'customer_id' => $sale->customer_id,
                'sale_id' => $sale->id,
                'issue_date' => $workDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'original_amount' => $amountPerInstallment,
                'balance_amount' => $amountPerInstallment,
                'status' => 'open',
                'method' => $method,
                'billing_type' => $billingType,
                'category_id' => $revenueCategory->id,
                'installments' => $installments,
                'installment_number' => $i,
                'parent_receivable_id' => $firstReceivable ? $firstReceivable->id : null,
                'document_no' => $sale->sale_number . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
            ]);
            
            if ($i === 1) {
                $firstReceivable = $receivable;
            } else {
                // Atualizar parent_receivable_id das parcelas seguintes
                $receivable->update(['parent_receivable_id' => $firstReceivable->id]);
            }
        }
    }

    /**
     * Cria transaction para pagamento à vista
     */
    protected function createCashTransaction(Sale $sale, string $method, float $amount, ?int $accountId): void
    {
        // Buscar conta apropriada
        $drAccount = $this->getAccountForMethod($sale, $method, $accountId);
        
        // Buscar categoria de receita
        $revenueCategory = $this->getRevenueCategory($sale->company_id);

        // Buscar conta de receita (simplificado)
        $revenueAccount = Account::where('company_id', $sale->company_id)
            ->where('name', 'like', '%Receita%')
            ->first();

        if (!$revenueAccount) {
            $revenueAccount = Account::create([
                'company_id' => $sale->company_id,
                'name' => 'Receita de Vendas',
                'type' => 'bank',
                'is_active' => true,
            ]);
        }

        Transaction::create([
            'company_id' => $sale->company_id,
            'store_id' => $sale->store_id,
            'txn_date' => now(),
            'description' => "Venda #{$sale->sale_number} - {$method}",
            'amount' => $amount,
            'dr_account_id' => $drAccount->id,
            'cr_account_id' => $revenueAccount->id,
            'category_id' => $revenueCategory->id,
            'link_type' => 'sale',
            'link_id' => $sale->id,
        ]);
    }

    /**
     * Lança COGS (Custo das Mercadorias Vendidas)
     */
    protected function recordCogs(Sale $sale, float $totalCost): void
    {
        if ($totalCost <= 0) {
            return;
        }

        // Buscar categoria CMV
        $cmvCategory = FinanceCategory::where('company_id', $sale->company_id)
            ->where('name', 'CMV')
            ->where('nature', 'expense')
            ->first();

        if (!$cmvCategory) {
            $cmvCategory = FinanceCategory::create([
                'company_id' => $sale->company_id,
                'name' => 'CMV',
                'nature' => 'expense',
                'is_system' => true,
            ]);
        }

        // Buscar conta de estoque (simplificado)
        $stockAccount = Account::where('company_id', $sale->company_id)
            ->where('name', 'like', '%Estoque%')
            ->first();

        if (!$stockAccount) {
            // Criar conta de estoque se não existir (usar 'bank' como tipo padrão)
            $stockAccount = Account::create([
                'company_id' => $sale->company_id,
                'name' => 'Estoque',
                'type' => 'bank', // Usar 'bank' em vez de 'asset' que não é válido
                'is_active' => true,
                'opening_balance' => 0,
            ]);
        }

        // Buscar conta de CMV (simplificado)
        $cmvAccount = Account::where('company_id', $sale->company_id)
            ->where('name', 'like', '%CMV%')
            ->first();

        if (!$cmvAccount) {
            $cmvAccount = Account::create([
                'company_id' => $sale->company_id,
                'name' => 'CMV - Custo Mercadorias Vendidas',
                'type' => 'bank',
                'is_active' => true,
            ]);
        }

        Transaction::create([
            'company_id' => $sale->company_id,
            'store_id' => $sale->store_id,
            'txn_date' => now(),
            'description' => "COGS - Venda #{$sale->sale_number}",
            'amount' => $totalCost,
            'dr_account_id' => $cmvAccount->id,
            'cr_account_id' => $stockAccount->id,
            'category_id' => $cmvCategory->id,
            'link_type' => 'sale',
            'link_id' => $sale->id,
        ]);
    }

    /**
     * Obtém conta apropriada para método de pagamento
     */
    protected function getAccountForMethod(Sale $sale, string $method, ?int $accountId): Account
    {
        if ($accountId) {
            return Account::findOrFail($accountId);
        }

        // Mapear métodos de pagamento para nomes de contas
        $accountConfig = match($method) {
            'money' => ['name' => 'Caixa', 'type' => 'cash', 'searchTerms' => ['Caixa', 'Dinheiro', 'Cash']],
            'pix' => ['name' => 'PIX', 'type' => 'bank', 'searchTerms' => ['PIX', 'Pix']],
            'card_credit' => ['name' => 'Cartão de Crédito', 'type' => 'bank', 'searchTerms' => ['Cartão Crédito', 'Crédito', 'Credit']],
            'card_debit' => ['name' => 'Cartão de Débito', 'type' => 'bank', 'searchTerms' => ['Cartão Débito', 'Débito', 'Debit']],
            'boleto' => ['name' => 'Boleto', 'type' => 'bank', 'searchTerms' => ['Boleto']],
            'boleto_installment' => ['name' => 'Boleto Parcelado', 'type' => 'bank', 'searchTerms' => ['Boleto Parcelado', 'Boleto']],
            'sinal' => ['name' => 'Sinal', 'type' => 'cash', 'searchTerms' => ['Sinal', 'Caixa', 'Dinheiro']],
            'other' => ['name' => 'Outros', 'type' => 'bank', 'searchTerms' => ['Outros', 'Other']],
            default => ['name' => 'Caixa', 'type' => 'cash', 'searchTerms' => ['Caixa', 'Dinheiro']],
        };

        // Primeiro, tentar buscar por nome exato
        $account = Account::where('company_id', $sale->company_id)
            ->where(function($q) use ($accountConfig) {
                foreach ($accountConfig['searchTerms'] as $term) {
                    $q->orWhere('name', 'like', '%' . $term . '%');
                }
            })
            ->where('is_active', true)
            ->first();

        // Se não encontrou, tentar buscar qualquer conta do tipo correto
        if (!$account) {
            $account = Account::where('company_id', $sale->company_id)
                ->where('type', $accountConfig['type'])
                ->where('is_active', true)
                ->first();
        }

        // Se ainda não encontrou, tentar qualquer conta ativa da empresa
        if (!$account) {
            $account = Account::where('company_id', $sale->company_id)
                ->where('is_active', true)
                ->first();
        }

        // Se ainda não encontrou, criar automaticamente
        if (!$account) {
            $account = Account::create([
                'company_id' => $sale->company_id,
                'store_id' => $sale->store_id ?? null,
                'name' => $accountConfig['name'],
                'type' => $accountConfig['type'],
                'is_active' => true,
                'opening_balance' => 0,
            ]);
        }

        return $account;
    }

    /**
     * Obtém categoria de receita
     */
    protected function getRevenueCategory(int $companyId): FinanceCategory
    {
        $category = FinanceCategory::where('company_id', $companyId)
            ->where('name', 'Receita Vendas')
            ->where('nature', 'revenue')
            ->first();

        if (!$category) {
            $category = FinanceCategory::create([
                'company_id' => $companyId,
                'name' => 'Receita Vendas',
                'nature' => 'revenue',
                'is_system' => true,
            ]);
        }

        return $category;
    }
}

