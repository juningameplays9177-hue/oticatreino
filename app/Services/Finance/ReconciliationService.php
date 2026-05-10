<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\BankReconciliation;
use App\Models\Finance\BankReconciliationItem;
use App\Models\Finance\Transaction;
use Illuminate\Support\Facades\DB;

class ReconciliationService
{
    /**
     * Importa extrato bancário (CSV/OFX simplificado)
     */
    public function importStatement(int $accountId, array $statements): BankReconciliation
    {
        return DB::transaction(function () use ($accountId, $statements) {
            $account = Account::findOrFail($accountId);
            
            if ($account->type !== 'bank') {
                throw new \Exception('A conta deve ser do tipo banco.');
            }

            $startingBalance = $statements[0]['balance'] ?? 0;
            $endingBalance = end($statements)['balance'] ?? $startingBalance;
            $statementDate = $statements[0]['date'] ?? now()->toDateString();

            $reconciliation = BankReconciliation::create([
                'account_id' => $accountId,
                'statement_date' => $statementDate,
                'starting_balance' => $startingBalance,
                'ending_balance' => $endingBalance,
                'status' => 'open',
            ]);

            // Sugerir matches
            foreach ($statements as $statement) {
                $this->suggestMatch($reconciliation, $statement);
            }

            return $reconciliation;
        });
    }

    /**
     * Sugere match entre transação do extrato e transaction do sistema
     */
    protected function suggestMatch(BankReconciliation $reconciliation, array $statement): void
    {
        // Buscar transaction por valor e data (tolerância de 2 dias)
        $amount = $statement['amount'];
        $date = $statement['date'];

        $transaction = Transaction::where('dr_account_id', $reconciliation->account_id)
            ->orWhere('cr_account_id', $reconciliation->account_id)
            ->whereBetween('txn_date', [
                \Carbon\Carbon::parse($date)->subDays(2),
                \Carbon\Carbon::parse($date)->addDays(2),
            ])
            ->where(function ($q) use ($amount) {
                $q->whereRaw('ABS(amount - ?) < 0.01', [$amount]);
            })
            ->first();

        BankReconciliationItem::create([
            'reconciliation_id' => $reconciliation->id,
            'transaction_id' => $transaction?->id,
            'statement_amount' => $amount,
            'matched' => $transaction !== null,
        ]);
    }

    /**
     * Marca item como conciliado
     */
    public function matchItem(BankReconciliationItem $item, ?int $transactionId = null): void
    {
        DB::transaction(function () use ($item, $transactionId) {
            if ($transactionId) {
                $item->update([
                    'transaction_id' => $transactionId,
                    'matched' => true,
                ]);
            } else {
                $item->update(['matched' => true]);
            }
        });
    }

    /**
     * Fecha uma conciliação
     */
    public function close(BankReconciliation $reconciliation): void
    {
        $reconciliation->update(['status' => 'closed']);
    }
}

