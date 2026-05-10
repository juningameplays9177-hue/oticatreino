<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Receivable;
use App\Services\Finance\ReceivablesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ReceivableController extends Controller
{
    protected ?ReceivablesService $receivablesService = null;

    public function __construct()
    {
        try {
            $this->receivablesService = app(ReceivablesService::class);
        } catch (\Exception $e) {
            // Ignorar erro se service não puder ser instanciado
        }
    }
    
    protected function getReceivablesService(): ReceivablesService
    {
        if (!$this->receivablesService) {
            $this->receivablesService = app(ReceivablesService::class);
        }
        return $this->receivablesService;
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
            $clients = [];
            $categories = [];
            $costCenters = [];
            
            if (Schema::hasTable('stores')) {
                // Se for gerente, mostrar apenas sua loja
                if ($user->isGerente() && $user->store_id) {
                    $stores = \App\Models\Store::where('id', $user->store_id)->where('active', true)->get();
                } else {
                    $stores = \App\Models\Store::where('active', true)->get();
                }
            }
            
            if (Schema::hasTable('clients')) {
                $clients = \App\Models\Client::where('active', true)->orderBy('name')->get();
            }
            
            if (Schema::hasTable('finance_categories')) {
                try {
                    // Buscar APENAS categorias de receita
                    $categories = \App\Models\Finance\FinanceCategory::where('company_id', $companyId)
                        ->where('nature', 'revenue')
                        ->where('is_active', true)
                        ->with('parent')
                        ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
                        ->orderBy('name')
                        ->get();
                    
                    // Se não houver categorias de receita, logar e manter vazio (não buscar despesas)
                    if ($categories->isEmpty()) {
                        \Log::warning("⚠️ Nenhuma categoria de RECEITA encontrada para empresa {$companyId}.");
                        \Log::warning("   Execute: php artisan db:seed --class=FinanceCategoriesSeeder");
                        $categories = collect([]);
                    } else {
                        \Log::info("✅ Encontradas {$categories->count()} categorias de receita para empresa {$companyId}");
                    }
                } catch (\Exception $e) {
                    \Log::error('Erro ao buscar categorias: ' . $e->getMessage());
                    $categories = collect([]);
                }
            } else {
                $categories = collect([]);
            }
            
            if (Schema::hasTable('cost_centers')) {
                try {
                    $costCenters = \App\Models\Finance\CostCenter::where('company_id', $companyId)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get();
                } catch (\Exception $e) {
                    \Log::warning('Erro ao buscar centros de custo: ' . $e->getMessage());
                    $costCenters = collect([]);
                }
            } else {
                $costCenters = collect([]);
            }
            
            return view('finance.receivables.create', compact('stores', 'clients', 'categories', 'costCenters'));
        } catch (\Throwable $e) {
            \Log::error('Erro ao carregar formulário de criação: ' . $e->getMessage());
            return redirect()->route('finance.receivables.index')
                ->with('error', 'Erro ao carregar formulário: ' . $e->getMessage());
        }
    }

    /**
     * Lista contas a receber
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = \App\Helpers\CompanyHelper::getCompanyId($user); // Fallback
        
        try {
            // Verificar se a tabela existe
            if (!Schema::hasTable('receivables')) {
                throw new \Exception('Tabela receivables não existe');
            }
            
            // Verificar se as tabelas relacionadas existem antes de fazer eager loading
            $with = [];
            if (Schema::hasTable('clients')) $with[] = 'customer';
            if (Schema::hasTable('stores')) $with[] = 'store';
            if (Schema::hasTable('sales')) $with[] = 'sale';
            if (Schema::hasTable('service_orders')) $with[] = 'serviceOrder';
            
            $baseQuery = Receivable::where('company_id', $companyId)
                ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
                ->when($request->from && $request->to, function ($q) use ($request) {
                    $q->whereBetween('due_date', [$request->from, $request->to]);
                });
            
            // Se for gerente, filtrar apenas pela loja dele
            if ($user->isGerente() && $user->store_id) {
                $baseQuery->where('store_id', $user->store_id);
            }
            
            // Calcular totais antes da paginação (sem filtro de status)
            $totalOpen = (clone $baseQuery)->where('status', 'open')->sum('balance_amount');
            $totalPartial = (clone $baseQuery)->where('status', 'partial')->sum('balance_amount');
            $totalPaid = (clone $baseQuery)->where('status', 'paid')->sum('original_amount');
            $totalOverdue = (clone $baseQuery)->where('due_date', '<', now())
                ->whereIn('status', ['open', 'partial'])
                ->sum('balance_amount');
            
            // Query com filtros e eager loading para paginação
            $query = $baseQuery
                ->when(!empty($with), fn($q) => $q->with($with))
                ->when($request->status, fn($q) => $q->where('status', $request->status));
            
            // Ordenar: primeiro por status (aberto/parcial primeiro, depois pagas), depois por vencimento
            $receivables = $query->orderByRaw("
                CASE 
                    WHEN status = 'paid' THEN 2
                    ELSE 1
                END ASC,
                due_date ASC
            ")->paginate(20);

            return view('finance.receivables.index', compact('receivables', 'totalOpen', 'totalPartial', 'totalPaid', 'totalOverdue'));
        } catch (\Throwable $e) {
            \Log::error('Erro ao carregar Receivables: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('finance.receivables.index', [
                'receivables' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
                'error' => 'Tabelas do módulo financeiro não foram criadas. Execute o SQL em database/finance_module.sql no phpMyAdmin. Erro: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Exibe detalhes de uma conta a receber
     */
    public function show(Receivable $receivable)
    {
        $receivable->load(['customer', 'store', 'sale', 'payments.account']);
        
        // Se for requisição AJAX, retornar JSON
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'receivable' => $receivable
            ]);
        }
        
        return view('finance.receivables.show', compact('receivable'));
    }

    /**
     * Cria uma nova conta a receber
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'customer_id' => 'nullable|exists:clients,id',
            'sale_id' => 'nullable|exists:sales,id',
            'document_no' => 'nullable|string|max:50',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'original_amount' => 'required|numeric|min:0.01',
            'interest_amount' => 'nullable|numeric|min:0',
            'fine_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'method' => 'nullable|in:pix,card,money,boleto,transfer,check,other',
            'billing_type' => 'nullable|in:crediario,carne,boleto,cartao_prazo,convenio,mensalidade,fiado,outro',
            'category_id' => 'required|exists:finance_categories,id',
            'cost_center_id' => 'nullable',
            'installments' => 'required|integer|min:1|max:360',
            'note' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);

        $user = auth()->user();
        $companyId = \App\Helpers\CompanyHelper::getCompanyId($user);
        
        try {
            // Processar anexo se houver
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $attachmentPath = $file->store('receivables/attachments', 'public');
            }

            $installments = $validated['installments'] ?? 1;
            $originalAmount = $validated['original_amount'];
            $amountPerInstallment = $originalAmount / $installments;
            
            // Se tiver parcelas, criar múltiplas contas
            if ($installments > 1) {
                $receivables = [];
                $issueDate = \Carbon\Carbon::parse($validated['issue_date']);
                $dueDate = \Carbon\Carbon::parse($validated['due_date']);
                $firstReceivable = null;
                
                for ($i = 1; $i <= $installments; $i++) {
                    $installmentDueDate = $dueDate->copy()->addMonths($i - 1);
                    
                    $receivable = Receivable::create([
                        'company_id' => $companyId,
                        'store_id' => $validated['store_id'],
                        'customer_id' => $validated['customer_id'] ?? null,
                        'sale_id' => $validated['sale_id'] ?? null,
                        'document_no' => $validated['document_no'] ? ($validated['document_no'] . '-' . str_pad($i, 3, '0', STR_PAD_LEFT)) : null,
                        'issue_date' => $validated['issue_date'],
                        'due_date' => $installmentDueDate->format('Y-m-d'),
                        'original_amount' => $amountPerInstallment,
                        'balance_amount' => $amountPerInstallment,
                        'interest_amount' => ($validated['interest_amount'] ?? 0) / $installments,
                        'fine_amount' => ($validated['fine_amount'] ?? 0) / $installments,
                        'discount_amount' => ($validated['discount_amount'] ?? 0) / $installments,
                        'status' => 'open',
                        'method' => $validated['method'] ?? null,
                        'billing_type' => $validated['billing_type'] ?? null,
                        'category_id' => $validated['category_id'],
                        'cost_center_id' => $validated['cost_center_id'] ?? null,
                        'installments' => $installments,
                        'installment_number' => $i,
                        'parent_receivable_id' => $i === 1 ? null : ($firstReceivable ? $firstReceivable->id : null),
                        'note' => $validated['note'] ?? null,
                        'attachment_path' => $i === 1 ? $attachmentPath : null, // Anexo apenas na primeira parcela
                    ]);
                    
                    if ($i === 1) {
                        $firstReceivable = $receivable;
                    } else {
                        // Atualizar parent_receivable_id das parcelas seguintes
                        $receivable->update(['parent_receivable_id' => $firstReceivable->id]);
                    }
                    
                    $receivables[] = $receivable;
                }
                
                $message = "Conta a receber criada com {$installments} parcela(s)!";
                return redirect()->route('finance.receivables.show', $firstReceivable)
                    ->with('success', $message);
            } else {
                // Conta única sem parcelas
                $receivable = Receivable::create([
                    'company_id' => $companyId,
                    'store_id' => $validated['store_id'],
                    'customer_id' => $validated['customer_id'] ?? null,
                    'sale_id' => $validated['sale_id'] ?? null,
                    'document_no' => $validated['document_no'] ?? null,
                    'issue_date' => $validated['issue_date'],
                    'due_date' => $validated['due_date'],
                    'original_amount' => $originalAmount,
                    'balance_amount' => $originalAmount,
                    'interest_amount' => $validated['interest_amount'] ?? 0,
                    'fine_amount' => $validated['fine_amount'] ?? 0,
                    'discount_amount' => $validated['discount_amount'] ?? 0,
                    'status' => 'open',
                    'method' => $validated['method'] ?? null,
                    'billing_type' => $validated['billing_type'] ?? null,
                    'category_id' => $validated['category_id'],
                    'cost_center_id' => $validated['cost_center_id'] ?? null,
                    'installments' => 1,
                    'installment_number' => 1,
                    'note' => $validated['note'] ?? null,
                    'attachment_path' => $attachmentPath,
                ]);

                return redirect()->route('finance.receivables.show', $receivable)
                    ->with('success', 'Conta a receber criada com sucesso!');
            }
        } catch (\Exception $e) {
            \Log::error('Erro ao criar conta a receber: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao criar conta a receber: ' . $e->getMessage());
        }
    }

    /**
     * Recebe uma conta a receber
     */
    public function receive(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'account_id' => 'nullable|exists:accounts,id',
                'method' => 'required|in:pix,card,money,boleto,other',
                'paid_at' => 'required|date',
                'gateway_fee_amount' => 'nullable|numeric|min:0',
                'note' => 'nullable|string',
            ]);

            $receivable = Receivable::findOrFail($id);
            
            // Se não informou conta, buscar automaticamente baseado no método
            $accountId = $validated['account_id'] ?? $this->getAccountForMethod($validated['method'], $receivable->company_id);
            
            if (!$accountId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Não foi possível encontrar uma conta para este método de pagamento. Por favor, cadastre uma conta primeiro.'
                ], 422);
            }
            
            $payment = $this->getReceivablesService()->receive(
                $receivable,
                $validated['amount'],
                $accountId,
                $validated['method'],
                new \DateTime($validated['paid_at']),
                $validated['gateway_fee_amount'] ?? 0,
                $validated['note'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Pagamento registrado com sucesso!',
                'payment' => $payment,
                'receivable' => $receivable->fresh(),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Erro ao receber pagamento: ' . $e->getMessage(), [
                'receivable_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 422);
        }
    }
    
    /**
     * Busca conta automaticamente baseada no método de pagamento
     */
    protected function getAccountForMethod(string $method, int $companyId): ?int
    {
        $accountConfig = match($method) {
            'money' => ['name' => 'Caixa', 'type' => 'cash', 'searchTerms' => ['Caixa', 'Dinheiro', 'Cash']],
            'pix' => ['name' => 'PIX', 'type' => 'bank', 'searchTerms' => ['PIX', 'Pix']],
            'card' => ['name' => 'Cartão', 'type' => 'bank', 'searchTerms' => ['Cartão', 'Card', 'Crédito', 'Débito']],
            'boleto' => ['name' => 'Boleto', 'type' => 'bank', 'searchTerms' => ['Boleto']],
            'other' => ['name' => 'Outros', 'type' => 'bank', 'searchTerms' => ['Outros', 'Other']],
            default => ['name' => 'Caixa', 'type' => 'cash', 'searchTerms' => ['Caixa', 'Dinheiro']],
        };

        // Primeiro, tentar buscar por nome exato
        $account = \App\Models\Finance\Account::where('company_id', $companyId)
            ->where(function($q) use ($accountConfig) {
                foreach ($accountConfig['searchTerms'] as $term) {
                    $q->orWhere('name', 'like', '%' . $term . '%');
                }
            })
            ->where('is_active', true)
            ->first();

        // Se não encontrou, tentar buscar qualquer conta do tipo correto
        if (!$account) {
            $account = \App\Models\Finance\Account::where('company_id', $companyId)
                ->where('type', $accountConfig['type'])
                ->where('is_active', true)
                ->first();
        }

        // Se ainda não encontrou, tentar qualquer conta ativa da empresa
        if (!$account) {
            $account = \App\Models\Finance\Account::where('company_id', $companyId)
                ->where('is_active', true)
                ->first();
        }

        return $account?->id;
    }

    /**
     * Cancela uma conta a receber
     */
    public function cancel(Request $request, $id)
    {
        $receivable = Receivable::findOrFail($id);
        
        try {
            $this->getReceivablesService()->cancel($receivable, $request->input('reason'));
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Gera PDF da conta a receber
     */
    public function pdf(Receivable $receivable)
    {
        try {
            $receivable->load(['customer', 'store', 'sale', 'payments.account', 'company']);
            
            // Usar helper app() para resolver o PDF
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('finance.receivables.pdf', compact('receivable'));
            
            return $pdf->download('conta-receber-' . $receivable->id . '.pdf');
        } catch (\Exception $e) {
            \Log::error('Erro ao gerar PDF: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao gerar PDF: ' . $e->getMessage());
        }
    }
}

