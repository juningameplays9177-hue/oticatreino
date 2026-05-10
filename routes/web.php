<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Finance;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(route('login'));
});

// Rota de teste para verificar se o Laravel está funcionando
Route::get('/test-laravel', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Laravel está funcionando corretamente',
        'auth' => auth()->check() ? 'autenticado' : 'não autenticado',
        'user' => auth()->user() ? auth()->user()->name : null,
    ]);
});

// Rotas de teste para diagnosticar problemas de 403
Route::middleware('auth')->group(function () {
    Route::get('/test-stock', function () {
        return response()->json([
            'status' => 'ok',
            'route' => 'stock',
            'user' => auth()->user()->name,
            'can_access' => true,
        ]);
    });
    
    // Rota de diagnóstico para clientes
    Route::get('/test-clients-diagnostic', [\App\Http\Controllers\ClientDiagnosticController::class, 'test']);
    
    // Rota para testar criação de cliente diretamente
    Route::get('/test-create-client', function() {
        try {
            \Illuminate\Support\Facades\DB::beginTransaction();
            
            $client = \App\Models\Client::create([
                'name' => 'TESTE AUTOMÁTICO ' . date('Y-m-d H:i:s'),
                'active' => true,
            ]);
            
            \Illuminate\Support\Facades\DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Cliente criado com sucesso!',
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'code' => $client->code ?? 'N/A',
                ]
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    });
    
    Route::get('/test-receivables', function () {
        return response()->json([
            'status' => 'ok',
            'route' => 'receivables',
            'user' => auth()->user()->name,
            'can_access' => true,
        ]);
    });
    
    Route::get('/test-os', function () {
        return response()->json([
            'status' => 'ok',
            'route' => 'os',
            'user' => auth()->user()->name,
            'can_access' => true,
        ]);
    });
    
    Route::get('/test-clients', function () {
        return response()->json([
            'status' => 'ok',
            'route' => 'clients',
            'user' => auth()->user()->name,
            'can_access' => true,
            'clients_route_exists' => Route::has('clients.index'),
        ]);
    });
    
    // Rota de teste específica para verificar acesso a /clients
    Route::get('/clients-test', function () {
        try {
            $controller = new \App\Http\Controllers\ClientsController();
            return response()->json([
                'status' => 'ok',
                'message' => 'Controller acessível',
                'user' => auth()->user()->name,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    });
    
    
    Route::get('/test-products', function () {
        return response()->json([
            'status' => 'ok',
            'route' => 'products',
            'user' => auth()->user()->name,
            'can_access' => true,
        ]);
    });
});

// PWA Routes
Route::get('/manifest.json', [\App\Http\Controllers\PwaController::class, 'manifest'])->name('manifest');
Route::get('/sw.js', [\App\Http\Controllers\PwaController::class, 'serviceWorker'])->name('service-worker');

// Dashboard
Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

// ✅ Seleção de loja via GET (sem CSRF) - APENAS GET
Route::match(['get'], '/dashboard/select-store', [\App\Http\Controllers\DashboardController::class, 'selectStore'])
    ->middleware(['auth'])
    ->name('dashboard.selectStore');

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rota para atualizar data de trabalho
    Route::put('/work-date', [\App\Http\Controllers\WorkDateController::class, 'update'])->name('work-date.update');

    // Rotas de gerenciamento de usuários (apenas admin)
    Route::middleware('admin')->group(function () {
        Route::resource('users', \App\Http\Controllers\UserController::class);
    });

    // Rotas de gerenciamento de clientes
    // IMPORTANTE: Rotas específicas devem vir ANTES das rotas com parâmetros
    
    // SOLUÇÃO TEMPORÁRIA: Usar rota alternativa devido a problema com LiteSpeed bloqueando /clients/
    // Rota principal com alias para contornar bloqueio do servidor
    Route::get('clientes', [\App\Http\Controllers\ClientsController::class, 'index'])->name('clients.index');
    Route::get('clients', [\App\Http\Controllers\ClientsController::class, 'index']); // Manter para compatibilidade
    
    Route::get('clients/importar', [\App\Http\Controllers\ClientsImportController::class, 'show'])->name('clients.import.show');
    Route::post('clients/importar', [\App\Http\Controllers\ClientsImportController::class, 'run'])->name('clients.import.run')->middleware('throttle:10,1');
    Route::get('clients/create', [\App\Http\Controllers\ClientsController::class, 'create'])->name('clients.create');
    
    // Rota de teste para verificar se POST está funcionando
    Route::post('clients/test', function(\Illuminate\Http\Request $request) {
        // Log DIRETO no arquivo
        $logFile = storage_path('logs/laravel.log');
        $logMessage = date('Y-m-d H:i:s') . " - [TESTE ROTA] POST /clients/test - Name: " . ($request->input('name') ?? 'VAZIO') . "\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        \Log::info('TESTE: Rota POST /clients/test chamada', [
            'all_data' => $request->all(),
            'user_id' => auth()->id(),
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Rota POST funcionando',
            'data' => $request->all(),
        ]);
    });
    
    // Rota principal de criação de clientes
    Route::post('clients', [\App\Http\Controllers\ClientsController::class, 'store'])->name('clients.store');
    Route::get('clients/{client}', [\App\Http\Controllers\ClientsController::class, 'show'])->name('clients.show');
    Route::get('clients/{client}/edit', [\App\Http\Controllers\ClientsController::class, 'edit'])->name('clients.edit');
    Route::match(['put', 'patch'], 'clients/{client}', [\App\Http\Controllers\ClientsController::class, 'update'])->name('clients.update');
    Route::delete('clients/{client}', [\App\Http\Controllers\ClientsController::class, 'destroy'])->name('clients.destroy');

    // Rotas de gerenciamento de produtos
    Route::resource('products', \App\Http\Controllers\ProductsController::class);
    Route::get('products/importar', [\App\Http\Controllers\ProductsImportController::class, 'show'])->name('products.import.show');
    Route::post('products/importar', [\App\Http\Controllers\ProductsImportController::class, 'run'])->name('products.import.run')->middleware('throttle:10,1');
    Route::post('products/{product}/imagens', [\App\Http\Controllers\ProductImagesController::class, 'store'])->name('products.images.store');
    Route::delete('products/imagens/{productImage}', [\App\Http\Controllers\ProductImagesController::class, 'destroy'])->name('products.images.destroy');

    // API para subgrupos (CORRIGIDO)
    Route::get('api/subgroups/{group}', function ($group) {
        $subgroups = \App\Models\ProductSubgroup::where('group_id', $group)->orderBy('name')->get();
        return response()->json($subgroups);
    })->name('api.subgroups');

    // API para próximo código de produto
    Route::get('api/products/next-code/{typeId}', function ($typeId) {
        try {
            $code = \App\Models\Product::generateRef($typeId);
            return response()->json(['code' => $code]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    })->name('api.products.nextCode');

    // Rotas de Ordens de Serviço
    Route::get('os/buscar-produto', [\App\Http\Controllers\ProductsLookupController::class, 'index'])->name('os.products.lookup');
    Route::get('os/buscar-cliente', [\App\Http\Controllers\ClientsLookupController::class, 'index'])->name('os.clients.lookup');
    Route::get('os/gerar-numero', [\App\Http\Controllers\OsController::class, 'generateNumber'])->name('os.generate-number');
    Route::get('os/medicos', [\App\Http\Controllers\OsController::class, 'doctors'])->name('os.doctors');
    Route::get('os/{o}/imprimir', [\App\Http\Controllers\OsPrintController::class, 'show'])->name('os.print');
    Route::post('os/{o}/status', [\App\Http\Controllers\OsStatusController::class, 'update'])->name('os.status.update');
    Route::post('os/{o}/imagens', [\App\Http\Controllers\OsImageController::class, 'store'])->name('os.images.store');
    Route::delete('os/imagens/{osImage}', [\App\Http\Controllers\OsImageController::class, 'destroy'])->name('os.images.destroy');
    Route::resource('os', \App\Http\Controllers\OsController::class)->parameters(['os' => 'o']);

    // Rotas de Cadastros
    Route::prefix('cadastros')->name('cadastros.')->group(function () {

        Route::middleware('admin')->group(function () {
            Route::resource('companies', \App\Http\Controllers\Cadastros\CompanyController::class)->parameters(['companies' => 'id']);
            Route::patch('companies/{id}/ativar', [\App\Http\Controllers\Cadastros\CompanyController::class, 'toggleActive'])->name('companies.toggle');
        });

        Route::resource('employees', \App\Http\Controllers\Cadastros\EmployeeController::class)->parameters(['employees' => 'employee']);
        Route::resource('suppliers', \App\Http\Controllers\Cadastros\SupplierController::class)->parameters(['suppliers' => 'supplier']);
        Route::resource('brands', \App\Http\Controllers\Cadastros\BrandController::class)->parameters(['brands' => 'brand']);
        Route::resource('product-groups', \App\Http\Controllers\Cadastros\ProductGroupController::class)->parameters(['product-groups' => 'productGroup']);
        Route::resource('product-subgroups', \App\Http\Controllers\Cadastros\ProductSubgroupController::class)->parameters(['product-subgroups' => 'productSubgroup']);

        Route::post('brands/ajax', [\App\Http\Controllers\Cadastros\BrandController::class, 'storeAjax'])->name('brands.storeAjax');
        Route::post('product-groups/ajax', [\App\Http\Controllers\Cadastros\ProductGroupController::class, 'storeAjax'])->name('product-groups.storeAjax');
        Route::post('product-subgroups/ajax', [\App\Http\Controllers\Cadastros\ProductSubgroupController::class, 'storeAjax'])->name('product-subgroups.storeAjax');
        Route::post('suppliers/ajax', [\App\Http\Controllers\Cadastros\SupplierController::class, 'storeAjax'])->name('suppliers.storeAjax');
        Route::post('product-colors/ajax', [\App\Http\Controllers\Cadastros\ProductColorController::class, 'storeAjax'])->name('product-colors.storeAjax');
        Route::post('product-sizes/ajax', [\App\Http\Controllers\Cadastros\ProductSizeController::class, 'storeAjax'])->name('product-sizes.storeAjax');
        Route::post('product-shapes/ajax', [\App\Http\Controllers\Cadastros\ProductShapeController::class, 'storeAjax'])->name('product-shapes.storeAjax');
        Route::post('product-types/ajax', [\App\Http\Controllers\Cadastros\ProductTypeController::class, 'storeAjax'])->name('product-types.storeAjax');

        Route::post('client-sources/ajax', [\App\Http\Controllers\Cadastros\TablesController::class, 'storeClientSource'])->name('client-sources.storeAjax');
    });

    // Rotas do Módulo Financeiro
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::resource('accounts', Finance\AccountController::class)->only(['index','store','update']);
        Route::resource('categories', Finance\CategoryController::class)->only(['index','store','update']);
        Route::resource('cost-centers', Finance\CostCenterController::class)->only(['index','store','update']);

        Route::resource('receivables', Finance\ReceivableController::class)->only(['index','create','store','show']);
        Route::post('receivables/{id}/receive', [Finance\ReceivableController::class,'receive'])->name('receivables.receive');
        Route::post('receivables/{id}/cancel', [Finance\ReceivableController::class,'cancel'])->name('receivables.cancel');
        Route::get('receivables/{receivable}/pdf', [Finance\ReceivableController::class,'pdf'])->name('receivables.pdf');

        Route::resource('payables', Finance\PayableController::class)->only(['index','create','store','show']);
        Route::post('payables/{id}/pay', [Finance\PayableController::class,'pay'])->name('payables.pay');
        Route::post('payables/{id}/cancel', [Finance\PayableController::class,'cancel'])->name('payables.cancel');

        Route::get('reconcile', [Finance\ReconcileController::class,'index'])->name('reconcile.index');
        Route::post('reconcile/import', [Finance\ReconcileController::class,'import'])->name('reconcile.import');
        Route::post('reconcile/{recon}/match', [Finance\ReconcileController::class,'match'])->name('reconcile.match');

        Route::get('reports/cashflow', [Finance\ReportController::class,'cashflow'])->name('reports.cashflow');
        Route::get('reports/dre', [Finance\ReportController::class,'dre'])->name('reports.dre');
        Route::get('reports/pdv', [Finance\ReportController::class,'pdv'])->name('reports.pdv');
    });

    // Rotas de Estoque
    Route::get('stock', [\App\Http\Controllers\StockController::class, 'index'])->name('stock.index');
    Route::put('stock/products/{product}', [\App\Http\Controllers\StockController::class, 'update'])->name('stock.update');
    Route::post('stock/products/{product}/adjust', [\App\Http\Controllers\StockController::class, 'adjust'])->name('stock.adjust');

    // Rota temporária para limpar lentes duplicadas
    Route::get('clean-lenses', [\App\Http\Controllers\CleanLensesController::class, 'clean'])->name('clean.lenses');
    Route::post('clean-lenses/confirm', [\App\Http\Controllers\CleanLensesController::class, 'confirm'])->name('clean.lenses.confirm');
});

require __DIR__.'/auth.php';
