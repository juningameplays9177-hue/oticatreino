<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Client;
use App\Models\Store;
use App\Models\Finance\CashSession;
use App\Models\Finance\Receivable;
use App\Models\Finance\Payable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Exibe o dashboard principal
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Determinar storeId e stores baseado no tipo de usuário
            // (o resolveStoreContext já trata o fallback da URL)
            [$storeId, $stores, $selectedStore] = $this->resolveStoreContext($request, $user);
            $companyId = $user->company_id ?? null;

            Log::info('🔍 [Dashboard] Carregando dashboard', [
                'store_id' => $storeId,
                'company_id' => $companyId,
                'user_id' => $user->id,
                'selected_store' => $selectedStore ? $selectedStore->name : null,
            ]);

            // Buscar todas as estatísticas de forma otimizada
            $stats = $this->getSalesStatistics($storeId, $companyId);
            $productStats = $this->getProductStatistics($storeId, $companyId);
            $clientStats = $this->getClientStatistics($companyId);
            $salesChart = $this->getSalesChart($storeId, $companyId);
            $upcomingPayables = $this->getUpcomingPayables($storeId, $companyId);
            $monthReceivables = $this->getMonthReceivables($storeId, $companyId);
            $financialSummary = $this->getFinancialSummary($storeId, $companyId);
            $carneOpen = $this->getCarneOpen($storeId, $companyId);

            // Preparar dados para a view
            $viewData = array_merge($stats, [
                'productsCount' => $productStats['count'],
                'lowStockCount' => $productStats['lowStock'],
                'clientsCount' => $clientStats['count'],
                'salesChart' => $salesChart,
                'upcomingPayables' => $upcomingPayables,
                'monthReceivables' => $monthReceivables,
                'receivablesTotal' => $financialSummary['receivables'],
                'payablesTotal' => $financialSummary['payables'],
                'carneOpen' => $carneOpen,
                'stores' => $stores,
                'selectedStore' => $selectedStore,
                'storeId' => $storeId,
            ]);

            // Headers anti-cache completos para garantir que a página sempre seja recarregada
            $timestamp = time();
            return response()
                ->view('dashboard', $viewData)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private, proxy-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT')
                ->header('Last-Modified', gmdate('D, d M Y H:i:s', $timestamp) . ' GMT')
                ->header('ETag', md5(json_encode($viewData) . $timestamp))
                ->header('X-Content-Type-Options', 'nosniff')
                ->header('X-Frame-Options', 'SAMEORIGIN');

        } catch (\Throwable $e) {
            Log::error('Erro ao carregar dashboard: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);

            return $this->getErrorResponse();
        }
    }

    /**
     * Resolve o contexto de loja (storeId, stores, selectedStore)
     */
    private function resolveStoreContext(Request $request, $user): array
    {
        if (!$user->isAdmin()) {
            $storeId = $user->store_id ?? null;
            $stores = collect([]);
            $selectedStore = $storeId ? Store::find($storeId) : null;
            return [$storeId, $stores, $selectedStore];
        }

        // Para admin: buscar loja da URL PRIMEIRO (mais confiável), depois da sessão
        $storeId = null;
        $urlStoreId = $request->query('store_id');
        $sessionStoreId = $request->session()->get('dashboard_store_id');
        
        Log::info('🔍 [Dashboard] Iniciando resolução de contexto de loja', [
            'url_store_id' => $urlStoreId,
            'session_store_id' => $sessionStoreId,
            'request_has_store_id' => $request->has('store_id'),
            'request_all' => $request->all(),
            'request_query' => $request->query(),
        ]);
        
        // PRIORIDADE 1: Tentar pegar da URL (mais confiável que sessão)
        if ($urlStoreId !== null && $urlStoreId !== '') {
            $validatedStoreId = filter_var($urlStoreId, FILTER_VALIDATE_INT);
            if ($validatedStoreId !== false) {
                $storeId = (int)$validatedStoreId;
                // Salvar na sessão para próxima vez (sem URL)
                $request->session()->put('dashboard_store_id', $storeId);
                $request->session()->save();
                Log::info('🔧 [Dashboard] Store ID da URL usado e salvo na sessão', [
                    'store_id' => $storeId,
                    'url_value' => $urlStoreId,
                    'user_id' => $user->id,
                ]);
            } else {
                Log::warning('⚠️ [Dashboard] Store ID da URL inválido', [
                    'url_value' => $urlStoreId,
                    'user_id' => $user->id,
                ]);
            }
        }
        
        // PRIORIDADE 2: Se não tiver na URL, tentar da sessão
        if (!$storeId && $sessionStoreId) {
            $storeId = (int)$sessionStoreId;
            Log::info('🔧 [Dashboard] Store ID da sessão usado', [
                'store_id' => $storeId,
                'user_id' => $user->id,
            ]);
        }
        
        // Debug: verificar todos os dados da sessão
        $allSessionData = $request->session()->all();
        
        // Garantir que stores existam para todas as companies
        $this->ensureStoresForCompanies();

        // Buscar todas as stores ativas
        $stores = Store::where('active', true)->orderBy('code')->get();
        
        // Também buscar todas as stores (incluindo inativas) para debug
        $allStores = Store::orderBy('code')->get();

        // Validar storeId selecionado
        if ($storeId) {
            $storeId = (int) $storeId;
            $storeExists = $stores->contains('id', $storeId);
            $storeExistsInAll = $allStores->contains('id', $storeId);
            
            // Buscar a loja diretamente para ver seu status
            $storeModel = Store::find($storeId);
            
            Log::info('🔍 [Dashboard] Validando loja', [
                'store_id' => $storeId,
                'store_exists_active' => $storeExists,
                'store_exists_all' => $storeExistsInAll,
                'store_model' => $storeModel ? [
                    'id' => $storeModel->id,
                    'name' => $storeModel->name,
                    'active' => $storeModel->active,
                ] : null,
                'stores_count' => $stores->count(),
                'all_stores_count' => $allStores->count(),
                'store_ids_active' => $stores->pluck('id')->toArray(),
                'store_ids_all' => $allStores->pluck('id')->toArray(),
            ]);
            
            if (!$storeExists) {
                // Loja não existe mais ou está inativa
                $originalStoreId = $storeId;
                
                if ($storeModel && !$storeModel->active) {
                    Log::warning('⚠️ [Dashboard] Loja existe mas está INATIVA', [
                        'store_id' => $originalStoreId,
                        'store_name' => $storeModel->name,
                        'store_active' => $storeModel->active,
                        'user_id' => $user->id,
                    ]);
                } else if (!$storeModel) {
                    Log::warning('⚠️ [Dashboard] Loja NÃO EXISTE no banco de dados', [
                        'store_id' => $originalStoreId,
                        'user_id' => $user->id,
                    ]);
                }
                
                // Limpar sessão
                $storeId = null;
                $request->session()->forget('dashboard_store_id');
                $request->session()->save();
            } else {
                // Garantir que o storeId está salvo na sessão (refresh para manter viva)
                $request->session()->put('dashboard_store_id', $storeId);
                $request->session()->save();
                
                Log::info('✅ [Dashboard] Loja validada e mantida na sessão', [
                    'store_id' => $storeId,
                    'store_name' => $storeModel ? $storeModel->name : 'N/A',
                    'user_id' => $user->id,
                    'session_id' => $request->session()->getId(),
                ]);
            }
        } else {
            Log::info('ℹ️ [Dashboard] Nenhuma loja selecionada', [
                'user_id' => $user->id,
                'session_id' => $request->session()->getId(),
                'url_store_id' => $urlStoreId,
                'session_store_id' => $sessionStoreId,
            ]);
        }
        
        Log::info('🔍 [Dashboard] Resolvendo contexto de loja - RESULTADO FINAL', [
            'store_id_final' => $storeId,
            'store_id_da_url' => $urlStoreId,
            'store_id_da_sessao_original' => $sessionStoreId,
            'user_id' => $user->id,
            'is_admin' => $user->isAdmin(),
            'session_id' => $request->session()->getId(),
            'session_has_dashboard_store_id' => $request->session()->has('dashboard_store_id'),
        ]);

        $selectedStore = $storeId ? Store::find($storeId) : null;

        return [$storeId, $stores, $selectedStore];
    }

    /**
     * Garante que todas as companies tenham stores associadas
     */
    private function ensureStoresForCompanies(): void
    {
        try {
            $hasCompanyId = Schema::hasColumn('stores', 'company_id');
            $companies = \App\Models\Company::where('is_active', true)->get();
            $createdCount = 0;

            foreach ($companies as $company) {
                $existingStore = $hasCompanyId
                    ? Store::where('company_id', $company->id)->first()
                    : Store::where('name', $company->trade_name ?: $company->legal_name)->first();

                if (!$existingStore) {
                    try {
                        $storeName = $company->trade_name ?: $company->legal_name;
                        $codeAndAbbrev = Store::generateCodeAndAbbreviation($company->slug, $company->id);

                        $storeData = [
                            'name' => $storeName,
                            'code' => $codeAndAbbrev['code'],
                            'abbreviation' => $codeAndAbbrev['abbreviation'],
                            'active' => $company->is_active,
                        ];

                        if ($hasCompanyId) {
                            $storeData['company_id'] = $company->id;
                        }

                        Store::create($storeData);
                        $createdCount++;
                    } catch (\Exception $e) {
                        Log::warning("Erro ao criar store para company {$company->id}: " . $e->getMessage());
                    }
                }
            }

            if ($createdCount > 0) {
                Log::info("Dashboard: {$createdCount} stores criadas automaticamente");
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao garantir stores para companies: ' . $e->getMessage());
        }
    }

    /**
     * Busca estatísticas de vendas de forma otimizada
     */
    private function getSalesStatistics(?int $storeId, ?int $companyId): array
    {
        if (!Schema::hasTable('sales')) {
            return $this->getDefaultSalesStats();
        }

        try {
            $baseQuery = Sale::where('status', 'completed');
            
            if ($storeId) {
                $baseQuery->where('store_id', $storeId);
                Log::debug('🔍 [Dashboard] Filtrando vendas por loja', ['store_id' => $storeId]);
            }
            if ($companyId) {
                $baseQuery->where('company_id', $companyId);
            }

            $today = Carbon::today();
            $monthStart = Carbon::now()->startOfMonth();

            // Uma única query para vendas de hoje
            $todaySales = (clone $baseQuery)
                ->whereDate('sale_date', $today)
                ->selectRaw('COALESCE(SUM(total_net), 0) as total, COUNT(*) as count')
                ->first();

            // Uma única query para vendas do mês
            $monthSales = (clone $baseQuery)
                ->where('sale_date', '>=', $monthStart)
                ->selectRaw('COALESCE(SUM(total_net), 0) as total, COUNT(*) as count')
                ->first();

            // Vendas entrada hoje (à vista - não geram recebíveis)
            // Vendas à vista são aquelas que não têm recebíveis associados
            $todayCashSales = (clone $baseQuery)
                ->whereDate('sale_date', $today)
                ->whereDoesntHave('receivables')
                ->selectRaw('COALESCE(SUM(total_net), 0) as total, COUNT(*) as count')
                ->first();

            // Vendas entrada mês (à vista - não geram recebíveis)
            $monthCashSales = (clone $baseQuery)
                ->where('sale_date', '>=', $monthStart)
                ->whereDoesntHave('receivables')
                ->selectRaw('COALESCE(SUM(total_net), 0) as total, COUNT(*) as count')
                ->first();

            // Total geral (apenas se necessário)
            $totalSales = (clone $baseQuery)
                ->selectRaw('COALESCE(SUM(total_net), 0) as total, COUNT(*) as count')
                ->first();

            return [
                'salesToday' => (float) ($todaySales->total ?? 0),
                'salesTodayCount' => (int) ($todaySales->count ?? 0),
                'salesMonth' => (float) ($monthSales->total ?? 0),
                'salesMonthCount' => (int) ($monthSales->count ?? 0),
                'salesTodayCash' => (float) ($todayCashSales->total ?? 0),
                'salesTodayCashCount' => (int) ($todayCashSales->count ?? 0),
                'salesMonthCash' => (float) ($monthCashSales->total ?? 0),
                'salesMonthCashCount' => (int) ($monthCashSales->count ?? 0),
                'salesTotal' => (float) ($totalSales->total ?? 0),
                'salesTotalCount' => (int) ($totalSales->count ?? 0),
            ];
        } catch (\Exception $e) {
            Log::warning('Erro ao buscar estatísticas de vendas: ' . $e->getMessage());
            return $this->getDefaultSalesStats();
        }
    }

    /**
     * Retorna estatísticas padrão de vendas
     */
    private function getDefaultSalesStats(): array
    {
        return [
            'salesToday' => 0,
            'salesTodayCount' => 0,
            'salesMonth' => 0,
            'salesMonthCount' => 0,
            'salesTodayCash' => 0,
            'salesTodayCashCount' => 0,
            'salesMonthCash' => 0,
            'salesMonthCashCount' => 0,
            'salesTotal' => 0,
            'salesTotalCount' => 0,
        ];
    }

    /**
     * Busca estatísticas de produtos
     */
    private function getProductStatistics(?int $storeId, ?int $companyId): array
    {
        $count = 0;
        $lowStock = 0;

        if (!Schema::hasTable('products')) {
            return ['count' => $count, 'lowStock' => $lowStock];
        }

        try {
            $count = Product::count();

            if ($storeId && Schema::hasTable('product_stocks')) {
                $lowStock = DB::table('product_stocks')
                    ->where('store_id', $storeId)
                    ->where('qty', '<', 10)
                    ->count();
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao buscar estatísticas de produtos: ' . $e->getMessage());
        }

        return ['count' => $count, 'lowStock' => $lowStock];
    }

    /**
     * Busca estatísticas de clientes
     */
    private function getClientStatistics(?int $companyId): array
    {
        $count = 0;

        if (!Schema::hasTable('clients')) {
            return ['count' => $count];
        }

        try {
            $count = Client::count();
        } catch (\Exception $e) {
            Log::warning('Erro ao buscar estatísticas de clientes: ' . $e->getMessage());
        }

        return ['count' => $count];
    }

    /**
     * Gera dados do gráfico de vendas dos últimos 7 dias (otimizado)
     */
    private function getSalesChart(?int $storeId, ?int $companyId): array
    {
        if (!Schema::hasTable('sales')) {
            return [];
        }

        try {
            $dates = [];
            for ($i = 6; $i >= 0; $i--) {
                $dates[] = Carbon::today()->subDays($i)->format('Y-m-d');
            }

            $baseQuery = Sale::where('status', 'completed')
                ->whereIn(DB::raw('DATE(sale_date)'), $dates);

            if ($storeId) {
                $baseQuery->where('store_id', $storeId);
            }
            if ($companyId) {
                $baseQuery->where('company_id', $companyId);
            }

            // Uma única query para todos os dias
            $results = $baseQuery
                ->selectRaw('
                    DATE(sale_date) as date,
                    COALESCE(SUM(total_net), 0) as total,
                    COUNT(*) as count
                ')
                ->groupBy(DB::raw('DATE(sale_date)'))
                ->get()
                ->keyBy(function ($item) {
                    return Carbon::parse($item->date)->format('Y-m-d');
                });

            $chart = [];
            foreach ($dates as $date) {
                $carbonDate = Carbon::parse($date);
                $result = $results->get($date);

                $chart[] = [
                    'date' => $carbonDate->format('d/m'),
                    'total' => (float) ($result->total ?? 0),
                    'count' => (int) ($result->count ?? 0),
                ];
            }

            return $chart;
        } catch (\Exception $e) {
            Log::warning('Erro ao gerar gráfico de vendas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca últimas vendas
     */
    private function getRecentSales(?int $storeId, ?int $companyId): Collection
    {
        if (!Schema::hasTable('sales')) {
            return collect([]);
        }

        try {
            $query = Sale::with(['customer', 'store'])
                ->where('status', 'completed')
                ->orderBy('sale_date', 'desc')
                ->limit(5);

            if ($storeId) {
                $query->where('store_id', $storeId);
            }
            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            return $query->get();
        } catch (\Exception $e) {
            Log::warning('Erro ao buscar últimas vendas: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Busca contas a pagar que estão para vencer nos próximos dias
     */
    private function getUpcomingPayables(?int $storeId, ?int $companyId): Collection
    {
        if (!Schema::hasTable('payables')) {
            return collect([]);
        }

        try {
            $today = Carbon::today();
            $next30Days = Carbon::today()->addDays(30);

            $query = Payable::with(['supplier', 'store', 'category'])
                ->whereIn('status', ['open', 'partial'])
                ->whereBetween('due_date', [$today, $next30Days])
                ->orderBy('due_date', 'asc')
                ->limit(10);

            if ($storeId) {
                $query->where('store_id', $storeId);
            }
            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            return $query->get();
        } catch (\Exception $e) {
            Log::warning('Erro ao buscar contas a pagar próximas: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Busca produtos mais vendidos
     */
    private function getTopProducts(?int $storeId, ?int $companyId): Collection
    {
        if (!Schema::hasTable('sale_items') || !Schema::hasTable('products')) {
            return collect([]);
        }

        try {
            $query = DB::table('sale_items')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sales.status', 'completed')
                ->select(
                    'products.id',
                    'products.name',
                    'products.ref',
                    DB::raw('SUM(sale_items.qty) as total_qty'),
                    DB::raw('SUM(sale_items.subtotal) as total_revenue')
                )
                ->groupBy('products.id', 'products.name', 'products.ref')
                ->orderBy('total_qty', 'desc')
                ->limit(5);

            if ($storeId) {
                $query->where('sales.store_id', $storeId);
            }
            if ($companyId) {
                $query->where('sales.company_id', $companyId);
            }

            return collect($query->get());
        } catch (\Exception $e) {
            Log::warning('Erro ao buscar produtos mais vendidos: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Busca resumo financeiro
     */
    private function getFinancialSummary(?int $storeId, ?int $companyId): array
    {
        $receivables = 0;
        $payables = 0;

        try {
            if (Schema::hasTable('receivables')) {
                // Buscar receivables abertos ou parcialmente pagos (status 'open' ou 'partial')
                $query = Receivable::whereIn('status', ['open', 'partial']);
                if ($storeId) {
                    $query->where('store_id', $storeId);
                }
                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
                // Usar balance_amount (saldo devedor) em vez de amount
                $receivables = (float) $query->sum('balance_amount');
            }

            if (Schema::hasTable('payables')) {
                // Buscar payables abertos ou parcialmente pagos (status 'open' ou 'partial')
                $query = Payable::whereIn('status', ['open', 'partial']);
                if ($storeId) {
                    $query->where('store_id', $storeId);
                }
                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
                // Usar balance_amount (saldo devedor) em vez de amount
                $payables = (float) $query->sum('balance_amount');
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao buscar resumo financeiro: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return ['receivables' => $receivables, 'payables' => $payables];
    }

    /**
     * Busca prestações de carnê em aberto
     */
    private function getCarneOpen(?int $storeId, ?int $companyId): float
    {
        if (!Schema::hasTable('receivables')) {
            return 0;
        }

        try {
            $query = Receivable::where('billing_type', 'carne')
                ->whereIn('status', ['open', 'partial']);
            
            if ($storeId) {
                $query->where('store_id', $storeId);
            }
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
            
            return (float) $query->sum('balance_amount');
        } catch (\Exception $e) {
            Log::warning('Erro ao buscar prestações de carnê em aberto: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Busca contas a receber do mês atual
     */
    private function getMonthReceivables(?int $storeId, ?int $companyId): Collection
    {
        if (!Schema::hasTable('receivables')) {
            return collect([]);
        }

        try {
            $monthStart = Carbon::now()->startOfMonth();
            $monthEnd = Carbon::now()->endOfMonth();
            
            $query = Receivable::with(['customer', 'store'])
                ->whereIn('status', ['open', 'partial'])
                ->whereBetween('due_date', [$monthStart, $monthEnd])
                ->orderBy('due_date', 'asc');
            
            if ($storeId) {
                $query->where('store_id', $storeId);
            }
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
            
            return $query->get();
        } catch (\Exception $e) {
            Log::warning('Erro ao buscar contas a receber do mês: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Busca sessão de caixa ativa
     */
    private function getCashSession(?int $storeId): ?CashSession
    {
        if (!$storeId || !Schema::hasTable('cash_sessions') || !class_exists(CashSession::class)) {
            return null;
        }

        try {
            return CashSession::where('store_id', $storeId)
                ->where('status', 'open')
                ->first();
        } catch (\Exception $e) {
            Log::warning('Erro ao buscar sessão de caixa: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Retorna resposta de erro
     */
    private function getErrorResponse()
    {
        $stores = collect([]);
        if (auth()->check() && auth()->user()->isAdmin()) {
            try {
                $stores = Store::where('active', true)->orderBy('code')->get();
            } catch (\Exception $e) {
                $stores = collect([]);
            }
        }

        $viewData = [
            'salesToday' => 0, 'salesTodayCount' => 0,
            'salesMonth' => 0, 'salesMonthCount' => 0,
            'salesTotal' => 0, 'salesTotalCount' => 0,
            'productsCount' => 0, 'lowStockCount' => 0,
            'clientsCount' => 0,
            'salesChart' => [], 'recentSales' => collect([]), 'topProducts' => collect([]),
            'cashSession' => null, 'receivablesTotal' => 0, 'payablesTotal' => 0,
            'stores' => $stores, 'selectedStore' => null, 'storeId' => null
        ];

        // Headers anti-cache completos
        $timestamp = time();
        return response()
            ->view('dashboard', $viewData)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private, proxy-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT')
            ->header('Last-Modified', gmdate('D, d M Y H:i:s', $timestamp) . ' GMT')
            ->header('ETag', md5(json_encode($viewData) . $timestamp))
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    /**
     * Seleciona uma loja para o dashboard (apenas admin)
     * APENAS ACEITA GET - não aceita POST/PUT/PATCH/DELETE
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function selectStore(Request $request)
    {
        // VALIDAÇÃO RIGOROSA: Apenas GET é permitido
        $method = $request->method();
        if ($method !== 'GET') {
            \Illuminate\Support\Facades\Log::error('❌ [Dashboard] Tentativa de acessar selectStore com método incorreto', [
                'method' => $method,
                'expected' => 'GET',
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
            ]);
            
            // Retornar 405 com mensagem clara
            abort(405, 'Método não permitido. Esta rota aceita apenas requisições GET.');
        }
        
        if (!auth()->user()->isAdmin()) {
            return redirect()->route('dashboard')
                ->with('error', 'Apenas administradores podem selecionar lojas.');
        }

        // Aceitar store_id APENAS via query string (GET)
        $storeIdParam = $request->query('store_id');
        
        // Validar se é um número inteiro válido
        $storeId = null;
        if ($storeIdParam !== null && $storeIdParam !== '') {
            $storeId = filter_var($storeIdParam, FILTER_VALIDATE_INT);
            if ($storeId === false) {
                return redirect()->route('dashboard')
                    ->with('error', 'ID de loja inválido.');
            }
        }

        if ($storeId) {
            // Validar que a loja existe e está ativa
            $store = Store::where('id', $storeId)
                ->where('active', true)
                ->first();

            if (!$store) {
                $request->session()->forget('dashboard_store_id');
                $request->session()->save();

                \Illuminate\Support\Facades\Log::warning('⚠️ [Dashboard] Loja não existe ou está inativa', [
                    'store_id' => $storeId,
                    'user_id' => auth()->id(),
                ]);

                return redirect()->route('dashboard')
                    ->with('error', 'Essa loja não existe ou está inativa.');
            }

            // Salvar na sessão - usar método direto sem regenerateToken que pode causar problemas
            $request->session()->put('dashboard_store_id', $storeId);
            
            // Salvar explicitamente e verificar
            $request->session()->save();
            
            // Verificar se foi salvo corretamente ANTES do redirecionamento
            $savedStoreId = $request->session()->get('dashboard_store_id');
            
            \Illuminate\Support\Facades\Log::info('✅ [Dashboard] Loja selecionada e salva na sessão', [
                'store_id_solicitado' => $storeId,
                'store_id_salvo' => $savedStoreId,
                'store_nome' => $store->name,
                'user_id' => auth()->id(),
                'session_id' => $request->session()->getId(),
                'session_salva' => $savedStoreId === $storeId,
                'session_todos_dados' => $request->session()->all(),
            ]);
            
            // Se não salvou, logar erro crítico
            if ($savedStoreId != $storeId) {
                \Illuminate\Support\Facades\Log::error('❌ [Dashboard] ERRO CRÍTICO: Sessão não salvou corretamente!', [
                    'store_id_solicitado' => $storeId,
                    'store_id_salvo' => $savedStoreId,
                    'session_id' => $request->session()->getId(),
                ]);
            }
        } else {
            // Remover filtro (todas as lojas)
            $request->session()->forget('dashboard_store_id');
            $request->session()->save();
            
            \Illuminate\Support\Facades\Log::info('✅ [Dashboard] Filtro de loja removido da sessão', [
                'user_id' => auth()->id(),
                'session_id' => $request->session()->getId(),
            ]);
        }

        // Garantir que a sessão foi salva ANTES do redirecionamento
        $finalCheck = $request->session()->get('dashboard_store_id');
        
        \Illuminate\Support\Facades\Log::info('🔄 [Dashboard] Preparando redirecionamento', [
            'store_id_original' => $storeId,
            'store_id_verificacao_final' => $finalCheck,
            'sessao_ok' => $finalCheck == $storeId,
            'session_id' => $request->session()->getId(),
            'user_id' => auth()->id(),
        ]);
        
        // Redirecionar para dashboard - incluir store_id na URL como fallback se a sessão não persistir
        $redirectUrl = route('dashboard');
        if ($storeId) {
            // Adicionar store_id na URL como fallback caso a sessão não funcione
            $redirectUrl .= '?store_id=' . $storeId . '&_t=' . time();
        } else {
            $redirectUrl .= '?_t=' . time();
        }
        
        \Illuminate\Support\Facades\Log::info('🔄 [Dashboard] Redirecionando para dashboard', [
            'url' => $redirectUrl,
            'store_id_salvo' => $storeId,
            'store_id_verificacao_final' => $finalCheck,
            'user_id' => auth()->id(),
        ]);
        
        return redirect($redirectUrl)
            ->with('success', $storeId ? 'Loja selecionada com sucesso!' : 'Filtro de loja removido.');
    }
}
