<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Payable;
use App\Services\Finance\PayablesService;
use App\Services\Finance\PayableRecurringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PayableController extends Controller
{
    protected ?PayablesService $payablesService = null;

    public function __construct()
    {
        try {
            $this->payablesService = app(PayablesService::class);
        } catch (\Exception $e) {
            // Ignorar erro se service não puder ser instanciado
        }
    }
    
    protected function getPayablesService(): PayablesService
    {
        if (!$this->payablesService) {
            $this->payablesService = app(PayablesService::class);
        }
        return $this->payablesService;
    }

    /**
     * Lista contas a pagar
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = \App\Helpers\CompanyHelper::getCompanyId($user);
        
        try {
            // Verificar se a tabela existe
            if (!Schema::hasTable('payables')) {
                throw new \Exception('Tabela payables não existe');
            }
            
            // Verificar se as tabelas relacionadas existem antes de fazer eager loading
            $with = [];
            try {
                if (Schema::hasTable('suppliers') && Schema::hasColumn('payables', 'supplier_id')) {
                    $with[] = 'supplier';
                }
                if (Schema::hasTable('stores') && Schema::hasColumn('payables', 'store_id')) {
                    $with[] = 'store';
                }
                if (Schema::hasTable('finance_categories') && Schema::hasColumn('payables', 'category_id')) {
                    $with[] = 'category';
                }
            } catch (\Exception $e) {
                \Log::warning('Erro ao verificar relacionamentos: ' . $e->getMessage());
            }
            
            $groupBy = $request->get('group', 'auto'); // auto, grouped, all
            
            // Verificar se as colunas de recorrência existem
            $hasRecurringColumns = Schema::hasColumn('payables', 'recurring_group_id') && 
                                   Schema::hasColumn('payables', 'installment_number');
            
            // Query base
            $baseQuery = Payable::when(!empty($with), fn($q) => $q->with($with))
                ->where('company_id', $companyId)
                ->when($request->status, fn($q) => $q->where('status', $request->status))
                ->when($request->supplier_id, fn($q) => $q->where('supplier_id', $request->supplier_id))
                ->when($request->from && $request->to, function ($q) use ($request) {
                    $q->whereBetween('due_date', [$request->from, $request->to]);
                });
            
            // Se for gerente, mostrar apenas as contas que ele criou
            if ($user->isGerente()) {
                // Filtrar por created_by (campo que identifica quem criou)
                if (Schema::hasColumn('payables', 'created_by')) {
                    $baseQuery->where('created_by', $user->id);
                } else {
                    // Se a coluna não existir, não mostrar nenhuma conta (ou mostrar apenas as que não têm created_by null)
                    // Isso força a execução da migration antes de usar o sistema
                    $baseQuery->whereRaw('1 = 0'); // Não mostrar nenhuma conta até a migration ser executada
                }
            }
            
            // Calcular totais antes da paginação
            try {
                $totalOpen = (clone $baseQuery)->where('status', 'open')->sum('balance_amount') ?? 0;
                $totalPartial = (clone $baseQuery)->where('status', 'partial')->sum('balance_amount') ?? 0;
                $totalPaid = (clone $baseQuery)->where('status', 'paid')->sum('original_amount') ?? 0;
                $totalOverdue = (clone $baseQuery)->where('due_date', '<', now())
                    ->whereIn('status', ['open', 'partial'])
                    ->sum('balance_amount') ?? 0;
            } catch (\Exception $e) {
                \Log::warning('Erro ao calcular totais: ' . $e->getMessage());
                $totalOpen = $totalPartial = $totalPaid = $totalOverdue = 0;
            }
            
            // Se agrupado ou auto, mostrar apenas primeira parcela de cada grupo (se as colunas existirem)
            if (($groupBy === 'grouped' || $groupBy === 'auto') && $hasRecurringColumns) {
                $payables = $baseQuery
                    ->where(function($q) {
                        $q->whereNull('recurring_group_id')
                          ->orWhere(function($subQ) {
                              $subQ->whereNotNull('recurring_group_id')
                                   ->where('installment_number', 1);
                          });
                    })
                    ->orderBy('due_date', 'asc')
                    ->paginate(20);
            } else {
                // Lista completa
                $payables = $baseQuery
                    ->orderBy('due_date', 'asc');
                
                if ($hasRecurringColumns) {
                    $payables = $payables->orderBy('installment_number', 'asc');
                }
                
                $payables = $payables->paginate(20);
            }
            
            // Buscar parcelas relacionadas para os grupos
            $groupedPayables = [];
            if (($groupBy === 'grouped' || $groupBy === 'auto') && $hasRecurringColumns) {
                try {
                    foreach ($payables as $payable) {
                        if ($payable->recurring_group_id ?? null) {
                            $groupedPayables[$payable->recurring_group_id] = Payable::where('recurring_group_id', $payable->recurring_group_id)
                                ->orderBy('installment_number')
                                ->get();
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Erro ao buscar parcelas agrupadas: ' . $e->getMessage());
                }
            }

            return view('finance.payables.index', compact('payables', 'totalOpen', 'totalPartial', 'totalPaid', 'totalOverdue', 'groupedPayables', 'groupBy'));
        } catch (\Throwable $e) {
            \Log::error('Erro ao carregar Payables: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Tentar retornar uma view de erro mais detalhada
            try {
                return view('finance.payables.index', [
                    'payables' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
                    'totalOpen' => 0,
                    'totalPartial' => 0,
                    'totalPaid' => 0,
                    'totalOverdue' => 0,
                    'groupedPayables' => [],
                    'groupBy' => 'auto',
                    'error' => 'Erro ao carregar contas a pagar: ' . $e->getMessage() . ' (Arquivo: ' . basename($e->getFile()) . ':' . $e->getLine() . ')'
                ]);
            } catch (\Exception $viewError) {
                // Se a view também falhar, retornar erro simples
                return response()->view('errors.500', [
                    'message' => 'Erro ao carregar página: ' . $e->getMessage()
                ], 500);
            }
        }
    }

    /**
     * Exibe formulário de criação
     */
    public function create()
    {
        $user = auth()->user();
        $companyId = \App\Helpers\CompanyHelper::getCompanyId($user);
        
        try {
            $stores = [];
            $suppliers = [];
            $categories = [];
            
            $selectedStoreId = null;
            
            if (Schema::hasTable('stores')) {
                // Se for gerente, mostrar apenas sua loja
                if ($user->isGerente() && $user->store_id) {
                    $stores = \App\Models\Store::where('id', $user->store_id)->where('active', true)->get();
                    $selectedStoreId = $user->store_id;
                } elseif ($user->isAdmin()) {
                    // Admin: usar loja selecionada no dashboard
                    $selectedStoreId = request()->session()->get('dashboard_store_id');
                    
                    if ($selectedStoreId) {
                        $selectedStoreId = (int) $selectedStoreId;
                        $stores = \App\Models\Store::where('id', $selectedStoreId)->where('active', true)->get();
                        
                        // Se a loja não foi encontrada, limpar sessão e mostrar todas
                        if ($stores->isEmpty()) {
                            request()->session()->forget('dashboard_store_id');
                            request()->session()->save();
                            $stores = \App\Models\Store::where('active', true)->get();
                            $selectedStoreId = null;
                        }
                    } else {
                        // Se não houver loja selecionada, mostrar todas as lojas para o admin poder selecionar
                        $stores = \App\Models\Store::where('active', true)->get();
                    }
                } else {
                    $stores = \App\Models\Store::where('active', true)->get();
                }
            }
            
            if (Schema::hasTable('suppliers')) {
                $suppliers = \App\Models\Supplier::orderBy('trade_name')->get();
            }
            
            if (Schema::hasTable('finance_categories')) {
                // Buscar todas as categorias de despesas (pais e filhos) da empresa
                // As categorias são compartilhadas entre todas as lojas da empresa
                $categories = \App\Models\Finance\FinanceCategory::where('company_id', $companyId)
                    ->where('nature', 'expense')
                    ->where('is_active', true)
                    ->with('parent')
                    ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
                    ->orderBy('name')
                    ->get();
                
                // Log para debug (remover em produção se necessário)
                \Log::info('Categorias carregadas para contas a pagar', [
                    'company_id' => $companyId,
                    'total' => $categories->count(),
                    'pais' => $categories->whereNull('parent_id')->count(),
                    'filhos' => $categories->whereNotNull('parent_id')->count(),
                ]);
            }
            
            return view('finance.payables.create', compact('stores', 'suppliers', 'categories', 'selectedStoreId'));
        } catch (\Throwable $e) {
            \Log::error('Erro ao carregar formulário de criação: ' . $e->getMessage());
            return redirect()->route('finance.payables.index')
                ->with('error', 'Erro ao carregar formulário: ' . $e->getMessage());
        }
    }

    /**
     * Exibe detalhes de uma conta a pagar
     */
    public function show(Payable $payable)
    {
        $user = auth()->user();
        
        // Se for gerente, verificar se ele criou esta conta
        if ($user->isGerente()) {
            if (Schema::hasColumn('payables', 'created_by')) {
                if ($payable->created_by !== $user->id) {
                    abort(403, 'Você não tem permissão para visualizar esta conta.');
                }
            } else {
                // Fallback: verificar se é da loja do gerente
                if ($user->store_id && $payable->store_id !== $user->store_id) {
                    abort(403, 'Você não tem permissão para visualizar esta conta.');
                }
            }
        }
        
        $payable->load(['supplier', 'store', 'category', 'costCenter', 'payments.account']);
        
        return view('finance.payables.show', compact('payable'));
    }

    /**
     * Cria uma nova conta a pagar
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'document_no' => 'nullable|string|max:50',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'original_amount' => 'required|numeric|min:0.01',
            'category_id' => 'nullable|exists:finance_categories,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'note' => 'nullable|string',
            'is_recurring' => 'nullable|boolean',
            'recurring_type' => 'nullable|in:daily,weekly,biweekly,monthly,bimonthly,quarterly,semiannual,yearly',
            'recurring_end_date' => 'nullable|date|after:due_date',
            'installments' => 'nullable|integer|min:1|max:360',
        ]);

        $user = auth()->user();
        $companyId = \App\Helpers\CompanyHelper::getCompanyId($user);
        
        try {
            $installments = $validated['installments'] ?? 1;
            $isRecurring = $validated['is_recurring'] ?? false;
            
            // Se tem parcelamento ou recorrência, usar o service
            if ($installments > 1 || $isRecurring) {
                $recurringService = new PayableRecurringService();
                $payableData = [
                    'company_id' => $companyId,
                    'store_id' => $validated['store_id'],
                    'supplier_id' => $validated['supplier_id'] ?? null,
                    'document_no' => $validated['document_no'] ?? null,
                    'issue_date' => $validated['issue_date'],
                    'due_date' => $validated['due_date'],
                    'original_amount' => $validated['original_amount'],
                    'category_id' => $validated['category_id'] ?? null,
                    'cost_center_id' => $validated['cost_center_id'] ?? null,
                    'note' => $validated['note'] ?? null,
                    'is_recurring' => $isRecurring,
                    'recurring_type' => $validated['recurring_type'] ?? null,
                    'recurring_end_date' => $validated['recurring_end_date'] ?? null,
                    'installments' => $installments,
                ];
                
                // Adicionar created_by se a coluna existir
                if (Schema::hasColumn('payables', 'created_by')) {
                    $payableData['created_by'] = $user->id;
                }
                
                $payable = $recurringService->createPayable($payableData);
                
                $message = $isRecurring 
                    ? "Conta recorrente criada com {$installments} parcela(s)!" 
                    : "Conta a pagar criada com {$installments} parcela(s)!";
            } else {
                // Conta simples sem parcelamento
                $payableData = [
                    'company_id' => $companyId,
                    'store_id' => $validated['store_id'],
                    'supplier_id' => $validated['supplier_id'] ?? null,
                    'document_no' => $validated['document_no'] ?? null,
                    'issue_date' => $validated['issue_date'],
                    'due_date' => $validated['due_date'],
                    'original_amount' => $validated['original_amount'],
                    'balance_amount' => $validated['original_amount'],
                    'status' => 'open',
                    'category_id' => $validated['category_id'] ?? null,
                    'cost_center_id' => $validated['cost_center_id'] ?? null,
                    'note' => $validated['note'] ?? null,
                    'installments' => 1,
                    'installment_number' => 1,
                ];
                
                // Adicionar created_by se a coluna existir
                if (Schema::hasColumn('payables', 'created_by')) {
                    $payableData['created_by'] = $user->id;
                }
                
                $payable = Payable::create($payableData);
                
                $message = 'Conta a pagar criada com sucesso!';
            }

            return redirect()->route('finance.payables.show', $payable)
                ->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Erro ao criar conta a pagar: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao criar conta a pagar: ' . $e->getMessage());
        }
    }

    /**
     * Paga uma conta a pagar
     */
    public function pay(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'account_id' => 'required|exists:accounts,id',
            'method' => 'required|in:pix,ted,boleto,cash,card',
            'paid_at' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $user = auth()->user();
        $payable = Payable::findOrFail($id);
        
        // Se for gerente, verificar se ele criou esta conta
        if ($user->isGerente()) {
            if (Schema::hasColumn('payables', 'created_by')) {
                if ($payable->created_by !== $user->id) {
                    return response()->json([
                        'error' => 'Você não tem permissão para pagar esta conta.'
                    ], 403);
                }
            } else {
                // Fallback: verificar se é da loja do gerente
                if ($user->store_id && $payable->store_id !== $user->store_id) {
                    return response()->json([
                        'error' => 'Você não tem permissão para pagar esta conta.'
                    ], 403);
                }
            }
        }
        
        // Validar se o valor não excede o saldo
        if ($validated['amount'] > $payable->balance_amount) {
            return response()->json([
                'error' => 'Valor do pagamento não pode ser maior que o saldo devedor (R$ ' . number_format($payable->balance_amount, 2, ',', '.') . ')'
            ], 422);
        }
        
        try {
            $payment = $this->getPayablesService()->pay(
                $payable,
                $validated['amount'],
                $validated['account_id'],
                $validated['method'],
                new \DateTime($validated['paid_at']),
                $validated['note'] ?? null
            );

            // Se for conta recorrente e foi paga completamente, gerar próxima parcela
            if ($payable->is_recurring && $payable->fresh()->status === 'paid') {
                try {
                    $recurringService = new PayableRecurringService();
                    $recurringService->generateNextRecurring($payable->fresh());
                } catch (\Exception $e) {
                    \Log::warning('Erro ao gerar próxima parcela recorrente: ' . $e->getMessage());
                    // Não falhar o pagamento se houver erro na recorrência
                }
            }

            return response()->json([
                'success' => true,
                'payment' => $payment,
                'payable' => $payable->fresh(),
                'message' => 'Pagamento registrado com sucesso!'
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao pagar conta: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Cancela uma conta a pagar
     */
    public function cancel(Request $request, $id)
    {
        $user = auth()->user();
        $payable = Payable::findOrFail($id);
        
        // Se for gerente, verificar se ele criou esta conta
        if ($user->isGerente()) {
            if (Schema::hasColumn('payables', 'created_by')) {
                if ($payable->created_by !== $user->id) {
                    return response()->json([
                        'error' => 'Você não tem permissão para cancelar esta conta.'
                    ], 403);
                }
            } else {
                // Fallback: verificar se é da loja do gerente
                if ($user->store_id && $payable->store_id !== $user->store_id) {
                    return response()->json([
                        'error' => 'Você não tem permissão para cancelar esta conta.'
                    ], 403);
                }
            }
        }
        
        try {
            $this->getPayablesService()->cancel($payable, $request->input('reason'));
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}

