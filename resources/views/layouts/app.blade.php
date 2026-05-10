<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#2563eb">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="HpOculos">
        <meta name="description" content="Sistema de gestão para óticas">
        
        <!-- Meta tags para prevenir cache -->
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
        <meta http-equiv="Pragma" content="no-cache">
        <meta http-equiv="Expires" content="0">

        <title>{{ !empty($title) ? $title . ' - ' : '' }}{{ config('app.name', 'Hospital dos Óculos') }}</title>
        
        <!-- PWA Manifest -->
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        
        <!-- Favicon -->
        <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>👓</text></svg>">
        
        <!-- Apple Touch Icons (removido - ícones não existem) -->

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
        
        <style>
            /* Garantir que SVGs não fiquem enormes */
            svg {
                max-width: 100%;
                height: auto;
            }
            /* Limitar tamanho máximo de ícones SVG */
            svg.w-5,
            svg.h-5 {
                width: 1.25rem;
                height: 1.25rem;
                max-width: 1.25rem;
                max-height: 1.25rem;
            }
            svg.w-6,
            svg.h-6 {
                width: 1.5rem;
                height: 1.5rem;
                max-width: 1.5rem;
                max-height: 1.5rem;
            }
        </style>
        
        <!-- Page Styles (after Vite to override if needed) -->
        @stack('styles')
    </head>
    <body class="font-sans antialiased bg-slate-50">
        <div class="min-h-screen flex">
            <!-- Sidebar -->
            <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-white border-r border-slate-200 shadow-sm">
                <!-- Logo -->
                <div class="flex items-center h-16 px-6 border-b border-slate-200 bg-gradient-to-r from-blue-50 to-white">
                    <h1 class="text-xl font-bold bg-gradient-to-r from-blue-600 to-blue-700 bg-clip-text text-transparent">
                        {{ config('app.name', 'Hospital dos Óculos') }}
                    </h1>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                    <!-- Dashboard -->
                    <a href="{{ route('dashboard') }}" 
                       class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'text-slate-700 hover:bg-slate-50' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Dashboard
                    </a>

                    @auth
                        @if(auth()->user()->isAdmin() ?? false)
                            <!-- Usuários -->
                            <a href="{{ route('users.index') }}" 
                               class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('users.*') ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'text-slate-700 hover:bg-slate-50' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                Usuários
                            </a>
                        @endif
                    @endauth

                    <!-- Clientes -->
                    <a href="{{ route('clients.index') }}" 
                       class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('clients.*') ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'text-slate-700 hover:bg-slate-50' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Clientes
                    </a>

                    <!-- Produtos -->
                    <a href="{{ route('products.index') }}" 
                       class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('products.*') ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'text-slate-700 hover:bg-slate-50' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        Produtos
                    </a>

                    @php
                        try {
                            // Verificar se existe qualquer rota de finance (usar receivables que sempre existe)
                            $hasFinanceRoute = \Illuminate\Support\Facades\Route::has('finance.receivables.index');
                        } catch (\Exception $e) {
                            $hasFinanceRoute = false;
                        }
                        $hasOsRoute = \Illuminate\Support\Facades\Route::has('os.index');
                        $hasStockRoute = \Illuminate\Support\Facades\Route::has('stock.index');
                    @endphp

                    @if($hasOsRoute)
                        <!-- O.S. -->
                        <a href="{{ route('os.create') }}" 
                           class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('os.*') ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'text-slate-700 hover:bg-slate-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            O.S.
                        </a>
                    @endif
                    
                    <!-- Estoque -->
                    @if($hasStockRoute)
                    <a href="{{ route('stock.index') }}" 
                       class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('stock.*') ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'text-slate-700 hover:bg-slate-50' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        Estoque
                    </a>
                    @endif

                    @if($hasFinanceRoute)
                        <!-- Financeiro (Menu Dropdown) -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" 
                                    class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('finance.*') ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'text-slate-700 hover:bg-slate-50' }}">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Financeiro
                                </div>
                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-cloak
                                 class="mt-1 ml-4 space-y-1 border-l-2 border-slate-200 pl-4">
                                @php
                                    $hasCashRoute = false;
                                    $hasReceivablesRoute = false;
                                    $hasPayablesRoute = false;
                                    $hasReconcileRoute = false;
                                    $hasReportsRoute = false;
                                    try {
                                        $hasCashRoute = \Illuminate\Support\Facades\Route::has('finance.cash.index');
                                        $hasReceivablesRoute = \Illuminate\Support\Facades\Route::has('finance.receivables.index');
                                        $hasPayablesRoute = \Illuminate\Support\Facades\Route::has('finance.payables.index');
                                        $hasReconcileRoute = \Illuminate\Support\Facades\Route::has('finance.reconcile.index');
                                        $hasReportsRoute = \Illuminate\Support\Facades\Route::has('finance.reports.cashflow');
                                    } catch (\Exception $e) {
                                        // Ignorar erros
                                    }
                                @endphp
                                @php
                                    $isGerente = auth()->user() && auth()->user()->isGerente();
                                @endphp
                                
                                {{-- Tesouraria (Sessões de Caixa) - REMOVIDO: PDV e abertura de caixa foram removidos --}}
                                {{-- @if($hasCashRoute && !$isGerente)
                                    <a href="{{ route('finance.cash.index') }}" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('finance.cash.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">Tesouraria</a>
                                @endif --}}
                                @if($hasReceivablesRoute)
                                    <a href="{{ route('finance.receivables.index') }}" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('finance.receivables.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">Contas a Receber</a>
                                @endif
                                @if($hasPayablesRoute)
                                    <a href="{{ route('finance.payables.index') }}" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('finance.payables.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">Contas a Pagar</a>
                                @endif
                                @if($hasReconcileRoute && !$isGerente)
                                    <a href="{{ route('finance.reconcile.index') }}" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('finance.reconcile.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">Conciliação</a>
                                @endif
                                @if($hasReportsRoute && !$isGerente)
                                    <a href="{{ route('finance.reports.cashflow') }}" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('finance.reports.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">Relatórios</a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Minhas Lojas (apenas para admin) -->
                    @if(auth()->user() && auth()->user()->isAdmin())
                        <a href="{{ route('cadastros.companies.index') }}" 
                           class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('cadastros.companies.*') ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'text-slate-700 hover:bg-slate-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            Minhas Lojas
                        </a>
                    @endif

                    <!-- Perfil -->
                    <a href="{{ route('profile.edit') }}" 
                       class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('profile.*') ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'text-slate-700 hover:bg-slate-50' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Meu Perfil
                    </a>
                </nav>

                <!-- User Info -->
                <div class="px-4 py-4 border-t border-slate-200 bg-slate-50">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-sm">
                                <span class="text-white font-semibold text-sm">
                                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                                </span>
                            </div>
                        </div>
                        <div class="ml-3 flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-900 truncate">
                                {{ auth()->user()->name ?? 'Usuário' }}
                            </p>
                            <p class="text-xs mt-1">
                                @auth
                                    @if(auth()->user()->isAdmin() ?? false)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            Administrador
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Gerente
                                        </span>
                                    @endif
                                @endauth
                            </p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="flex-1 lg:ml-64">
                <!-- Top Header -->
                <header class="bg-white border-b border-slate-200 sticky top-0 z-10">
                    <div class="flex items-center justify-between min-h-[120px] py-3 px-4 sm:px-6 lg:px-8">
                        <!-- Mobile menu button -->
                        <button type="button" 
                                class="lg:hidden p-2 rounded-md text-slate-400 hover:text-slate-500 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 self-start mt-2"
                                onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>

                        <!-- Data atual - Centralizada e grande para facilitar leitura -->
                        <div class="flex-1 flex items-center justify-center">
                            <div class="text-center">
                                <div class="text-2xl sm:text-3xl font-bold text-slate-800" style="font-size: 1.75rem; line-height: 1.2;" id="current-date">
                                    <!-- Será preenchido via JavaScript -->
                                </div>
                                <div class="text-sm sm:text-base text-slate-600 mt-1" id="current-time">
                                    <!-- Será preenchido via JavaScript -->
                                </div>
                                @php
                                    $workDate = \App\Helpers\WorkDateHelper::getWorkDate();
                                    $isToday = $workDate->isToday();
                                @endphp
                                <div class="mt-3 flex items-center justify-center gap-3">
                                    <span class="text-sm font-medium text-slate-600">Data de Trabalho:</span>
                                    <button 
                                        type="button"
                                        onclick="openWorkDateModal()"
                                        class="inline-flex items-center px-4 py-2 rounded-lg text-base font-bold {{ $isToday ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' }} hover:opacity-80 transition-opacity shadow-sm"
                                        title="Clique para alterar a data de trabalho"
                                        style="font-size: 1.1rem;">
                                        {{ $workDate->format('d/m/Y') }}
                                        @if(!$isToday)
                                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        @endif
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Right side -->
                        <div class="flex items-center space-x-4 self-start mt-2">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" 
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest bg-slate-600 hover:bg-slate-700 focus:bg-slate-700 active:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                    Sair
                                </button>
                            </form>
                        </div>
                    </div>
                </header>

                <!-- Mobile Menu -->
                <div id="mobile-menu" class="hidden lg:hidden bg-white border-b border-slate-200">
                    <nav class="px-4 py-4 space-y-2">
                        <a href="{{ route('dashboard') }}" 
                           class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            Dashboard
                        </a>
                        @auth
                            @if(auth()->user()->isAdmin() ?? false)
                                <a href="{{ route('users.index') }}" 
                                   class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('users.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                    Usuários
                                </a>
                            @endif
                        @endauth
                        <a href="{{ route('clients.index') }}" 
                           class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('clients.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Clientes
                        </a>
                        <a href="{{ route('products.index') }}" 
                           class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('products.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            Produtos
                        </a>
                        @php
                            try {
                                // Verificar se existe qualquer rota de finance (usar receivables que sempre existe)
                                $hasFinanceRoute = \Illuminate\Support\Facades\Route::has('finance.receivables.index');
                            } catch (\Exception $e) {
                                $hasFinanceRoute = false;
                            }
                            $hasOsRouteMobile = \Illuminate\Support\Facades\Route::has('os.index');
                            $hasStockRoute = \Illuminate\Support\Facades\Route::has('stock.index');
                        @endphp

                        @if($hasOsRouteMobile)
                            <!-- O.S. -->
                            <a href="{{ route('os.create') }}" 
                               class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('os.*') ? 'bg-purple-50 text-purple-700' : 'text-slate-700 hover:bg-slate-50' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                O.S.
                            </a>
                        @endif
                        
                        <!-- Estoque -->
                        @if($hasStockRoute)
                        <a href="{{ route('stock.index') }}" 
                           class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('stock.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            Estoque
                        </a>
                        @endif

                        @if($hasFinanceRoute)
                            <!-- Financeiro (Menu Dropdown) -->
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('finance.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Financeiro
                                    </div>
                                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="open" @click.away="open = false" x-cloak class="mt-1 ml-4 space-y-1 border-l-2 border-slate-200 pl-4">
                                    @php
                                        $hasCashRouteMobile = false;
                                        $hasReceivablesRouteMobile = false;
                                        $hasPayablesRouteMobile = false;
                                        $hasReconcileRouteMobile = false;
                                        $hasReportsRouteMobile = false;
                                        try {
                                            $hasCashRouteMobile = \Illuminate\Support\Facades\Route::has('finance.cash.index');
                                            $hasReceivablesRouteMobile = \Illuminate\Support\Facades\Route::has('finance.receivables.index');
                                            $hasPayablesRouteMobile = \Illuminate\Support\Facades\Route::has('finance.payables.index');
                                            $hasReconcileRouteMobile = \Illuminate\Support\Facades\Route::has('finance.reconcile.index');
                                            $hasReportsRouteMobile = \Illuminate\Support\Facades\Route::has('finance.reports.cashflow');
                                        } catch (\Exception $e) {
                                            // Ignorar erros
                                        }
                                    @endphp
                                    {{-- Tesouraria (Sessões de Caixa) - REMOVIDO: PDV e abertura de caixa foram removidos --}}
                                    {{-- @if($hasCashRouteMobile)
                                        <a href="{{ route('finance.cash.index') }}" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('finance.cash.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">Tesouraria</a>
                                    @endif --}}
                                    @if($hasReceivablesRouteMobile)
                                        <a href="{{ route('finance.receivables.index') }}" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('finance.receivables.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">Contas a Receber</a>
                                    @endif
                                    @if($hasPayablesRouteMobile)
                                        <a href="{{ route('finance.payables.index') }}" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('finance.payables.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">Contas a Pagar</a>
                                    @endif
                                    @if($hasReconcileRouteMobile)
                                        <a href="{{ route('finance.reconcile.index') }}" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('finance.reconcile.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">Conciliação</a>
                                    @endif
                                    @if($hasReportsRouteMobile)
                                        <a href="{{ route('finance.reports.cashflow') }}" class="block px-4 py-2 text-sm rounded-lg {{ request()->routeIs('finance.reports.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">Relatórios</a>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <!-- Minhas Lojas -->
                        <a href="{{ route('cadastros.companies.index') }}" 
                           class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('cadastros.companies.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            Minhas Lojas
                        </a>
                        <a href="{{ route('profile.edit') }}" 
                           class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('profile.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Meu Perfil
                        </a>
                    </nav>
                </div>

                <!-- Page Content -->
                <main class="flex-1 overflow-y-auto">
                    <div class="py-8">
                        @hasSection('content')
                            @yield('content')
                        @else
                            {{ $slot ?? '' }}
                        @endif
                    </div>
                </main>
            </div>
        </div>
    </body>
    
    <!-- PWA Service Worker Registration -->
    <script>
        // Registrar Service Worker (não bloquear se falhar)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then((registration) => {
                        console.log('Service Worker registrado com sucesso:', registration.scope);
                    })
                    .catch((error) => {
                        console.warn('Falha ao registrar Service Worker (não crítico):', error);
                        // Não bloquear o funcionamento da página se o SW falhar
                    });
            });
        }

        // Prompt de Instalação PWA
        let deferredPrompt;
        let installBanner = null;

        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevenir o prompt automático
            e.preventDefault();
            deferredPrompt = e;
            
            // Mostrar banner de instalação personalizado
            showInstallBanner();
        });

        function showInstallBanner() {
            // Verificar se já está instalado
            if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
                return;
            }

            // Verificar se já existe um banner
            if (document.getElementById('pwa-install-banner')) {
                return;
            }

            // Criar banner de instalação
            const banner = document.createElement('div');
            banner.id = 'pwa-install-banner';
            banner.className = 'fixed bottom-0 left-0 right-0 bg-blue-600 text-white p-4 shadow-lg z-50 flex items-center justify-between';
            banner.style.display = 'flex';
            banner.innerHTML = `
                <div class="flex items-center gap-3 flex-1">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    <div>
                        <p class="font-semibold">Instale nosso app!</p>
                        <p class="text-sm text-blue-100">Acesse rapidamente pelo seu dispositivo</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button id="pwa-install-btn" class="bg-white text-blue-600 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition-colors">
                        Instalar
                    </button>
                    <button id="pwa-dismiss-btn" class="text-white hover:text-blue-100 p-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            document.body.appendChild(banner);
            installBanner = banner;

            // Botão de instalar
            document.getElementById('pwa-install-btn').addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    console.log('Resultado do prompt:', outcome);
                    deferredPrompt = null;
                    hideInstallBanner();
                }
            });

            // Botão de fechar
            document.getElementById('pwa-dismiss-btn').addEventListener('click', () => {
                hideInstallBanner();
                // Salvar preferência no localStorage
                localStorage.setItem('pwa-install-dismissed', Date.now());
            });

            // Verificar se foi dispensado recentemente (menos de 7 dias)
            const dismissed = localStorage.getItem('pwa-install-dismissed');
            if (dismissed && (Date.now() - parseInt(dismissed)) < 7 * 24 * 60 * 60 * 1000) {
                hideInstallBanner();
            }
        }

        function hideInstallBanner() {
            if (installBanner) {
                installBanner.style.transition = 'transform 0.3s ease-out';
                installBanner.style.transform = 'translateY(100%)';
                setTimeout(() => {
                    if (installBanner && installBanner.parentNode) {
                        installBanner.parentNode.removeChild(installBanner);
                    }
                    installBanner = null;
                }, 300);
            }
        }

        // Detectar quando o app é instalado
        window.addEventListener('appinstalled', () => {
            console.log('PWA instalado com sucesso!');
            hideInstallBanner();
            deferredPrompt = null;
        });

        // Detectar se está rodando como PWA
        if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
            console.log('App rodando como PWA instalado');
        }

        // Função para formatar data em português
        function formatDate(date) {
            const diasSemana = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
            const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            
            const diaSemana = diasSemana[date.getDay()];
            const dia = String(date.getDate()).padStart(2, '0');
            const mes = meses[date.getMonth()];
            const ano = date.getFullYear();
            
            return `${diaSemana}, ${dia} de ${mes} de ${ano}`;
        }
        
        // Atualizar data e hora
        function updateDateTime() {
            const now = new Date();
            
            // Atualizar data
            const dateElement = document.getElementById('current-date');
            if (dateElement) {
                dateElement.textContent = formatDate(now);
            }
            
            // Atualizar hora
            const timeElement = document.getElementById('current-time');
            if (timeElement) {
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                timeElement.textContent = hours + ':' + minutes;
            }
        }
        
        // Atualizar imediatamente e depois a cada minuto
        updateDateTime();
        setInterval(updateDateTime, 60000); // Atualizar a cada 60 segundos

        // Modal para alterar data de trabalho
        function openWorkDateModal() {
            const modal = document.getElementById('work-date-modal');
            if (modal) {
                modal.classList.remove('hidden');
            }
        }

        function closeWorkDateModal() {
            const modal = document.getElementById('work-date-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        // Fechar modal ao clicar fora
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('work-date-modal');
            if (modal && event.target === modal) {
                closeWorkDateModal();
            }
        });
    </script>

    <!-- Modal para alterar data de trabalho -->
    <div id="work-date-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-800">Alterar Data de Trabalho</h3>
                <button type="button" onclick="closeWorkDateModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('work-date.update') }}" id="work-date-form">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label for="new_work_date" class="block text-sm font-medium text-slate-700 mb-2">
                        Selecione a data de trabalho:
                    </label>
                    <input
                        type="date"
                        id="new_work_date"
                        name="work_date"
                        value="{{ \App\Helpers\WorkDateHelper::getWorkDate()->format('Y-m-d') }}"
                        min="{{ \App\Helpers\WorkDateHelper::getFirstAvailableDate()->format('Y-m-d') }}"
                        max="{{ \App\Helpers\WorkDateHelper::getLastAvailableDate()->format('Y-m-d') }}"
                        required
                        class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2 text-base"
                    />
                    <p class="mt-2 text-xs text-slate-500">
                        Você pode selecionar uma data entre {{ \App\Helpers\WorkDateHelper::getFirstAvailableDate()->format('d/m/Y') }} e {{ \App\Helpers\WorkDateHelper::getLastAvailableDate()->format('d/m/Y') }}
                    </p>
                </div>
                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeWorkDateModal()"
                        class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        Alterar Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</html>
