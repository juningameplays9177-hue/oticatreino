<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\CashMovement;
use App\Models\Finance\CashSession;
use App\Models\Finance\Receivable;
use App\Models\Finance\ReceivablePayment;
use App\Models\Finance\Transaction;
use Illuminate\Support\Facades\DB;

class ReceivablesService
{
    /**
     * Recebe um valor de conta a receber
     */
    public function receive(
        Receivable $receivable,
        float $amount,
        int $accountId,
        string $method,
        \DateTime $paidAt,
        float $fee = 0,
        ?string $note = null
    ): ReceivablePayment {
        return DB::transaction(function () use ($receivable, $amount, $accountId, $method, $paidAt, $fee, $note) {
            if ($receivable->status === 'canceled') {
                throw new \Exception('Esta conta a receber foi cancelada e não pode ser recebida.');
            }

            // Permitir pagamentos mesmo se já estiver paga (para antecipação de outras parcelas ou ajustes)
            // Mas verificar se o valor não excede o valor original
            $totalPaid = $receivable->getPaidAmount();
            $maxAllowed = $receivable->original_amount;
            
            if (($totalPaid + $amount) > $maxAllowed) {
                throw new \Exception('Valor excede o valor original da conta. Valor original: R$ ' . number_format($maxAllowed, 2, ',', '.'));
            }
            
            // Se já está paga, permitir pagamento adicional apenas se for menor que o valor original
            if ($receivable->status === 'paid' && $amount > 0) {
                // Reabrir a conta se houver pagamento adicional
                $receivable->status = 'partial';
                $receivable->balance_amount = $maxAllowed - ($totalPaid + $amount);
            } elseif ($amount > $receivable->balance_amount) {
                throw new \Exception('Valor excede o saldo pendente. Saldo: R$ ' . number_format($receivable->balance_amount, 2, ',', '.'));
            }

            $account = Account::findOrFail($accountId);

            // Criar pagamento
            $payment = ReceivablePayment::create([
                'receivable_id' => $receivable->id,
                'account_id' => $accountId,
                'paid_at' => $paidAt,
                'amount' => $amount,
                'gateway_fee_amount' => $fee,
                'method' => $method,
                'note' => $note,
            ]);

            // Atualizar saldo
            if ($receivable->status === 'paid') {
                // Se estava paga, recalcular o saldo
                $newTotalPaid = $totalPaid + $amount;
                $receivable->balance_amount = max(0, $maxAllowed - $newTotalPaid);
            } else {
                $receivable->balance_amount -= $amount;
            }
            
            // Atualizar status baseado no novo saldo
            if ($receivable->balance_amount <= 0.01) {
                $receivable->status = 'paid';
                $receivable->balance_amount = 0;
            } else {
                $receivable->status = 'partial';
            }
            
            $receivable->save();

            // Criar transaction
            $this->createReceiptTransaction($receivable, $account, $amount);

            // Se houver sessão de caixa aberta e método compatível, criar cash_movement
            $this->createCashMovementIfApplicable($receivable, $account, $amount, $method);

            return $payment;
        });
    }

    /**
     * Cria transaction de recebimento
     */
    protected function createReceiptTransaction(Receivable $receivable, Account $account, float $amount): void
    {
        // Buscar conta de recebíveis (simplificado)
        $receivablesAccount = Account::where('company_id', $receivable->company_id)
            ->where('name', 'like', '%Recebíveis%')
            ->first();

        if (!$receivablesAccount) {
            // Criar se não existir (usar 'bank' como tipo padrão, já que 'asset' não é válido)
            $receivablesAccount = Account::create([
                'company_id' => $receivable->company_id,
                'name' => 'Contas a Receber',
                'type' => 'bank',
                'is_active' => true,
            ]);
        }

        Transaction::create([
            'company_id' => $receivable->company_id,
            'store_id' => $receivable->store_id,
            'txn_date' => now(),
            'description' => "Recebimento - Conta a Receber #{$receivable->id}",
            'amount' => $amount,
            'dr_account_id' => $account->id,
            'cr_account_id' => $receivablesAccount->id,
            'link_type' => 'receivable',
            'link_id' => $receivable->id,
        ]);
    }

    /**
     * Cria cash_movement se aplicável
     */
    protected function createCashMovementIfApplicable(
        Receivable $receivable,
        Account $account,
        float $amount,
        string $method
    ): void {
        // Verificar se há sessão aberta para o usuário atual na loja
        $userId = auth()->id();
        if (!$userId) {
            return;
        }
        
        $cashboxService = app(\App\Services\Finance\CashboxService::class);
        $session = $cashboxService->getOpenSession($receivable->store_id, $userId);

        // Verificar se a conta da sessão corresponde à conta do recebimento
        if ($session && $session->account_id === $account->id && in_array($method, ['money', 'pix'])) {
            $cashboxService->recordMovement(
                $session,
                'in',
                $method,
                $amount,
                null,
                'receivable',
                $receivable->id,
                "Recebimento conta a receber #{$receivable->id}"
            );
        }
    }

    /**
     * Cancela uma conta a receber
     */
    public function cancel(Receivable $receivable, ?string $reason = null): void
    {
        DB::transaction(function () use ($receivable, $reason) {
            if ($receivable->status === 'paid') {
                throw new \Exception('Não é possível cancelar uma conta já paga.');
            }

            $receivable->update([
                'status' => 'canceled',
                'balance_amount' => 0,
            ]);

            // Criar transaction reversa se necessário
            // (implementar conforme regra de negócio)
        });
    }
}

