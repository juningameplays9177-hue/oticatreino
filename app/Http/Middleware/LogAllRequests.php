<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAllRequests
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log apenas requisições POST para /clients usando o sistema de logs do Laravel
        if (($request->is('clients') || $request->is('clients/*')) && $request->isMethod('POST')) {
            try {
                \Log::info('🔍 LogAllRequests: Requisição POST para /clients detectada', [
                    'url' => $request->fullUrl(),
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()?->name,
                    'has_csrf' => $request->has('_token'),
                    'csrf_token' => $request->input('_token') ? 'present' : 'missing',
                    'data_keys' => array_keys($request->except(['password', '_token'])),
                    'name' => $request->input('name'),
                ]);
            } catch (\Exception $e) {
                // Ignorar erros de log para não quebrar a aplicação
            }
        }
        
        $response = $next($request);
        
        // Log a resposta também
        if (($request->is('clients') || $request->is('clients/*')) && $request->isMethod('POST')) {
            try {
                \Log::info('🔍 LogAllRequests: Resposta para POST /clients', [
                    'status' => $response->getStatusCode(),
                    'is_redirect' => $response->isRedirection(),
                    'redirect_url' => $response->isRedirection() ? $response->getTargetUrl() : null,
                ]);
            } catch (\Exception $e) {
                // Ignorar erros de log para não quebrar a aplicação
            }
        }
        
        return $response;
    }
}
