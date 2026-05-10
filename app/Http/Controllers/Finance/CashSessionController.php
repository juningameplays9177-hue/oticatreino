<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\CashSession;
use App\Services\Finance\CashboxService;
use App\Traits\HasStoreFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class CashSessionController extends Controller
{
    use HasStoreFilter;
    protected ?CashboxService $cashboxService = null;

    public function __construct()
    {
        // Injeção lazy para evitar erro se tabelas não existirem
        try {
            $this->cashboxService = app(CashboxService::class);
        } catch (\Exception $e) {
            // Ignorar erro se service não puder ser instanciado
        }
    }
    
    protected function getCashboxService(): CashboxService
    {
        if (!$this->cashboxService) {
            try {
                $this->cashboxService = app(CashboxService::class);
            } catch (\Exception $e) {
                throw new \Exception('Serviço de caixa não disponível. Verifique se as tabelas do módulo financeiro foram criadas. Erro: ' . $e->getMessage());
            }
        }
        return $this->cashboxService;
    }

    /**
     * Lista sessões de caixa
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return redirect()->route('login');
            }
            
            $companyId = \App\Helpers\CompanyHelper::getCompanyId($user);
            
            // Verificar se a tabela existe
            if (!Schema::hasTable('cash_sessions')) {
                throw new \Exception('Tabela cash_sessions não existe');
            }
            
            // Verificar se as tabelas relacionadas existem antes de fazer eager loading
            $with = [];
            if (Schema::hasTable('stores')) $with[] = 'store';
            if (Schema::hasTable('accounts')) $with[] = 'account';
            if (Schema::hasTable('users')) {
                $with[] = 'openedBy';
                $with[] = 'closedBy';
            }
            
            // Aplicar filtro de loja se for gerente
            $storeId = $this->getUserStoreId();
            if ($storeId === null && $request->store_id) {
                $storeId = $request->store_id; // Admin pode escolher
            }
            
            $sessions = CashSession::when(!empty($with), fn($q) => $q->with($with))
                ->where('company_id', $companyId)
                ->when($storeId, fn($q) => $q->where('store_id', $storeId))
                ->when($request->status, fn($q) => $q->where('status', $request->status))
                ->orderBy('opened_at', 'desc')
                ->paginate(20);

            // Buscar stores e accounts para os formulários
            $stores = [];
            $accounts = [];
            
            if (Schema::hasTable('stores')) {
                try {
                    $stores = \App\Models\Store::where('active', true)->get();
                } catch (\Exception $e) {
                    Log::warning('Erro ao buscar stores: ' . $e->getMessage());
                }
            }
            
            if (Schema::hasTable('accounts')) {
                try {
                    $accounts = \App\Models\Finance\Account::where('is_active', true)
                        ->where('company_id', $companyId)
                        ->get();
                } catch (\Exception $e) {
                    Log::warning('Erro ao buscar accounts: ' . $e->getMessage());
                }
            }

            return view('finance.cash-sessions.index', compact('sessions', 'stores', 'accounts'));
        } catch (\Throwable $e) {
            // Capturar qualquer erro, incluindo erros fatais
            \Log::error('Erro ao carregar Cash Sessions: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // Se a tabela não existir, mostrar mensagem amigável
            return view('finance.cash-sessions.index', [
                'sessions' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
                'stores' => collect([]),
                'accounts' => collect([]),
                'error' => 'Tabelas do módulo financeiro não foram criadas. Execute o SQL em database/finance_module.sql no phpMyAdmin. Erro: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Abre uma sessão de caixa
     */
    public function open(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'account_id' => 'required|exists:accounts,id',
            'opening_amount' => 'required|numeric|min:0',
        ]);

        try {
            $user = auth()->user();
            $companyId = \App\Helpers\CompanyHelper::getCompanyId($user);
            
            $session = $this->getCashboxService()->openSession(
                $companyId,
                $validated['store_id'],
                $validated['account_id'],
                $validated['opening_amount'],
                $user
            );

            return response()->json([
                'success' => true,
                'session' => $session->load(['store', 'account', 'openedBy']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Fecha uma sessão de caixa
     */
    public function close(Request $request, CashSession $session)
    {
        $validated = $request->validate([
            'counted_by_method' => 'required|array',
            'counted_by_method.*.method' => 'required|in:money,pix,card,boleto,other',
            'counted_by_method.*.amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $session = $this->getCashboxService()->closeSession(
                $session,
                $validated['counted_by_method'],
                $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'session' => $session->load(['store', 'account', 'openedBy', 'closedBy']),
                'difference' => $session->getDifference(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Registra movimento de caixa
     */
    public function movement(Request $request, CashSession $session)
    {
        $validated = $request->validate([
            'type' => 'required|in:in,out',
            'method' => 'required|in:money,pix,card,boleto,other',
            'amount' => 'required|numeric|min:0.01',
            'category_id' => 'nullable|exists:finance_categories,id',
            'note' => 'nullable|string',
        ]);

        try {
            $movement = $this->getCashboxService()->recordMovement(
                $session,
                $validated['type'],
                $validated['method'],
                $validated['amount'],
                $validated['category_id'] ?? null,
                null,
                null,
                $validated['note'] ?? null
            );

            return response()->json([
                'success' => true,
                'movement' => $movement,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Imprime espelho de caixa
     */
    public function print(CashSession $session)
    {
        $session->load(['store', 'account', 'openedBy', 'closedBy', 'movements']);
        
        return view('finance.cash-sessions.print', compact('session'));
    }
}

