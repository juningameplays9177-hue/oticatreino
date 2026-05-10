<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configurar timezone padrão
        date_default_timezone_set('America/Sao_Paulo');
        \Carbon\Carbon::setLocale('pt_BR');
        
        // HTTPS será forçado via .htaccess
        // Não forçar aqui para evitar conflitos com certificado SSL
        // if ($this->app->environment('production')) {
        //     URL::forceScheme('https');
        // }
        
        // Configurar route model binding para rotas com hífens
        Route::bind('productGroup', function ($value) {
            return \App\Models\ProductGroup::findOrFail($value);
        });
        
        Route::bind('productSubgroup', function ($value) {
            return \App\Models\ProductSubgroup::findOrFail($value);
        });
    }
}

