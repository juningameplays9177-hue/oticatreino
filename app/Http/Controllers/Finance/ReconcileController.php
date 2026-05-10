<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\BankReconciliation;
use App\Services\Finance\ReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ReconcileController extends Controller
{
    protected ?ReconciliationService $reconciliationService = null;

    public function __construct()
    {
        try {
            $this->reconciliationService = app(ReconciliationService::class);
        } catch (\Exception $e) {
            // Ignorar erro se service não puder ser instanciado
        }
    }
    
    protected function getReconciliationService(): ReconciliationService
    {
        if (!$this->reconciliationService) {
            $this->reconciliationService = app(ReconciliationService::class);
        }
        return $this->reconciliationService;
    }

    public function index(Request $request)
    {
        try {
            // Verificar se a tabela existe
            if (!Schema::hasTable('bank_reconciliations')) {
                throw new \Exception('Tabela bank_reconciliations não existe');
            }
            
            // Verificar se a tabela relacionada existe antes de fazer eager loading
            $with = [];
            if (Schema::hasTable('accounts')) $with[] = 'account';
            
            $reconciliations = BankReconciliation::when(!empty($with), fn($q) => $q->with($with))
                ->when($request->account_id, fn($q) => $q->where('account_id', $request->account_id))
                ->orderBy('statement_date', 'desc')
                ->paginate(20);

            return view('finance.reconcile.index', compact('reconciliations'));
        } catch (\Throwable $e) {
            \Log::error('Erro ao carregar Reconciliations: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('finance.reconcile.index', [
                'reconciliations' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
                'error' => 'Tabelas do módulo financeiro não foram criadas. Execute o SQL em database/finance_module.sql no phpMyAdmin. Erro: ' . $e->getMessage()
            ]);
        }
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'statements' => 'required|array',
            'statements.*.date' => 'required|date',
            'statements.*.amount' => 'required|numeric',
            'statements.*.balance' => 'nullable|numeric',
        ]);

        try {
            $reconciliation = $this->getReconciliationService()->importStatement(
                $validated['account_id'],
                $validated['statements']
            );

            return response()->json([
                'success' => true,
                'reconciliation' => $reconciliation->load('items.transaction'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function match(Request $request, BankReconciliation $recon)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:bank_reconciliation_items,id',
            'transaction_id' => 'nullable|exists:transactions,id',
        ]);

        $item = $recon->items()->findOrFail($validated['item_id']);
        
        $this->getReconciliationService()->matchItem(
            $item,
            $validated['transaction_id'] ?? null
        );

        return response()->json(['success' => true]);
    }
}

