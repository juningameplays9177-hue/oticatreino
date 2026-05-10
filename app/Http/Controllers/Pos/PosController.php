<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Store;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\Client;
use App\Models\Finance\CashSession;
use App\Services\Finance\CashboxService;
use App\Services\Finance\SaleSettlementService;
use App\Services\Finance\StockCostService;
use App\Traits\HasStoreFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PosController extends Controller
{
    use HasStoreFilter;
    protected ?CashboxService $cashboxService = null;
    protected ?SaleSettlementService $saleSettlementService = null;
    protected ?StockCostService $stockCostService = null;

    public function __construct()
    {
        // Injeção lazy para evitar erro se tabelas não existirem
        // Não instanciar services no construtor para evitar erros fatais
    }
    
    protected function getCashboxService(): ?CashboxService
    {
        if (!$this->cashboxService) {
            try {
                $this->cashboxService = app(CashboxService::class);
            } catch (\Exception $e) {
                Log::warning('CashboxService não disponível: ' . $e->getMessage());
                return null;
            }
        }
        return $this->cashboxService;
    }
    
    protected function getSaleSettlementService(): ?SaleSettlementService
    {
        if (!$this->saleSettlementService) {
            try {
                $this->saleSettlementService = app(SaleSettlementService::class);
            } catch (\Exception $e) {
                Log::warning('SaleSettlementService não disponível: ' . $e->getMessage());
                return null;
            }
        }
        return $this->saleSettlementService;
    }
    
    protected function getStockCostService(): ?StockCostService
    {
        if (!$this->stockCostService) {
            try {
                $this->stockCostService = app(StockCostService::class);
            } catch (\Exception $e) {
                Log::warning('StockCostService não disponível: ' . $e->getMessage());
                return null;
            }
        }
        return $this->stockCostService;
    }

    /**
     * Exibe a tela do PDV
     */
    public function index()
    {
        // Inicializar variáveis com valores padrão
        $store = null;
        $cashSession = null;
        $error = null;
        
        // Fechar automaticamente caixas de dias anteriores do usuário atual
        try {
            $cashboxService = $this->getCashboxService();
            if ($cashboxService && $storeId) {
                // Isso já fecha automaticamente caixas de dias anteriores
                $cashboxService->getOpenSession($storeId, $user->id);
            }
        } catch (\Exception $e) {
            // Ignorar erros ao verificar caixas antigos
            Log::debug('Erro ao verificar caixas antigos: ' . $e->getMessage());
        }
        
        try {
            if (!auth()->check()) {
                return redirect()->route('login');
            }
            
            $user = auth()->user();
            if (!$user) {
                return redirect()->route('login');
            }
            
            // Se for gerente, usar sempre a loja dele (não permitir mudança)
            if ($user->role === 'gerente' && $user->store_id) {
                $storeId = $user->store_id;
                session(['pdv_store_id' => $storeId]); // Forçar na sessão
            } else {
                // Admin pode escolher loja
                $storeId = session('pdv_store_id') ?? $user->store_id ?? null;
            }
            
            // Buscar a loja selecionada
            if ($storeId) {
                try {
                    if (Schema::hasTable('stores')) {
                        $store = Store::find($storeId);
                    }
                } catch (\Exception $e) {
                    Log::warning('Erro ao buscar loja no PDV: ' . $e->getMessage());
                } catch (\Throwable $e) {
                    Log::warning('Erro fatal ao buscar loja no PDV: ' . $e->getMessage());
                }
            }
            
            // Verificar sessão de caixa aberta
            if ($storeId) {
                try {
                    if (Schema::hasTable('cash_sessions') && Schema::hasTable('users') && class_exists(CashSession::class)) {
                        try {
                            $cashboxService = $this->getCashboxService();
                            if ($cashboxService) {
                                // Para admin, buscar qualquer sessão aberta (incluindo retroativas)
                                // Para não-admin, buscar apenas sessão do dia atual
                                $isAdmin = $user->isAdmin();
                                
                                if ($isAdmin) {
                                    // Admin pode ver qualquer sessão aberta (incluindo retroativas)
                                    $cashSession = \App\Models\Finance\CashSession::where('store_id', $storeId)
                                        ->where('opened_by', $user->id)
                                        ->where('status', 'open')
                                        ->orderBy('opened_at', 'desc')
                                        ->first();
                                } else {
                                    // Não-admin: buscar apenas sessão do dia atual
                                    $cashSession = $cashboxService->getOpenSession($storeId, $user->id);
                                }
                            } else {
                                $cashSession = null;
                            }
                        } catch (\Exception $e) {
                            Log::warning('Erro ao consultar CashSession: ' . $e->getMessage());
                        } catch (\Throwable $e) {
                            Log::warning('Erro fatal ao consultar CashSession: ' . $e->getMessage());
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Erro ao verificar tabelas para sessão de caixa: ' . $e->getMessage());
                } catch (\Throwable $e) {
                    Log::warning('Erro fatal ao verificar sessão de caixa: ' . $e->getMessage());
                }
            }

        } catch (\Throwable $e) {
            // Capturar qualquer erro, incluindo erros fatais
            Log::error('Erro ao carregar PDV: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            $error = 'Erro ao carregar PDV: ' . $e->getMessage();
        }
        
        // Buscar lojas para seleção (gerente só vê sua loja)
        $stores = collect([]);
        try {
            if (Schema::hasTable('stores')) {
                // Se for gerente, mostrar apenas sua loja
                if ($user->isGerente() && $user->store_id) {
                    $stores = Store::where('id', $user->store_id)->where('active', true)->orderBy('code')->get();
                } else {
                    $stores = Store::where('active', true)->orderBy('code')->get();
                }
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao buscar lojas no PDV: ' . $e->getMessage());
        }
        
        // Buscar origens ativas para o select
        $sources = collect([]);
        try {
            if (Schema::hasTable('client_sources')) {
                $sources = \App\Models\ClientSource::where('is_active', true)->orderBy('name')->get();
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao buscar origens no PDV: ' . $e->getMessage());
        }
        
        // Sempre tentar renderizar a view, mesmo com erros
        try {
            return view('pdv.index', compact('store', 'stores', 'cashSession', 'error', 'sources'));
        } catch (\Throwable $viewError) {
            // Se até a view falhar, retornar resposta simples
            Log::error('Erro ao renderizar view do PDV: ' . $viewError->getMessage(), [
                'trace' => $viewError->getTraceAsString()
            ]);
            return response('Erro ao carregar PDV. Verifique os logs em storage/logs/laravel.log para mais detalhes.', 500);
        }
    }

    /**
     * Busca produto por código/ref/nome
     */
    public function scan(Request $request)
    {
        try {
            $search = $request->input('search');
            
            if (!$search) {
                return response()->json(['error' => 'Código ou nome do produto é obrigatório'], 400);
            }
            
            $user = auth()->user();
            // Usar store_id da sessão ou do usuário
            $storeId = $request->input('store_id') ?? session('pdv_store_id') ?? $user->store_id ?? null;
            
            // Salvar store_id na sessão se fornecido
            if ($request->input('store_id')) {
                session(['pdv_store_id' => $request->input('store_id')]);
            }
            
            $product = Product::where('archived', false)
                ->where(function($q) use ($search) {
                    $q->where('ref', $search)
                      ->orWhere('ean13', $search)
                      ->orWhere('name', 'like', "%{$search}%");
                })
                ->first();

            if (!$product) {
                return response()->json(['error' => 'Produto não encontrado'], 404);
            }

            $price = 0;
            $stock = 0;
            
            if ($storeId && Schema::hasTable('product_prices')) {
                try {
                    $priceRecord = $product->prices()->where('store_id', $storeId)->first();
                    $price = $priceRecord?->price ?? 0;
                } catch (\Exception $e) {
                    Log::warning('Erro ao buscar preço do produto: ' . $e->getMessage());
                }
            }
            
            if ($storeId && Schema::hasTable('product_stocks')) {
                try {
                    $stockRecord = $product->stocks()->where('store_id', $storeId)->first();
                    $stock = $stockRecord?->qty ?? 0;
                } catch (\Exception $e) {
                    Log::warning('Erro ao buscar estoque do produto: ' . $e->getMessage());
                }
            }
            
            return response()->json([
                'id' => $product->id,
                'name' => $product->name,
                'ref' => $product->ref,
                'price' => $price,
                'stock' => $stock,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro no scan do PDV: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao buscar produto: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Finaliza venda (checkout)
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'customer_id' => 'nullable|exists:clients,id',
            'service_order_id' => 'nullable|exists:service_orders,id',
            'payments' => 'required|array|min:1',
            'payments.*.method' => 'required|in:money,pix,card_credit,card_debit,boleto,boleto_installment,sinal,other',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.account_id' => 'nullable|exists:accounts,id',
            'payments.*.installments' => 'nullable|integer|min:1',
            'payments.*.balance' => 'nullable|numeric|min:0', // Saldo para pagamento sinal
            'payments.*.sinal_method' => 'nullable|string|in:money,pix,card_credit,card_debit', // Método de pagamento do sinal
            'create_os' => 'nullable|boolean', // Flag para criar OS automaticamente
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $user = auth()->user();
                // Usar store_id da sessão ou do usuário
                $storeId = session('pdv_store_id') ?? $user->store_id ?? 1; // Fallback
                $companyId = \App\Helpers\CompanyHelper::getCompanyId($user); // Fallback
                
                // Buscar loja com tratamento de erro
                $store = null;
                try {
                    if (Schema::hasTable('stores')) {
                        $store = Store::find($storeId);
                    }
                } catch (\Exception $e) {
                    Log::warning('Erro ao buscar loja no checkout: ' . $e->getMessage());
                }
                
                // Se não encontrou loja, criar uma temporária ou usar fallback
                if (!$store) {
                    // Usar valores padrão se não houver loja
                    $storeId = 1;
                }
            
            // Calcular totais
            $totalGross = 0;
            $totalDiscount = 0;
            
            foreach ($validated['items'] as $item) {
                $subtotal = ($item['price'] * $item['qty']) - ($item['discount'] ?? 0);
                $totalGross += $item['price'] * $item['qty'];
                $totalDiscount += $item['discount'] ?? 0;
            }
            
            $totalNet = $totalGross - $totalDiscount;
            
            // Verificar se há pagamento "sinal" nos pagamentos
            $hasSinalPayment = false;
            $sinalPaymentData = null;
            foreach ($validated['payments'] as $payment) {
                if (isset($payment['method']) && $payment['method'] === 'sinal') {
                    $hasSinalPayment = true;
                    $sinalPaymentData = $payment;
                    break;
                }
            }
            
            // Validar soma dos pagamentos
            $paymentsTotal = array_sum(array_column($validated['payments'], 'amount'));
            
            if ($hasSinalPayment && $sinalPaymentData) {
                // Quando há sinal, validar que:
                // 1. Não há outros pagamentos além do sinal
                $otherPayments = array_filter($validated['payments'], function($p) {
                    return !isset($p['method']) || $p['method'] !== 'sinal';
                });
                
                if (count($otherPayments) > 0) {
                    return response()->json([
                        'error' => 'Quando há pagamento por Sinal, não é possível adicionar outros métodos de pagamento.'
                    ], 422);
                }
                
                // 2. O valor do sinal + saldo = total da venda
                $sinalAmount = $sinalPaymentData['amount'] ?? 0;
                $sinalBalance = $sinalPaymentData['balance'] ?? ($totalNet - $sinalAmount);
                $sinalTotal = $sinalAmount + $sinalBalance;
                
                if (abs($sinalTotal - $totalNet) > 0.01) {
                    return response()->json([
                        'error' => 'O valor do sinal + saldo não confere com o total da venda.'
                    ], 422);
                }
                
                // 3. A soma dos pagamentos deve ser igual ao valor do sinal (não ao total)
                if (abs($paymentsTotal - $sinalAmount) > 0.01) {
                    return response()->json([
                        'error' => 'A soma dos pagamentos não confere com o valor do sinal.'
                    ], 422);
                }
            } else {
                // Quando não há sinal, validar que a soma dos pagamentos = total da venda
                if (abs($paymentsTotal - $totalNet) > 0.01) {
                    return response()->json([
                        'error' => 'A soma dos pagamentos não confere com o total da venda.'
                    ], 422);
                }
            }

            // Criar venda
            $saleNumber = $store ? $this->generateSaleNumber($store->id) : $this->generateSaleNumber($storeId);
            
            // Obter data de trabalho (para lançamentos retroativos)
            $workDate = \App\Helpers\WorkDateHelper::getWorkDate();
            
            $sale = Sale::create([
                'company_id' => $companyId,
                'store_id' => $storeId,
                'customer_id' => $validated['customer_id'] ?? null,
                'service_order_id' => $validated['service_order_id'] ?? null,
                'sale_number' => $saleNumber,
                'sale_date' => $workDate,
                'total_gross' => $totalGross,
                'total_discount' => $totalDiscount,
                'total_net' => $totalNet,
                'status' => 'completed',
            ]);
            
            // Criar itens
            foreach ($validated['items'] as $itemData) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $itemData['product_id'],
                    'qty' => $itemData['qty'],
                    'unit_price' => $itemData['price'],
                    'discount' => $itemData['discount'] ?? 0,
                    'subtotal' => ($itemData['price'] * $itemData['qty']) - ($itemData['discount'] ?? 0),
                ]);
            }

            // Verificar se há OS vinculada ou se precisa criar uma nova
            $os = null;
            if (!empty($validated['service_order_id'])) {
                try {
                    $os = ServiceOrder::find($validated['service_order_id']);
                } catch (\Exception $e) {
                    Log::warning('Erro ao buscar OS: ' . $e->getMessage());
                }
            }
            
            // Se não houver OS vinculada e create_os for true, criar OS automaticamente
            if (!$os && !empty($validated['create_os'])) {
                try {
                    $osService = app(\App\Services\OsService::class);
                    
                    // Verificar se há produto "Conserto" nos itens
                    $hasConserto = false;
                    foreach ($validated['items'] as $itemData) {
                        $product = \App\Models\Product::with('productType')->find($itemData['product_id']);
                        if ($product && $product->productType && 
                            stripos($product->productType->name, 'conserto') !== false) {
                            $hasConserto = true;
                            break;
                        }
                    }
                    
                    // Preparar itens para a OS (formato esperado pelo OsService)
                    $osItems = [];
                    foreach ($validated['items'] as $itemData) {
                        $product = \App\Models\Product::find($itemData['product_id']);
                        $osItems[] = [
                            'product_id' => $itemData['product_id'],
                            'name' => $product ? $product->name : 'Produto',
                            'ref' => $product ? $product->ref : '',
                            'qty' => $itemData['qty'],
                            'unit_price' => $itemData['price'],
                            'unit' => $product ? ($product->unit ?? 'UN') : 'UN',
                            'product_type_name' => $product && $product->productType ? $product->productType->name : null
                        ];
                    }
                    
                    // Criar OS com os dados da venda
                    $osData = [
                        'store_id' => $storeId,
                        'client_id' => $validated['customer_id'] ?? null,
                        'employee_id' => $user->employee_id ?? null,
                        'items' => $osItems,
                        'subtotal' => $totalGross,
                        'discount_value' => $totalDiscount,
                        'total_value' => $totalNet,
                        'status' => 'REGISTRADA',
                        'prescription' => [
                            'use_custom' => false
                        ]
                    ];
                    
                    $os = $osService->create($osData);
                    
                    // Vincular OS à venda
                    $sale->update(['service_order_id' => $os->id]);
                    
                    Log::info('OS criada automaticamente no checkout', [
                        'os_id' => $os->id,
                        'os_number' => $os->os_number,
                        'sale_id' => $sale->id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Erro ao criar OS automaticamente: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Continuar sem OS se houver erro
                }
            }
            
            // Preparar pagamentos - verificar se há sinal nos pagamentos
            $paymentsToProcess = $validated['payments'];
            
            // Verificar se há pagamento "sinal" nos pagamentos
            $sinalPayment = null;
            foreach ($paymentsToProcess as $payment) {
                if (isset($payment['method']) && $payment['method'] === 'sinal') {
                    $sinalPayment = $payment;
                    break;
                }
            }
            
            // Se houver sinal e OS foi criada, atualizar OS com dados do sinal
            if ($sinalPayment && $os) {
                $sinalAmount = $sinalPayment['amount'] ?? 0;
                $sinalMethod = $sinalPayment['sinal_method'] ?? 'money';
                $sinalBalance = $sinalPayment['balance'] ?? 0;
                
                if ($sinalAmount > 0) {
                    $os->update([
                        'sinal_amount' => $sinalAmount,
                        'sinal_method' => $sinalMethod
                    ]);
                }
            }

            // Baixar estoque e liquidar venda
            $saleSettlementService = $this->getSaleSettlementService();
            if ($saleSettlementService) {
                $saleSettlementService->settleSale($sale, $paymentsToProcess);
            }
            
            // Se a venda foi vinculada a uma OS, atualizar status da OS
            if ($os && $os->status !== 'ENTREGUE') {
                try {
                    $os->update(['status' => 'ENTREGUE']);
                } catch (\Exception $e) {
                    Log::warning('Erro ao atualizar status da OS: ' . $e->getMessage());
                }
            }

            // Criar cash_movements se houver sessão aberta
            $cashSession = null;
            if ($storeId && Schema::hasTable('cash_sessions') && class_exists(CashSession::class)) {
                try {
                    $cashboxService = $this->getCashboxService();
                    if ($cashboxService) {
                        // Para admin, buscar qualquer sessão aberta (incluindo retroativas)
                        // Para não-admin, buscar apenas sessão do dia atual
                        $isAdmin = $user->isAdmin();
                        
                        if ($isAdmin) {
                            // Admin pode usar qualquer sessão aberta da loja (incluindo retroativas)
                            // Buscar qualquer sessão aberta da loja, não apenas do usuário
                            $cashSession = \App\Models\Finance\CashSession::where('store_id', $storeId)
                                ->where('status', 'open')
                                ->orderBy('opened_at', 'desc')
                                ->first();
                            
                            // Se não encontrou, tentar buscar apenas do usuário (fallback)
                            if (!$cashSession) {
                                $cashSession = \App\Models\Finance\CashSession::where('store_id', $storeId)
                                    ->where('opened_by', $user->id)
                                    ->where('status', 'open')
                                    ->orderBy('opened_at', 'desc')
                                    ->first();
                            }
                        } else {
                            // Não-admin: buscar apenas sessão do dia atual
                            $cashSession = $cashboxService->getOpenSession($storeId, $user->id);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Erro ao buscar sessão de caixa no checkout: ' . $e->getMessage());
                }
            }

            if ($cashSession) {
                Log::info('Sessão de caixa encontrada para registrar movimentos', [
                    'cash_session_id' => $cashSession->id,
                    'store_id' => $storeId,
                    'sale_id' => $sale->id,
                    'payments_count' => count($validated['payments'])
                ]);
                
                $cashboxService = $this->getCashboxService();
                if ($cashboxService) {
                    foreach ($validated['payments'] as $payment) {
                        $method = $payment['method'];
                        $amount = $payment['amount'];
                        
                        // Se for pagamento "sinal", usar o método de pagamento do sinal
                        if ($method === 'sinal' && isset($payment['sinal_method'])) {
                            $method = $payment['sinal_method'];
                            Log::info('Pagamento sinal detectado', [
                                'sinal_method' => $method,
                                'sinal_amount' => $amount,
                                'balance' => $payment['balance'] ?? 0
                            ]);
                        }
                        
                        // Apenas dinheiro e PIX entram no caixa físico
                        if (in_array($method, ['money', 'pix'])) {
                            // Validar que o valor é maior que zero
                            if ($amount > 0) {
                                try {
                                    // Verificar se a sessão ainda está aberta antes de criar movimento
                                    $cashSession->refresh();
                                    if ($cashSession->status !== 'open') {
                                        Log::warning('Sessão de caixa não está mais aberta', [
                                            'cash_session_id' => $cashSession->id,
                                            'status' => $cashSession->status,
                                            'sale_id' => $sale->id
                                        ]);
                                        continue;
                                    }
                                    
                                    $movement = $cashboxService->recordMovement(
                                        $cashSession,
                                        'in',
                                        $method,
                                        $amount,
                                        null, // categoryId
                                        'sale', // originType
                                        $sale->id, // originId
                                        "Venda #{$sale->sale_number}" . ($payment['method'] === 'sinal' ? ' (Sinal)' : '') // note
                                    );
                                    
                                    // Verificar se o movimento foi criado
                                    if (!$movement || !$movement->id) {
                                        Log::error('Movimento de caixa não foi criado (retornou null ou sem ID)', [
                                            'cash_session_id' => $cashSession->id,
                                            'method' => $method,
                                            'amount' => $amount,
                                            'sale_id' => $sale->id
                                        ]);
                                    } else {
                                        Log::info('Movimento de caixa criado com sucesso', [
                                            'movement_id' => $movement->id,
                                            'cash_session_id' => $cashSession->id,
                                            'type' => 'in',
                                            'method' => $method,
                                            'amount' => $amount,
                                            'sale_id' => $sale->id,
                                            'sale_number' => $sale->sale_number
                                        ]);
                                    }
                                } catch (\Exception $e) {
                                    Log::error('Erro ao criar movimento de caixa', [
                                        'error' => $e->getMessage(),
                                        'cash_session_id' => $cashSession->id,
                                        'method' => $method,
                                        'amount' => $amount,
                                        'sale_id' => $sale->id,
                                        'file' => $e->getFile(),
                                        'line' => $e->getLine(),
                                        'trace' => $e->getTraceAsString()
                                    ]);
                                }
                            } else {
                                Log::warning('Tentativa de criar movimento com valor zero ou negativo', [
                                    'method' => $method,
                                    'amount' => $amount,
                                    'cash_session_id' => $cashSession->id,
                                    'sale_id' => $sale->id
                                ]);
                            }
                        } else {
                            Log::debug('Pagamento não registrado no caixa físico', [
                                'method' => $method,
                                'amount' => $amount,
                                'reason' => 'Método não é money ou pix'
                            ]);
                        }
                    }
                } else {
                    Log::warning('CashboxService não disponível para criar movimentos', [
                        'cash_session_id' => $cashSession->id,
                        'sale_id' => $sale->id
                    ]);
                }
            } else {
                Log::warning('Nenhuma sessão de caixa encontrada para registrar movimentos', [
                    'store_id' => $storeId,
                    'user_id' => $user->id,
                    'sale_id' => $sale->id
                ]);
            }

            return response()->json([
                'success' => true,
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'total' => $totalNet,
            ]);
        });
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao processar venda: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gera número da venda
     */
    protected function generateSaleNumber(int $storeId): string
    {
        $storeCode = 'ST'; // Fallback padrão
        
        try {
            if (Schema::hasTable('stores')) {
                $store = Store::find($storeId);
                if ($store && $store->code) {
                    $storeCode = $store->code;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao buscar código da loja para gerar número da venda: ' . $e->getMessage());
        }
        
        $year = date('Y');
        
        try {
            $count = Sale::where('store_id', $storeId)
                ->whereYear('sale_date', $year)
                ->count() + 1;
        } catch (\Exception $e) {
            Log::warning('Erro ao contar vendas: ' . $e->getMessage());
            $count = 1; // Fallback
        }
        
        return strtoupper($storeCode) . '-V-' . $year . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Definir loja para o PDV
     */
    public function setStore(Request $request)
    {
        $storeId = $request->input('store_id');
        
        if (empty($storeId)) {
            // Limpar a sessão se store_id estiver vazio
            session()->forget('pdv_store_id');
            return response()->json(['success' => true, 'message' => 'Loja desmarcada']);
        }
        
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id'
        ]);

        // Salvar na sessão
        session(['pdv_store_id' => $validated['store_id']]);
        
        // Garantir que a sessão foi salva
        session()->save();

        return response()->json([
            'success' => true, 
            'store_id' => $validated['store_id'],
            'message' => 'Loja selecionada com sucesso'
        ]);
    }

    /**
     * Abrir caixa
     */
    public function openCash(Request $request)
    {
        try {
            // Log da requisição recebida
            Log::info('Tentativa de abrir caixa', [
                'request_data' => $request->all(),
                'user_id' => auth()->id()
            ]);
            
            // Normalizar opening_amount (pode vir com vírgula)
            $openingAmount = $request->input('opening_amount');
            if (is_string($openingAmount)) {
                $openingAmount = str_replace(',', '.', $openingAmount);
            }
            $request->merge(['opening_amount' => $openingAmount]);
            
            $user = auth()->user();
            $isAdmin = $user && $user->isAdmin();
            
            $validationRules = [
                'store_id' => 'required|exists:stores,id',
                'opening_amount' => 'required|numeric|min:0',
            ];
            
            // Permitir data retroativa apenas para admin
            if ($isAdmin) {
                $validationRules['opened_at'] = 'nullable|date';
            }
            
            $validated = $request->validate($validationRules);
            
            // Garantir que opening_amount seja float
            $validated['opening_amount'] = (float) $validated['opening_amount'];
            
            Log::info('Validação passou', ['validated' => $validated]);

            // $user já foi definido acima
            if (!$user) {
                return response()->json(['error' => 'Usuário não autenticado'], 401);
            }

            $companyId = \App\Helpers\CompanyHelper::getCompanyId($user);
            
            // Preparar data de abertura (retroativa se for admin)
            $openedAt = null;
            if ($isAdmin && isset($validated['opened_at']) && !empty($validated['opened_at'])) {
                try {
                    $openedAt = \Carbon\Carbon::parse($validated['opened_at']);
                    // Validar que a data não é futura
                    if ($openedAt->isFuture()) {
                        return response()->json(['error' => 'Não é possível abrir caixa com data futura'], 422);
                    }
                } catch (\Exception $e) {
                    return response()->json(['error' => 'Data inválida: ' . $e->getMessage()], 422);
                }
            }
            
            // Buscar primeira conta ativa da empresa ou criar uma padrão
            $account = null;
            $accountsTable = 'accounts'; // Nome da tabela conforme o modelo
            
            if (Schema::hasTable($accountsTable)) {
                try {
                    // Buscar conta do tipo 'cash' ativa
                    $account = \App\Models\Finance\Account::where('company_id', $companyId)
                        ->where('type', 'cash')
                        ->where('is_active', true)
                        ->first();
                    
                    // Se não existe conta cash ativa, criar ou reativar
                    if (!$account) {
                        try {
                            // Verificar se já existe uma conta do tipo cash
                            $existingCashAccount = \App\Models\Finance\Account::where('company_id', $companyId)
                                ->where('type', 'cash')
                                ->first();
                            
                            if ($existingCashAccount) {
                                // Ativar a conta existente se estiver inativa
                                if (!$existingCashAccount->is_active) {
                                    $existingCashAccount->update(['is_active' => true]);
                                }
                                $account = $existingCashAccount;
                                Log::info('Conta financeira existente reativada para empresa ' . $companyId);
                            } else {
                                // Criar nova conta do tipo cash
                                $account = \App\Models\Finance\Account::create([
                                    'company_id' => $companyId,
                                    'name' => 'Caixa Principal',
                                    'type' => 'cash',
                                    'is_active' => true,
                                    'opening_balance' => 0,
                                ]);
                                Log::info('Conta financeira padrão criada automaticamente para empresa ' . $companyId);
                            }
                        } catch (\Exception $e) {
                            Log::error('Erro ao criar/ativar conta financeira: ' . $e->getMessage(), [
                                'trace' => $e->getTraceAsString(),
                                'company_id' => $companyId
                            ]);
                            return response()->json([
                                'error' => 'Não foi possível criar conta financeira. Verifique se a tabela accounts existe e tem as colunas necessárias. Erro: ' . $e->getMessage()
                            ], 500);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Erro ao buscar conta financeira: ' . $e->getMessage());
                    return response()->json([
                        'error' => 'Erro ao buscar conta financeira: ' . $e->getMessage()
                    ], 500);
                }
            } else {
                Log::warning('Tabela accounts não existe');
                return response()->json([
                    'error' => 'Tabela de contas financeiras não existe. Execute as migrations do módulo financeiro ou crie a tabela accounts manualmente.'
                ], 500);
            }

            if (!$account) {
                Log::error('Conta financeira não encontrada após tentativas de criação', [
                    'company_id' => $companyId
                ]);
                return response()->json([
                    'error' => 'Não foi possível obter ou criar uma conta financeira. Execute: php public/debug_cash.php para diagnosticar.'
                ], 500);
            }
            
            Log::info('Conta financeira encontrada/criada', [
                'account_id' => $account->id,
                'account_name' => $account->name,
                'account_type' => $account->type
            ]);

            // Verificar se a tabela cash_sessions existe
            if (!Schema::hasTable('cash_sessions')) {
                Log::error('Tabela cash_sessions não existe ao tentar abrir caixa');
                return response()->json([
                    'error' => 'Tabela de sessões de caixa não existe. Execute as migrations do módulo financeiro.'
                ], 500);
            }

            // Verificar se já existe sessão aberta
            try {
                $cashboxService = $this->getCashboxService();
                
                if ($isAdmin && $openedAt) {
                    // Para admin abrindo caixa retroativo, verificar se já existe sessão aberta naquela data específica
                    $sessionDateStart = $openedAt->copy()->startOfDay();
                    $sessionDateEnd = $openedAt->copy()->endOfDay();
                    
                    $existingSession = \App\Models\Finance\CashSession::where('store_id', $validated['store_id'])
                        ->where('opened_by', $user->id)
                        ->where('status', 'open')
                        ->whereBetween('opened_at', [$sessionDateStart, $sessionDateEnd])
                        ->first();
                } else {
                    // Para não-admin ou admin sem data retroativa, usar verificação normal (apenas dia atual)
                    if ($cashboxService) {
                        $existingSession = $cashboxService->getOpenSession($validated['store_id'], $user->id);
                    } else {
                        $existingSession = null;
                    }
                }
                
                if ($existingSession) {
                    return response()->json([
                        'error' => 'Já existe uma sessão de caixa aberta para esta loja' . ($isAdmin && $openedAt ? ' nesta data' : '')
                    ], 400);
                }
            } catch (\Exception $e) {
                Log::error('Erro ao verificar sessão existente: ' . $e->getMessage());
                // Continuar mesmo com erro na verificação
            }

            $cashboxService = $this->getCashboxService();
            if (!$cashboxService) {
                Log::error('CashboxService não disponível ao tentar abrir caixa');
                return response()->json([
                    'error' => 'Serviço de caixa não disponível. Verifique se as tabelas do módulo financeiro foram criadas.'
                ], 500);
            }

            try {
                Log::info('Tentando abrir sessão de caixa', [
                    'company_id' => $companyId,
                    'store_id' => $validated['store_id'],
                    'account_id' => $account->id,
                    'opening_amount' => $validated['opening_amount'],
                    'user_id' => $user->id
                ]);
                
                $session = $cashboxService->openSession(
                    $companyId,
                    $validated['store_id'],
                    $account->id,
                    $validated['opening_amount'],
                    $user,
                    $openedAt
                );

                Log::info('Sessão de caixa aberta com sucesso', ['session_id' => $session->id]);

                return response()->json([
                    'success' => true,
                    'session' => $session,
                    'message' => 'Caixa aberto com sucesso!'
                ]);
            } catch (\Exception $serviceError) {
                Log::error('Erro no CashboxService ao abrir caixa: ' . $serviceError->getMessage(), [
                    'trace' => $serviceError->getTraceAsString(),
                    'file' => $serviceError->getFile(),
                    'line' => $serviceError->getLine()
                ]);
                return response()->json([
                    'error' => 'Erro ao abrir sessão de caixa: ' . $serviceError->getMessage()
                ], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Dados inválidos: ' . implode(', ', $e->errors()['opening_amount'] ?? [])
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao abrir caixa: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'error' => 'Erro ao abrir caixa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fechar caixa
     */
    public function closeCash(Request $request)
    {
        try {
            Log::info('Tentativa de fechar caixa', [
                'request_data' => $request->all(),
                'user_id' => auth()->id()
            ]);
            
            $validated = $request->validate([
                'store_id' => 'required|exists:stores,id',
                'counted_by_method' => 'required|array',
                'counted_by_method.*.method' => 'required|in:money,pix,card,card_credit,card_debit,boleto,boleto_installment,other',
                'counted_by_method.*.amount' => 'required|numeric|min:0',
                'notes' => 'nullable|string',
            ]);
            
            // Normalizar métodos de pagamento para o formato esperado pelo CashboxService
            $normalizedMethods = [];
            foreach ($validated['counted_by_method'] as $method) {
                // Converter card_credit e card_debit para 'card' se necessário
                $normalizedMethod = $method['method'];
                if (in_array($normalizedMethod, ['card_credit', 'card_debit'])) {
                    $normalizedMethod = 'card';
                }
                $normalizedMethods[] = [
                    'method' => $normalizedMethod,
                    'amount' => $method['amount']
                ];
            }
            $validated['counted_by_method'] = $normalizedMethods;

            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Usuário não autenticado'], 401);
            }

            if (!Schema::hasTable('cash_sessions')) {
                return response()->json(['error' => 'Tabela de sessões de caixa não existe'], 500);
            }

            // Buscar sessão usando o service (que já fecha automaticamente caixas de dias anteriores)
            $cashboxService = $this->getCashboxService();
            if (!$cashboxService) {
                return response()->json(['error' => 'Serviço de caixa não disponível'], 500);
            }
            
            // Para admin, buscar qualquer sessão aberta (incluindo retroativas)
            // Para não-admin, buscar apenas sessão do dia atual
            $isAdmin = $user->isAdmin();
            
            if ($isAdmin) {
                // Admin pode fechar qualquer sessão aberta (incluindo retroativas)
                $session = \App\Models\Finance\CashSession::where('store_id', $validated['store_id'])
                    ->where('opened_by', $user->id)
                    ->where('status', 'open')
                    ->orderBy('opened_at', 'desc')
                    ->first();
            } else {
                // Não-admin: buscar apenas sessão do dia atual
                $session = $cashboxService->getOpenSession($validated['store_id'], $user->id);
            }

            if (!$session) {
                return response()->json(['error' => 'Nenhuma sessão de caixa aberta encontrada'], 404);
            }

            Log::info('Fechando sessão de caixa', [
                'session_id' => $session->id,
                'counted_by_method' => $validated['counted_by_method']
            ]);
            
            $session = $cashboxService->closeSession(
                $session,
                $validated['counted_by_method'],
                $validated['notes'] ?? null
            );

            Log::info('Caixa fechado com sucesso', [
                'session_id' => $session->id,
                'difference' => $session->getDifference()
            ]);

            return response()->json([
                'success' => true,
                'session' => $session,
                'difference' => $session->getDifference(),
                'message' => 'Caixa fechado com sucesso!'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao fechar caixa: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json(['error' => 'Erro ao fechar caixa: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Criar OS a partir do carrinho do PDV
     */
    public function createOsFromCart(Request $request)
    {
        $validated = $request->validate([
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'customer_id' => 'required|exists:clients,id',
            'delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $user = auth()->user();
                $storeId = session('pdv_store_id') ?? $user->store_id ?? 1;
                $companyId = \App\Helpers\CompanyHelper::getCompanyId($user);
                
                // Verificar se há item Conserto
                $hasConserto = false;
                if (!empty($validated['items'])) {
                    foreach ($validated['items'] as $item) {
                        $product = \App\Models\Product::with('productType')->find($item['product_id'] ?? null);
                        if ($product) {
                            $productName = strtolower($product->name ?? '');
                            $productTypeName = $product->productType ? strtolower($product->productType->name ?? '') : '';
                            if (strpos($productName, 'conserto') !== false || strpos($productTypeName, 'conserto') !== false) {
                                $hasConserto = true;
                                break;
                            }
                        }
                    }
                }
                
                // Gerar número da OS
                $osNumber = ServiceOrder::generateOsNumber($storeId, $hasConserto);
                
                // Calcular totais (pode ser zero se não houver itens)
                $subtotal = 0;
                $totalDiscount = 0;
                
                if (!empty($validated['items'])) {
                    foreach ($validated['items'] as $item) {
                        $subtotal += $item['price'] * $item['qty'];
                        $totalDiscount += $item['discount'] ?? 0;
                    }
                }
                
                $totalValue = $subtotal - $totalDiscount;
                
                // Obter data de trabalho (para lançamentos retroativos)
                $workDate = \App\Helpers\WorkDateHelper::getWorkDate();
                
                // Criar OS
                $os = ServiceOrder::create([
                    'os_number' => $osNumber,
                    'company_id' => $companyId,
                    'store_id' => $storeId,
                    'client_id' => $validated['customer_id'],
                    'employee_id' => $user->id,
                    'registered_at' => $workDate,
                    'delivery_date' => $validated['delivery_date'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'REGISTRADA',
                    'subtotal' => $subtotal,
                    'discount_value' => $totalDiscount,
                    'total_value' => $totalValue,
                    'source' => 'PDV',
                ]);
                
                // Criar itens da OS (se houver)
                if (!empty($validated['items'])) {
                    foreach ($validated['items'] as $item) {
                        ServiceOrderItem::create([
                            'service_order_id' => $os->id,
                            'product_id' => $item['product_id'],
                            'qty' => $item['qty'],
                            'unit_price' => $item['price'],
                            'discount' => $item['discount'] ?? 0,
                            'subtotal' => ($item['price'] * $item['qty']) - ($item['discount'] ?? 0),
                        ]);
                    }
                }
                
                return response()->json([
                    'success' => true,
                    'os' => $os->load(['client', 'items.product']),
                    'message' => 'OS criada com sucesso! Número: ' . $osNumber
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Erro ao criar OS do PDV: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao criar OS: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Carregar OS no PDV para converter em venda
     */
    public function loadOs(Request $request, $osId)
    {
        try {
            $os = ServiceOrder::with(['client', 'items.product'])->findOrFail($osId);
            
            // Verificar se a OS já foi convertida em venda
            if ($os->sale) {
                return response()->json([
                    'error' => 'Esta OS já foi convertida em venda.'
                ], 422);
            }
            
            // Verificar se a OS está em status que permite venda
            // Permitir carregar OS com status PRONTA, REGISTRADA ou EM_PRODUCAO (para permitir ajustes)
            if (!in_array($os->status, ['PRONTA', 'ENTREGUE', 'REGISTRADA', 'EM_PRODUCAO'])) {
                return response()->json([
                    'error' => 'Esta OS ainda não está pronta para venda. Status atual: ' . $os->status
                ], 422);
            }
            
            // Preparar dados para o PDV
            $items = $os->items->map(function ($item) {
                return [
                    'id' => $item->product_id,
                    'product_id' => $item->product_id,
                    'name' => $item->product->name ?? 'Produto não encontrado',
                    'ref' => $item->product->ref ?? 'N/A',
                    'price' => (float) $item->unit_price,
                    'qty' => (float) $item->qty,
                    'discount' => (float) ($item->discount ?? 0),
                ];
            });
            
            return response()->json([
                'success' => true,
                'os' => [
                    'id' => $os->id,
                    'os_number' => $os->os_number,
                    'client_id' => $os->client_id,
                    'client_name' => $os->client->name ?? null,
                    'total_value' => (float) $os->total_value,
                    'subtotal' => (float) $os->subtotal,
                    'discount_value' => (float) $os->discount_value,
                ],
                'items' => $items,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar OS no PDV: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao carregar OS: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Buscar OS para carregar no PDV
     */
    public function searchOs(Request $request)
    {
        try {
            $query = $request->get('q', '');
            $storeId = session('pdv_store_id') ?? auth()->user()->store_id ?? null;
            
            $osQuery = ServiceOrder::with(['client'])
                ->whereIn('status', ['PRONTA', 'REGISTRADA', 'EM_PRODUCAO'])
                ->whereDoesntHave('sale'); // Apenas OS que ainda não foram vendidas
            
            if ($storeId) {
                $osQuery->where('store_id', $storeId);
            }
            
            if (!empty($query)) {
                $osQuery->where(function ($q) use ($query) {
                    $q->where('os_number', 'like', "%{$query}%")
                      ->orWhereHas('client', function ($clientQuery) use ($query) {
                          $clientQuery->where('name', 'like', "%{$query}%")
                                     ->orWhere('cpf_cnpj', 'like', "%{$query}%");
                      });
                });
            }
            
            $osList = $osQuery->orderBy('registered_at', 'desc')
                             ->limit(20)
                             ->get()
                             ->map(function ($os) {
                                 return [
                                     'id' => $os->id,
                                     'os_number' => $os->os_number,
                                     'client_name' => $os->client->name ?? 'Sem cliente',
                                     'total_value' => (float) $os->total_value,
                                     'registered_at' => $os->registered_at->format('d/m/Y H:i'),
                                 ];
                             });
            
            return response()->json([
                'success' => true,
                'os_list' => $osList,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar OS: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao buscar OS: ' . $e->getMessage()], 500);
        }
    }
}

