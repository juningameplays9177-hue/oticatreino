<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/auth.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
        
        // Adicionar middleware global para logar requisições POST para /clients
        $middleware->web(append: [
            \App\Http\Middleware\LogAllRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Log de todas as exceções para debug
        $exceptions->report(function (\Throwable $e) {
            if (app()->bound('log')) {
                \Illuminate\Support\Facades\Log::error('Exceção capturada: ' . $e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        });
        
        // Tratamento específico para 403
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, $request) {
            if (app()->bound('log')) {
                \Illuminate\Support\Facades\Log::error('403 Access Denied', [
                    'url' => $request->fullUrl(),
                    'user' => auth()->user()?->id,
                    'message' => $e->getMessage(),
                ]);
            }
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Acesso negado',
                ], 403);
            }
            
            return response()->view('errors.403', ['message' => $e->getMessage()], 403);
        });
    })->create();
