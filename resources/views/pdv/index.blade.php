@extends('layouts.app')

@section('content')
@if(isset($error) && $error)
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-yellow-800 mb-2">Erro ao carregar PDV</h2>
            <p class="text-yellow-700">{{ $error }}</p>
        </div>
    </div>
@else
<div class="min-h-screen bg-gray-100 p-4">
    <div class="max-w-7xl mx-auto">
        <!-- Cabeçalho com Seleção de Loja Obrigatória -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-4">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-gray-800 mb-4">PDV - Ponto de Venda</h1>
                    
                    @php
                        $user = auth()->user();
                        $isGerente = $user && $user->isGerente();
                    @endphp
                    @if($isGerente && $store)
                        <!-- Gerente: Mostrar apenas a loja dele (sem seleção) -->
                        <div class="bg-blue-50 border-2 border-blue-300 rounded-lg p-4">
                            <label class="block text-sm font-bold text-blue-900 mb-2">
                                🏪 LOJA
                            </label>
                            <div class="px-4 py-3 text-lg font-semibold border-2 border-blue-500 rounded-lg bg-white">
                                {{ $store->name }}
                            </div>
                            <p class="text-xs text-blue-700 mt-2">Você está trabalhando na sua loja designada</p>
                        </div>
                    @else
                        <!-- Admin: Seleção de Loja -->
                        <div class="bg-blue-50 border-2 border-blue-300 rounded-lg p-4">
                            <label for="store-select" class="block text-sm font-bold text-blue-900 mb-2">
                                🏪 SELECIONE A LOJA (OBRIGATÓRIO)
                            </label>
                            <select 
                                id="store-select" 
                                class="w-full md:w-auto px-4 py-3 text-lg font-semibold border-2 border-blue-500 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                                required
                            >
                                <option value="">⚠️ Selecione uma loja para começar</option>
                                @foreach($stores ?? [] as $storeOption)
                                    @php
                                        $selectedStoreId = session('pdv_store_id') ?? ($store ? $store->id : null);
                                    @endphp
                                    <option value="{{ $storeOption->id }}" {{ $selectedStoreId == $storeOption->id ? 'selected' : '' }}>
                                        @if($storeOption->abbreviation)[{{ $storeOption->abbreviation }}]@endif {{ $storeOption->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-blue-700 mt-2">Você precisa selecionar uma loja antes de usar o PDV</p>
                        </div>
                    @endif
                </div>
                
                <!-- Status do Caixa -->
                <div class="text-right">
                    <div class="text-sm font-medium text-gray-700 mb-2">Status do Caixa:</div>
                    @if($cashSession)
                        <div class="flex items-center gap-3">
                            <div class="flex flex-col items-end">
                                <span class="inline-block px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-bold">
                                    ✅ CAIXA ABERTO
                                </span>
                                @if($cashSession->opened_at)
                                    <div class="mt-3 px-3 py-2 bg-blue-50 border-2 border-blue-300 rounded-lg">
                                        <div class="text-center">
                                            <div class="text-sm text-blue-700 mb-1 font-semibold">📅 DATA DO CAIXA</div>
                                            <div class="text-2xl font-black text-blue-900" style="font-size: 2rem; line-height: 1.2; letter-spacing: 0.05em;">
                                                {{ \Carbon\Carbon::parse($cashSession->opened_at)->format('d/m/Y') }}
                                            </div>
                                            <div class="text-xl font-black text-blue-800 mt-1" style="font-size: 1.5rem;">
                                                🕐 {{ \Carbon\Carbon::parse($cashSession->opened_at)->format('H:i') }}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <button 
                                id="btn-fechar-caixa"
                                type="button"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold cursor-pointer"
                                style="background-color: #dc2626 !important; color: #ffffff !important;"
                            >
                                Fechar Caixa
                            </button>
                        </div>
                    @else
                        <div class="flex items-center gap-2">
                            <span class="inline-block px-4 py-2 bg-red-100 text-red-800 rounded-full text-sm font-bold">
                                ❌ CAIXA FECHADO
                            </span>
                            <button 
                                onclick="openOpenCashModal(); return false;" 
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold cursor-pointer"
                                type="button"
                            >
                                Abrir Caixa
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Aviso se loja não selecionada -->
        <div id="store-warning" class="hidden bg-yellow-50 border-2 border-yellow-400 rounded-lg p-4 mb-4">
            <div class="flex items-center gap-2">
                <span class="text-2xl">⚠️</span>
                <div>
                    <p class="font-bold text-yellow-900">Selecione uma loja no topo da página para começar a vender!</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4" id="pdv-content">
            <!-- Coluna Esquerda: Busca e Itens -->
            <div class="lg:col-span-2 space-y-4">
                <!-- Busca de Produto -->
                <div class="bg-white rounded-lg shadow-md p-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        🔍 Buscar Produto (digite o código ou nome)
                    </label>
                    <input 
                        type="text" 
                        id="product-search" 
                        class="w-full px-4 py-3 text-lg border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Digite o código do produto ou nome e pressione Enter"
                        autofocus
                        disabled
                    >
                    <div id="product-results" class="mt-2 hidden max-h-60 overflow-y-auto border border-gray-200 rounded-lg bg-white shadow-lg z-50"></div>
                </div>

                <!-- Lista de Itens -->
                <div class="bg-white rounded-lg shadow-md p-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">📦 Itens da Venda</h2>
                    <div id="items-list" class="space-y-2">
                        <!-- Itens serão adicionados aqui via JavaScript -->
                    </div>
                    <div id="empty-state" class="text-center py-8 text-gray-500">
                        <p class="text-lg">Nenhum item adicionado ainda.</p>
                        <p class="text-sm mt-2">Use o campo de busca acima para adicionar produtos.</p>
                    </div>
                </div>

                <!-- Totais -->
                <div class="bg-white rounded-lg shadow-md p-4 border-2 border-blue-200">
                    <div class="flex justify-between text-lg mb-2">
                        <span class="text-gray-600">Subtotal:</span>
                        <span id="subtotal" class="font-semibold">R$ 0,00</span>
                    </div>
                    <div class="flex justify-between text-lg mb-2">
                        <span class="text-gray-600">Desconto:</span>
                        <span id="discount" class="font-semibold text-red-600">R$ 0,00</span>
                    </div>
                    <div class="flex justify-between text-2xl font-bold border-t-2 border-blue-300 pt-2 mt-2">
                        <span>TOTAL:</span>
                        <span id="total" class="text-blue-600">R$ 0,00</span>
                    </div>
                </div>
            </div>

            <!-- Coluna Direita: Cliente e Pagamentos -->
            <div class="space-y-4">
                <!-- Cliente -->
                <div class="bg-white rounded-lg shadow-md p-4">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">
                            👤 Cliente (Opcional)
                        </label>
                        <button 
                            onclick="openClientModal()" 
                            class="text-xs text-blue-600 hover:text-blue-800 font-medium bg-blue-50 px-2 py-1 rounded"
                        >
                            + Novo Cliente
                        </button>
                    </div>
                    <input 
                        type="text" 
                        id="customer-search" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        placeholder="Buscar cliente..."
                        autocomplete="off"
                        disabled
                    >
                    <input type="hidden" id="customer-id">
                    <div id="customer-results" class="mt-1 hidden max-h-40 overflow-y-auto border border-gray-200 rounded-lg bg-white shadow-lg z-50"></div>
                    <div id="customer-name" class="mt-2 text-sm text-gray-600 font-medium"></div>
                </div>

                <!-- Formas de Pagamento -->
                <div class="bg-white rounded-lg shadow-md p-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">💳 Formas de Pagamento</h2>
                    
                    <!-- Botões de Pagamento -->
                    <div class="grid grid-cols-2 gap-2 mb-4">
                        <button onclick="addPayment('money')" class="payment-btn" data-method="money" disabled style="background-color: #e5e7eb; color: #111827; border: 2px solid #d1d5db;">
                            <span>💵</span>
                            <span>Dinheiro</span>
                        </button>
                        <button onclick="addPayment('pix')" class="payment-btn" data-method="pix" disabled style="background-color: #e5e7eb; color: #111827; border: 2px solid #d1d5db;">
                            <span>📱</span>
                            <span>PIX</span>
                        </button>
                        <button onclick="addPayment('card_credit')" class="payment-btn" data-method="card_credit" disabled style="background-color: #e5e7eb; color: #111827; border: 2px solid #d1d5db;">
                            <span>💳</span>
                            <span>Cartão Crédito</span>
                        </button>
                        <button onclick="addPayment('card_debit')" class="payment-btn" data-method="card_debit" disabled style="background-color: #e5e7eb; color: #111827; border: 2px solid #d1d5db;">
                            <span>💳</span>
                            <span>Cartão Débito</span>
                        </button>
                        <button onclick="addPayment('boleto')" class="payment-btn" data-method="boleto" disabled style="background-color: #e5e7eb; color: #111827; border: 2px solid #d1d5db;">
                            <span>📄</span>
                            <span>Boleto</span>
                        </button>
                        <button onclick="addPayment('boleto_installment')" class="payment-btn" data-method="boleto_installment" disabled style="background-color: #e5e7eb; color: #111827; border: 2px solid #d1d5db;">
                            <span>📄</span>
                            <span>Boleto Parcelado</span>
                        </button>
                        <button onclick="addPayment('sinal')" class="payment-btn" data-method="sinal" disabled style="background-color: #e5e7eb; color: #111827; border: 2px solid #d1d5db;">
                            <span>💰</span>
                            <span>Sinal</span>
                        </button>
                    </div>

                    <!-- Lista de Pagamentos -->
                    <div id="payments-list" class="space-y-2 mb-4">
                        <!-- Pagamentos serão adicionados aqui -->
                    </div>

                    <div class="flex justify-between text-lg font-semibold border-t pt-2">
                        <span>Total Pago:</span>
                        <span id="paid-total">R$ 0,00</span>
                    </div>

                    <!-- Botões de Ação -->
                    <div class="flex flex-col gap-3 mt-4">
                        <button 
                            type="button"
                            id="create-os-btn"
                            class="w-full px-6 py-4 bg-gradient-to-r from-purple-600 to-purple-700 rounded-lg hover:from-purple-700 hover:to-purple-800 font-bold text-base shadow-lg transition-all duration-200"
                        >
                            <div class="flex items-center justify-center gap-2">
                                <span class="text-xl" id="create-os-btn-icon">📋</span>
                                <span id="create-os-btn-text">Criar Nova OS</span>
                            </div>
                        </button>
                        <div class="text-xs text-gray-500 mt-2 text-center">
                            💡 A OS também será criada automaticamente ao finalizar a venda
                        </div>
                    </div>
                    
                    <!-- Botão Finalizar -->
                    <button 
                        onclick="checkout()" 
                        id="checkout-btn"
                        class="w-full mt-2 px-6 py-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold text-lg disabled:bg-gray-400 disabled:cursor-not-allowed"
                        disabled
                    >
                        ✅ Finalizar Venda
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Abrir Caixa -->
<div id="open-cash-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50" style="display: flex; align-items: center; justify-content: center; padding: 1rem;">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-auto my-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Abrir Caixa</h2>
                <button onclick="closeOpenCashModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <form id="open-cash-form" onsubmit="saveOpenCash(event)">
                @php
                    $user = auth()->user();
                    $isAdmin = $user && $user->isAdmin();
                @endphp
                
                @if($isAdmin)
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        📅 Data de Abertura (Retroativo)
                        <span class="text-xs text-gray-500 font-normal">(Opcional - deixe em branco para data atual)</span>
                    </label>
                    <input 
                        type="datetime-local" 
                        name="opened_at" 
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-lg"
                        max="{{ now()->format('Y-m-d\TH:i') }}"
                    >
                    <p class="text-xs text-gray-500 mt-1">Selecione uma data/hora passada para abrir caixa retroativo</p>
                </div>
                @endif
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor Inicial no Caixa *</label>
                    <input 
                        type="number" 
                        step="0.01" 
                        min="0" 
                        name="opening_amount" 
                        required
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-lg"
                        placeholder="0,00"
                    >
                    <p class="text-xs text-gray-500 mt-1">Digite o valor em dinheiro que está no caixa agora</p>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeOpenCashModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Abrir Caixa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Criar OS -->
<div id="create-os-modal" class="hidden fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm z-50" style="display: flex; align-items: center; justify-content: center; padding: 1rem; animation: fadeIn 0.2s ease-out;" onclick="if(event.target === this) closeCreateOsModal()">
    <div class="bg-white rounded-2xl shadow-2xl max-w-6xl w-full max-h-[95vh] overflow-hidden flex flex-col mx-auto my-auto transform transition-all" onclick="event.stopPropagation()" style="animation: slideUp 0.3s ease-out;">
        <!-- Header com gradiente -->
        <div class="bg-gradient-to-r from-purple-600 via-blue-600 to-indigo-600 px-6 py-5 border-b border-purple-700">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="bg-white bg-opacity-20 rounded-lg p-2">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">Criar Nova OS</h2>
                        <p class="text-purple-100 text-sm">Preencha os dados da ordem de serviço</p>
                        <p id="create-os-number-preview" class="text-purple-200 text-xs mt-1 font-semibold" style="display: none;">
                            📋 Número da OS: <span id="create-os-number-display" class="font-bold"></span>
                        </p>
                    </div>
                </div>
                <button onclick="closeCreateOsModal()" class="text-white hover:bg-white hover:bg-opacity-20 rounded-full p-2 transition-all duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <div class="overflow-y-auto flex-1 bg-gray-50">
            <form id="create-os-form" onsubmit="saveCreateOs(event)">
                <!-- Tabs melhoradas -->
                <div class="bg-white border-b border-gray-200 sticky top-0 z-10 shadow-sm">
                    <nav class="flex space-x-1 overflow-x-auto px-6" style="scrollbar-width: none;">
                        <button type="button" onclick="switchCreateOsTab('basic')" id="create-os-tab-basic" class="create-os-tab-btn active px-6 py-4 text-sm font-semibold text-blue-600 border-b-3 border-blue-600 whitespace-nowrap transition-all duration-200 hover:text-blue-700 hover:bg-blue-50">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Dados Básicos e Itens
                        </button>
                        <button type="button" onclick="switchCreateOsTab('prescription')" id="create-os-tab-prescription" class="create-os-tab-btn px-6 py-4 text-sm font-semibold text-gray-500 border-b-3 border-transparent whitespace-nowrap transition-all duration-200 hover:text-gray-700 hover:bg-gray-50">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Receita
                        </button>
                        <button type="button" onclick="switchCreateOsTab('images')" id="create-os-tab-images" class="create-os-tab-btn px-6 py-4 text-sm font-semibold text-gray-500 border-b-3 border-transparent whitespace-nowrap transition-all duration-200 hover:text-gray-700 hover:bg-gray-50">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Imagens
                        </button>
                    </nav>
                </div>
                
                <div class="p-6">

                <!-- Tab: Dados Básicos e Itens (Unificado) -->
                <div id="create-os-content-basic" class="create-os-tab-content">
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <div class="w-1 h-6 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full"></div>
                            Informações Principais
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Cliente <span class="text-red-500">*</span></label>
                                
                                <!-- Busca de Cliente Integrada -->
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </div>
                                    <input 
                                        type="text" 
                                        id="create-os-customer-search"
                                        class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all bg-white hover:border-gray-300"
                                        placeholder="Digite nome ou CPF/CNPJ do cliente..."
                                        autocomplete="off"
                                    >
                                    <div id="create-os-customer-results" class="hidden absolute z-50 w-full mt-2 bg-white border-2 border-gray-200 rounded-xl shadow-xl max-h-60 overflow-y-auto"></div>
                                </div>
                                
                                <!-- Cliente Selecionado -->
                                <div id="create-os-customer-selected" class="mt-3 hidden">
                                    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-xl shadow-sm">
                                        <div class="flex items-center gap-3">
                                            <div class="bg-green-500 rounded-full p-2">
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900" id="create-os-customer-selected-name"></div>
                                                <div class="text-xs text-gray-600" id="create-os-customer-selected-cpf"></div>
                                            </div>
                                        </div>
                                        <button 
                                            type="button"
                                            onclick="clearCreateOsSelectedCustomer()"
                                            class="text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg p-2 transition-all text-sm font-medium"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <input type="hidden" id="create-os-customer-id">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Origem</label>
                                <div class="flex gap-1">
                                    <select id="create-os-source" class="flex-1 px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all bg-white hover:border-gray-300">
                                        <option value="">Selecione ou digite</option>
                                        @if(isset($sources) && $sources->count() > 0)
                                            @foreach($sources as $source)
                                                <option value="{{ $source->name }}">{{ $source->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <input 
                                        type="text" 
                                        id="create-os-source-custom"
                                        placeholder="Ex: Elizabeth"
                                        class="flex-1 px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all bg-white hover:border-gray-300"
                                        style="display: none;"
                                    >
                                    <button 
                                        type="button" 
                                        onclick="toggleSourceInput()" 
                                        class="px-3 py-3 bg-gray-400 text-white rounded-xl hover:bg-gray-500 transition-colors text-xs font-bold"
                                        title="Alternar entre select e texto"
                                    >
                                        ↔
                                    </button>
                                    <button 
                                        type="button" 
                                        onclick="openQuickSourceModal()" 
                                        class="px-3 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors text-sm font-bold shadow-md"
                                        title="Cadastrar nova origem"
                                        style="min-width: 40px;"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Seção de Itens -->
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <div class="w-1 h-6 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full"></div>
                            Buscar Produtos
                        </h3>
                        <div class="relative">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input 
                                    type="text" 
                                    id="create-os-product-search" 
                                    placeholder="Digite nome, ref ou EAN do produto..." 
                                    autocomplete="off"
                                    class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all bg-white hover:border-gray-300"
                                >
                                <!-- Loading indicator -->
                                <div id="create-os-product-search-loading" class="hidden absolute inset-y-0 right-0 pr-4 flex items-center">
                                    <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                            <!-- Resultados do autocomplete -->
                            <div id="create-os-product-results" class="hidden absolute z-50 w-full mt-2 bg-white border-2 border-gray-200 rounded-xl shadow-2xl max-h-96 overflow-y-auto"></div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">💡 Digite para buscar produtos em tempo real. Use nome, código de referência ou EAN.</p>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <div class="w-1 h-6 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full"></div>
                            Itens Adicionados
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Produto</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Qtd</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Unit.</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Total</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider"></th>
                                    </tr>
                                </thead>
                                <tbody id="create-os-items-table-body" class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center">
                                            <div class="flex flex-col items-center justify-center text-gray-400">
                                                <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                                </svg>
                                                <p class="text-sm font-medium">Nenhum item adicionado</p>
                                                <p class="text-xs mt-1">Busque e adicione produtos acima</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl shadow-sm p-6 border-2 border-blue-100">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <div class="w-1 h-6 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full"></div>
                            Resumo Financeiro
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div class="bg-white rounded-lg p-4 shadow-sm border-2 border-gray-200">
                                <label class="block text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide">Subtotal</label>
                                <div id="create-os-subtotal-display" class="text-2xl font-bold text-gray-800">R$ 0,00</div>
                            </div>
                            <div class="bg-gradient-to-br from-green-600 to-emerald-700 rounded-lg p-4 shadow-lg border-2 border-green-500">
                                <label class="block text-xs font-semibold text-white mb-2 uppercase tracking-wide opacity-90">Total</label>
                                <div id="create-os-total-display" class="text-3xl font-bold text-white drop-shadow-md">R$ 0,00</div>
                            </div>
                        </div>
                        
                        <!-- Campo de Sinal -->
                        <div class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-lg p-4 border-2 border-yellow-200">
                            <label class="block text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                <span>💰</span>
                                <span>Pagamento com Sinal</span>
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Valor do Sinal</label>
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        min="0"
                                        id="create-os-sinal-amount" 
                                        placeholder="0,00"
                                        class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 text-sm"
                                        oninput="updateCreateOsSinalBalance()"
                                    >
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Método de Pagamento</label>
                                    <select 
                                        id="create-os-sinal-method" 
                                        class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 text-sm"
                                    >
                                        <option value="money">💵 Dinheiro</option>
                                        <option value="pix">📱 PIX</option>
                                        <option value="card_credit">💳 Cartão Crédito</option>
                                        <option value="card_debit">💳 Cartão Débito</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Saldo a Receber</label>
                                    <div id="create-os-sinal-balance" class="w-full px-3 py-2 bg-gray-100 border-2 border-gray-300 rounded-lg text-sm font-bold text-orange-700">
                                        R$ 0,00
                                    </div>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-gray-600">💡 O saldo será criado como conta a receber vinculada à OS</p>
                        </div>
                    </div>
                </div>
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <div class="w-1 h-6 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full"></div>
                            Buscar Produtos
                        </h3>
                        <div class="relative">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input 
                                    type="text" 
                                    id="create-os-product-search" 
                                    placeholder="Digite nome, ref ou EAN do produto..." 
                                    autocomplete="off"
                                    class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all bg-white hover:border-gray-300"
                                >
                                <!-- Loading indicator -->
                                <div id="create-os-product-search-loading" class="hidden absolute inset-y-0 right-0 pr-4 flex items-center">
                                    <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                            <!-- Resultados do autocomplete -->
                            <div id="create-os-product-results" class="hidden absolute z-50 w-full mt-2 bg-white border-2 border-gray-200 rounded-xl shadow-2xl max-h-96 overflow-y-auto"></div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">💡 Digite para buscar produtos em tempo real. Use nome, código de referência ou EAN.</p>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <div class="w-1 h-6 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full"></div>
                            Itens Adicionados
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Produto</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Qtd</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Unit.</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Total</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider"></th>
                                    </tr>
                                </thead>
                                <tbody id="create-os-items-table-body" class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center">
                                            <div class="flex flex-col items-center justify-center text-gray-400">
                                                <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                                </svg>
                                                <p class="text-sm font-medium">Nenhum item adicionado</p>
                                                <p class="text-xs mt-1">Busque e adicione produtos acima</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl shadow-sm p-6 border-2 border-blue-100">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <div class="w-1 h-6 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full"></div>
                            Resumo Financeiro
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-white rounded-lg p-4 shadow-sm border-2 border-gray-200">
                                <label class="block text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide">Subtotal</label>
                                <div id="create-os-subtotal-display" class="text-2xl font-bold text-gray-800">R$ 0,00</div>
                            </div>
                            <div class="bg-gradient-to-br from-green-600 to-emerald-700 rounded-lg p-4 shadow-lg border-2 border-green-500">
                                <label class="block text-xs font-semibold text-white mb-2 uppercase tracking-wide opacity-90">Total</label>
                                <div id="create-os-total-display" class="text-3xl font-bold text-white drop-shadow-md">R$ 0,00</div>
                            </div>
                        </div>
                <!-- Tab: Receita -->
                <div id="create-os-content-prescription" class="create-os-tab-content hidden">
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <label class="flex items-center cursor-pointer p-4 bg-blue-50 rounded-xl border-2 border-blue-200 hover:bg-blue-100 transition-all">
                            <input 
                                type="checkbox" 
                                id="create-os-use-custom-prescription" 
                                class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                            >
                            <span class="ml-3 text-sm font-semibold text-gray-700">Usar receita personalizada (não vincular receita existente)</span>
                        </label>
                    </div>
                    <div id="create-os-prescription-fields" class="space-y-6">
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                                <div class="w-1 h-6 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full"></div>
                                LONGE
                            </h3>
                            <!-- OD (Olho Direito) -->
                            <div class="mb-6">
                                <h4 class="text-base font-bold text-gray-700 mb-3 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                    OD (Olho Direito)
                                </h4>
                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Esférico</label>
                                        <input 
                                            type="text" 
                                            id="create-os-prescription-longe-esferico-od" 
                                            placeholder="Ex: -2.00" 
                                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Cilíndrico</label>
                                        <input 
                                            type="text" 
                                            id="create-os-prescription-longe-cilindrico-od" 
                                            placeholder="Ex: -0.75" 
                                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Eixo</label>
                                        <input 
                                            type="text" 
                                            id="create-os-prescription-longe-eixo-od" 
                                            placeholder="Ex: 180" 
                                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">DNP</label>
                                        <input type="text" id="create-os-prescription-longe-dnp-od" placeholder="-" class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Altura</label>
                                        <input type="text" id="create-os-prescription-longe-altura-od" placeholder="-" class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- OE (Olho Esquerdo) -->
                            <div>
                                <h4 class="text-base font-bold text-gray-700 mb-3 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                    OE (Olho Esquerdo)
                                </h4>
                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Esférico</label>
                                        <input 
                                            type="text" 
                                            id="create-os-prescription-longe-esferico-oe" 
                                            placeholder="Ex: -2.00" 
                                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Cilíndrico</label>
                                        <input 
                                            type="text" 
                                            id="create-os-prescription-longe-cilindrico-oe" 
                                            placeholder="Ex: -0.75" 
                                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Eixo</label>
                                        <input 
                                            type="text" 
                                            id="create-os-prescription-longe-eixo-oe" 
                                            placeholder="Ex: 180" 
                                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">DNP</label>
                                        <input type="text" id="create-os-prescription-longe-dnp-oe" placeholder="-" class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Altura</label>
                                        <input type="text" id="create-os-prescription-longe-altura-oe" placeholder="-" class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                    <div class="w-1 h-6 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full"></div>
                                    PERTO
                                </h3>
                                <div id="create-os-perto-calculado-indicator" class="hidden flex items-center gap-2 px-3 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-semibold">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Calculado automaticamente
                                </div>
                            </div>
                            <!-- OD (Olho Direito) -->
                            <div class="mb-6">
                                <h4 class="text-base font-bold text-gray-700 mb-3 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                    OD (Olho Direito)
                                </h4>
                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Esférico</label>
                                        <input 
                                            type="text" 
                                            id="create-os-prescription-perto-esferico-od" 
                                            placeholder="Calculado automaticamente" 
                                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Cilíndrico</label>
                                        <input 
                                            type="text" 
                                            id="create-os-prescription-perto-cilindrico-od" 
                                            placeholder="Calculado automaticamente" 
                                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Eixo</label>
                                        <input 
                                            type="text" 
                                            id="create-os-prescription-perto-eixo-od" 
                                            placeholder="Calculado automaticamente" 
                                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">DNP</label>
                                        <input type="text" id="create-os-prescription-perto-dnp-od" placeholder="-" class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Altura</label>
                                        <input type="text" id="create-os-prescription-perto-altura-od" placeholder="-" class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- OE (Olho Esquerdo) -->
                            <div>
                                <h4 class="text-base font-bold text-gray-700 mb-3 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                    OE (Olho Esquerdo)
                                </h4>
                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Esférico</label>
                                        <input 
                                            type="text" 
                                            id="create-os-prescription-perto-esferico-oe" 
                                            placeholder="Calculado automaticamente" 
                                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Cilíndrico</label>
                                        <input 
                                            type="text" 
                                            id="create-os-prescription-perto-cilindrico-oe" 
                                            placeholder="Calculado automaticamente" 
                                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Eixo</label>
                                        <input 
                                            type="text" 
                                            id="create-os-prescription-perto-eixo-oe" 
                                            placeholder="Calculado automaticamente" 
                                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">DNP</label>
                                        <input type="text" id="create-os-prescription-perto-dnp-oe" placeholder="-" class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Altura</label>
                                        <input type="text" id="create-os-prescription-perto-altura-oe" placeholder="-" class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base transition-all">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                                <div class="w-1 h-6 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full"></div>
                                Informações Adicionais
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Adição (ADD)
                                        <span class="text-xs text-gray-500 font-normal">(cálculo automático)</span>
                                    </label>
                                    <div class="flex gap-2">
                                        <input 
                                            type="text" 
                                            id="create-os-prescription-adicao" 
                                            placeholder="Ex: +2.00" 
                                            class="flex-1 px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all"
                                        >
                                        <button 
                                            type="button"
                                            onclick="calcularGrauPertoManual()"
                                            class="px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold text-sm transition-all"
                                            title="Calcular agora"
                                        >
                                            🔄 Calcular
                                        </button>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">
                                        💡 O sistema calculará automaticamente: Perto = Longe + Adição
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Médico</label>
                                    <input type="text" id="create-os-prescription-doctor-name" placeholder="Nome do médico" class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Válida até</label>
                                    <input type="date" id="create-os-prescription-valid-until" class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Anexo (PDF/Imagem)</label>
                                    <div class="relative">
                                        <input type="file" id="create-os-prescription-attachment" accept=".pdf,.png,.jpg,.jpeg,.gif,.tiff" class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all cursor-pointer hover:border-gray-300">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Imagens -->
                <div id="create-os-content-images" class="create-os-tab-content hidden">
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <div class="w-1 h-6 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full"></div>
                            Upload de Imagens
                        </h3>
                        <p class="text-sm text-gray-600 mb-6">Adicione até 5 imagens relacionadas à ordem de serviço</p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                            @for($i = 1; $i <= 5; $i++)
                                <div class="relative group border-2 border-dashed border-gray-300 rounded-xl overflow-hidden bg-gradient-to-br from-gray-50 to-gray-100 hover:border-blue-400 hover:from-blue-50 hover:to-indigo-50 transition-all duration-200 cursor-pointer" style="aspect-ratio: 1;">
                                    <input type="file" id="create-os-image-{{ $i }}" accept="image/*" class="hidden" onchange="previewCreateOsImage(this, {{ $i }})">
                                    <label for="create-os-image-{{ $i }}" class="cursor-pointer block h-full w-full flex flex-col items-center justify-center text-center p-4">
                                        <div class="bg-white rounded-full p-3 mb-2 shadow-sm group-hover:bg-blue-100 transition-all">
                                            <svg class="w-6 h-6 text-gray-400 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <span class="text-xs font-semibold text-gray-600 group-hover:text-blue-600 transition-colors">Imagem {{ $i }}</span>
                                    </label>
                                    <img id="create-os-preview-{{ $i }}" class="hidden absolute inset-0 w-full h-full object-cover rounded-xl" alt="Preview">
                                    <button type="button" onclick="removeCreateOsImage({{ $i }})" class="hidden absolute top-2 right-2 bg-red-500 text-white rounded-full w-7 h-7 flex items-center justify-center hover:bg-red-600 z-10 shadow-lg transition-all" id="create-os-remove-{{ $i }}" title="Remover imagem">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <!-- Footer fixo -->
        <div class="border-t-2 border-gray-200 p-6 bg-gradient-to-r from-gray-50 to-white sticky bottom-0">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-lg shadow-sm border-2 border-gray-200">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <span class="text-sm font-semibold text-gray-700">Itens:</span>
                        <span id="create-os-items-count" class="text-sm font-bold text-blue-600">0</span>
                    </div>
                    <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-lg shadow-sm border-2 border-gray-200">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm font-semibold text-gray-700">Total:</span>
                        <span id="create-os-total" class="text-lg font-bold text-green-600">R$ 0,00</span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button 
                        type="button" 
                        onclick="closeCreateOsModal()" 
                        class="px-6 py-3 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 font-semibold transition-all duration-200 shadow-sm hover:shadow-md"
                    >
                        Cancelar
                    </button>
                    <button 
                        type="submit" 
                        form="create-os-form"
                        class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl hover:from-green-700 hover:to-emerald-700 font-bold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2 border-2 border-green-500"
                        style="color: #ffffff !important; background: linear-gradient(to right, #16a34a, #059669) !important;"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #ffffff !important;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span style="color: #ffffff !important;">Criar OS</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Carregar OS -->
<div id="load-os-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50" onclick="if(event.target === this) closeLoadOsModal()">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto mx-auto my-auto" style="margin-top: 5vh;" onclick="event.stopPropagation()">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800">📥 Carregar OS no PDV</h2>
                <button onclick="closeLoadOsModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar OS (Número ou Cliente)</label>
                <input 
                    type="text" 
                    id="os-search-input"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Digite o número da OS ou nome do cliente..."
                    onkeyup="searchOs()"
                >
            </div>
            <div id="os-results" class="space-y-2 max-h-96 overflow-y-auto">
                <p class="text-gray-500 text-center py-4">Digite para buscar OS prontas para venda...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Fechar Caixa -->
<div id="close-cash-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50" style="align-items: center; justify-content: center; padding: 1rem; z-index: 99999;">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto mx-auto my-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Fechar Caixa</h2>
                <button onclick="closeCloseCashModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <form id="close-cash-form" onsubmit="saveCloseCash(event)">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Contagem por Forma de Pagamento</label>
                    <div id="counted-methods" class="space-y-2">
                        <div class="flex gap-2">
                            <select class="flex-1 rounded-lg border-gray-300" name="counted_by_method[0][method]">
                                <option value="money">💵 Dinheiro</option>
                                <option value="pix">📱 PIX</option>
                                <option value="card_credit">💳 Cartão Crédito</option>
                                <option value="card_debit">💳 Cartão Débito</option>
                                <option value="boleto">📄 Boleto</option>
                                <option value="boleto_installment">📄 Boleto Parcelado</option>
                                <option value="other">➕ Outros</option>
                            </select>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                name="counted_by_method[0][amount]" 
                                required
                                placeholder="Valor"
                                class="w-32 rounded-lg border-gray-300"
                            >
                        </div>
                    </div>
                    <button type="button" onclick="addCountedMethod()" class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                        + Adicionar Forma de Pagamento
                    </button>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea 
                        name="notes" 
                        rows="3"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    ></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeCloseCashModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700" style="background-color: #dc2626 !important; color: #ffffff !important;">
                        Fechar Caixa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Novo Cliente -->
<div id="client-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Novo Cliente</h2>
                <button onclick="closeClientModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <form id="client-form" onsubmit="saveClient(event)">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                        <input type="text" name="name" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CPF/CNPJ</label>
                        <input type="text" name="cpf_cnpj" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento</label>
                        <input type="date" name="birth_date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-3">
                    <button type="button" onclick="closeClientModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Garantir que as funções estejam disponíveis globalmente
window.openOpenCashModal = function() {
    console.log('Tentando abrir modal de caixa...', { currentStoreId });
    
    // Verificar se o modal existe
    const modal = document.getElementById('open-cash-modal');
    if (!modal) {
        console.error('Modal open-cash-modal não encontrado!');
        alert('❌ Erro: Modal não encontrado. Recarregue a página.');
        return;
    }
    
    // Verificar se há loja selecionada
    if (!currentStoreId) {
        const confirmOpen = confirm('⚠️ Você precisa selecionar uma loja primeiro!\n\nDeseja continuar mesmo assim?');
        if (!confirmOpen) {
            return;
        }
    }
    
    // Abrir modal
    try {
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        console.log('Modal aberto com sucesso');
        
        // Focar no campo de valor
        const amountInput = modal.querySelector('input[name="opening_amount"]');
        if (amountInput) {
            setTimeout(() => amountInput.focus(), 100);
        }
    } catch (error) {
        console.error('Erro ao abrir modal:', error);
        alert('❌ Erro ao abrir modal: ' + error.message);
    }
};

window.closeOpenCashModal = function() {
    const modal = document.getElementById('open-cash-modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
    const form = document.getElementById('open-cash-form');
    if (form) {
        form.reset();
    }
};

let items = [];
let payments = [];
let totalGross = 0;
let totalDiscount = 0;
let totalNet = 0;

// Função auxiliar para escapar HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

let currentStoreId = {{ (session('pdv_store_id') ?? ($store ? $store->id : null)) ?: 'null' }};
let cashSessionOpen = {{ $cashSession ? 'true' : 'false' }};

console.log('PDV inicializado', { 
    currentStoreId, 
    store: {{ $store ? $store->id : 'null' }}, 
    sessionStoreId: '{{ session('pdv_store_id') ?? '' }}',
    cashSessionOpen 
});
let productSearchTimeout = null;
let customerSearchTimeout = null;
let countedMethodCount = 1;

// Verificar se loja está selecionada ao carregar
document.addEventListener('DOMContentLoaded', function() {
    checkStoreSelected();
    updatePDVState();
    updateOsButtons();
    
    // Adicionar event listeners para os botões de OS
    setupOsButtons();
});

// Função para lidar com clique no botão criar OS
function handleCreateOsClick() {
    console.log('handleCreateOsClick chamado', { items: items.length, cashSessionOpen, currentStoreId });
    
    // Verificações antes de abrir
    if (!currentStoreId) {
        alert('⚠️ Selecione uma loja primeiro!');
        return;
    }
    
    if (!cashSessionOpen) {
        alert('⚠️ Abra o caixa primeiro para criar uma OS!');
        return;
    }
    
    // Não precisa ter produtos no carrinho - pode criar OS vazia e adicionar depois
    openCreateOsModal();
}

// Função para configurar os botões de OS
function setupOsButtons() {
    const createOsBtn = document.getElementById('create-os-btn');
    
    if (createOsBtn) {
        // Remover listeners antigos
        createOsBtn.onclick = null;
        createOsBtn.removeEventListener('click', handleCreateOsClick);
        
        // Garantir que o botão seja clicável
        createOsBtn.style.pointerEvents = 'auto';
        createOsBtn.style.cursor = 'pointer';
        createOsBtn.style.zIndex = '10';
        
        // Usar onclick direto para garantir que funcione
        createOsBtn.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            handleCreateOsClick();
            return false;
        };
        
        // Também adicionar event listener como backup
        createOsBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            handleCreateOsClick();
            return false;
        });
    }
}

// Seleção de loja
let isChangingStore = false; // Flag para evitar múltiplos cliques

// Seletor de loja (apenas para admin, gerente não tem seletor)
const storeSelect = document.getElementById('store-select');
if (storeSelect) {
    storeSelect.addEventListener('change', function(e) {
    if (isChangingStore) {
        return; // Ignorar se já está processando
    }
    
    const selectedStoreId = e.target.value;
    const selectElement = e.target;
    const previousValue = currentStoreId;
    
    if (!selectedStoreId) {
        // Se desmarcou, limpar sessão
        isChangingStore = true;
        selectElement.disabled = true;
        
        fetch('{{ route('pdv.set-store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ store_id: '' })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentStoreId = null;
                checkStoreSelected();
                window.location.reload();
            } else {
                selectElement.value = previousValue || '';
                selectElement.disabled = false;
                isChangingStore = false;
            }
        })
        .catch(error => {
            selectElement.value = previousValue || '';
            selectElement.disabled = false;
            isChangingStore = false;
        });
        return;
    }
    
    // Desabilitar o select enquanto processa
    isChangingStore = true;
    selectElement.disabled = true;
    
    fetch('{{ route('pdv.set-store') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ store_id: selectedStoreId })
    })
    .then(res => {
        if (!res.ok) {
            throw new Error('Erro na resposta do servidor');
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            currentStoreId = selectedStoreId;
            // Recarregar a página para atualizar o estado completo
            window.location.reload();
        } else {
            alert(data.error || 'Erro ao selecionar loja');
            selectElement.value = previousValue || '';
            selectElement.disabled = false;
            isChangingStore = false;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao selecionar loja. Tente novamente.');
        selectElement.value = previousValue || '';
        selectElement.disabled = false;
        isChangingStore = false;
    });
    });
}

function checkStoreSelected() {
    const storeSelect = document.getElementById('store-select');
    const storeWarning = document.getElementById('store-warning');
    const pdvContent = document.getElementById('pdv-content');
    
    // Se for gerente, sempre considerar loja selecionada (não tem seletor)
    @if(auth()->user() && auth()->user()->isGerente() && $store)
        // Gerente sempre tem loja definida
        if (!currentStoreId) {
            currentStoreId = {{ $store->id }};
        }
        storeWarning?.classList.add('hidden');
        if (pdvContent) {
            pdvContent.style.opacity = '1';
            pdvContent.style.pointerEvents = 'auto';
        }
        enableAllInputs();
        return;
    @endif
    
    if (!currentStoreId || (storeSelect && !storeSelect.value)) {
        storeWarning.classList.remove('hidden');
        pdvContent.style.opacity = '0.5';
        pdvContent.style.pointerEvents = 'none';
        disableAllInputs();
    } else {
        storeWarning.classList.add('hidden');
        pdvContent.style.opacity = '1';
        pdvContent.style.pointerEvents = 'auto';
        enableAllInputs();
        updateOsButtons();
    }
}

function disableAllInputs() {
    const productSearch = document.getElementById('product-search');
    const customerSearch = document.getElementById('customer-search');
    
    if (productSearch) productSearch.disabled = true;
    if (customerSearch) customerSearch.disabled = true;
    document.querySelectorAll('button[onclick^="addPayment"]').forEach(btn => btn.disabled = true);
    const createOsBtn = document.getElementById('create-os-btn');
    if (createOsBtn) createOsBtn.disabled = true;
}

function enableAllInputs() {
    const productSearch = document.getElementById('product-search');
    const customerSearch = document.getElementById('customer-search');
    
    // Cliente sempre pode ser buscado (não depende de loja nem caixa)
    if (customerSearch) customerSearch.disabled = false;
    
    // Produtos podem ser buscados se houver loja selecionada (mesmo sem caixa aberto)
    if (productSearch && currentStoreId) {
        productSearch.disabled = false;
    }
    
    // Pagamentos e adicionar produtos à venda só quando caixa estiver aberto
    if (cashSessionOpen) {
        document.querySelectorAll('button[onclick^="addPayment"]').forEach(btn => btn.disabled = false);
        const createOsBtn = document.getElementById('create-os-btn');
        if (createOsBtn) createOsBtn.disabled = false;
    }
}

function updatePDVState() {
    updateOsButtons();
    // Atualizar estado dos campos baseado em loja e caixa
    if (!currentStoreId) {
        disableAllInputs();
    } else {
        enableAllInputs();
    }
}

// As funções openOpenCashModal e closeOpenCashModal já foram definidas no início do script
// (definidas como window.openOpenCashModal e window.closeOpenCashModal)

function openCloseCashModal() {
    console.log('🔵 openCloseCashModal chamado');
    console.log('cashSessionOpen:', cashSessionOpen);
    
    let modal = document.getElementById('close-cash-modal');
    console.log('Modal encontrado:', modal);
    
    // Se não encontrou, tentar buscar de outra forma
    if (!modal) {
        modal = document.querySelector('#close-cash-modal');
        console.log('Tentativa 2 - Modal encontrado:', modal);
    }
    
    if (!modal) {
        console.error('❌ Modal close-cash-modal não encontrado!');
        console.log('Todos os modais no DOM:', document.querySelectorAll('[id*="modal"]'));
        alert('❌ Erro: Modal não encontrado. Recarregue a página.');
        return;
    }
    
    // PRIMEIRO: SEMPRE mover o modal para o body ANTES de fazer qualquer coisa
    // Isso garante que não está dentro de um elemento oculto
    const currentParent = modal.parentElement;
    console.log('Modal parent atual:', currentParent);
    console.log('Parent é body?', currentParent === document.body);
    
    // SEMPRE mover para o body (mesmo que já esteja, para garantir)
    if (currentParent !== document.body) {
        console.log('⚠️ Modal não está no body! Movendo...');
        if (currentParent) {
            const parentStyle = window.getComputedStyle(currentParent);
            console.log('Parent display:', parentStyle.display);
            console.log('Parent visibility:', parentStyle.visibility);
        }
    }
    
    // Mover para o body (sempre)
    document.body.appendChild(modal);
    console.log('✅ Modal garantido no body');
    
    // Verificar se agora está no body
    const newParent = modal.parentElement;
    console.log('Novo parent:', newParent);
    console.log('Novo parent é body?', newParent === document.body);
    
    // Remover classe hidden
    modal.classList.remove('hidden');
    
    // Remover style display: none se houver
    if (modal.style.display === 'none') {
        modal.style.display = '';
    }
    
    // Forçar exibição com estilos inline usando setProperty para garantir !important
    modal.style.setProperty('position', 'fixed', 'important');
    modal.style.setProperty('top', '0', 'important');
    modal.style.setProperty('left', '0', 'important');
    modal.style.setProperty('right', '0', 'important');
    modal.style.setProperty('bottom', '0', 'important');
    modal.style.setProperty('display', 'flex', 'important');
    modal.style.setProperty('align-items', 'center', 'important');
    modal.style.setProperty('justify-content', 'center', 'important');
    modal.style.setProperty('z-index', '99999', 'important');
    modal.style.setProperty('padding', '1rem', 'important');
    modal.style.setProperty('background-color', 'rgba(0, 0, 0, 0.5)', 'important');
    modal.style.setProperty('visibility', 'visible', 'important');
    modal.style.setProperty('opacity', '1', 'important');
    modal.style.setProperty('pointer-events', 'auto', 'important');
    
    // Garantir que o conteúdo do modal está visível
    const modalContent = modal.querySelector('div.bg-white');
    if (modalContent) {
        modalContent.style.position = 'relative';
        modalContent.style.zIndex = '100000';
        modalContent.style.maxWidth = '42rem';
        modalContent.style.width = '100%';
        modalContent.style.margin = 'auto';
        console.log('✅ Conteúdo do modal configurado');
    } else {
        console.warn('⚠️ Conteúdo do modal (div.bg-white) não encontrado');
    }
    
    // Verificar se realmente está visível
    setTimeout(() => {
        // Garantir que está no body
        if (modal.parentElement !== document.body) {
            console.warn('⚠️ Modal não está no body, movendo...');
            document.body.appendChild(modal);
        }
        
        const computedStyle = window.getComputedStyle(modal);
        const isVisible = computedStyle.display === 'flex' && 
                         computedStyle.visibility === 'visible' &&
                         computedStyle.opacity !== '0' &&
                         modal.offsetParent !== null;
        
        console.log('Modal visível?', isVisible);
        console.log('Display:', computedStyle.display);
        console.log('Visibility:', computedStyle.visibility);
        console.log('Opacity:', computedStyle.opacity);
        console.log('Z-index:', computedStyle.zIndex);
        console.log('OffsetParent:', modal.offsetParent);
        console.log('Parent:', modal.parentElement);
        console.log('Parent display:', modal.parentElement ? window.getComputedStyle(modal.parentElement).display : 'N/A');
        
        if (!isVisible) {
            console.error('❌ Modal ainda não está visível após configuração!');
            console.log('Tentando corrigir...');
            
            // Forçar novamente todos os estilos
            modal.style.setProperty('position', 'fixed', 'important');
            modal.style.setProperty('display', 'flex', 'important');
            modal.style.setProperty('visibility', 'visible', 'important');
            modal.style.setProperty('opacity', '1', 'important');
            modal.style.setProperty('z-index', '99999', 'important');
            modal.style.setProperty('top', '0', 'important');
            modal.style.setProperty('left', '0', 'important');
            modal.style.setProperty('right', '0', 'important');
            modal.style.setProperty('bottom', '0', 'important');
            
            // Garantir que está no body
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
            
            // Verificar novamente após 50ms
            setTimeout(() => {
                const finalCheck = window.getComputedStyle(modal);
                const finalVisible = finalCheck.display === 'flex' && 
                                    finalCheck.visibility === 'visible' &&
                                    modal.offsetParent !== null;
                console.log('Verificação final - Modal visível?', finalVisible);
                if (!finalVisible) {
                    console.error('❌ Modal ainda não está visível após todas as tentativas!');
                    alert('Erro ao exibir modal. Tente recarregar a página.');
                }
            }, 50);
        } else {
            console.log('✅ Modal está visível!');
        }
    }, 100);
    
    // Forçar scroll para o topo
    window.scrollTo(0, 0);
    
    console.log('✅ Função openCloseCashModal concluída');
}

// Garantir que a função esteja no escopo global
window.openCloseCashModal = openCloseCashModal;

// Adicionar listener direto no botão Fechar Caixa
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM carregado, configurando botão Fechar Caixa...');
    
    function setupCloseCashButton() {
        const closeCashBtn = document.getElementById('btn-fechar-caixa');
        if (closeCashBtn) {
            console.log('✅ Botão Fechar Caixa encontrado:', closeCashBtn);
            
            // Remover listeners anteriores se houver
            const newBtn = closeCashBtn.cloneNode(true);
            closeCashBtn.parentNode.replaceChild(newBtn, closeCashBtn);
            
            // Adicionar listener
            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('🖱️ Clique no botão Fechar Caixa detectado!');
                
                if (typeof openCloseCashModal === 'function') {
                    console.log('✅ Chamando openCloseCashModal');
                    openCloseCashModal();
                } else if (typeof window.openCloseCashModal === 'function') {
                    console.log('✅ Chamando window.openCloseCashModal');
                    window.openCloseCashModal();
                } else {
                    console.error('❌ openCloseCashModal não encontrado!');
                    alert('Erro: Função não encontrada. Recarregue a página.');
                }
                
                return false;
            });
            
            console.log('✅ Listener adicionado ao botão Fechar Caixa');
        } else {
            console.warn('⚠️ Botão Fechar Caixa (btn-fechar-caixa) não encontrado no DOM');
            // Tentar novamente após um delay
            setTimeout(setupCloseCashButton, 500);
        }
    }
    
    // Tentar configurar imediatamente
    setupCloseCashButton();
    
    // Tentar novamente após um delay para garantir
    setTimeout(setupCloseCashButton, 1000);
});

function closeCloseCashModal() {
    console.log('closeCloseCashModal chamado');
    const modal = document.getElementById('close-cash-modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
    const form = document.getElementById('close-cash-form');
    if (form) {
        form.reset();
    }
    if (typeof countedMethodCount !== 'undefined') {
        countedMethodCount = 1;
    }
    const methodsContainer = document.getElementById('counted-methods');
    if (methodsContainer) {
        methodsContainer.innerHTML = `
            <div class="flex gap-2">
                <select class="flex-1 rounded-lg border-gray-300" name="counted_by_method[0][method]">
                    <option value="money">💵 Dinheiro</option>
                    <option value="pix">📱 PIX</option>
                    <option value="card_credit">💳 Cartão Crédito</option>
                    <option value="card_debit">💳 Cartão Débito</option>
                    <option value="boleto">📄 Boleto</option>
                    <option value="boleto_installment">📄 Boleto Parcelado</option>
                    <option value="other">➕ Outros</option>
                </select>
                <input type="number" step="0.01" min="0" name="counted_by_method[0][amount]" required placeholder="Valor" class="w-32 rounded-lg border-gray-300">
            </div>
        `;
    }
}

// Garantir que as funções estejam no escopo global
window.closeCloseCashModal = closeCloseCashModal;

function addCountedMethod() {
    const container = document.getElementById('counted-methods');
    const div = document.createElement('div');
    div.className = 'flex gap-2';
    div.innerHTML = `
        <select class="flex-1 rounded-lg border-gray-300" name="counted_by_method[${countedMethodCount}][method]">
            <option value="money">💵 Dinheiro</option>
            <option value="pix">📱 PIX</option>
            <option value="card_credit">💳 Cartão Crédito</option>
            <option value="card_debit">💳 Cartão Débito</option>
            <option value="boleto">📄 Boleto</option>
            <option value="boleto_installment">📄 Boleto Parcelado</option>
            <option value="other">➕ Outros</option>
        </select>
        <input type="number" step="0.01" min="0" name="counted_by_method[${countedMethodCount}][amount]" required placeholder="Valor" class="w-32 rounded-lg border-gray-300">
        <button type="button" onclick="this.parentElement.remove()" class="px-2 py-1 bg-red-500 text-white rounded">×</button>
    `;
    container.appendChild(div);
    countedMethodCount++;
}

async function saveOpenCash(event) {
    event.preventDefault();
    if (!currentStoreId) {
        alert('⚠️ Selecione uma loja primeiro!');
        closeOpenCashModal();
        return;
    }
    
    const formData = new FormData(event.target);
    const openingAmount = formData.get('opening_amount');
    
    if (!openingAmount || parseFloat(openingAmount) < 0) {
        alert('⚠️ Digite um valor inicial válido!');
        return;
    }
    
    formData.append('store_id', currentStoreId);
    
    // Desabilitar botão durante processamento
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Abrindo...';
    
    try {
        const response = await fetch('{{ route('pdv.open-cash') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        });
        
        // Verificar status da resposta
        if (!response.ok) {
            let errorMessage = `Erro ${response.status}: ${response.statusText}`;
            try {
                const errorData = await response.json();
                errorMessage = errorData.error || errorMessage;
            } catch (e) {
                const text = await response.text();
                console.error('Resposta do servidor (texto):', text);
            }
            alert('❌ ' + errorMessage);
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            return;
        }
        
        let data;
        try {
            data = await response.json();
        } catch (jsonError) {
            console.error('Erro ao parsear JSON:', jsonError);
            const text = await response.text();
            console.error('Resposta do servidor:', text);
            alert('❌ Erro ao processar resposta do servidor.\n\nExecute: php public/debug_cash.php para diagnosticar.\n\nResposta: ' + text.substring(0, 200));
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            return;
        }
        
        if (data.success) {
            alert('✅ Caixa aberto com sucesso!');
            window.location.reload();
        } else {
            alert('❌ ' + (data.error || 'Erro ao abrir caixa'));
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    } catch (error) {
        console.error('Erro na requisição:', error);
        alert('❌ Erro ao abrir caixa.\n\nVerifique:\n1. Se selecionou uma loja\n2. Se digitou um valor válido\n3. Execute: php public/debug_cash.php para diagnosticar\n\nErro: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

async function saveCloseCash(event) {
    event.preventDefault();
    console.log('saveCloseCash chamado', { currentStoreId });
    
    if (!currentStoreId) {
        alert('⚠️ Selecione uma loja primeiro!');
        return;
    }
    
    // Desabilitar botão durante processamento
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Fechando...';
    
    const formData = new FormData(event.target);
    
    // Coletar métodos de pagamento
    const methods = [];
    const methodInputs = document.querySelectorAll('#counted-methods select[name*="[method]"]');
    const amountInputs = document.querySelectorAll('#counted-methods input[name*="[amount]"]');
    
    methodInputs.forEach((select, index) => {
        const amount = amountInputs[index];
        if (select.value && amount.value) {
            methods.push({
                method: select.value,
                amount: parseFloat(amount.value) || 0
            });
        }
    });
    
    if (methods.length === 0) {
        alert('Adicione pelo menos uma forma de pagamento');
        return;
    }
    
    const data = {
        store_id: currentStoreId,
        counted_by_method: methods,
        notes: formData.get('notes') || ''
    };
    
    try {
        const response = await fetch('{{ route('pdv.close-cash') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });
        
        // Verificar status da resposta
        if (!response.ok) {
            let errorMessage = `Erro ${response.status}: ${response.statusText}`;
            try {
                const errorData = await response.json();
                errorMessage = errorData.error || errorMessage;
            } catch (e) {
                const text = await response.text();
                console.error('Resposta do servidor (texto):', text);
                errorMessage = text || errorMessage;
            }
            alert('❌ ' + errorMessage);
            return;
        }
        
        let result;
        try {
            result = await response.json();
        } catch (jsonError) {
            console.error('Erro ao parsear JSON:', jsonError);
            const text = await response.text();
            console.error('Resposta do servidor:', text);
            alert('❌ Erro ao processar resposta do servidor.\n\nResposta: ' + text.substring(0, 200));
            return;
        }
        
        if (result.success) {
            const diff = result.difference || 0;
            let message = '✅ Caixa fechado com sucesso!';
            if (diff > 0) {
                message += `\n💰 Diferença positiva: R$ ${diff.toFixed(2).replace('.', ',')}`;
            } else if (diff < 0) {
                message += `\n⚠️ Diferença negativa: R$ ${Math.abs(diff).toFixed(2).replace('.', ',')}`;
            } else {
                message += '\n✓ Caixa fechou sem diferença!';
            }
            alert(message);
            closeCloseCashModal();
            location.reload();
        } else {
            alert('❌ Erro: ' + (result.error || 'Erro ao fechar caixa'));
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    } catch (error) {
        console.error('Erro na requisição:', error);
        alert('❌ Erro ao fechar caixa.\n\nErro: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

// Busca de produto com autocomplete (aguardar DOM estar pronto)
document.addEventListener('DOMContentLoaded', function() {
    const productSearchEl = document.getElementById('product-search');
    if (productSearchEl) {
        productSearchEl.addEventListener('input', function(e) {
        // Permitir busca mesmo sem caixa aberto, mas avisar se não houver loja
        if (!currentStoreId) {
            const resultsDiv = document.getElementById('product-results');
            if (resultsDiv) {
                resultsDiv.innerHTML = '<div class="p-3 text-red-600 text-sm">⚠️ Selecione uma loja primeiro!</div>';
                resultsDiv.classList.remove('hidden');
            }
            return;
        }
        
        const search = e.target.value.trim();
        if (search.length < 2) {
            const resultsDiv = document.getElementById('product-results');
            if (resultsDiv) {
                resultsDiv.classList.add('hidden');
            }
            return;
        }
        
        clearTimeout(productSearchTimeout);
        productSearchTimeout = setTimeout(() => {
            const url = `{{ route('os.products.lookup') }}?q=${encodeURIComponent(search)}&store_id=${currentStoreId}`;
            console.log('Buscando produtos:', url);
            
            fetch(url)
                .then(res => {
                    if (!res.ok) {
                        throw new Error(`HTTP ${res.status}: ${res.statusText}`);
                    }
                    return res.json();
                })
                .then(data => {
                    console.log('Produtos encontrados:', data);
                    const resultsDiv = document.getElementById('product-results');
                    if (!resultsDiv) {
                        console.error('Elemento product-results não encontrado!');
                        return;
                    }
                    
                    if (!data || data.length === 0) {
                        resultsDiv.innerHTML = '<div class="p-3 text-gray-500 text-sm">Nenhum produto encontrado</div>';
                        resultsDiv.classList.remove('hidden');
                        return;
                    }
                    
                    resultsDiv.innerHTML = data.map(product => {
                        const safeName = (product.name || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
                        const safeRef = (product.ref || 'N/A').replace(/'/g, "\\'").replace(/"/g, '&quot;');
                        return `
                            <div onclick="selectProduct(${product.id}, '${safeName}', ${product.unit_price || 0}, '${safeRef}')" 
                                 class="p-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100">
                                <div class="font-semibold">${product.name || 'Sem nome'}</div>
                                <div class="text-sm text-gray-600">Ref: ${product.ref || 'N/A'} | R$ ${(product.unit_price || 0).toFixed(2)}</div>
                            </div>
                        `;
                    }).join('');
                    resultsDiv.classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Erro ao buscar produtos:', error);
                    const resultsDiv = document.getElementById('product-results');
                    if (resultsDiv) {
                        resultsDiv.innerHTML = `<div class="p-3 text-red-600 text-sm">❌ Erro ao buscar produtos: ${error.message}</div>`;
                        resultsDiv.classList.remove('hidden');
                    }
                });
        }, 300);
        });
    }
    
    // Event listener para Enter na busca de produtos
    const productSearchEnter = document.getElementById('product-search');
    if (productSearchEnter) {
        productSearchEnter.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && !currentStoreId) {
        e.preventDefault();
        alert('Selecione uma loja primeiro!');
        return;
    }
            if (e.key === 'Enter' && !cashSessionOpen) {
                e.preventDefault();
                alert('Abra o caixa primeiro!');
                return;
            }
        });
    }
    
    // Busca de cliente com autocomplete
    const customerSearchEl = document.getElementById('customer-search');
    if (customerSearchEl) {
        customerSearchEl.addEventListener('input', function(e) {
            const search = e.target.value.trim();
            // Permitir busca com pelo menos 1 caractere (para CPF/CNPJ)
            if (search.length < 1) {
                const resultsDiv = document.getElementById('customer-results');
                if (resultsDiv) {
                    resultsDiv.classList.add('hidden');
                }
                return;
            }
            
            clearTimeout(customerSearchTimeout);
            customerSearchTimeout = setTimeout(() => {
                const url = `{{ route('os.clients.lookup') }}?q=${encodeURIComponent(search)}`;
                console.log('Buscando clientes:', url);
                
                fetch(url)
                    .then(res => {
                        if (!res.ok) {
                            throw new Error(`HTTP ${res.status}: ${res.statusText}`);
                        }
                        return res.json();
                    })
                    .then(data => {
                        console.log('Clientes encontrados:', data);
                        const resultsDiv = document.getElementById('customer-results');
                        if (!resultsDiv) {
                            console.error('Elemento customer-results não encontrado!');
                            return;
                        }
                        
                        if (!data || data.length === 0) {
                            // Detectar se é CPF/CNPJ ou nome
                            const searchValue = search.trim();
                            const cleanCpfCnpj = searchValue.replace(/[^\d]/g, '');
                            const isOnlyNumbers = /^[\d.\-\/\s]+$/.test(searchValue);
                            
                            // Se só tem números, assumir que é CPF/CNPJ
                            const nameToUse = isOnlyNumbers ? '' : searchValue;
                            const cpfCnpjToUse = isOnlyNumbers ? cleanCpfCnpj : '';
                            
                            resultsDiv.innerHTML = `
                                <div class="p-3 border-t border-gray-200 bg-yellow-50">
                                    <div class="text-sm text-gray-700 mb-2 font-medium">❌ Cliente não encontrado</div>
                                    <button 
                                        onclick="openClientModalWithData('${nameToUse.replace(/'/g, "\\'")}', '${cpfCnpjToUse}')" 
                                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium text-sm transition-colors shadow-md"
                                    >
                                        ✏️ Cadastrar ${nameToUse ? `"${nameToUse.length > 25 ? nameToUse.substring(0, 25) + '...' : nameToUse}"` : 'Novo Cliente'}
                                    </button>
                                </div>
                            `;
                            resultsDiv.classList.remove('hidden');
                            return;
                        }
                        
                        resultsDiv.innerHTML = data.map(client => {
                            const safeName = (client.name || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
                            const cpfCnpj = client.cpf_cnpj ? ` (${client.cpf_cnpj})` : '';
                            return `
                                <div onclick="selectCustomer(${client.id}, '${safeName}')" 
                                     class="p-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 text-sm">
                                    ${client.name || 'Sem nome'}${cpfCnpj}
                                </div>
                            `;
                        }).join('');
                        resultsDiv.classList.remove('hidden');
                    })
                    .catch(error => {
                        console.error('Erro ao buscar clientes:', error);
                        const resultsDiv = document.getElementById('customer-results');
                        if (resultsDiv) {
                            resultsDiv.innerHTML = `<div class="p-2 text-red-600 text-sm">❌ Erro ao buscar clientes: ${error.message}</div>`;
                            resultsDiv.classList.remove('hidden');
                        }
                    });
            }, 300);
        });
    }
}); // Fim do DOMContentLoaded para buscas

function selectProduct(productId, name, price, ref) {
    // Verificar se pode adicionar produto (precisa de loja e caixa aberto)
    if (!currentStoreId) {
        alert('⚠️ Selecione uma loja primeiro!');
        return;
    }
    if (!cashSessionOpen) {
        alert('⚠️ Abra o caixa primeiro para adicionar produtos à venda!');
        return;
    }
    
    addItem({ id: productId, name, price, ref });
    const productSearch = document.getElementById('product-search');
    const productResults = document.getElementById('product-results');
    if (productSearch) {
        productSearch.value = '';
        productSearch.focus();
    }
    if (productResults) productResults.classList.add('hidden');
}

function searchProduct(search) {
    if (!search.trim() || !currentStoreId || !cashSessionOpen) return;
    
    fetch('{{ route('pdv.scan') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ search, store_id: currentStoreId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            return;
        }
        addItem(data);
        document.getElementById('product-search').value = '';
        document.getElementById('product-search').focus();
    })
    .catch(error => {
        alert('Erro ao buscar produto');
    });
}

// Busca de cliente movida para dentro do DOMContentLoaded acima

function selectCustomer(id, name) {
    document.getElementById('customer-id').value = id;
    const customerNameEl = document.getElementById('customer-name');
    if (customerNameEl) {
        customerNameEl.textContent = name;
        customerNameEl.value = name; // Para compatibilidade
    }
    document.getElementById('customer-search').value = name;
    document.getElementById('customer-results').classList.add('hidden');
    
    // Fechar modal de cliente se estiver aberto
    closeClientModal();
    
    // Atualizar modal de criar OS se estiver aberto
    const createOsModal = document.getElementById('create-os-modal');
    if (createOsModal && !createOsModal.classList.contains('hidden')) {
        updateCreateOsModalInfo();
    }
}

function openClientModal() {
    openClientModalWithData('', '');
}

function openClientModalWithData(name = '', cpfCnpj = '') {
    const clientModal = document.getElementById('client-modal');
    if (clientModal) {
        // Preencher formulário se dados fornecidos
        const nameInput = document.querySelector('#client-form input[name="name"]');
        const cpfCnpjInput = document.querySelector('#client-form input[name="cpf_cnpj"]');
        
        if (nameInput && name) {
            nameInput.value = name;
        }
        if (cpfCnpjInput && cpfCnpj) {
            cpfCnpjInput.value = cpfCnpj;
        }
        
        // Fechar resultados de busca
        document.getElementById('customer-results').classList.add('hidden');
        
        // Abrir modal
        clientModal.classList.remove('hidden');
        
        // Focar no primeiro campo vazio
        if (!name && nameInput) {
            nameInput.focus();
        } else if (!cpfCnpj && cpfCnpjInput) {
            cpfCnpjInput.focus();
        }
    }
}

function closeClientModal() {
    const modal = document.getElementById('client-modal');
    const form = document.getElementById('client-form');
    if (modal) {
        modal.classList.add('hidden');
    }
    if (form) {
        form.reset();
    }
}

function saveClient(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    fetch('{{ route('clients.store') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Fechar modal de cliente
            closeClientModal();
            
            // Verificar se estava vindo do modal de criar OS
            const createOsModal = document.getElementById('create-os-modal');
            const wasFromCreateOs = createOsModal && createOsModal.classList.contains('hidden');
            
            if (wasFromCreateOs) {
                // Reabrir modal de criar OS e selecionar cliente
                createOsModal.classList.remove('hidden');
                selectCreateOsCustomer(data.client.id, data.client.name, data.client.cpf_cnpj || '');
            } else {
                // Selecionar cliente no campo principal do PDV
                selectCustomer(data.client.id, data.client.name);
                const customerSearch = document.getElementById('customer-search');
                if (customerSearch) {
                    customerSearch.value = data.client.name;
                }
            }
        } else {
            alert('❌ Erro ao cadastrar cliente: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro ao cadastrar cliente:', error);
        alert('❌ Erro ao cadastrar cliente: ' + error.message);
    });
}

function addItem(product) {
    // Verificar se o produto já está na lista
    const existingItem = items.find(i => i.product_id === product.id);
    
    if (existingItem) {
        // Se já existe, apenas aumentar a quantidade
        existingItem.qty += 1;
        updateItemsList();
        calculateTotals();
        updateOsButtons();
        return;
    }
    
    // Criar novo item com ID único baseado em timestamp + índice
    const item = {
        id: Date.now() + Math.random(), // ID único
        product_id: product.id,
        name: product.name,
        ref: product.ref || 'N/A',
        price: parseFloat(product.price || product.unit_price || 0),
        qty: 1,
        discount: 0
    };
    
    items.push(item);
    updateItemsList();
    calculateTotals();
}

function removeItem(id) {
    // Converter para número para garantir comparação correta
    const numId = typeof id === 'string' ? parseFloat(id) : id;
    const index = items.findIndex(i => {
        const itemId = typeof i.id === 'string' ? parseFloat(i.id) : i.id;
        return itemId === numId;
    });
    
    if (index !== -1) {
        items.splice(index, 1);
        updateItemsList();
        calculateTotals();
        updateOsButtons();
        console.log('Item removido:', numId, 'Itens restantes:', items.length);
    } else {
        console.warn('Item não encontrado para remover:', numId);
    }
}

function updateItemQty(id, delta) {
    const item = items.find(i => i.id === id);
    if (item) {
        item.qty = Math.max(0.001, item.qty + delta);
        if (item.qty <= 0) {
            removeItem(id);
        } else {
            updateItemsList();
            calculateTotals();
            updateOsButtons();
        }
    }
}

function updateItemQtyInput(id, value) {
    const item = items.find(i => i.id === id);
    if (item) {
        // Converter valor brasileiro (vírgula) para decimal
        const qtyStr = String(value).replace(',', '.');
        const qty = parseFloat(qtyStr) || 0.001;
        if (qty <= 0) {
            removeItem(id);
        } else {
            item.qty = qty;
            updateItemsList();
            calculateTotals();
            updateOsButtons();
        }
    }
}

function formatQtyInput(input) {
    let value = input.value;
    // Remover tudo exceto números e vírgula/ponto
    value = value.replace(/[^\d,.-]/g, '');
    // Substituir vírgula por ponto para parseFloat
    const numValue = parseFloat(value.replace(',', '.')) || 0.001;
    // Formatar com 2 decimais e vírgula
    input.value = numValue.toFixed(2).replace('.', ',');
    
    // Atualizar o item se tiver data-item-id no parent
    const itemDiv = input.closest('[data-item-id]');
    if (itemDiv) {
        const itemId = parseFloat(itemDiv.getAttribute('data-item-id'));
        updateItemQtyInput(itemId, input.value);
    }
}

function updateItemPrice(id, value) {
    const item = items.find(i => i.id === id);
    if (item) {
        // Converter valor brasileiro (vírgula) para decimal
        const priceStr = String(value).replace(',', '.');
        const price = parseFloat(priceStr) || 0;
        item.price = Math.max(0, price);
        updateItemsList();
        calculateTotals();
        updateOsButtons();
    }
}

function formatPriceInput(input) {
    let value = input.value;
    // Remover tudo exceto números e vírgula/ponto
    value = value.replace(/[^\d,.-]/g, '');
    // Substituir vírgula por ponto para parseFloat
    const numValue = parseFloat(value.replace(',', '.')) || 0;
    // Formatar com 2 decimais e vírgula
    input.value = numValue.toFixed(2).replace('.', ',');
    
    // Atualizar o item se tiver data-item-id no parent
    const itemDiv = input.closest('[data-item-id]');
    if (itemDiv) {
        const itemId = parseFloat(itemDiv.getAttribute('data-item-id'));
        updateItemPrice(itemId, input.value);
    }
}

function updateItemsList() {
    const list = document.getElementById('items-list');
    const empty = document.getElementById('empty-state');
    
    if (items.length === 0) {
        list.innerHTML = '';
        empty.style.display = 'block';
        return;
    }
    
    empty.style.display = 'none';
    list.innerHTML = items.map(item => {
        const subtotal = (item.price * item.qty) - (item.discount || 0);
        return `
        <div class="p-3 border border-gray-200 rounded-lg hover:bg-gray-50" data-item-id="${item.id}">
            <div class="flex items-start justify-between gap-4 mb-2">
                <div class="flex-1">
                    <div class="font-semibold text-gray-800">${escapeHtml(item.name)}</div>
                    <div class="text-sm text-gray-600">Ref: ${escapeHtml(item.ref)}</div>
                </div>
                <button onclick="removeItem(${item.id})" class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700 font-bold text-sm" title="Remover item">×</button>
            </div>
            <div class="grid grid-cols-3 gap-2 items-center">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Preço Unitário</label>
                    <input 
                        type="text" 
                        inputmode="decimal"
                        value="${item.price.toFixed(2).replace('.', ',')}"
                        onchange="updateItemPrice(${item.id}, this.value)"
                        onblur="formatPriceInput(this)"
                        onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || event.charCode === 44 || event.charCode === 46"
                        class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-semibold"
                        placeholder="0,00"
                    >
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Quantidade</label>
                    <div class="flex items-center gap-1">
                        <button onclick="updateItemQty(${item.id}, -0.1)" class="px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600 font-bold text-sm" title="Diminuir">-</button>
                        <input 
                            type="text" 
                            inputmode="decimal"
                            value="${item.qty.toFixed(2).replace('.', ',')}"
                            onchange="updateItemQtyInput(${item.id}, this.value)"
                            onblur="formatQtyInput(this)"
                            onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || event.charCode === 44 || event.charCode === 46"
                            class="w-full px-2 py-1 text-sm border border-gray-300 rounded text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-medium"
                        >
                        <button onclick="updateItemQty(${item.id}, 0.1)" class="px-2 py-1 bg-green-500 text-white rounded hover:bg-green-600 font-bold text-sm" title="Aumentar">+</button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Subtotal</label>
                    <div class="px-2 py-1 text-sm font-semibold text-gray-800 bg-gray-100 rounded text-center">
                        R$ ${subtotal.toFixed(2).replace('.', ',')}
                    </div>
                </div>
            </div>
        </div>
    `;
    }).join('');
}

function calculateTotals() {
    totalGross = items.reduce((sum, item) => sum + (item.price * item.qty), 0);
    totalDiscount = items.reduce((sum, item) => sum + (item.discount || 0), 0);
    totalNet = totalGross - totalDiscount;

    document.getElementById('subtotal').textContent = `R$ ${totalGross.toFixed(2).replace('.', ',')}`;
    document.getElementById('discount').textContent = `R$ ${totalDiscount.toFixed(2).replace('.', ',')}`;
    document.getElementById('total').textContent = `R$ ${totalNet.toFixed(2).replace('.', ',')}`;

    updatePaymentsTotal();
    
    // Atualizar modal de criar OS se estiver aberto
    const createOsModal = document.getElementById('create-os-modal');
    if (createOsModal && !createOsModal.classList.contains('hidden')) {
        updateCreateOsModalInfo();
    }
}

function addPayment(method) {
    if (!currentStoreId || !cashSessionOpen) {
        alert('Selecione uma loja e abra o caixa primeiro!');
        return;
    }
    
    if (totalNet <= 0) {
        alert('Adicione itens à venda primeiro');
        return;
    }

    // Tratamento especial para "Sinal"
    if (method === 'sinal') {
        // Se já houver outros pagamentos, remover antes de adicionar sinal
        if (payments.length > 0) {
            const confirmRemove = confirm('⚠️ Ao adicionar Sinal, os outros pagamentos serão removidos.\n\nDeseja continuar?');
            if (!confirmRemove) {
                return;
            }
            payments = [];
        }
        
        // Abrir modal para configurar sinal
        const sinalAmount = prompt('💰 Digite o valor do sinal (R$):');
        if (!sinalAmount || parseFloat(sinalAmount) <= 0) {
            return;
        }
        
        const sinalValue = parseFloat(sinalAmount.replace(',', '.'));
        if (sinalValue >= totalNet) {
            alert('⚠️ O valor do sinal não pode ser maior ou igual ao total da venda.');
            return;
        }
        
        // Selecionar método de pagamento do sinal
        const sinalMethod = prompt('💳 Selecione o método de pagamento do sinal:\n1 - Dinheiro\n2 - PIX\n3 - Cartão de Crédito\n4 - Cartão de Débito\n\nDigite o número:');
        const methodMap = {
            '1': 'money',
            '2': 'pix',
            '3': 'card_credit',
            '4': 'card_debit'
        };
        
        const selectedMethod = methodMap[sinalMethod] || 'money';
        const balance = totalNet - sinalValue;
        
        const payment = {
            id: Date.now(),
            method: 'sinal',
            amount: sinalValue,
            sinal_method: selectedMethod, // Método de pagamento do sinal
            balance: balance, // Saldo a receber
            installments: null
        };
        
        payments.push(payment);
        updatePaymentsList();
        updatePaymentsTotal();
        return;
    }
    
    // Se já houver sinal, não permitir adicionar outros pagamentos
    const hasSinal = payments.find(p => p.method === 'sinal');
    if (hasSinal) {
        alert('⚠️ Quando há pagamento por Sinal, não é possível adicionar outros métodos de pagamento.\n\nRemova o Sinal primeiro se desejar usar outro método.');
        return;
    }

    const remaining = totalNet - payments.reduce((sum, p) => sum + p.amount, 0);
    const amount = remaining > 0 ? remaining : 0;

    if (amount <= 0) {
        alert('Valor já está totalmente pago');
        return;
    }

    const payment = {
        id: Date.now(),
        method: method,
        amount: amount,
        installments: (method === 'card_credit' || method === 'boleto_installment') ? 1 : null
    };

    payments.push(payment);
    updatePaymentsList();
    updatePaymentsTotal();
}

function removePayment(id) {
    payments = payments.filter(p => p.id !== id);
    updatePaymentsList();
    updatePaymentsTotal();
}

function updatePaymentAmount(id, amount) {
    const payment = payments.find(p => p.id === id);
    if (payment) {
        payment.amount = parseFloat(amount) || 0;
        updatePaymentsList();
        updatePaymentsTotal();
    }
}

function updatePaymentInstallments(id, installments) {
    const payment = payments.find(p => p.id === id);
    if (payment) {
        payment.installments = parseInt(installments) || 1;
        updatePaymentsList();
    }
}

function updatePaymentsList() {
    const list = document.getElementById('payments-list');
    list.innerHTML = payments.map(payment => `
        <div class="p-2 border border-gray-200 rounded bg-gray-50">
            <div class="text-sm font-semibold mb-1 text-gray-800 whitespace-nowrap">${getMethodName(payment.method)}</div>
            <input 
                type="number" 
                step="0.01" 
                value="${payment.amount.toFixed(2)}"
                onchange="updatePaymentAmount(${payment.id}, this.value)"
                class="w-full mb-1 px-2 py-1 text-sm border border-gray-300 rounded"
                ${payment.method === 'sinal' ? 'readonly' : ''}
            >
            ${payment.method === 'sinal' && payment.balance ? `
                <div class="text-xs text-gray-600 mb-1">
                    💰 Saldo a receber: R$ ${payment.balance.toFixed(2).replace('.', ',')}
                </div>
            ` : ''}
            ${(payment.method === 'card_credit' || payment.method === 'boleto_installment') ? `
                <input 
                    type="number" 
                    min="1" 
                    value="${payment.installments || 1}"
                    onchange="updatePaymentInstallments(${payment.id}, this.value)"
                    placeholder="Parcelas"
                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded"
                >
            ` : ''}
            <button onclick="removePayment(${payment.id})" class="mt-1 w-full px-2 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600">Remover</button>
        </div>
    `).join('');
}

function getMethodName(method) {
    const names = {
        'money': '💵 Dinheiro',
        'pix': '📱 PIX',
        'card_credit': '💳 Cartão de Crédito',
        'card_debit': '💳 Cartão de Débito',
        'boleto': '📄 Boleto',
        'boleto_installment': '📄 Boleto Parcelado',
        'sinal': '💰 Sinal',
        'other': '➕ Outros'
    };
    return names[method] || method;
}

function updatePaymentsTotal() {
    const paid = payments.reduce((sum, p) => sum + p.amount, 0);
    
    // Para pagamento "Sinal", considerar o saldo também
    const sinalPayment = payments.find(p => p.method === 'sinal' && p.balance);
    const totalWithBalance = paid + (sinalPayment ? sinalPayment.balance : 0);
    
    document.getElementById('paid-total').textContent = `R$ ${paid.toFixed(2).replace('.', ',')}`;
    
    const btn = document.getElementById('checkout-btn');
    // Para "Sinal", considerar que o total está coberto (sinal + saldo)
    const diff = sinalPayment && sinalPayment.balance 
        ? Math.abs(totalWithBalance - totalNet)
        : Math.abs(paid - totalNet);
    btn.disabled = diff > 0.01 || items.length === 0 || !currentStoreId || !cashSessionOpen;
    
    // Atualizar botões de OS
    updateOsButtons();
}

function updateOsButtons() {
    const createOsBtn = document.getElementById('create-os-btn');
    const createOsBtnText = document.getElementById('create-os-btn-text');
    const createOsBtnIcon = document.getElementById('create-os-btn-icon');
    
    // Garantir que os event listeners estejam configurados
    setupOsButtons();
    
    // Criar OS: só precisa de caixa aberto e loja selecionada (não precisa ter itens)
    if (createOsBtn) {
        const shouldDisable = !cashSessionOpen || !currentStoreId;
        
        // Não usar disabled, mas sim classes CSS e pointer-events
        if (shouldDisable) {
            // Estado desabilitado: fundo cinza claro, texto escuro
            createOsBtn.className = 'w-full px-6 py-4 bg-gray-300 rounded-lg font-bold text-base shadow-lg transition-all duration-200 cursor-not-allowed';
            createOsBtn.style.pointerEvents = 'auto'; // Permitir clique para mostrar mensagem
            createOsBtn.style.setProperty('background-color', '#d1d5db', 'important'); // gray-300
            createOsBtn.style.setProperty('background-image', 'none', 'important');
            createOsBtn.style.setProperty('color', '#111827', 'important'); // gray-900 - texto bem escuro
            if (createOsBtnText) {
                createOsBtnText.style.setProperty('color', '#111827', 'important');
            }
            if (createOsBtnIcon) {
                createOsBtnIcon.style.setProperty('color', '#111827', 'important');
            }
            createOsBtn.title = !cashSessionOpen ? 'Abra o caixa primeiro' : 'Selecione uma loja primeiro';
        } else {
            // Estado habilitado: fundo roxo escuro, texto branco (OK porque fundo é colorido)
            createOsBtn.className = 'w-full px-6 py-4 rounded-lg font-bold text-base shadow-lg transition-all duration-200 cursor-pointer';
            createOsBtn.style.pointerEvents = 'auto';
            createOsBtn.style.cursor = 'pointer';
            createOsBtn.style.setProperty('background', 'linear-gradient(to right, #9333ea, #7e22ce)', 'important'); // purple-600 to purple-700
            createOsBtn.style.setProperty('color', '#ffffff', 'important'); // branco OK porque fundo é roxo escuro
            createOsBtn.style.setProperty('z-index', '10', 'important');
            if (createOsBtnText) {
                createOsBtnText.style.setProperty('color', '#ffffff', 'important');
            }
            if (createOsBtnIcon) {
                createOsBtnIcon.style.setProperty('color', '#ffffff', 'important');
            }
            createOsBtn.onmouseenter = function() {
                this.style.setProperty('background', 'linear-gradient(to right, #7e22ce, #6b21a8)', 'important');
                this.style.cursor = 'pointer';
            };
            createOsBtn.onmouseleave = function() {
                this.style.setProperty('background', 'linear-gradient(to right, #9333ea, #7e22ce)', 'important');
                this.style.cursor = 'pointer';
            };
            createOsBtn.title = 'Criar uma nova Ordem de Serviço';
        }
    }
}

async function checkout() {
    if (!currentStoreId) {
        alert('Selecione uma loja primeiro!');
        return;
    }
    
    if (!cashSessionOpen) {
        alert('Abra o caixa primeiro!');
        return;
    }
    
    if (items.length === 0) {
        alert('Adicione itens à venda');
        return;
    }

    // Verificar se há pagamento "sinal" configurado
    const sinalPayment = payments.find(p => p.method === 'sinal');
    
    // Se houver sinal, validar que não há outros pagamentos e que o valor está correto
    if (sinalPayment) {
        // Quando há sinal, não deve haver outros pagamentos
        const otherPayments = payments.filter(p => p.method !== 'sinal');
        if (otherPayments.length > 0) {
            alert('⚠️ Quando há pagamento por Sinal, não é possível adicionar outros métodos de pagamento.\n\nRemova os outros pagamentos ou remova o Sinal.');
            return;
        }
        
        // Validar que o valor do sinal é menor que o total
        if (sinalPayment.amount >= totalNet) {
            alert('⚠️ O valor do sinal não pode ser maior ou igual ao total da venda.');
            return;
        }
        
        // Validar que o sinal + saldo = total
        const sinalBalance = sinalPayment.balance || (totalNet - sinalPayment.amount);
        const sinalTotal = sinalPayment.amount + sinalBalance;
        const diff = Math.abs(sinalTotal - totalNet);
        
        if (diff > 0.01) {
            alert(`⚠️ O valor do sinal (R$ ${sinalPayment.amount.toFixed(2).replace('.', ',')}) + saldo (R$ ${sinalBalance.toFixed(2).replace('.', ',')}) não confere com o total (R$ ${totalNet.toFixed(2).replace('.', ',')})`);
            return;
        }
    } else {
        // Se não há sinal, validar que a soma dos pagamentos confere com o total
        const finalPaid = payments.reduce((sum, p) => sum + p.amount, 0);
        const diff = Math.abs(finalPaid - totalNet);
        
        if (diff > 0.01) {
            alert(`A soma dos pagamentos (R$ ${finalPaid.toFixed(2).replace('.', ',')}) não confere com o total (R$ ${totalNet.toFixed(2).replace('.', ',')})`);
            return;
        }
    }

    // Preparar pagamentos com dados do sinal se houver
    const paymentsToSend = payments.map(p => {
        const payment = {
            method: p.method,
            amount: p.amount,
            installments: p.installments || null
        };
        
        // Se for sinal, incluir método de pagamento do sinal e saldo
        if (p.method === 'sinal') {
            payment.sinal_method = p.sinal_method || 'money';
            payment.balance = p.balance || 0;
        }
        
        return payment;
    });
    
    const saleData = {
        items: items.map(item => ({
            product_id: item.product_id,
            qty: item.qty,
            price: item.price,
            discount: item.discount || 0
        })),
        customer_id: document.getElementById('customer-id').value || null,
        service_order_id: currentServiceOrderId || null, // Se houver OS carregada
        payments: paymentsToSend,
        store_id: currentStoreId,
        // Flag para criar OS automaticamente
        create_os: true
    };

    try {
        const response = await fetch('{{ route('pdv.checkout') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(saleData)
        });

        const data = await response.json();

        if (data.error) {
            alert(data.error);
            return;
        }

        alert(`✅ Venda finalizada com sucesso!\nNúmero: ${data.sale_number}`);
        
        // Limpar
        items = [];
        payments = [];
        currentServiceOrderId = null; // Limpar OS vinculada
        document.getElementById('product-search').value = '';
        document.getElementById('customer-id').value = '';
        document.getElementById('customer-name').textContent = '';
        document.getElementById('customer-search').value = '';
        updateItemsList();
        updatePaymentsList();
        calculateTotals();
        document.getElementById('product-search').focus();
    } catch (error) {
        alert('Erro ao finalizar venda');
    }
}

// Fechar resultados ao clicar fora
document.addEventListener('click', function(e) {
    if (!e.target.closest('#product-results') && !e.target.closest('#product-search')) {
        document.getElementById('product-results').classList.add('hidden');
    }
    if (!e.target.closest('#customer-results') && !e.target.closest('#customer-search')) {
        document.getElementById('customer-results').classList.add('hidden');
    }
});

// Variável para armazenar service_order_id atual
let currentServiceOrderId = null;

// Função para abrir modal de criar OS
function openCreateOsModal() {
    // Verificações antes de abrir
    if (!currentStoreId) {
        alert('⚠️ Selecione uma loja primeiro!');
        return;
    }
    
    if (!cashSessionOpen) {
        alert('⚠️ Abra o caixa primeiro para criar uma OS!');
        return;
    }
    
    const modal = document.getElementById('create-os-modal');
    if (!modal) {
        alert('❌ Erro: Modal não encontrado. Recarregue a página.');
        return;
    }
    
    // Atualizar informações no modal
    updateCreateOsModalInfo();
    
    // Gerar e exibir número da OS
    generateAndDisplayOsNumber();
    
    // Configurar busca de cliente integrada
    setupCreateOsCustomerSearch();
    
    // Configurar busca de produtos com autocomplete
    setupCreateOsProductSearch();
    
    // Configurar cálculo automático de receita
    setupPrescriptionAutoCalculate();
    
    // Inicializar itens da OS (começar vazio ou com itens do carrinho se houver)
    createOsItems = items.length > 0 ? items.map(item => ({
        id: Date.now() + Math.random(),
        product_id: item.product_id,
        name: item.name || 'Produto',
        ref: item.ref || '',
        qty: item.qty,
        unit_price: item.price,
        unit: 'UN',
        product_type_name: item.product_type_name || null,
    })) : [];
    updateCreateOsItemsTable();
    calculateCreateOsTotals();
    checkPrescriptionRequired(); // Verificar se receita é obrigatória
    
    // Preencher loja selecionada
    const storeSelect = document.getElementById('create-os-store-id');
    if (storeSelect && currentStoreId) {
        storeSelect.value = currentStoreId;
    }
    
    // Mostrar modal - CSS já centraliza automaticamente
    modal.classList.remove('hidden');
}

// Variável para armazenar itens da OS sendo criada
let createOsItems = [];

// Função para trocar abas no modal de criar OS
function switchCreateOsTab(tabName) {
    // Esconder todos os conteúdos
    document.querySelectorAll('.create-os-tab-content').forEach(c => c.classList.add('hidden'));
    // Remover active de todos os botões
    document.querySelectorAll('.create-os-tab-btn').forEach(b => {
        b.classList.remove('active', 'border-blue-600', 'text-blue-600');
        b.classList.add('border-transparent', 'text-gray-500');
    });
    // Mostrar conteúdo da aba selecionada
    document.getElementById('create-os-content-' + tabName).classList.remove('hidden');
    // Ativar botão da aba
    const btn = document.getElementById('create-os-tab-' + tabName);
    btn.classList.add('active', 'border-blue-600', 'text-blue-600');
    btn.classList.remove('border-transparent', 'text-gray-500');
    
    // Se for a aba de receita, configurar listeners novamente
    if (tabName === 'prescription') {
        setupPrescriptionAutoCalculate();
    }
}

// Configurar autocomplete de produtos
function setupCreateOsProductSearch() {
    const searchInput = document.getElementById('create-os-product-search');
    const resultsDiv = document.getElementById('create-os-product-results');
    const loadingDiv = document.getElementById('create-os-product-search-loading');
    
    console.log('🔧 Configurando busca de produtos:', {
        searchInput: !!searchInput,
        resultsDiv: !!resultsDiv,
        loadingDiv: !!loadingDiv,
        currentStoreId: currentStoreId
    });
    
    if (!searchInput || !resultsDiv) {
        console.warn('⚠️ Elementos de busca não encontrados');
        return;
    }
    
    // Limpar timeout anterior se existir
    if (window.createOsProductSearchTimeout) {
        clearTimeout(window.createOsProductSearchTimeout);
    }
    
    let searchTimeout;
    let selectedIndex = -1;
    
    // Remover listeners anteriores (se houver)
    const newSearchInput = searchInput.cloneNode(true);
    searchInput.parentNode.replaceChild(newSearchInput, searchInput);
    
    // Obter referências atualizadas
    const actualSearchInput = document.getElementById('create-os-product-search');
    const actualResultsDiv = document.getElementById('create-os-product-results');
    const actualLoadingDiv = document.getElementById('create-os-product-search-loading');
    
    actualSearchInput.addEventListener('input', function(e) {
        const search = e.target.value.trim();
        
        // Limpar seleção
        selectedIndex = -1;
        
        if (search.length < 2) {
            actualResultsDiv.classList.add('hidden');
            if (actualLoadingDiv) actualLoadingDiv.classList.add('hidden');
            return;
        }
        
        // Mostrar loading
        if (actualLoadingDiv) actualLoadingDiv.classList.remove('hidden');
        actualResultsDiv.classList.add('hidden');
        
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            // Usar loja já selecionada no PDV
            const storeId = currentStoreId;
            
            if (!storeId) {
                actualResultsDiv.innerHTML = `
                    <div class="p-4 text-center text-yellow-600 bg-yellow-50 border-b border-yellow-200">
                        <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <p class="text-sm font-medium">Selecione uma loja no PDV primeiro!</p>
                    </div>
                `;
                actualResultsDiv.classList.remove('hidden');
                if (actualLoadingDiv) actualLoadingDiv.classList.add('hidden');
                return;
            }
            
            const url = `{{ route('os.products.lookup') }}?q=${encodeURIComponent(search)}&store_id=${storeId}`;
            
            console.log('🔍 Buscando produtos:', { search, storeId, url });
            
            fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(res => {
                    console.log('📥 Resposta recebida:', res.status, res.statusText);
                    if (!res.ok) {
                        console.error('❌ Erro na requisição:', res.status, res.statusText);
                        throw new Error('Erro na requisição: ' + res.status);
                    }
                    return res.json();
                })
                .then(data => {
                    console.log('✅ Dados recebidos:', data);
                    if (actualLoadingDiv) actualLoadingDiv.classList.add('hidden');
                    
                    if (!data || data.length === 0) {
                        actualResultsDiv.innerHTML = `
                            <div class="p-4 text-center text-gray-500">
                                <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-sm font-medium">Nenhum produto encontrado</p>
                                <p class="text-xs mt-1">Tente buscar por nome, código ou EAN</p>
                            </div>
                        `;
                        actualResultsDiv.classList.remove('hidden');
                        return;
                    }
                    
                    // Mostrar até 20 resultados
                    const displayData = data.slice(0, 20);
                    const hasMore = data.length > 20;
                    
                    actualResultsDiv.innerHTML = displayData.map((product, idx) => {
                        const safeName = (product.name || 'Sem nome').replace(/"/g, '&quot;').replace(/'/g, "\\'");
                        const ref = (product.ref || 'N/A').replace(/"/g, '&quot;');
                        const price = (product.unit_price || 0).toFixed(2).replace('.', ',');
                        const highlight = search.toLowerCase();
                        
                        // Destacar texto pesquisado
                        const highlightedName = safeName.replace(
                            new RegExp(`(${highlight})`, 'gi'),
                            '<mark class="bg-yellow-200 px-1 rounded">$1</mark>'
                        );
                        
                        const productTypeName = (product.product_type_name || '').replace(/"/g, '&quot;').replace(/'/g, "\\'");
                        return `
                            <div 
                                onclick="selectCreateOsProduct(${product.id}, '${safeName}', '${ref}', ${product.unit_price || 0}, '${product.unit || 'UN'}', '${productTypeName}')" 
                                class="product-result-item p-4 hover:bg-blue-50 cursor-pointer border-b border-gray-100 transition-all ${idx === selectedIndex ? 'bg-blue-50' : ''}"
                                data-index="${idx}"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-900 text-sm mb-1">${highlightedName}</div>
                                        <div class="flex items-center gap-3 text-xs text-gray-600">
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                                </svg>
                                                Ref: <span class="font-medium">${ref}</span>
                                            </span>
                                            ${product.ean13 ? `<span class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                                                </svg>
                                                EAN: <span class="font-medium">${product.ean13}</span>
                                            </span>` : ''}
                                        </div>
                                    </div>
                                    <div class="ml-4 text-right">
                                        <div class="text-lg font-bold text-green-600">R$ ${price}</div>
                                        <div class="text-xs text-gray-500">${product.unit || 'UN'}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('') + (hasMore ? `
                        <div class="p-3 text-center text-xs text-gray-500 bg-gray-50 border-t border-gray-200">
                            Mostrando 20 de ${data.length} resultados. Digite mais para refinar a busca.
                        </div>
                    ` : '');
                    
                    actualResultsDiv.classList.remove('hidden');
                })
                .catch(error => {
                    console.error('❌ Erro ao buscar produtos:', error);
                    if (actualLoadingDiv) actualLoadingDiv.classList.add('hidden');
                    actualResultsDiv.innerHTML = `
                        <div class="p-4 text-center text-red-600 bg-red-50 border-b border-red-200">
                            <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm font-medium">Erro ao buscar produtos</p>
                            <p class="text-xs mt-1">${error.message || 'Tente novamente'}</p>
                        </div>
                    `;
                    actualResultsDiv.classList.remove('hidden');
                });
        }, 300); // Debounce de 300ms
    });
    
    // Navegação com teclado
    actualSearchInput.addEventListener('keydown', function(e) {
        const results = actualResultsDiv.querySelectorAll('.product-result-item');
        
        if (results.length === 0) return;
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, results.length - 1);
            updateProductSelection(results);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, -1);
            updateProductSelection(results);
        } else if (e.key === 'Enter' && selectedIndex >= 0) {
            e.preventDefault();
            const selected = results[selectedIndex];
            if (selected) {
                selected.click();
            }
        } else if (e.key === 'Escape') {
            actualResultsDiv.classList.add('hidden');
            selectedIndex = -1;
        }
    });
    
    // Fechar resultados ao clicar fora
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#create-os-product-results') && !e.target.closest('#create-os-product-search')) {
            actualResultsDiv.classList.add('hidden');
            selectedIndex = -1;
        }
    });
    
    console.log('✅ Busca de produtos configurada com sucesso');
}

function updateProductSelection(results) {
    results.forEach((item, idx) => {
        if (idx === selectedIndex) {
            item.classList.add('bg-blue-50');
            item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        } else {
            item.classList.remove('bg-blue-50');
        }
    });
}

// Selecionar produto do autocomplete
function selectCreateOsProduct(id, name, ref, unitPrice, unit, productTypeName) {
    addCreateOsItem({
        id: id,
        product_id: id,
        name: name,
        ref: ref,
        qty: 1,
        unit_price: unitPrice,
        unit: unit || 'UN',
        product_type_name: productTypeName || null,
    });
    
    // Limpar busca
    document.getElementById('create-os-product-search').value = '';
    document.getElementById('create-os-product-results').classList.add('hidden');
    document.getElementById('create-os-product-search').focus();
}

// Buscar produto para adicionar na OS (função antiga mantida para compatibilidade)
function searchCreateOsProduct() {
    const query = document.getElementById('create-os-product-search').value.trim();
    if (query.length < 2) {
        alert('⚠️ Digite pelo menos 2 caracteres para buscar!');
        return;
    }
    // O autocomplete já faz a busca, então apenas focar no campo
    document.getElementById('create-os-product-search').focus();
}

// Adicionar item à OS
function addCreateOsItem(product) {
    const item = {
        id: Date.now() + Math.random(),
        product_id: product.id,
        name: product.name,
        ref: product.ref || '',
        qty: 1,
        unit_price: product.unit_price || 0,
        unit: product.unit || 'UN',
        price_adjust: 0,
        add_disc_percent: 0,
        product_type_name: product.product_type_name || null, // Adicionar nome do tipo de produto
    };
    createOsItems.push(item);
    updateCreateOsItemsTable();
    calculateCreateOsTotals();
    checkPrescriptionRequired(); // Verificar se receita é obrigatória
}

// Remover item da OS
function removeCreateOsItem(id) {
    createOsItems = createOsItems.filter(i => i.id !== id);
    updateCreateOsItemsTable();
    calculateCreateOsTotals();
    checkPrescriptionRequired(); // Verificar se receita é obrigatória
}

// Verificar se há item Conserto e tornar receita opcional
function checkPrescriptionRequired() {
    // Verificar se há item Conserto nos itens da OS
    const hasConserto = createOsItems.some(item => {
        const itemName = (item.name || '').toLowerCase();
        const productTypeName = (item.product_type_name || '').toLowerCase();
        return itemName.includes('conserto') || productTypeName.includes('conserto');
    });
    
    // Se houver Conserto, tornar campos de receita opcionais
    const prescriptionTab = document.getElementById('create-os-tab-prescription');
    const prescriptionFields = document.querySelectorAll('#create-os-prescription-fields input, #create-os-prescription-fields textarea, #create-os-prescription-fields select');
    
    if (hasConserto) {
        // Adicionar indicador visual de que receita é opcional
        if (prescriptionTab) {
            prescriptionTab.classList.add('opacity-75');
            prescriptionTab.title = 'Receita é opcional quando há item Conserto';
        }
        
        // Remover required de todos os campos de receita
        prescriptionFields.forEach(field => {
            field.removeAttribute('required');
        });
    } else {
        // Remover indicador visual
        if (prescriptionTab) {
            prescriptionTab.classList.remove('opacity-75');
            prescriptionTab.title = '';
        }
        
        // Campos de receita voltam a ser opcionais por padrão (não são obrigatórios)
        // Mas se o usuário marcar o checkbox, alguns campos podem ser obrigatórios
    }
}

// Atualizar tabela de itens da OS
function updateCreateOsItemsTable() {
    const tbody = document.getElementById('create-os-items-table-body');
    if (!tbody) return;
    
    if (createOsItems.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-4 text-center text-gray-500 text-xs">Nenhum item adicionado. Busque e adicione produtos acima.</td></tr>';
        // Gerar número da OS mesmo sem itens
        generateAndDisplayOsNumber();
        return;
    }
    
    // Gerar número da OS quando itens mudarem
    generateAndDisplayOsNumber();
    
            tbody.innerHTML = createOsItems.map((item, idx) => {
                const lineTotal = item.qty * item.unit_price;
                return `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 rounded-lg p-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-900">${item.name || 'Sem nome'}</div>
                            <div class="text-xs text-gray-500">Ref: ${item.ref || 'N/A'}</div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-4">
                    <input 
                        type="number" 
                        step="0.001" 
                        value="${item.qty}" 
                        min="0.001" 
                        onchange="updateCreateOsItem(${idx}, 'qty', this.value)" 
                        class="w-24 px-3 py-2 text-sm border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-center font-medium"
                    >
                </td>
                <td class="px-4 py-4">
                    <input 
                        type="number" 
                        step="0.01" 
                        value="${item.unit_price.toFixed(2)}" 
                        min="0" 
                        onchange="updateCreateOsItem(${idx}, 'unit_price', this.value)" 
                        class="w-32 px-3 py-2 text-sm border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-right font-medium"
                    >
                </td>
                <td class="px-4 py-4">
                    <div class="text-base font-bold text-gray-900">R$ ${lineTotal.toFixed(2).replace('.', ',')}</div>
                </td>
                <td class="px-4 py-4">
                    <button 
                        type="button" 
                        onclick="removeCreateOsItem(${item.id})" 
                        class="text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg p-2 transition-all"
                        title="Remover item"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

// Atualizar item da OS
function updateCreateOsItem(idx, field, value) {
    if (createOsItems[idx]) {
        createOsItems[idx][field] = parseFloat(value) || 0;
        updateCreateOsItemsTable();
        calculateCreateOsTotals();
    }
}

// Calcular totais da OS
function calculateCreateOsTotals() {
    let subtotal = 0;
    createOsItems.forEach(item => {
        subtotal += item.qty * item.unit_price;
    });
    
    const total = subtotal;
    
    const subtotalDisplay = document.getElementById('create-os-subtotal-display');
    const totalDisplay = document.getElementById('create-os-total-display');
    
    // Atualizar saldo do sinal se houver
    updateCreateOsSinalBalance();
    
    if (subtotalDisplay) subtotalDisplay.textContent = 'R$ ' + subtotal.toFixed(2).replace('.', ',');
    if (totalDisplay) totalDisplay.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
    
    // Atualizar também no rodapé
    const itemsCount = document.getElementById('create-os-items-count');
    const totalFooter = document.getElementById('create-os-total');
    if (itemsCount) itemsCount.textContent = createOsItems.length;
    if (totalFooter) totalFooter.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
    
    // Atualizar saldo do sinal se houver
    updateCreateOsSinalBalance();
}

// Atualizar saldo do sinal
function updateCreateOsSinalBalance() {
    const sinalAmountInput = document.getElementById('create-os-sinal-amount');
    const balanceDisplay = document.getElementById('create-os-sinal-balance');
    
    if (!sinalAmountInput || !balanceDisplay) return;
    
    const total = createOsItems.reduce((sum, item) => sum + (item.qty * item.unit_price), 0);
    const sinalAmount = parseFloat(sinalAmountInput.value) || 0;
    const balance = total - sinalAmount;
    
    if (sinalAmount > 0 && balance > 0) {
        balanceDisplay.textContent = `R$ ${balance.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.')}`;
        balanceDisplay.classList.remove('text-gray-600');
        balanceDisplay.classList.add('text-orange-700', 'font-bold');
    } else {
        balanceDisplay.textContent = 'R$ 0,00';
        balanceDisplay.classList.remove('text-orange-700', 'font-bold');
        balanceDisplay.classList.add('text-gray-600');
    }
}

// Preview de imagem da OS
function previewCreateOsImage(input, index) {
    const file = input.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('create-os-preview-' + index);
            const removeBtn = document.getElementById('create-os-remove-' + index);
            if (preview) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
            }
            if (removeBtn) {
                removeBtn.classList.remove('hidden');
            }
        };
        reader.readAsDataURL(file);
    }
}

// Remover imagem da OS
function removeCreateOsImage(index) {
    const input = document.getElementById('create-os-image-' + index);
    const preview = document.getElementById('create-os-preview-' + index);
    const removeBtn = document.getElementById('create-os-remove-' + index);
    
    if (input) input.value = '';
    if (preview) preview.classList.add('hidden');
    if (removeBtn) removeBtn.classList.add('hidden');
}

// Função para calcular grau de perto manualmente (chamada pelo botão)
function calcularGrauPertoManual() {
    calcularGrauPerto();
}

// Variável para evitar múltiplas chamadas simultâneas
let isCalculating = false;

// Função para calcular grau de perto automaticamente
function calcularGrauPerto() {
    // Evitar múltiplas chamadas simultâneas
    if (isCalculating) {
        return;
    }
    isCalculating = true;
    
    try {
        // Obter valor da adição
        const adicaoInput = document.getElementById('create-os-prescription-adicao');
        if (!adicaoInput) {
            return;
        }
        
        // Capturar valor diretamente do campo
        const adicaoRaw = adicaoInput.value || '';
        const adicaoStr = adicaoRaw.trim();
        
        // Converter adição para número (suporta +2.00, 2.00, -2.00, etc)
        let adicao = 0;
        if (adicaoStr && adicaoStr !== '-' && !adicaoStr.startsWith('Ex:') && !adicaoStr.startsWith('Calculado')) {
            // Remover espaços, "Ex:", etc e converter
            let cleanAdicao = adicaoStr.replace(/\s/g, '').replace(/^Ex:/i, '').replace(/^Ex/i, '');
            // Remover qualquer texto que não seja número, sinal ou ponto
            cleanAdicao = cleanAdicao.replace(/[^\d+\-.]/g, '');
            adicao = parseFloat(cleanAdicao);
            if (isNaN(adicao)) {
                return;
            }
        }
        
        // Se não houver adição válida, não calcular
        if (!adicaoStr || adicaoStr === '-' || adicaoStr.startsWith('Ex:') || adicaoStr.startsWith('Calculado') || adicao === 0) {
            return;
        }
        
        // Função auxiliar para converter grau para número
    function parseGrau(value) {
        if (!value || value.trim() === '' || value === '-' || value.startsWith('Ex:')) return null;
        const clean = value.trim().replace(/\s/g, '').replace(/^Ex:/i, '').replace(/^Ex/i, '');
        const parsed = parseFloat(clean);
        console.log('  parseGrau:', value, '→', parsed);
        return isNaN(parsed) ? null : parsed;
    }
    
    // Função auxiliar para formatar grau
    function formatGrau(num) {
        if (num === null || isNaN(num)) return '';
        // Formatar com 2 casas decimais e sinal
        const formatted = num.toFixed(2);
        return num >= 0 ? '+' + formatted : formatted;
    }
    
    console.log('🔄 Calculando grau de perto...', { adicao });
    
    // Calcular OD (Olho Direito)
    const esfLongeODField = document.getElementById('create-os-prescription-longe-esferico-od');
    const cilLongeODField = document.getElementById('create-os-prescription-longe-cilindrico-od');
    const eixoLongeODField = document.getElementById('create-os-prescription-longe-eixo-od');
    
    if (!esfLongeODField) {
        console.warn('❌ Campo esfLongeOD não encontrado');
        return;
    }
    
    const esfLongeOD = parseGrau(esfLongeODField.value);
    const cilLongeOD = parseGrau(cilLongeODField?.value);
    const eixoLongeOD = parseGrau(eixoLongeODField?.value);
    
    console.log('👁️ OD Longe:', { esf: esfLongeOD, cil: cilLongeOD, eixo: eixoLongeOD });
    
    if (esfLongeOD !== null && !isNaN(esfLongeOD)) {
        // Perto_ESF = Longe_ESF + Adição
        const esfPertoOD = esfLongeOD + adicao;
        const pertoEsfODField = document.getElementById('create-os-prescription-perto-esferico-od');
        if (pertoEsfODField) {
            const valorCalculado = formatGrau(esfPertoOD);
            pertoEsfODField.value = valorCalculado;
            pertoEsfODField.classList.add('bg-green-50', 'border-green-300');
            setTimeout(() => {
                pertoEsfODField.classList.remove('bg-green-50', 'border-green-300');
            }, 2000);
            console.log('✅ OD Perto ESF calculado:', esfLongeOD, '+', adicao, '=', valorCalculado);
        } else {
            console.warn('❌ Campo perto-esferico-od não encontrado');
        }
        
        // Perto_CIL = Longe_CIL (mantém o mesmo)
        if (cilLongeOD !== null) {
            const pertoCilODField = document.getElementById('create-os-prescription-perto-cilindrico-od');
            if (pertoCilODField) {
                pertoCilODField.value = formatGrau(cilLongeOD);
                pertoCilODField.classList.add('bg-green-50', 'border-green-300');
                setTimeout(() => {
                    pertoCilODField.classList.remove('bg-green-50', 'border-green-300');
                }, 2000);
                console.log('✅ OD Perto CIL calculado:', formatGrau(cilLongeOD));
            }
        }
        
        // Perto_EIXO = Longe_EIXO (mantém o mesmo)
        if (eixoLongeOD !== null) {
            const pertoEixoODField = document.getElementById('create-os-prescription-perto-eixo-od');
            if (pertoEixoODField) {
                pertoEixoODField.value = eixoLongeOD.toString();
                pertoEixoODField.classList.add('bg-green-50', 'border-green-300');
                setTimeout(() => {
                    pertoEixoODField.classList.remove('bg-green-50', 'border-green-300');
                }, 2000);
                console.log('✅ OD Perto EIXO calculado:', eixoLongeOD.toString());
            }
        }
    }
    
    // Calcular OE (Olho Esquerdo)
    const esfLongeOEField = document.getElementById('create-os-prescription-longe-esferico-oe');
    const cilLongeOEField = document.getElementById('create-os-prescription-longe-cilindrico-oe');
    const eixoLongeOEField = document.getElementById('create-os-prescription-longe-eixo-oe');
    
    const esfLongeOE = parseGrau(esfLongeOEField?.value);
    const cilLongeOE = parseGrau(cilLongeOEField?.value);
    const eixoLongeOE = parseGrau(eixoLongeOEField?.value);
    
    console.log('👁️ OE Longe:', { esf: esfLongeOE, cil: cilLongeOE, eixo: eixoLongeOE });
    
    if (esfLongeOE !== null && !isNaN(esfLongeOE)) {
        // Perto_ESF = Longe_ESF + Adição
        const esfPertoOE = esfLongeOE + adicao;
        const pertoEsfOEField = document.getElementById('create-os-prescription-perto-esferico-oe');
        if (pertoEsfOEField) {
            pertoEsfOEField.value = formatGrau(esfPertoOE);
            pertoEsfOEField.classList.add('bg-green-50', 'border-green-300');
            setTimeout(() => {
                pertoEsfOEField.classList.remove('bg-green-50', 'border-green-300');
            }, 2000);
            console.log('✅ OE Perto ESF calculado:', formatGrau(esfPertoOE));
        }
        
        // Perto_CIL = Longe_CIL (mantém o mesmo)
        if (cilLongeOE !== null) {
            const pertoCilOEField = document.getElementById('create-os-prescription-perto-cilindrico-oe');
            if (pertoCilOEField) {
                pertoCilOEField.value = formatGrau(cilLongeOE);
                pertoCilOEField.classList.add('bg-green-50', 'border-green-300');
                setTimeout(() => {
                    pertoCilOEField.classList.remove('bg-green-50', 'border-green-300');
                }, 2000);
                console.log('✅ OE Perto CIL calculado:', formatGrau(cilLongeOE));
            }
        }
        
        // Perto_EIXO = Longe_EIXO (mantém o mesmo)
        if (eixoLongeOE !== null) {
            const pertoEixoOEField = document.getElementById('create-os-prescription-perto-eixo-oe');
            if (pertoEixoOEField) {
                pertoEixoOEField.value = eixoLongeOE.toString();
                pertoEixoOEField.classList.add('bg-green-50', 'border-green-300');
                setTimeout(() => {
                    pertoEixoOEField.classList.remove('bg-green-50', 'border-green-300');
                }, 2000);
                console.log('✅ OE Perto EIXO calculado:', eixoLongeOE.toString());
            }
        }
        }
    } finally {
        // Liberar flag após um pequeno delay para evitar chamadas muito frequentes
        setTimeout(() => {
            isCalculating = false;
        }, 100);
    }
}

// Configurar listeners para cálculo automático quando o modal abrir
function setupPrescriptionAutoCalculate() {
    console.log('🔧 Configurando listeners de cálculo automático...');
    
    // Aguardar um pouco para garantir que os campos existam
    setTimeout(() => {
        // Listener para campo de adição
        const adicaoField = document.getElementById('create-os-prescription-adicao');
        if (adicaoField) {
            console.log('✅ Campo de adição encontrado, valor atual:', adicaoField.value);
            console.log('🔍 Campo de adição - tipo:', adicaoField.type, 'placeholder:', adicaoField.placeholder);
            
            // Wrapper para garantir que o valor está atualizado
            const calcularWrapper = function(e) {
                setTimeout(() => {
                    calcularGrauPerto();
                }, 200);
            };
            
            // Remover listeners antigos se existirem
            const oldWrapper = adicaoField._calcularWrapper;
            if (oldWrapper) {
                adicaoField.removeEventListener('input', oldWrapper);
                adicaoField.removeEventListener('change', oldWrapper);
                adicaoField.removeEventListener('blur', oldWrapper);
                adicaoField.removeEventListener('keyup', oldWrapper);
            }
            
            // Guardar referência para poder remover depois
            adicaoField._calcularWrapper = calcularWrapper;
            
            // Adicionar novos listeners
            adicaoField.addEventListener('input', calcularWrapper);
            adicaoField.addEventListener('change', calcularWrapper);
            adicaoField.addEventListener('blur', calcularWrapper);
            adicaoField.addEventListener('keyup', calcularWrapper);
            adicaoField.addEventListener('paste', (e) => {
                setTimeout(() => {
                    const campo = document.getElementById('create-os-prescription-adicao');
                    if (campo) {
                        console.log('📋 Valor colado:', JSON.stringify(campo.value));
                        calcularGrauPerto();
                    }
                }, 150);
            });
        } else {
            console.warn('❌ Campo de adição NÃO encontrado');
        }
        
        // Listeners para campos de longe OD
        const camposLongeOD = [
            'create-os-prescription-longe-esferico-od',
            'create-os-prescription-longe-cilindrico-od',
            'create-os-prescription-longe-eixo-od'
        ];
        
        camposLongeOD.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                // Remover listeners antigos se existirem
                field.removeEventListener('input', calcularGrauPerto);
                field.removeEventListener('change', calcularGrauPerto);
                field.removeEventListener('blur', calcularGrauPerto);
                // Adicionar novos listeners
                field.addEventListener('input', calcularGrauPerto);
                field.addEventListener('change', calcularGrauPerto);
                field.addEventListener('blur', calcularGrauPerto);
                field.addEventListener('keyup', calcularGrauPerto);
            } else {
                console.warn('❌ Campo não encontrado:', fieldId);
            }
        });
        
        // Listeners para campos de longe OE
        const camposLongeOE = [
            'create-os-prescription-longe-esferico-oe',
            'create-os-prescription-longe-cilindrico-oe',
            'create-os-prescription-longe-eixo-oe'
        ];
        
        camposLongeOE.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                // Remover listeners antigos se existirem
                field.removeEventListener('input', calcularGrauPerto);
                field.removeEventListener('change', calcularGrauPerto);
                field.removeEventListener('blur', calcularGrauPerto);
                // Adicionar novos listeners
                field.addEventListener('input', calcularGrauPerto);
                field.addEventListener('change', calcularGrauPerto);
                field.addEventListener('blur', calcularGrauPerto);
                field.addEventListener('keyup', calcularGrauPerto);
            } else {
                console.warn('❌ Campo não encontrado:', fieldId);
            }
        });
        
        console.log('✅ Listeners configurados');
        
        // Testar cálculo imediatamente se já houver valores
        setTimeout(() => {
            console.log('🧪 Testando cálculo automático...');
            calcularGrauPerto();
        }, 300);
    }, 200);
}

// Listener global para capturar qualquer mudança nos campos de receita (mesmo se criados dinamicamente)
(function() {
    let lastValues = {};
    let isChecking = false;
    
    function checkForChanges() {
        if (isChecking) return;
        isChecking = true;
        
        const fields = [
            'create-os-prescription-adicao',
            'create-os-prescription-longe-esferico-od',
            'create-os-prescription-longe-cilindrico-od',
            'create-os-prescription-longe-eixo-od',
            'create-os-prescription-longe-esferico-oe',
            'create-os-prescription-longe-cilindrico-oe',
            'create-os-prescription-longe-eixo-oe'
        ];
        
        let hasChanges = false;
        fields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                const currentValue = field.value || '';
                if (lastValues[fieldId] !== currentValue) {
                    lastValues[fieldId] = currentValue;
                    // Ignorar valores vazios, "-", ou placeholders
                    if (currentValue && 
                        currentValue.trim() !== '' && 
                        currentValue !== '-' && 
                        !currentValue.startsWith('Ex:') &&
                        !currentValue.startsWith('Calculado')) {
                        hasChanges = true;
                    }
                }
            } else {
                // Se campo não existe, resetar valor
                if (lastValues[fieldId] !== undefined) {
                    delete lastValues[fieldId];
                }
            }
        });
        
        if (hasChanges) {
            setTimeout(() => {
                calcularGrauPerto();
                isChecking = false;
            }, 200);
        } else {
            isChecking = false;
        }
    }
    
    // Verificar mudanças a cada 150ms quando a aba de receita estiver visível
    setInterval(() => {
        const prescriptionTab = document.getElementById('create-os-content-prescription');
        if (prescriptionTab && !prescriptionTab.classList.contains('hidden')) {
            checkForChanges();
        }
    }, 150);
})();

// Listener global para capturar qualquer mudança nos campos de receita
document.addEventListener('DOMContentLoaded', function() {
    // Usar delegação de eventos para capturar mudanças mesmo em elementos dinâmicos
    // Listener global para input (mais responsivo)
    let inputTimeout;
    document.addEventListener('input', function(e) {
        const target = e.target;
        if (!target) return;
        
        if (target && (
            target.id === 'create-os-prescription-adicao' ||
            (target.id && target.id.startsWith('create-os-prescription-longe-'))
        )) {
            // Debounce para evitar múltiplas chamadas
            clearTimeout(inputTimeout);
            inputTimeout = setTimeout(() => {
                calcularGrauPerto();
            }, 300);
        }
    });
    
    // Listener global para change (quando campo perde foco)
    document.addEventListener('change', function(e) {
        const target = e.target;
        if (target && (
            target.id === 'create-os-prescription-adicao' ||
            (target.id && target.id.startsWith('create-os-prescription-longe-'))
        )) {
            calcularGrauPerto();
        }
    });
});

// Configurar busca de cliente no modal de criar OS
function setupCreateOsCustomerSearch() {
    const searchInput = document.getElementById('create-os-customer-search');
    const resultsDiv = document.getElementById('create-os-customer-results');
    
    if (!searchInput || !resultsDiv) return;
    
    let searchTimeout;
    
    searchInput.addEventListener('input', function(e) {
        const search = e.target.value.trim();
        
        if (search.length < 1) {
            resultsDiv.classList.add('hidden');
            return;
        }
        
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const url = `{{ route('os.clients.lookup') }}?q=${encodeURIComponent(search)}`;
            
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (!data || data.length === 0) {
                        // Detectar se é CPF/CNPJ ou nome
                        const cleanCpfCnpj = search.replace(/[^\d]/g, '');
                        const isOnlyNumbers = /^[\d.\-\/\s]+$/.test(search);
                        
                        const nameToUse = isOnlyNumbers ? '' : search;
                        const cpfCnpjToUse = isOnlyNumbers ? cleanCpfCnpj : '';
                        
                        resultsDiv.innerHTML = `
                            <div class="p-3 border-t border-gray-200 bg-yellow-50">
                                <div class="text-sm text-gray-700 mb-2 font-medium">❌ Cliente não encontrado</div>
                                <button 
                                    onclick="openClientModalFromCreateOs('${nameToUse.replace(/'/g, "\\'")}', '${cpfCnpjToUse}')" 
                                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium text-sm transition-colors shadow-md"
                                >
                                    ✏️ Cadastrar ${nameToUse ? `"${nameToUse.length > 25 ? nameToUse.substring(0, 25) + '...' : nameToUse}"` : 'Novo Cliente'}
                                </button>
                            </div>
                        `;
                        resultsDiv.classList.remove('hidden');
                        return;
                    }
                    
                    resultsDiv.innerHTML = data.map(client => {
                        const safeName = (client.name || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
                        const cpfCnpj = client.cpf_cnpj || '';
                        return `
                            <div onclick="selectCreateOsCustomer(${client.id}, '${safeName}', '${cpfCnpj}')" 
                                 class="p-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 text-sm">
                                <div class="font-medium">${client.name || 'Sem nome'}</div>
                                ${cpfCnpj ? `<div class="text-xs text-gray-600">${cpfCnpj}</div>` : ''}
                            </div>
                        `;
                    }).join('');
                    resultsDiv.classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Erro ao buscar clientes:', error);
                    resultsDiv.innerHTML = `<div class="p-2 text-red-600 text-sm">❌ Erro ao buscar clientes</div>`;
                    resultsDiv.classList.remove('hidden');
                });
        }, 300);
    });
    
    // Fechar resultados ao clicar fora
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#create-os-customer-results') && !e.target.closest('#create-os-customer-search')) {
            resultsDiv.classList.add('hidden');
        }
    });
    
    // A busca de produto agora é feita automaticamente pelo setupCreateOsProductSearch()
}

// Abrir modal de cliente a partir do modal de criar OS (fecha o modal de criar OS temporariamente)
function openClientModalFromCreateOs(name = '', cpfCnpj = '') {
    // Fechar modal de criar OS temporariamente
    const createOsModal = document.getElementById('create-os-modal');
    if (createOsModal) {
        createOsModal.classList.add('hidden');
    }
    
    // Abrir modal de cliente com dados pré-preenchidos
    openClientModalWithData(name, cpfCnpj);
}

// Função para fechar modal de criar OS
function closeCreateOsModal() {
    const modal = document.getElementById('create-os-modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
        // Limpar formulário
        const form = document.getElementById('create-os-form');
        if (form) form.reset();
        const deliveryDate = document.getElementById('create-os-delivery-date');
        if (deliveryDate) deliveryDate.value = '';
        const notes = document.getElementById('create-os-notes');
        if (notes) notes.value = '';
        // Limpar busca e seleção de cliente
        clearCreateOsSelectedCustomer();
        const searchInput = document.getElementById('create-os-customer-search');
        if (searchInput) searchInput.value = '';
        const resultsDiv = document.getElementById('create-os-customer-results');
        if (resultsDiv) resultsDiv.classList.add('hidden');
        // Limpar itens da OS
        createOsItems = [];
        updateCreateOsItemsTable();
        calculateCreateOsTotals();
        // Limpar imagens
        for (let i = 1; i <= 5; i++) {
            removeCreateOsImage(i);
        }
        // Resetar para aba Dados
        switchCreateOsTab('basic');
    }
}

// Função para gerar e exibir o número da OS
async function generateAndDisplayOsNumber() {
    if (!currentStoreId) {
        return;
    }
    
    try {
        // Verificar se há produto "Conserto" nos itens
        const hasConserto = createOsItems.some(item => {
            const name = (item.name || '').toLowerCase();
            const typeName = (item.product_type_name || '').toLowerCase();
            return name.includes('conserto') || typeName.includes('conserto');
        });
        
        const response = await fetch(`{{ route('os.generate-number') }}?store_id=${currentStoreId}&is_conserto=${hasConserto}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data.os_number) {
                const previewElement = document.getElementById('create-os-number-preview');
                const displayElement = document.getElementById('create-os-number-display');
                if (previewElement && displayElement) {
                    displayElement.textContent = data.os_number;
                    previewElement.style.display = 'block';
                }
            }
        }
    } catch (error) {
        console.error('Erro ao gerar número da OS:', error);
    }
}

// Atualizar informações do modal de criar OS
function updateCreateOsModalInfo() {
    // Atualizar contagem de itens e total (usar createOsItems)
    const itemsCount = document.getElementById('create-os-items-count');
    if (itemsCount) itemsCount.textContent = createOsItems.length;
    calculateCreateOsTotals();
    
    // Atualizar cliente selecionado se houver do campo principal
    const customerId = document.getElementById('customer-id').value;
    const customerNameEl = document.getElementById('customer-name');
    if (customerId && customerNameEl && customerNameEl.textContent.trim()) {
        selectCreateOsCustomer(customerId, customerNameEl.textContent.trim());
    }
    
    // Gerar número da OS quando itens mudarem
    generateAndDisplayOsNumber();
}

// Selecionar cliente no modal de criar OS
function selectCreateOsCustomer(id, name, cpfCnpj = '') {
    document.getElementById('create-os-customer-id').value = id;
    document.getElementById('create-os-customer-selected-name').textContent = name;
    document.getElementById('create-os-customer-selected-cpf').textContent = cpfCnpj ? `CPF/CNPJ: ${cpfCnpj}` : '';
    document.getElementById('create-os-customer-selected').classList.remove('hidden');
    document.getElementById('create-os-customer-search').value = '';
    document.getElementById('create-os-customer-results').classList.add('hidden');
}

// Limpar cliente selecionado no modal de criar OS
function clearCreateOsSelectedCustomer() {
    document.getElementById('create-os-customer-id').value = '';
    document.getElementById('create-os-customer-selected').classList.add('hidden');
    document.getElementById('create-os-customer-search').value = '';
    document.getElementById('create-os-customer-search').focus();
}

// Função para salvar criação de OS
async function saveCreateOs(event) {
    event.preventDefault();
    
    // Validações obrigatórias
    const storeId = currentStoreId || session('pdv_store_id'); // Usar loja já selecionada no PDV
    
    const customerId = document.getElementById('create-os-customer-id').value || null;
    if (!customerId) {
        alert('⚠️ Selecione um cliente antes de criar a OS!');
        switchCreateOsTab('basic');
        document.getElementById('create-os-customer-search').focus();
        return;
    }
    
    // Coletar dados do formulário
    const sourceSelect = document.getElementById('create-os-source');
    const sourceCustom = document.getElementById('create-os-source-custom');
    const source = (sourceSelect && sourceSelect.style.display !== 'none' ? sourceSelect.value : (sourceCustom ? sourceCustom.value.trim() : '')) || null;
    
    // Coletar dados do sinal
    const sinalAmount = parseFloat(document.getElementById('create-os-sinal-amount')?.value) || 0;
    const sinalMethod = document.getElementById('create-os-sinal-method')?.value || null;
    const total = createOsItems.reduce((sum, item) => sum + (item.qty * item.unit_price), 0);
    const sinalBalance = sinalAmount > 0 && sinalAmount < total ? total - sinalAmount : 0;
    const discountValue = 0;
    const advanceType = 'SEM';
    const advanceValue = 0;
    
    // Coletar itens (pode ser vazio)
    const osItems = createOsItems.length > 0 ? createOsItems.map(item => ({
        type: 'PRODUTO',
        product_id: item.product_id,
        name: item.name,
        ref: item.ref || '',
        unit: item.unit || 'UN',
        qty: parseFloat(item.qty) || 0,
        unit_price: parseFloat(item.unit_price) || 0,
        price_adjust: parseFloat(item.price_adjust) || 0,
        add_disc_percent: parseFloat(item.add_disc_percent) || 0,
    })) : [];
    
    // Verificar se há item Conserto - se houver, receita não é obrigatória
    const hasConserto = osItems.some(item => {
        const itemName = (item.name || '').toLowerCase();
        return itemName.includes('conserto');
    });
    
    // Coletar dados de receita (sempre coletar se houver dados preenchidos)
    const useCustomPrescription = document.getElementById('create-os-use-custom-prescription')?.checked || false;
    const prescription = {};
    
    // Coletar todos os campos da receita
    // Função auxiliar para coletar valor (retorna null apenas se realmente vazio)
    const getValue = (id) => {
        const el = document.getElementById(id);
        if (!el) return null;
        const value = el.value.trim();
        return value === '' ? null : value;
    };
    
    const customDoctorName = getValue('create-os-prescription-doctor-name');
    const customValidUntil = getValue('create-os-prescription-valid-until');
    const customAdicao = getValue('create-os-prescription-adicao');
    
    // Longe OD
    const customLongeEsfericoOD = getValue('create-os-prescription-longe-esferico-od');
    const customLongeCilindricoOD = getValue('create-os-prescription-longe-cilindrico-od');
    const customLongeEixoOD = getValue('create-os-prescription-longe-eixo-od');
    const customLongeAlturaOD = getValue('create-os-prescription-longe-altura-od');
    const customLongeDnpOD = getValue('create-os-prescription-longe-dnp-od');
    
    // Longe OE
    const customLongeEsfericoOE = getValue('create-os-prescription-longe-esferico-oe');
    const customLongeCilindricoOE = getValue('create-os-prescription-longe-cilindrico-oe');
    const customLongeEixoOE = getValue('create-os-prescription-longe-eixo-oe');
    const customLongeAlturaOE = getValue('create-os-prescription-longe-altura-oe');
    const customLongeDnpOE = getValue('create-os-prescription-longe-dnp-oe');
    
    // Perto OD
    const customPertoEsfericoOD = getValue('create-os-prescription-perto-esferico-od');
    const customPertoCilindricoOD = getValue('create-os-prescription-perto-cilindrico-od');
    const customPertoEixoOD = getValue('create-os-prescription-perto-eixo-od');
    const customPertoAlturaOD = getValue('create-os-prescription-perto-altura-od');
    const customPertoDnpOD = getValue('create-os-prescription-perto-dnp-od');
    
    // Perto OE
    const customPertoEsfericoOE = getValue('create-os-prescription-perto-esferico-oe');
    const customPertoCilindricoOE = getValue('create-os-prescription-perto-cilindrico-oe');
    const customPertoEixoOE = getValue('create-os-prescription-perto-eixo-oe');
    const customPertoAlturaOE = getValue('create-os-prescription-perto-altura-oe');
    const customPertoDnpOE = getValue('create-os-prescription-perto-dnp-oe');
    
    // Verificar se há algum dado preenchido (verificar todos os campos, não apenas alguns)
    const hasPrescriptionData = customLongeEsfericoOD || customLongeEsfericoOE || 
                                customPertoEsfericoOD || customPertoEsfericoOE ||
                                customLongeCilindricoOD || customLongeCilindricoOE ||
                                customPertoCilindricoOD || customPertoCilindricoOE ||
                                customLongeEixoOD || customLongeEixoOE ||
                                customPertoEixoOD || customPertoEixoOE ||
                                customLongeAlturaOD || customLongeAlturaOE ||
                                customPertoAlturaOD || customPertoAlturaOE ||
                                customLongeDnpOD || customLongeDnpOE ||
                                customPertoDnpOD || customPertoDnpOE ||
                                customAdicao || customDoctorName || customValidUntil;
    
    // Se houver dados ou checkbox marcado, salvar receita
    if (useCustomPrescription || hasPrescriptionData) {
        prescription.use_custom = true;
        // Sempre incluir todos os campos, usando os valores coletados diretamente
        // (já são null se vazios devido à função getValue)
        prescription.custom_doctor_name = customDoctorName;
        prescription.custom_valid_until = customValidUntil;
        prescription.custom_adicao = customAdicao;
        
        // Longe OD - sempre incluir todos os campos
        prescription.custom_longe_esferico_od = customLongeEsfericoOD;
        prescription.custom_longe_cilindrico_od = customLongeCilindricoOD;
        prescription.custom_longe_eixo_od = customLongeEixoOD;
        prescription.custom_longe_altura_od = customLongeAlturaOD;
        prescription.custom_longe_dnp_od = customLongeDnpOD;
        
        // Longe OE - sempre incluir todos os campos
        prescription.custom_longe_esferico_oe = customLongeEsfericoOE;
        prescription.custom_longe_cilindrico_oe = customLongeCilindricoOE;
        prescription.custom_longe_eixo_oe = customLongeEixoOE;
        prescription.custom_longe_altura_oe = customLongeAlturaOE;
        prescription.custom_longe_dnp_oe = customLongeDnpOE;
        
        // Perto OD - sempre incluir todos os campos
        prescription.custom_perto_esferico_od = customPertoEsfericoOD;
        prescription.custom_perto_cilindrico_od = customPertoCilindricoOD;
        prescription.custom_perto_eixo_od = customPertoEixoOD;
        prescription.custom_perto_altura_od = customPertoAlturaOD;
        prescription.custom_perto_dnp_od = customPertoDnpOD;
        
        // Perto OE - sempre incluir todos os campos
        prescription.custom_perto_esferico_oe = customPertoEsfericoOE;
        prescription.custom_perto_cilindrico_oe = customPertoCilindricoOE;
        prescription.custom_perto_eixo_oe = customPertoEixoOE;
        prescription.custom_perto_altura_oe = customPertoAlturaOE;
        prescription.custom_perto_dnp_oe = customPertoDnpOE;
    }
    
    // Coletar imagens
    const images = [];
    for (let i = 1; i <= 5; i++) {
        const imageInput = document.getElementById('create-os-image-' + i);
        if (imageInput && imageInput.files && imageInput.files[0]) {
            images.push(imageInput.files[0]);
        }
    }
    
    // Coletar anexo de receita
    const prescriptionAttachment = document.getElementById('create-os-prescription-attachment')?.files[0] || null;
    
    // Preparar FormData para envio
    const formData = new FormData();
    formData.append('store_id', storeId);
    formData.append('client_id', customerId);
    if (source) formData.append('source', source);
    
    // Adicionar dados do sinal se houver
    if (sinalAmount > 0 && sinalBalance > 0) {
        formData.append('sinal_amount', sinalAmount);
        formData.append('sinal_method', sinalMethod);
        formData.append('sinal_balance', sinalBalance);
    }
    if (discountValue > 0) formData.append('discount_value', discountValue);
    formData.append('advance_type', advanceType);
    if (advanceValue > 0) formData.append('advance_value', advanceValue);
    
    // Adicionar itens
    if (osItems.length > 0) {
        osItems.forEach((item, idx) => {
            formData.append(`items[${idx}][type]`, item.type || 'PRODUTO');
            formData.append(`items[${idx}][product_id]`, item.product_id || '');
            formData.append(`items[${idx}][name]`, item.name || '');
            formData.append(`items[${idx}][ref]`, item.ref || '');
            formData.append(`items[${idx}][unit]`, item.unit || 'UN');
            formData.append(`items[${idx}][qty]`, item.qty || 0);
            formData.append(`items[${idx}][unit_price]`, item.unit_price || 0);
            formData.append(`items[${idx}][price_adjust]`, item.price_adjust || 0);
            formData.append(`items[${idx}][add_disc_percent]`, item.add_disc_percent || 0);
        });
    }
    // Se não houver itens, não enviar array de itens (backend aceita OS sem itens)
    
    // Adicionar receita
    if (Object.keys(prescription).length > 0) {
        Object.keys(prescription).forEach(key => {
            // Enviar todos os valores, mesmo que sejam null ou string vazia
            // O backend vai tratar strings vazias como null
            let value = prescription[key];
            if (typeof value === 'boolean') {
                value = value ? '1' : '0';
            }
            // Enviar null como string vazia para que o backend possa processar
            if (value === null || value === undefined) {
                value = '';
            }
            formData.append(`prescription[${key}]`, value);
        });
    }
    
    // Adicionar anexo de receita
    if (prescriptionAttachment) {
        formData.append('prescription_attachment', prescriptionAttachment);
    }
    
    // Adicionar imagens
    images.forEach((image, idx) => {
        formData.append(`images[${idx}]`, image);
    });
    
    // Adicionar CSRF token
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    
    try {
        const response = await fetch('{{ route('os.store') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(errorText || 'Erro ao criar OS');
        }
        
        // Se redirecionou, significa sucesso
        if (response.redirected) {
            alert('✅ OS criada com sucesso!');
            closeCreateOsModal();
            // Recarregar página para mostrar a nova OS
            window.location.href = response.url;
            return;
        }
        
        const data = await response.json();
        
        if (data.success || data.os) {
            const osId = data.service_order_id || data.os?.id;
            const osNumber = data.os_number || data.os?.os_number || 'N/A';
            
            closeCreateOsModal();
            
            // Se a OS foi criada com sucesso e tem ID, adicionar automaticamente ao carrinho
            if (osId) {
                try {
                    // Buscar dados da OS para carregar no carrinho
                    const baseUrl = '{{ url('pdv/load-os') }}';
                    const loadUrl = `${baseUrl}/${osId}`;
                    
                    const loadResponse = await fetch(loadUrl, {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                    
                    if (loadResponse.ok) {
                        const loadData = await loadResponse.json();
                        if (loadData.success) {
                            // Marcar como carregamento automático para não mostrar alert duplicado
                            loadData._autoLoad = true;
                            
                            // Limpar carrinho atual
                            items = [];
                            
                            // Carregar itens da OS
                            if (loadData.items && loadData.items.length > 0) {
                                loadData.items.forEach((item, index) => {
                                    items.push({
                                        id: Date.now() + index + Math.random(),
                                        product_id: item.product_id,
                                        name: item.name || 'Produto sem nome',
                                        ref: item.ref || 'N/A',
                                        price: parseFloat(item.price || 0),
                                        qty: parseFloat(item.qty || 1),
                                        discount: parseFloat(item.discount || 0),
                                    });
                                });
                            }
                            
                            // Carregar cliente se houver
                            if (loadData.os && loadData.os.client_id) {
                                const customerIdInput = document.getElementById('customer-id');
                                const customerNameDiv = document.getElementById('customer-name');
                                const customerSearchInput = document.getElementById('customer-search');
                                
                                if (customerIdInput) customerIdInput.value = loadData.os.client_id;
                                if (customerNameDiv) customerNameDiv.textContent = loadData.os.client_name || '';
                                if (customerSearchInput) customerSearchInput.value = loadData.os.client_name || '';
                            }
                            
                            // Armazenar ID da OS
                            if (loadData.os && loadData.os.id) {
                                currentServiceOrderId = loadData.os.id;
                            }
                            
                            // Atualizar interface
                            updateItemsList();
                            calculateTotals();
                            updateOsButtons();
                            
                            // Mostrar mensagem de sucesso
                            const itemCount = loadData.items ? loadData.items.length : 0;
                            if (itemCount > 0) {
                                alert('✅ OS criada e adicionada ao carrinho!\n\nNúmero: ' + osNumber + '\nItens: ' + itemCount);
                            } else {
                                alert('✅ OS criada com sucesso!\n\nNúmero: ' + osNumber + '\n\nVocê pode adicionar produtos ao carrinho e finalizar a venda.');
                            }
                        }
                    }
                } catch (loadError) {
                    console.error('Erro ao carregar OS no carrinho:', loadError);
                    alert('✅ OS criada com sucesso!\n\nNúmero: ' + osNumber + '\n\n⚠️ Não foi possível carregar automaticamente no carrinho. Use "Carregar OS Existente" para adicioná-la.');
                }
            } else {
                alert('✅ OS criada com sucesso!\n\nNúmero: ' + osNumber);
            }
        } else {
            // Se houver erros de validação
            if (data.errors) {
                const errorMessages = Object.values(data.errors).flat().join('\n');
                alert('❌ Erro ao criar OS:\n' + errorMessages);
            } else {
                alert('❌ Erro ao criar OS: ' + (data.error || 'Erro desconhecido'));
            }
        }
    } catch (error) {
        console.error('Erro ao criar OS:', error);
        alert('❌ Erro ao criar OS: ' + error.message);
    }
}

// Função para criar OS a partir do carrinho (mantida para compatibilidade)
async function createOsFromCart() {
    openCreateOsModal();
}

// Função para abrir modal de carregar OS
function loadOsModal() {
    console.log('Abrindo modal de carregar OS...');
    const modal = document.getElementById('load-os-modal');
    if (!modal) {
        console.error('Modal load-os-modal não encontrado!');
        alert('❌ Erro: Modal não encontrado. Recarregue a página.');
        return;
    }
    
    try {
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        modal.style.padding = '1rem';
        console.log('Modal aberto com sucesso');
        
        const searchInput = document.getElementById('os-search-input');
        if (searchInput) {
            searchInput.focus();
            // Limpar busca anterior
            searchInput.value = '';
            document.getElementById('os-results').innerHTML = '<p class="text-gray-500 text-center py-4">Digite para buscar OS prontas para venda...</p>';
        }
    } catch (error) {
        console.error('Erro ao abrir modal:', error);
        alert('❌ Erro ao abrir modal: ' + error.message);
    }
}

// Função para fechar modal de carregar OS
function closeLoadOsModal() {
    const modal = document.getElementById('load-os-modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
        const searchInput = document.getElementById('os-search-input');
        if (searchInput) {
            searchInput.value = '';
        }
        const resultsDiv = document.getElementById('os-results');
        if (resultsDiv) {
            resultsDiv.innerHTML = '<p class="text-gray-500 text-center py-4">Digite para buscar OS prontas para venda...</p>';
        }
    }
}

// Função para buscar OS
let osSearchTimeout;
async function searchOs() {
    clearTimeout(osSearchTimeout);
    const searchInput = document.getElementById('os-search-input');
    if (!searchInput) {
        console.error('Campo os-search-input não encontrado!');
        return;
    }
    
    const query = searchInput.value.trim();
    
    osSearchTimeout = setTimeout(async () => {
        // Se a busca estiver vazia, mostrar mensagem
        if (query.length === 0) {
            document.getElementById('os-results').innerHTML = '<p class="text-gray-500 text-center py-4">Digite para buscar OS prontas para venda...</p>';
            return;
        }
        
        // Se tiver menos de 2 caracteres, mostrar mensagem
        if (query.length < 2) {
            document.getElementById('os-results').innerHTML = '<p class="text-gray-500 text-center py-4">Digite pelo menos 2 caracteres para buscar...</p>';
            return;
        }
        
        console.log('Buscando OS com query:', query);
        
        try {
            const url = `{{ route('pdv.search-os') }}?q=${encodeURIComponent(query)}`;
            console.log('URL da busca:', url);
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            console.log('Resposta da busca:', response.status, response.statusText);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Erro na resposta:', errorText);
                throw new Error(`Erro ${response.status}: ${errorText.substring(0, 100)}`);
            }
            
            const data = await response.json();
            console.log('Dados recebidos:', data);
            
            if (data.success && data.os_list && data.os_list.length > 0) {
                const resultsHtml = data.os_list.map(os => `
                    <div onclick="loadOs(${os.id})" class="p-3 border border-gray-200 rounded-lg hover:bg-blue-50 cursor-pointer transition-colors">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="font-semibold text-gray-800">${escapeHtml(os.os_number)}</div>
                                <div class="text-sm text-gray-600">Cliente: ${escapeHtml(os.client_name)}</div>
                                <div class="text-xs text-gray-500">Registrada em: ${escapeHtml(os.registered_at)}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-blue-600">R$ ${parseFloat(os.total_value || 0).toFixed(2).replace('.', ',')}</div>
                            </div>
                        </div>
                    </div>
                `).join('');
                document.getElementById('os-results').innerHTML = resultsHtml;
            } else {
                document.getElementById('os-results').innerHTML = '<p class="text-gray-500 text-center py-4">Nenhuma OS encontrada.</p>';
            }
        } catch (error) {
            console.error('Erro ao buscar OS:', error);
            document.getElementById('os-results').innerHTML = `<p class="text-red-500 text-center py-4">Erro ao buscar OS: ${error.message}</p>`;
        }
    }, 300);
}

// Função para carregar OS no PDV
async function loadOs(osId) {
    console.log('Carregando OS:', osId);
    
    if (!osId) {
        alert('❌ ID da OS inválido!');
        return;
    }
    
    try {
        // Construir URL corretamente
        const baseUrl = '{{ url('pdv/load-os') }}';
        const url = `${baseUrl}/${osId}`;
        console.log('URL para carregar OS:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        
        console.log('Resposta do carregamento:', response.status, response.statusText);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Erro na resposta:', errorText);
            throw new Error(`Erro ${response.status}: ${errorText.substring(0, 200)}`);
        }
        
        const data = await response.json();
        console.log('Dados da OS recebidos:', data);
        
        if (data.success) {
            // Limpar carrinho atual
            items = [];
            
            // Carregar itens da OS
            if (data.items && data.items.length > 0) {
                data.items.forEach((item, index) => {
                    items.push({
                        id: Date.now() + index + Math.random(),
                        product_id: item.product_id,
                        name: item.name || 'Produto sem nome',
                        ref: item.ref || 'N/A',
                        price: parseFloat(item.price || 0),
                        qty: parseFloat(item.qty || 1),
                        discount: parseFloat(item.discount || 0),
                    });
                });
            }
            
            // Carregar cliente se houver
            if (data.os && data.os.client_id) {
                const customerIdInput = document.getElementById('customer-id');
                const customerNameDiv = document.getElementById('customer-name');
                const customerSearchInput = document.getElementById('customer-search');
                
                if (customerIdInput) customerIdInput.value = data.os.client_id;
                if (customerNameDiv) customerNameDiv.textContent = data.os.client_name || '';
                if (customerSearchInput) customerSearchInput.value = data.os.client_name || '';
            }
            
            // Armazenar ID da OS
            if (data.os && data.os.id) {
                currentServiceOrderId = data.os.id;
            }
            
            // Atualizar interface
            updateItemsList();
            calculateTotals();
            updateOsButtons();
            
            // Fechar modal (se estiver aberto)
            closeLoadOsModal();
            
            // Não mostrar alert se foi chamado automaticamente após criar OS (já foi mostrado antes)
            if (!data._autoLoad) {
                alert('✅ OS carregada com sucesso!\n\nNúmero: ' + (data.os.os_number || 'N/A'));
            }
        } else {
            alert('❌ Erro ao carregar OS: ' + (data.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('Erro completo ao carregar OS:', error);
        alert('❌ Erro ao carregar OS: ' + error.message + '\n\nVerifique o console para mais detalhes.');
    }
}
// Função para alternar entre select e input customizado de origem
function toggleSourceInput() {
    const select = document.getElementById('create-os-source');
    const custom = document.getElementById('create-os-source-custom');
    
    if (select && custom) {
        if (select.style.display === 'none') {
            select.style.display = 'block';
            custom.style.display = 'none';
            custom.name = 'source_custom';
            select.name = 'source';
        } else {
            select.style.display = 'none';
            custom.style.display = 'block';
            select.name = 'source_select';
            custom.name = 'source';
        }
    }
}

// Função para abrir modal rápido de cadastro de origem
function openQuickSourceModal() {
    const modal = document.getElementById('quick-source-modal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.right = '0';
        modal.style.bottom = '0';
        modal.style.zIndex = '50';
        const nameInput = document.getElementById('quick-source-name');
        if (nameInput) {
            nameInput.focus();
        }
    }
}

// Função para fechar modal rápido de origem
function closeQuickSourceModal() {
    const modal = document.getElementById('quick-source-modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
        document.getElementById('quick-source-form').reset();
    }
}

// Fechar modal ao clicar fora
document.getElementById('quick-source-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeQuickSourceModal();
    }
});

// Função para salvar origem rapidamente via AJAX
async function saveQuickSource(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const name = formData.get('name');
    
    if (!name || name.trim() === '') {
        alert('⚠️ Digite o nome da origem!');
        return;
    }
    
    try {
        const response = await fetch('{{ route("cadastros.client-sources.storeAjax") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ name: name.trim() })
        });
        
        // Verificar se a resposta é JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Resposta não é JSON:', text.substring(0, 200));
            alert('❌ Erro: Resposta do servidor não é válida. Verifique o console.');
            return;
        }
        
        const result = await response.json();
        
        if (!response.ok) {
            const errorMsg = result.message || result.error || 'Erro ao salvar';
            let errorDetails = errorMsg;
            if (result.errors && Object.keys(result.errors).length > 0) {
                errorDetails += '\n' + Object.values(result.errors).flat().join('\n');
            }
            alert('❌ ' + errorDetails);
            if (result.errors) {
                console.error('Erros de validação:', result.errors);
            }
            return;
        }
        
        if (result.success) {
            // Adicionar ao select
            const select = document.getElementById('create-os-source');
            if (select) {
                const option = document.createElement('option');
                option.value = result.data.name;
                option.textContent = result.data.name;
                option.selected = true;
                select.appendChild(option);
                
                // Se o input customizado estiver visível, também atualizar
                const customInput = document.getElementById('create-os-source-custom');
                if (customInput && customInput.style.display !== 'none') {
                    customInput.value = result.data.name;
                }
            }
            
            closeQuickSourceModal();
            alert('✅ ' + result.message);
        } else {
            alert('❌ ' + (result.message || 'Erro ao salvar'));
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('❌ Erro ao salvar. Verifique o console para mais detalhes.');
    }
}

</script>

<!-- Modal rápido para cadastrar origem -->
<div id="quick-source-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; display: none; align-items: center; justify-content: center;">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-auto" onclick="event.stopPropagation()" style="margin: auto;">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900">➕ Nova Origem</h3>
                <button type="button" onclick="closeQuickSourceModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="quick-source-form" onsubmit="saveQuickSource(event)">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nome da Origem *</label>
                        <input 
                            type="text" 
                            id="quick-source-name"
                            name="name" 
                            required 
                            class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                            placeholder="Ex: Elizabeth, Instagram, etc."
                        >
                    </div>
                </div>
                
                <div class="mt-6 flex gap-3 justify-end">
                    <button 
                        type="button" 
                        onclick="closeQuickSourceModal()" 
                        class="px-4 py-2 text-sm bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300"
                    >
                        Cancelar
                    </button>
                    <button 
                        type="submit" 
                        class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700"
                    >
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Garantir que o texto dos botões de pagamento sempre seja visível */
button[onclick^="addPayment"] {
    min-height: 48px;
    white-space: nowrap;
    overflow: visible;
}

button[onclick^="addPayment"] span {
    display: inline-block;
    line-height: 1.2;
}

/* Estilos para botões de pagamento */
.payment-btn {
    px: 0.75rem;
    py: 0.75rem;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
}

/* Botões desabilitados - fundo cinza claro, texto preto */
.payment-btn:disabled {
    background-color: #e5e7eb !important;
    color: #111827 !important;
    border: 2px solid #d1d5db !important;
    cursor: not-allowed !important;
}

.payment-btn:disabled span {
    color: #111827 !important;
}

/* Botões habilitados - cores específicas com texto branco */
.payment-btn:not(:disabled)[data-method="money"] {
    background-color: #16a34a !important; /* green-600 */
    color: #ffffff !important;
    border: none !important;
}

.payment-btn:not(:disabled)[data-method="money"]:hover {
    background-color: #15803d !important; /* green-700 */
}

.payment-btn:not(:disabled)[data-method="pix"] {
    background-color: #2563eb !important; /* blue-600 */
    color: #ffffff !important;
    border: none !important;
}

.payment-btn:not(:disabled)[data-method="pix"]:hover {
    background-color: #1d4ed8 !important; /* blue-700 */
}

.payment-btn:not(:disabled)[data-method="card_credit"] {
    background-color: #9333ea !important; /* purple-600 */
    color: #ffffff !important;
    border: none !important;
}

.payment-btn:not(:disabled)[data-method="card_credit"]:hover {
    background-color: #7e22ce !important; /* purple-700 */
}

.payment-btn:not(:disabled)[data-method="card_debit"] {
    background-color: #4f46e5 !important; /* indigo-600 */
    color: #ffffff !important;
    border: none !important;
}

.payment-btn:not(:disabled)[data-method="card_debit"]:hover {
    background-color: #4338ca !important; /* indigo-700 */
}

.payment-btn:not(:disabled)[data-method="boleto"] {
    background-color: #ea580c !important; /* orange-600 */
    color: #ffffff !important;
    border: none !important;
}

.payment-btn:not(:disabled)[data-method="boleto"]:hover {
    background-color: #c2410c !important; /* orange-700 */
}

.payment-btn:not(:disabled)[data-method="boleto_installment"] {
    background-color: #ca8a04 !important; /* yellow-600 */
    color: #ffffff !important;
    border: none !important;
}

.payment-btn:not(:disabled)[data-method="boleto_installment"]:hover {
    background-color: #a16207 !important; /* yellow-700 */
}

.payment-btn:not(:disabled) span {
    color: #ffffff !important;
}

/* Garantir que o texto na lista de pagamentos seja sempre visível */
#payments-list .text-sm {
    overflow: visible;
    text-overflow: clip;
    white-space: normal;
    word-wrap: break-word;
    color: #1f2937 !important;
}

/* Garantir que selects de formas de pagamento mostrem texto completo */
select[name*="method"] {
    min-width: 150px;
}

/* Garantir que os modais sempre fiquem centralizados */
#open-cash-modal,
#close-cash-modal,
#create-os-modal,
#load-os-modal {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    z-index: 9999 !important;
    padding: 1rem !important;
}

#open-cash-modal.hidden,
#close-cash-modal.hidden,
#create-os-modal.hidden,
#load-os-modal.hidden {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
}

/* Garantir que modais sem hidden sejam exibidos */
#open-cash-modal:not(.hidden),
#close-cash-modal:not(.hidden),
#create-os-modal:not(.hidden),
#load-os-modal:not(.hidden) {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
    pointer-events: auto !important;
}

/* Estilos para abas do modal de criar OS */
.create-os-tab-content {
    display: block;
}
.create-os-tab-content.hidden {
    display: none !important;
}
.create-os-tab-btn.active {
    color: rgb(37 99 235) !important;
    border-color: rgb(37 99 235) !important;
}

/* Estilos para campos editáveis de preço e quantidade */
#items-list input[type="text"][inputmode="decimal"] {
    background-color: #fef3c7;
    border-color: #fbbf24;
    transition: all 0.2s;
}

#items-list input[type="text"][inputmode="decimal"]:focus {
    background-color: #fef3c7;
    border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
    outline: none;
}

#items-list input[type="text"][inputmode="decimal"]:hover {
    background-color: #fde68a;
    border-color: #f59e0b;
}

/* Estilos para abas do modal de criar OS */
.create-os-tab-content {
    display: block;
    animation: fadeIn 0.3s ease-out;
}
.create-os-tab-content.hidden {
    display: none !important;
}
.create-os-tab-btn {
    position: relative;
    transition: all 0.2s ease;
}
.create-os-tab-btn.active {
    color: rgb(37 99 235) !important;
    border-color: rgb(37 99 235) !important;
    background-color: rgba(59, 130, 246, 0.05);
}
.create-os-tab-btn:hover:not(.active) {
    background-color: rgba(0, 0, 0, 0.02);
}

/* Animações */
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Melhorias nos resultados de busca */
#create-os-customer-results {
    animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
    from {
        transform: translateY(-10px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

#create-os-customer-results > div {
    transition: all 0.2s ease;
}

#create-os-customer-results > div:hover {
    background-color: #f3f4f6;
    transform: translateX(4px);
}

/* Estilos para resultados de produtos */
#create-os-product-results {
    animation: slideDown 0.2s ease-out;
}

#create-os-product-results .product-result-item {
    transition: all 0.15s ease;
}

#create-os-product-results .product-result-item:hover {
    background-color: #eff6ff !important;
    transform: translateX(4px);
}

#create-os-product-results mark {
    background-color: #fef08a;
    padding: 0 2px;
    border-radius: 2px;
    font-weight: 600;
}

/* Scrollbar customizada para resultados */
#create-os-product-results::-webkit-scrollbar {
    width: 8px;
}

#create-os-product-results::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

#create-os-product-results::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

#create-os-product-results::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Melhorar visualização da seção de receita no modal */
#create-os-content-prescription {
    font-size: 14px;
}

#create-os-content-prescription .grid {
    gap: 1rem !important;
}

#create-os-content-prescription input[type="text"],
#create-os-content-prescription input[type="date"] {
    font-size: 16px !important; /* Previne zoom no mobile */
    padding: 0.75rem 1rem !important;
    min-height: 44px; /* Tamanho mínimo para facilitar toque */
}

#create-os-content-prescription label {
    font-size: 13px !important;
    margin-bottom: 0.5rem !important;
}

/* Garantir que o modal de receita seja scrollável e visível */
#create-os-modal .overflow-y-auto {
    max-height: calc(95vh - 200px);
}

/* Melhorar espaçamento entre seções */
#create-os-prescription-fields > div {
    margin-bottom: 1.5rem;
}

/* Ajustar tamanho dos campos em telas menores */
@media (max-width: 768px) {
    #create-os-content-prescription .grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    
    #create-os-content-prescription input[type="text"],
    #create-os-content-prescription input[type="date"] {
        font-size: 16px !important;
        padding: 0.875rem 1rem !important;
    }
}
</style>
@endif
@endsection
