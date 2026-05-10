<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use App\Models\Company;
use App\Models\Store;
use App\Models\Client;
use App\Models\Prescription;
use App\Services\OsService;
use App\Http\Requests\StoreServiceOrderRequest;
use App\Http\Requests\UpdateServiceOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OsController extends Controller
{
    protected $osService;

    public function __construct()
    {
        // Injeção lazy para evitar erro se service não puder ser instanciado
        try {
            $this->osService = app(OsService::class);
        } catch (\Exception $e) {
            // Ignorar erro se service não puder ser instanciado
        }
    }
    
    protected function getOsService(): OsService
    {
        if (!$this->osService) {
            $this->osService = app(OsService::class);
        }
        return $this->osService;
    }

    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $query = ServiceOrder::with(['client', 'store', 'employee', 'items']);

            // Se for gerente, filtrar apenas pela loja dele
            if ($user && $user->isGerente() && $user->store_id) {
                $query->where('store_id', $user->store_id);
            }

            // Busca
            if ($request->filled('q')) {
                $query->search($request->q);
            }

            // Filtros
            $filters = $request->only([
                'company_id', 'store_id', 'employee_id', 'status',
                'advance_type', 'source', 'period_type', 'from', 'to',
                'min_value', 'max_value'
            ]);

            // Período
            if ($request->filled('period')) {
                $period = $request->period;
                $now = Carbon::now();
                switch ($period) {
                    case 'hoje':
                        $filters['from'] = $now->startOfDay()->format('Y-m-d');
                        $filters['to'] = $now->endOfDay()->format('Y-m-d');
                        break;
                    case 'ontem':
                        $filters['from'] = $now->copy()->subDay()->startOfDay()->format('Y-m-d');
                        $filters['to'] = $now->copy()->subDay()->endOfDay()->format('Y-m-d');
                        break;
                    case 'amanha':
                        $filters['from'] = $now->copy()->addDay()->startOfDay()->format('Y-m-d');
                        $filters['to'] = $now->copy()->addDay()->endOfDay()->format('Y-m-d');
                        break;
                    case 'ultimos_7':
                        $filters['from'] = $now->copy()->subDays(7)->format('Y-m-d');
                        $filters['to'] = $now->format('Y-m-d');
                        break;
                    case 'ultimos_30':
                        $filters['from'] = $now->copy()->subDays(30)->format('Y-m-d');
                        $filters['to'] = $now->format('Y-m-d');
                        break;
                    case 'este_mes':
                        $filters['from'] = $now->startOfMonth()->format('Y-m-d');
                        $filters['to'] = $now->endOfMonth()->format('Y-m-d');
                        break;
                    case 'proximo_mes':
                        $filters['from'] = $now->copy()->addMonth()->startOfMonth()->format('Y-m-d');
                        $filters['to'] = $now->copy()->addMonth()->endOfMonth()->format('Y-m-d');
                        break;
                    case 'mes_anterior':
                        $filters['from'] = $now->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                        $filters['to'] = $now->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                        break;
                    case 'este_ano':
                        $filters['from'] = $now->startOfYear()->format('Y-m-d');
                        $filters['to'] = $now->endOfYear()->format('Y-m-d');
                        break;
                }
            }

            $query->filters($filters);

        // Ordenação
        $sort = $request->get('sort', 'registered_desc');
        switch ($sort) {
            case 'registered_asc':
                $query->orderBy('registered_at', 'asc');
                break;
            case 'registered_desc':
                $query->orderBy('registered_at', 'desc');
                break;
            case 'value_asc':
                $query->orderBy('total_value', 'asc');
                break;
            case 'value_desc':
                $query->orderBy('total_value', 'desc');
                break;
            case 'delivery_asc':
                $query->orderBy('delivery_date', 'asc');
                break;
            case 'delivery_desc':
                $query->orderBy('delivery_date', 'desc');
                break;
            default:
                $query->orderBy('registered_at', 'desc');
        }

            $serviceOrders = $query->paginate(50)->withQueryString();

            // Dados para filtros
            $companies = Company::where('is_active', true)->orderBy('trade_name')->get();
            
            // Se for gerente, mostrar apenas sua loja
            if ($user && $user->isGerente() && $user->store_id) {
                $stores = Store::where('id', $user->store_id)->where('active', true)->orderBy('name')->get();
            } else {
            $stores = Store::where('active', true)->orderBy('name')->get();
            }
            
            $employees = \App\Models\User::orderBy('name')->get();
            $sources = ServiceOrder::distinct()->whereNotNull('source')->pluck('source')->sort()->values();

            return view('os.index', compact('serviceOrders', 'companies', 'stores', 'employees', 'sources'));
        } catch (\Exception $e) {
            // Se houver erro (ex: tabela não existe), mostrar página vazia com mensagem
            return view('os.index', [
                'serviceOrders' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50),
                'companies' => collect([]),
                'stores' => collect([]),
                'employees' => collect([]),
                'sources' => collect([]),
                'error' => 'Erro ao carregar dados: ' . $e->getMessage()
            ]);
        }
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $companies = Company::where('is_active', true)->orderBy('trade_name')->get();
        
        // Se for gerente, mostrar apenas sua loja
        if ($user && $user->isGerente() && $user->store_id) {
            $stores = Store::where('id', $user->store_id)->where('active', true)->orderBy('code')->get();
            $selectedStoreId = $user->store_id;
        } elseif ($user && $user->isAdmin()) {
            // Admin: usar loja selecionada no dashboard
            $selectedStoreId = $request->session()->get('dashboard_store_id');
            
            \Illuminate\Support\Facades\Log::info('🔍 [OS Create] Verificando loja selecionada', [
                'selectedStoreId' => $selectedStoreId,
                'user_id' => $user->id,
            ]);
            
            if ($selectedStoreId) {
                $selectedStoreId = (int) $selectedStoreId;
                $stores = Store::where('id', $selectedStoreId)->where('active', true)->orderBy('code')->get();
                
                // Se a loja não foi encontrada, limpar sessão e mostrar todas
                if ($stores->isEmpty()) {
                    $request->session()->forget('dashboard_store_id');
                    $request->session()->save();
                    $stores = Store::where('active', true)->orderBy('code')->get();
                    $selectedStoreId = null;
                }
            } else {
                // Se não houver loja selecionada, mostrar todas as lojas para o admin poder selecionar
                $stores = Store::where('active', true)->orderBy('code')->get();
                $selectedStoreId = null;
            }
        } else {
            $stores = Store::where('active', true)->orderBy('code')->get();
            $selectedStoreId = null;
        }
        
        return view('os.create', compact('companies', 'stores', 'selectedStoreId'));
    }

    public function store(StoreServiceOrderRequest $request)
    {
        \Illuminate\Support\Facades\Log::info('✅ ========== OsController::store INICIADO ==========', [
            'method' => $request->method(),
            'has_csrf' => $request->has('_token'),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
        ]);
        
        try {
            // Se for admin e não tiver store_id, tentar buscar da sessão
            if (auth()->user()->isAdmin() && !$request->has('store_id')) {
                $storeId = $request->session()->get('dashboard_store_id');
                if ($storeId) {
                    $request->merge(['store_id' => $storeId]);
                } else {
                    return redirect()->route('os.create')
                        ->with('error', '⚠️ Você precisa selecionar uma loja no dashboard antes de criar uma OS.')
                        ->withInput();
                }
            }
            
            $data = $request->validated();
            \Illuminate\Support\Facades\Log::info('✅ Dados validados com sucesso', [
                'items_count' => count($data['items'] ?? []),
                'payment_type' => $data['payment_type'] ?? 'N/A',
                'client_id' => $data['client_id'] ?? 'N/A',
                'store_id' => $data['store_id'] ?? 'N/A',
            ]);
            
            $data['images'] = $request->file('images') ?? [];
            
            \Illuminate\Support\Facades\Log::info('✅ Chamando OsService::create');
            $serviceOrder = $this->getOsService()->create($data);
            \Illuminate\Support\Facades\Log::info('✅✅✅ OS CRIADA COM SUCESSO NO BANCO!', [
                'os_id' => $serviceOrder->id,
                'os_number' => $serviceOrder->os_number,
            ]);

            // Se for requisição AJAX (do PDV), retornar JSON
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'os' => $serviceOrder->load(['client', 'items.product']),
                    'message' => 'Ordem de Serviço criada com sucesso! Nº ' . $serviceOrder->os_number
                ]);
            }

            // Se for requisição AJAX (do PDV), retornar JSON
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'os' => $serviceOrder->load(['client', 'items.product']),
                    'service_order_id' => $serviceOrder->id,
                    'os_number' => $serviceOrder->os_number,
                    'message' => 'Ordem de Serviço criada com sucesso! Nº ' . $serviceOrder->os_number
                ]);
            }

            return redirect()->route('os.index')
                ->with('success', 'Ordem de Serviço criada com sucesso! Nº ' . $serviceOrder->os_number);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('❌ Erro de validação ao criar OS', [
                'errors' => $e->errors(),
                'input' => $request->except(['_token', 'password']),
            ]);
            // Erro de validação - retornar com erros
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            // Log do erro completo
            \Illuminate\Support\Facades\Log::error('❌ EXCEÇÃO ao criar OS', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['_token', 'password']),
            ]);
            
            // Se for requisição AJAX, retornar JSON com erro
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Erro ao criar O.S.: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao criar O.S.: ' . $e->getMessage());
        }
    }

    public function show(Request $request, ServiceOrder $o)
    {
        $o->load(['client', 'store', 'employee', 'items.product', 'images', 'prescription']);
        
        // Carregar recebíveis vinculados à OS
        $receivables = \App\Models\Finance\Receivable::where('os_id', $o->id)
            ->with(['payments'])
            ->get();
        
        // Se for requisição AJAX ou tiver parâmetro ajax=1, retornar JSON
        if ($request->expectsJson() || $request->ajax() || $request->has('ajax')) {
            return response()->json([
                'id' => $o->id,
                'os_number' => $o->os_number,
                'status' => $o->status,
                'registered_at' => $o->registered_at?->toISOString(),
                'delivery_date' => $o->delivery_date?->toISOString(),
                'delivery_time' => $o->delivery_time?->toISOString(),
                'subtotal' => $o->subtotal,
                'discount_value' => $o->discount_value,
                'total_value' => $o->total_value,
                'sinal_amount' => $o->sinal_amount ?? 0, // Incluir valor do sinal
                'sinal_method' => $o->sinal_method ?? null, // Incluir método do sinal
                'notes' => $o->notes,
                'client' => $o->client ? [
                    'id' => $o->client->id,
                    'name' => $o->client->name,
                    'cpf_cnpj' => $o->client->cpf_cnpj,
                    'phone' => $o->client->phone,
                ] : null,
                'items' => $o->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'name' => $item->name,
                        'ref' => $item->ref,
                        'qty' => $item->qty,
                        'unit_price' => $item->unit_price,
                    ];
                }),
                'receivables' => $receivables->map(function ($receivable) {
                    return [
                        'id' => $receivable->id,
                        'original_amount' => $receivable->original_amount,
                        'balance_amount' => $receivable->balance_amount,
                        'status' => $receivable->status,
                        'due_date' => $receivable->due_date?->format('Y-m-d'),
                        'note' => $receivable->note,
                        'payments' => $receivable->payments->map(function ($payment) {
                            return [
                                'id' => $payment->id,
                                'amount' => $payment->amount,
                                'method' => $payment->method,
                                'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
                            ];
                        }),
                    ];
                }),
                'prescription' => $o->prescription ? [
                    'use_custom' => $o->prescription->use_custom,
                    'custom_doctor_name' => $o->prescription->custom_doctor_name,
                    'custom_valid_until' => $o->prescription->custom_valid_until?->format('Y-m-d'),
                    'custom_adicao' => $o->prescription->custom_adicao,
                    'custom_notes' => $o->prescription->custom_notes,
                    'custom_attachment_path' => $o->prescription->custom_attachment_path,
                    // Longe OD
                    'custom_longe_esferico_od' => $o->prescription->custom_longe_esferico_od,
                    'custom_longe_cilindrico_od' => $o->prescription->custom_longe_cilindrico_od,
                    'custom_longe_eixo_od' => $o->prescription->custom_longe_eixo_od,
                    'custom_longe_altura_od' => $o->prescription->custom_longe_altura_od,
                    'custom_longe_dnp_od' => $o->prescription->custom_longe_dnp_od,
                    // Longe OE
                    'custom_longe_esferico_oe' => $o->prescription->custom_longe_esferico_oe,
                    'custom_longe_cilindrico_oe' => $o->prescription->custom_longe_cilindrico_oe,
                    'custom_longe_eixo_oe' => $o->prescription->custom_longe_eixo_oe,
                    'custom_longe_altura_oe' => $o->prescription->custom_longe_altura_oe,
                    'custom_longe_dnp_oe' => $o->prescription->custom_longe_dnp_oe,
                    // Perto OD
                    'custom_perto_esferico_od' => $o->prescription->custom_perto_esferico_od,
                    'custom_perto_cilindrico_od' => $o->prescription->custom_perto_cilindrico_od,
                    'custom_perto_eixo_od' => $o->prescription->custom_perto_eixo_od,
                    'custom_perto_altura_od' => $o->prescription->custom_perto_altura_od,
                    'custom_perto_dnp_od' => $o->prescription->custom_perto_dnp_od,
                    // Perto OE
                    'custom_perto_esferico_oe' => $o->prescription->custom_perto_esferico_oe,
                    'custom_perto_cilindrico_oe' => $o->prescription->custom_perto_cilindrico_oe,
                    'custom_perto_eixo_oe' => $o->prescription->custom_perto_eixo_oe,
                    'custom_perto_altura_oe' => $o->prescription->custom_perto_altura_oe,
                    'custom_perto_dnp_oe' => $o->prescription->custom_perto_dnp_oe,
                ] : null,
            ]);
        }
        
        // Se não for AJAX, redirecionar para edição
        return redirect()->route('os.edit', $o);
    }

    public function edit(ServiceOrder $o)
    {
        try {
            // Carregar relações básicas
            $o->load(['client', 'store', 'employee', 'items.product', 'images', 'prescription']);
            
            // Carregar prescription aninhada de forma segura
            if ($o->prescription && $o->prescription->prescription_id) {
                $o->prescription->load('prescription');
            }
            
            $companies = Company::where('is_active', true)->orderBy('trade_name')->get();
            $stores = Store::where('active', true)->orderBy('name')->get();
            
            // Carregar receitas do cliente se houver
            $prescriptions = [];
            if ($o->client_id) {
                $prescriptions = Prescription::where('client_id', $o->client_id)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
            }

            return view('os.edit', compact('o', 'companies', 'stores', 'prescriptions'));
        } catch (\Exception $e) {
            Log::error('Erro ao carregar OS para edição', [
                'os_id' => $o->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('os.index')
                ->with('error', 'Erro ao carregar OS para edição: ' . $e->getMessage());
        }
    }

    public function update(UpdateServiceOrderRequest $request, ServiceOrder $o)
    {
        try {
            $data = $request->validated();
            $data['images'] = $request->file('images') ?? [];
            
            $serviceOrder = $this->getOsService()->update($o, $data);

            return redirect()->route('os.index')
                ->with('success', 'Ordem de Serviço atualizada com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao atualizar O.S.: ' . $e->getMessage());
        }
    }

    public function destroy(ServiceOrder $o)
    {
        if (!$o->canCancel()) {
            return redirect()->back()
                ->with('error', 'Esta O.S. não pode ser cancelada.');
        }

        try {
            $o->update(['status' => 'CANCELADA', 'cancel_reason' => 'Cancelada pelo usuário']);
            return redirect()->route('os.index')
                ->with('success', 'Ordem de Serviço cancelada com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao cancelar O.S.: ' . $e->getMessage());
        }
    }

    /**
     * Retorna o próximo número da OS sem incrementar (apenas para visualização)
     */
    public function generateNumber(Request $request)
    {
        try {
            // Buscar store_id de múltiplas fontes
            $storeId = $request->get('store_id') 
                ?? $request->query('store_id')
                ?? $request->input('store_id');
            
            $isConserto = $request->boolean('is_conserto', false);
            
            \Illuminate\Support\Facades\Log::info('🔍 [OS] generateNumber chamado', [
                'store_id' => $storeId,
                'is_conserto' => $isConserto,
                'user_id' => auth()->id(),
            ]);
            
            if (!$storeId) {
                // Tentar buscar da sessão do dashboard se for admin
                if (auth()->user()->isAdmin()) {
                    $storeId = $request->session()->get('dashboard_store_id');
                    \Illuminate\Support\Facades\Log::info('🔍 [OS] Tentando buscar store_id da sessão', ['store_id' => $storeId]);
                }
                
                // Se ainda não tiver, tentar da loja do usuário
                if (!$storeId && auth()->user()->store_id) {
                    $storeId = auth()->user()->store_id;
                    \Illuminate\Support\Facades\Log::info('🔍 [OS] Usando store_id do usuário', ['store_id' => $storeId]);
                }
            }
            
            if (!$storeId) {
                \Illuminate\Support\Facades\Log::warning('❌ [OS] Loja não informada');
                return response()->json(['error' => 'Loja não informada. Por favor, selecione uma loja.'], 400);
            }
            
            // Validar que a loja existe
            $store = \App\Models\Store::find($storeId);
            if (!$store) {
                \Illuminate\Support\Facades\Log::error('❌ [OS] Loja não encontrada', ['store_id' => $storeId]);
                return response()->json(['error' => 'Loja não encontrada'], 404);
            }
            
            // Usar getNextOsNumber que não incrementa o contador
            $osNumber = ServiceOrder::getNextOsNumber($storeId, $isConserto);
            
            \Illuminate\Support\Facades\Log::info('✅ [OS] Número gerado com sucesso', [
                'os_number' => $osNumber,
                'store_id' => $storeId
            ]);
            
            return response()->json([
                'os_number' => $osNumber,
                'store_id' => $storeId
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('❌ [OS] Erro ao gerar número', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Erro ao gerar número da OS: ' . $e->getMessage()
            ], 500);
        }
    }

    public function doctors(Request $request)
    {
        $search = $request->get('q', '');
        $doctors = Prescription::whereNotNull('doctor_name')
            ->where('doctor_name', 'like', '%' . $search . '%')
            ->distinct()
            ->pluck('doctor_name')
            ->take(10)
            ->map(fn($name) => ['id' => $name, 'text' => $name]);

        return response()->json($doctors);
    }
}

