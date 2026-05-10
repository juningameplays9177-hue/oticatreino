<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\Payable;
use App\Models\Finance\PayablePayment;
use App\Models\Finance\Transaction;
use Illuminate\Support\Facades\DB;

class PayablesService
{
    /**
     * Paga uma conta a pagar
     */
    public function pay(
        Payable $payable,
        float $amount,
        int $accountId,
        string $method,
        \DateTime $paidAt,
        ?string $note = null
    ): PayablePayment {
        return DB::transaction(function () use ($payable, $amount, $accountId, $method, $paidAt, $note) {
            if ($payable->status === 'paid' || $payable->status === 'canceled') {
                throw new \Exception('Esta conta a pagar não pode ser paga.');
            }

            if ($amount > $payable->balance_amount) {
                throw new \Exception('Valor excede o saldo pendente.');
            }

            $account = Account::findOrFail($accountId);

            // Criar pagamento
            $payment = PayablePayment::create([
                'payable_id' => $payable->id,
                'account_id' => $accountId,
                'paid_at' => $paidAt,
                'amount' => $amount,
                'method' => $method,
                'note' => $note,
            ]);

            // Atualizar saldo
            $payable->balance_amount -= $amount;
            
            if ($payable->balance_amount <= 0.01) {
                $payable->status = 'paid';
                $payable->balance_amount = 0;
            } else {
                $payable->status = 'partial';
            }
            
            $payable->save();

            // Criar transaction
            $this->createPaymentTransaction($payable, $account, $amount);

            return $payment;
        });
    }

    /**
     * Cria transaction de pagamento
     */
    protected function createPaymentTransaction(Payable $payable, Account $account, float $amount): void
    {
        // Buscar categoria da despesa
        $category = $payable->category;
        
        if (!$category) {
            throw new \Exception('Conta a pagar deve ter uma categoria associada.');
        }

        // Buscar conta de pagáveis (simplificado)
        $payablesAccount = Account::where('company_id', $payable->company_id)
            ->where('name', 'like', '%Pagáveis%')
            ->first();

        if (!$payablesAccount) {
            $payablesAccount = Account::create([
                'company_id' => $payable->company_id,
                'name' => 'Contas a Pagar',
                'type' => 'liability',
                'is_active' => true,
            ]);
        }

        Transaction::create([
            'company_id' => $payable->company_id,
            'store_id' => $payable->store_id,
            'txn_date' => now(),
            'description' => "Pagamento - Conta a Pagar #{$payable->id}",
            'amount' => $amount,
            'dr_account_id' => $category->id, // Simplificado - idealmente seria conta de despesa
            'cr_account_id' => $account->id,
            'category_id' => $category->id,
            'cost_center_id' => $payable->cost_center_id,
            'link_type' => 'payable',
            'link_id' => $payable->id,
        ]);
    }

    /**
     * Cancela uma conta a pagar
     */
    public function cancel(Payable $payable, ?string $reason = null): void
    {
        DB::transaction(function () use ($payable, $reason) {
            if ($payable->status === 'paid') {
                throw new \Exception('Não é possível cancelar uma conta já paga.');
            }

            $payable->update([
                'status' => 'canceled',
                'balance_amount' => 0,
            ]);
        });
    }
}

