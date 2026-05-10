<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientPhone;
use App\Models\ClientEmail;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Traits\HasStoreFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClientsController extends Controller
{
    use HasStoreFilter;
    
    /**
     * Listagem com filtros e paginação
     */
    public function index(Request $request)
    {
        // Log para diagnóstico
        try {
            \Log::info('ClientsController::index chamado', [
                'user_id' => auth()->id(),
                'user_name' => auth()->user()?->name ?? 'N/A',
                'request_uri' => $request->fullUrl(),
                'ip' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            // Ignorar erros de log
        }
        
        $query = Client::query();
        
        // Clientes não são filtrados por loja (são compartilhados)
        // Mas podemos filtrar vendas por loja se necessário

        // Busca livre
        if ($request->filled('q')) {
            $query->search($request->q);
        }

        // Filtros
        $filters = $request->only(['type', 'active', 'city', 'district', 'from', 'to']);
        
        // Processar período inteligente
        if ($request->filled('period')) {
            $period = $request->period;
            $today = now();
            
            switch ($period) {
                case 'today':
                    $filters['from'] = $today->format('Y-m-d');
                    $filters['to'] = $today->format('Y-m-d');
                    break;
                case 'yesterday':
                    $yesterday = $today->copy()->subDay();
                    $filters['from'] = $yesterday->format('Y-m-d');
                    $filters['to'] = $yesterday->format('Y-m-d');
                    break;
                case 'this_week':
                    $filters['from'] = $today->copy()->startOfWeek()->format('Y-m-d');
                    $filters['to'] = $today->format('Y-m-d');
                    break;
                case 'last_7':
                    $filters['from'] = $today->copy()->subDays(7)->format('Y-m-d');
                    $filters['to'] = $today->format('Y-m-d');
                    break;
                case 'last_30':
                    $filters['from'] = $today->copy()->subDays(30)->format('Y-m-d');
                    $filters['to'] = $today->format('Y-m-d');
                    break;
                case 'this_month':
                    $filters['from'] = $today->copy()->startOfMonth()->format('Y-m-d');
                    $filters['to'] = $today->format('Y-m-d');
                    break;
                case 'last_month':
                    $lastMonth = $today->copy()->subMonth();
                    $filters['from'] = $lastMonth->startOfMonth()->format('Y-m-d');
                    $filters['to'] = $lastMonth->endOfMonth()->format('Y-m-d');
                    break;
                case 'this_year':
                    $filters['from'] = $today->copy()->startOfYear()->format('Y-m-d');
                    $filters['to'] = $today->format('Y-m-d');
                    break;
            }
        }

        $query->filters($filters);

        // Ordenar por data de criação (mais recente primeiro)
        $query->orderBy('created_at', 'desc');

        // Log antes da paginação
        try {
            $totalBeforePaginate = $query->count();
            \Log::info('ClientsController::index - Antes da paginação', [
                'total_clients' => $totalBeforePaginate,
                'filters' => $filters,
            ]);
        } catch (\Exception $e) {
            \Log::error('ClientsController::index - Erro ao contar clientes', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $totalBeforePaginate = 0;
        }

        // Paginação
        try {
            $clients = $query->paginate(50)->withQueryString();
            
            // Log após paginação
            \Log::info('ClientsController::index - Após paginação', [
                'total' => $clients->total(),
                'current_page' => $clients->currentPage(),
                'per_page' => $clients->perPage(),
            ]);
        } catch (\Exception $e) {
            \Log::error('ClientsController::index - Erro na paginação', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Retornar erro amigável
            return redirect()->back()
                ->with('error', 'Erro ao carregar clientes: ' . $e->getMessage());
        }

        return view('clients.index', compact('clients'));
    }

    /**
     * Mostrar formulário de criação
     */
    public function create()
    {
        \Log::info('ClientsController::create - Acessando formulário de criação', [
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name,
        ]);
        
        return view('clients.create');
    }

    /**
     * Salvar novo cliente
     */
    public function store(StoreClientRequest $request)
    {
        // Log inicial usando sistema de logs do Laravel
        try {
            \Log::info('ClientsController::store - MÉTODO CHAMADO', [
                'user_id' => auth()->id(),
                'user_name' => auth()->user()?->name,
                'request_data' => $request->except(['password', '_token']),
                'has_name' => $request->has('name'),
                'name_value' => $request->input('name'),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
            ]);
        } catch (\Exception $e) {
            // Ignorar erros de log
        }
        
        // Verificar se o nome está presente antes de começar
        if (!$request->has('name') || empty(trim($request->input('name')))) {
            \Log::warning('ClientsController::store - Nome não fornecido');
            return redirect()->back()
                ->withInput()
                ->with('error', 'O nome do cliente é obrigatório.');
        }
        
        DB::beginTransaction();

        try {
            // Log antes da validação
            \Log::info('ClientsController::store - Antes da validação', [
                'all_input' => $request->all(),
            ]);
            
            // Normalizar CPF/CNPJ
            $data = $request->validated();
            
            \Log::info('ClientsController::store - Dados validados', [
                'validated_data' => $data,
            ]);
            
            // Garantir que 'active' tenha um valor padrão
            if (!isset($data['active'])) {
                $data['active'] = true;
            }
            
            // Garantir que 'name' existe
            if (empty($data['name'])) {
                \Log::error('ClientsController::store - Nome vazio após validação');
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'O nome do cliente é obrigatório.');
            }
            
            if (isset($data['cpf_cnpj'])) {
                $data['cpf_cnpj'] = Client::normalizeCpfCnpj($data['cpf_cnpj']);
            }

            // Separar dados do cliente dos arrays de telefones e e-mails
            $phones = $data['phones'] ?? [];
            $emails = $data['emails'] ?? [];
            
            // Remover phones e emails do array de dados do cliente
            unset($data['phones'], $data['emails']);
            
            // Verificar se a coluna state existe antes de tentar salvar
            if (isset($data['state']) && !Schema::hasColumn('clients', 'state')) {
                unset($data['state']);
            }
            
            // Filtrar arrays vazios de telefones e e-mails
            $phones = array_filter($phones, function($phone) {
                return !empty($phone['phone']);
            });
            
            $emails = array_filter($emails, function($email) {
                return !empty($email['email']);
            });

            // Verificar se o campo 'code' existe antes de criar
            if (!Schema::hasColumn('clients', 'code')) {
                // Se não existir, remover do array de dados
                unset($data['code']);
            }
            
            // Log antes de criar
            \Log::info('ClientsController::store - Tentando criar cliente', [
                'data_to_create' => $data,
            ]);
            
            // Criar cliente
            try {
                \Log::info('ClientsController::store - Chamando Client::create', [
                    'data_keys' => array_keys($data),
                    'data_name' => $data['name'] ?? 'N/A',
                ]);
                
                $client = Client::create($data);
                
                \Log::info('ClientsController::store - Client::create retornou', [
                    'client_id' => $client->id ?? 'NULL',
                    'client_name' => $client->name ?? 'NULL',
                    'client_exists' => $client->exists ?? false,
                ]);
                
                // Verificar se o cliente foi realmente salvo
                if (!$client->id) {
                    \Log::error('ClientsController::store - Cliente criado mas sem ID!');
                    throw new \Exception('Cliente criado mas não recebeu ID do banco de dados');
                }
                
                // Recarregar do banco para garantir
                $client->refresh();
                \Log::info('ClientsController::store - Cliente recarregado do banco', [
                    'client_id' => $client->id,
                    'client_name' => $client->name,
                ]);
            } catch (\Exception $createException) {
                \Log::error('Erro ao criar cliente (create): ' . $createException->getMessage(), [
                    'data' => $data,
                    'exception_class' => get_class($createException),
                    'trace' => $createException->getTraceAsString(),
                ]);
                throw $createException;
            }

            // Salvar telefones
            if (!empty($phones)) {
                foreach ($phones as $phoneData) {
                    if (!empty($phoneData['phone'])) {
                        ClientPhone::create([
                            'client_id' => $client->id,
                            'phone' => ClientPhone::normalizePhone($phoneData['phone']),
                            'label' => $phoneData['label'] ?? null,
                        ]);
                    }
                }
            }

            // Salvar e-mails
            if (!empty($emails)) {
                foreach ($emails as $emailData) {
                    if (!empty($emailData['email'])) {
                        ClientEmail::create([
                            'client_id' => $client->id,
                            'email' => $emailData['email'],
                            'label' => $emailData['label'] ?? null,
                        ]);
                    }
                }
            }


            DB::commit();
            
            // Verificar se o cliente foi realmente salvo no banco
            $client->refresh();
            $clientExists = Client::find($client->id);
            
            \Log::info('ClientsController::store - Transação commitada, verificando se cliente foi salvo', [
                'client_id' => $client->id,
                'client_name' => $client->name,
                'client_code' => $client->code ?? 'N/A',
                'client_exists_in_db' => $clientExists ? 'SIM' : 'NÃO',
                'total_clients_in_db' => Client::count(),
            ]);
            
            if (!$clientExists) {
                \Log::error('ClientsController::store - ERRO: Cliente não encontrado no banco após commit!');
                throw new \Exception('Cliente não foi salvo no banco de dados');
            }

            // Verificar se é requisição AJAX de forma mais robusta
            $isAjax = $request->wantsJson() 
                || $request->ajax() 
                || $request->header('X-Requested-With') === 'XMLHttpRequest'
                || $request->expectsJson();
            
            \Log::info('ClientsController::store - Verificando tipo de requisição', [
                'wantsJson' => $request->wantsJson(),
                'ajax' => $request->ajax(),
                'x_requested_with' => $request->header('X-Requested-With'),
                'expectsJson' => $request->expectsJson(),
                'isAjax' => $isAjax,
            ]);
            
            // Se for requisição AJAX, retornar JSON
            if ($isAjax) {
                \Log::info('ClientsController::store - Retornando JSON');
                return response()->json([
                    'success' => true,
                    'message' => 'Cliente cadastrado com sucesso!',
                    'client' => [
                        'id' => $client->id,
                        'name' => $client->name,
                        'cpf_cnpj' => $client->cpf_cnpj,
                        'code' => $client->code ?? null,
                    ]
                ]);
            }

            \Log::info('ClientsController::store - Redirecionando para listagem', [
                'client_id' => $client->id,
                'client_name' => $client->name,
            ]);
            
            // Limpar qualquer cache que possa estar interferindo
            \Cache::forget('clients_count');
            
            \Log::info('ClientsController::store - Redirecionando para clients.index', [
                'route_name' => 'clients.index',
                'route_url' => route('clients.index'),
            ]);
            
            return redirect()->route('clients.index')
                ->with('success', 'Cliente cadastrado com sucesso! ID: ' . $client->id)
                ->with('new_client_id', $client->id); // Passar ID para destacar na listagem
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            
            // Log de erro de validação
            \Log::warning('Erro de validação ao cadastrar cliente', [
                'errors' => $e->errors(),
                'request_data' => $request->except(['password', '_token']),
                'all_errors' => $e->errors(),
            ]);
            
            // Construir mensagem de erro detalhada
            $errorMessages = [];
            foreach ($e->errors() as $field => $messages) {
                $errorMessages = array_merge($errorMessages, $messages);
            }
            $errorMessage = 'Erro de validação: ' . implode(', ', $errorMessages);
            
            // Se for requisição AJAX, retornar JSON
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors(),
                    'message' => $errorMessage
                ], 422);
            }
            
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', $errorMessage);
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log detalhado do erro
            \Log::error('Erro ao cadastrar cliente', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password', '_token']),
            ]);
            
            // Se for requisição AJAX, retornar JSON
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Erro ao cadastrar cliente: ' . $e->getMessage()
                ], 400);
            }
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao cadastrar cliente: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulário de edição
     */
    public function edit(Client $client)
    {
        $client->load(['phones', 'emails']);
        return view('clients.edit', compact('client'));
    }

    /**
     * Atualizar cliente
     */
    public function update(UpdateClientRequest $request, Client $client)
    {
        DB::beginTransaction();

        try {
            // Normalizar CPF/CNPJ
            $data = $request->validated();
            if (isset($data['cpf_cnpj'])) {
                $data['cpf_cnpj'] = Client::normalizeCpfCnpj($data['cpf_cnpj']);
            }

            // Atualizar cliente
            $client->update($data);

            // Atualizar telefones (remover e recriar)
            $client->phones()->delete();
            if ($request->filled('phones')) {
                foreach ($request->phones as $phoneData) {
                    if (!empty($phoneData['phone'])) {
                        ClientPhone::create([
                            'client_id' => $client->id,
                            'phone' => ClientPhone::normalizePhone($phoneData['phone']),
                            'label' => $phoneData['label'] ?? null,
                        ]);
                    }
                }
            }

            // Atualizar e-mails (remover e recriar)
            $client->emails()->delete();
            if ($request->filled('emails')) {
                foreach ($request->emails as $emailData) {
                    if (!empty($emailData['email'])) {
                        ClientEmail::create([
                            'client_id' => $client->id,
                            'email' => $emailData['email'],
                            'label' => $emailData['label'] ?? null,
                        ]);
                    }
                }
            }


            DB::commit();

            return redirect()->route('clients.index')
                ->with('success', 'Cliente atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao atualizar cliente: ' . $e->getMessage());
        }
    }

    /**
     * Excluir cliente (soft delete ou inativar)
     */
    public function destroy(Client $client)
    {
        try {
            // Inativar ao invés de excluir
            $client->update(['active' => false]);
            
            return redirect()->route('clients.index')
                ->with('success', 'Cliente inativado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao inativar cliente: ' . $e->getMessage());
        }
    }
    
    /**
     * Exibe detalhes completos do cliente
     */
    public function show(Client $client)
    {
        try {
            // Carregar relacionamentos
            $client->load(['phones', 'emails']);
            
            // Buscar vendas
            $sales = [];
            if (Schema::hasTable('sales')) {
                $sales = \App\Models\Sale::where('customer_id', $client->id)
                    ->with(['store', 'items.product'])
                    ->orderBy('sale_date', 'desc')
                    ->limit(50)
                    ->get();
            }
            
            // Buscar ordens de serviço
            $serviceOrders = [];
            if (Schema::hasTable('service_orders')) {
                $serviceOrders = \App\Models\ServiceOrder::where('client_id', $client->id)
                    ->with(['store', 'items.product', 'prescription'])
                    ->orderBy('registered_at', 'desc')
                    ->limit(50)
                    ->get();
            }
            
            // Buscar contas a receber (pendências financeiras)
            $receivables = [];
            $totalReceivables = 0;
            $totalOverdue = 0;
            if (Schema::hasTable('receivables')) {
                $receivables = \App\Models\Finance\Receivable::where('customer_id', $client->id)
                    ->whereIn('status', ['open', 'partial'])
                    ->with(['store', 'payments'])
                    ->orderBy('due_date', 'asc')
                    ->get();
                
                $totalReceivables = $receivables->sum('balance_amount');
                $totalOverdue = $receivables->filter(function($r) {
                    return $r->isOverdue();
                })->sum('balance_amount');
            }
            
            // Calcular estatísticas
            $totalSales = $sales->sum('total_net');
            $totalServiceOrders = $serviceOrders->sum('total_value');
            $lastSale = $sales->first();
            $lastServiceOrder = $serviceOrders->first();
            
            return view('clients.show', compact(
                'client',
                'sales',
                'serviceOrders',
                'receivables',
                'totalSales',
                'totalServiceOrders',
                'totalReceivables',
                'totalOverdue',
                'lastSale',
                'lastServiceOrder'
            ));
        } catch (\Exception $e) {
            \Log::error('Erro ao carregar detalhes do cliente: ' . $e->getMessage());
            return redirect()->route('clients.index')
                ->with('error', 'Erro ao carregar detalhes do cliente: ' . $e->getMessage());
        }
    }
}
