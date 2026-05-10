<x-app-layout title="Nova Ordem de Serviço">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white rounded-lg shadow-lg border-2 border-slate-300 p-6 md:p-8">
            <!-- Botão Consulta no canto superior direito -->
            <div class="flex justify-end mb-4">
                <a href="{{ route('os.index') }}" class="px-6 py-3 text-lg bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition-colors shadow-lg">
                    🔍 Consulta
                </a>
            </div>
            
            <!-- Cabeçalho com Tipo e Número -->
            <div class="mb-6 pb-4 border-b-2 border-slate-300">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-3xl md:text-4xl font-black text-slate-900 mb-2">Nova Ordem de Serviço</h1>
                        <!-- Seletor OS / Conserto -->
                        <div class="flex items-center gap-4 mb-3">
                            <span class="text-lg md:text-xl font-bold text-slate-600">Tipo:</span>
                            <div class="flex gap-2">
                                <button type="button" id="tipo-os-btn" onclick="setTipoOs(false)" class="tipo-btn active px-5 py-2.5 text-lg font-bold rounded-lg border-2 transition-colors bg-blue-600 text-white border-blue-700 shadow">
                                    📋 OS
                                </button>
                                <button type="button" id="tipo-conserto-btn" onclick="setTipoOs(true)" class="tipo-btn px-5 py-2.5 text-lg font-bold rounded-lg border-2 transition-colors bg-slate-200 text-slate-700 border-slate-400 hover:bg-slate-300">
                                    🔧 Conserto
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-lg md:text-xl font-bold text-slate-600">Número:</span>
                            <span id="os_number_display" class="text-2xl md:text-3xl font-black text-blue-600 bg-blue-50 px-4 py-2 rounded-lg border-2 border-blue-300">
                                Carregando...
                            </span>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('os.index') }}" class="px-6 py-3 text-lg bg-slate-200 text-slate-800 font-bold rounded-lg hover:bg-slate-300 transition-colors">
                            ❌ Cancelar
                        </a>
                        <button type="button" id="finalizarOsBtn" class="px-6 py-3 text-lg bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition-colors shadow-lg">
                            ✅ Finalizar OS
                        </button>
                    </div>
                </div>
            </div>

            @if($errors->any())
                <div class="mb-6 p-4 bg-red-100 border-2 border-red-400 rounded-lg">
                    <h3 class="text-xl font-black text-red-900 mb-2">❌ Erros de Validação:</h3>
                    <ul class="list-disc list-inside text-red-900 text-lg font-semibold">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 bg-red-100 border-2 border-red-400 rounded-lg">
                    <h3 class="text-xl font-black text-red-900 mb-2">❌ Erro:</h3>
                    <p class="text-red-900 text-lg font-semibold">{{ session('error') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('os.store') }}" enctype="multipart/form-data" id="osForm">
                @csrf
                <input type="hidden" id="is_conserto" name="is_conserto" value="0">

                <!-- Tabs Simplificadas -->
                <div class="mb-6 border-b-2 border-slate-300">
                    <nav class="flex space-x-4 overflow-x-auto">
                        <button type="button" onclick="switchTab('dados-itens')" id="tab-dados-itens" class="tab-btn active px-6 py-3 text-xl font-bold text-blue-700 border-b-4 border-blue-600 whitespace-nowrap">
                            📋 Dados e Itens
                        </button>
                        <button type="button" onclick="switchTab('receita')" id="tab-receita" class="tab-btn px-6 py-3 text-xl font-bold text-slate-500 border-b-4 border-transparent whitespace-nowrap">
                            👁️ Receita
                        </button>
                        <button type="button" onclick="switchTab('pagamento')" id="tab-pagamento" class="tab-btn px-6 py-3 text-xl font-bold text-slate-500 border-b-4 border-transparent whitespace-nowrap">
                            💰 Pagamento
                        </button>
                    </nav>
                </div>

                <!-- Tab: Dados e Itens (Unificada) -->
                <div id="content-dados-itens" class="tab-content">
                    <!-- Seção: Dados Básicos -->
                    <div class="mb-8 p-6 bg-slate-50 rounded-lg border-2 border-slate-300">
                        <h2 class="text-2xl md:text-3xl font-black text-slate-900 mb-6 pb-3 border-b-2 border-slate-400">
                            📝 DADOS BÁSICOS
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @if(auth()->user()->isAdmin() && isset($selectedStoreId) && $selectedStoreId)
                                <!-- Admin: loja vem da sessão do dashboard, campo hidden -->
                                <input type="hidden" id="store_id" name="store_id" value="{{ $selectedStoreId }}" required>
                                <div>
                                    <label class="block text-lg md:text-xl font-bold text-slate-900 mb-2">
                                        🏪 Loja
                                    </label>
                                    <div class="w-full text-lg md:text-xl px-4 py-3 rounded-lg border-2 border-blue-400 bg-blue-50 font-semibold">
                                        @php
                                            $selectedStore = $stores->firstWhere('id', $selectedStoreId);
                                        @endphp
                                        @if($selectedStore)
                                            @if($selectedStore->abbreviation)[{{ $selectedStore->abbreviation }}]@endif {{ $selectedStore->name }}
                                        @else
                                            <span class="text-red-600 font-bold">⚠️ Nenhuma loja selecionada no dashboard</span>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-sm text-slate-600">
                                        💡 Para alterar a loja, <a href="{{ route('dashboard') }}" class="text-blue-600 underline font-bold">selecione no dashboard</a>
                                    </p>
                                </div>
                            @elseif(auth()->user()->isAdmin() && (!isset($selectedStoreId) || !$selectedStoreId))
                                <!-- Admin sem loja selecionada: mostrar aviso e link para dashboard -->
                                <div class="col-span-2 p-4 bg-yellow-50 border-2 border-yellow-400 rounded-lg">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-lg font-bold text-yellow-900">⚠️ ATENÇÃO: Nenhuma loja selecionada!</span>
                                    </div>
                                    <p class="text-yellow-800 mb-3">
                                        Você precisa selecionar uma loja no dashboard antes de criar uma OS.
                                    </p>
                                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        Ir para Dashboard e Selecionar Loja
                                    </a>
                                </div>
                            @else
                                <!-- Gerente ou outros: mostrar campo de seleção -->
                                <div>
                                    <label for="store_id" class="block text-lg md:text-xl font-bold text-slate-900 mb-2">
                                        🏪 Loja *
                                    </label>
                                    <select id="store_id" name="store_id" required onchange="onStoreIdChange(this); loadOsNumber();" class="w-full text-lg md:text-xl px-4 py-3 rounded-lg border-2 border-slate-400 shadow-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-500 font-semibold">
                                        <option value="">Selecione a loja</option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store->id }}" {{ (isset($selectedStoreId) && $selectedStoreId == $store->id) ? 'selected' : '' }}>@if($store->abbreviation)[{{ $store->abbreviation }}]@endif {{ $store->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <!-- Modo OS: busca de cliente cadastrado -->
                            <div id="client-search-wrapper" class="col-span-2 md:col-span-1">
                                <label for="client_search" class="block text-lg md:text-xl font-bold text-slate-900 mb-2">
                                    👤 Cliente *
                                </label>
                                <input type="text" id="client_search" placeholder="Digite o nome do cliente..." class="w-full text-lg md:text-xl px-4 py-3 rounded-lg border-2 border-slate-400 shadow-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-500 font-semibold">
                                <input type="hidden" id="client_id" name="client_id" value="">
                                <!-- Lista de resultados da busca -->
                                <div id="client_results" class="mt-2 hidden max-h-64 overflow-y-auto border-2 border-slate-300 rounded-lg bg-white"></div>
                                <div id="client_selected" class="mt-2 text-lg text-blue-700 font-semibold"></div>
                            </div>
                            <!-- Modo Conserto: nome e contato simples (sem cadastro) -->
                            <div id="conserto-client-wrapper" class="hidden col-span-2 space-y-4">
                                <div>
                                    <label for="conserto_client_name" class="block text-lg md:text-xl font-bold text-slate-900 mb-2">
                                        👤 Nome
                                    </label>
                                    <input type="text" id="conserto_client_name" name="conserto_client_name" placeholder="Nome do cliente..." class="w-full text-lg md:text-xl px-4 py-3 rounded-lg border-2 border-slate-400 shadow-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-500 font-semibold">
                                </div>
                                <div>
                                    <label for="conserto_client_contact" class="block text-lg md:text-xl font-bold text-slate-900 mb-2">
                                        📞 Contato (telefone ou e-mail)
                                    </label>
                                    <input type="text" id="conserto_client_contact" name="conserto_client_contact" placeholder="Telefone ou e-mail..." class="w-full text-lg md:text-xl px-4 py-3 rounded-lg border-2 border-slate-400 shadow-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-500 font-semibold">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Itens -->
                    <div class="mb-8 p-6 bg-slate-50 rounded-lg border-2 border-slate-300">
                        <h2 class="text-2xl md:text-3xl font-black text-slate-900 mb-6 pb-3 border-b-2 border-slate-400">
                            🛍️ ITENS DA OS
                        </h2>
                        <div class="mb-6">
                            <div class="flex items-center gap-4 mb-4">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" id="use_barcode_scanner" class="w-5 h-5 rounded border-slate-400 text-blue-600">
                                    <span class="ml-3 text-lg md:text-xl text-slate-700 font-semibold">Usando leitor de códigos de barras</span>
                                </label>
                            </div>
                            <div class="flex gap-3">
                                <input type="text" id="product_search" placeholder="Buscar produto (nome, ref, EAN)..." class="flex-1 text-lg md:text-xl px-4 py-3 rounded-lg border-2 border-slate-400 shadow-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-500 font-semibold">
                            </div>
                            <!-- Lista de resultados da busca -->
                            <div id="product_results" class="mt-4 hidden max-h-64 overflow-y-auto border-2 border-slate-300 rounded-lg bg-white"></div>
                        </div>

                        <div class="overflow-x-auto mb-6">
                            <table class="min-w-full divide-y divide-slate-300 text-lg border-2 border-slate-400">
                                <thead class="bg-slate-200">
                                    <tr>
                                        <th class="px-4 py-4 text-left text-lg font-black text-slate-900 uppercase border-r-2 border-slate-400">Produto</th>
                                        <th class="px-4 py-4 text-left text-lg font-black text-slate-900 uppercase border-r-2 border-slate-400">Qtd</th>
                                        <th class="px-4 py-4 text-left text-lg font-black text-slate-900 uppercase border-r-2 border-slate-400">Preço Unit.</th>
                                        <th class="px-4 py-4 text-left text-lg font-black text-slate-900 uppercase border-r-2 border-slate-400">Total</th>
                                        <th class="px-4 py-4 text-left text-lg font-black text-slate-900 uppercase">Ação</th>
                                    </tr>
                                </thead>
                                <tbody id="items_table_body" class="bg-white divide-y divide-slate-300">
                                    <tr>
                                        <td colspan="5" class="px-6 py-6 text-center text-slate-600 text-xl font-semibold">
                                            Nenhum item adicionado. Busque e adicione produtos acima.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="bg-blue-50 rounded-lg p-6 border-2 border-blue-300">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xl">
                                <div>
                                    <span class="text-slate-700 font-bold">Subtotal:</span>
                                    <span id="subtotal_display" class="ml-3 font-black text-2xl text-blue-700">R$ 0,00</span>
                                </div>
                                <div>
                                    <label class="block text-lg font-bold text-slate-700 mb-2">Desconto:</label>
                                    <input type="number" step="0.01" id="discount_value" name="discount_value" value="0" min="0" onchange="calculateTotals()" class="w-full text-xl px-4 py-3 rounded-lg border-2 border-slate-400 font-semibold" required>
                                </div>
                                <div>
                                    <span class="text-slate-700 font-bold">Total:</span>
                                    <span id="total_display" class="ml-3 font-black text-3xl text-green-700">R$ 0,00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data de Entrega (Última coisa) -->
                    <div class="mb-8 p-6 bg-yellow-50 rounded-lg border-2 border-yellow-400">
                        <h2 class="text-2xl md:text-3xl font-black text-slate-900 mb-4">
                            📅 DATA DE ENTREGA
                        </h2>
                        <div>
                            <label for="delivery_date" class="block text-lg md:text-xl font-bold text-slate-900 mb-2">
                                Data de Entrega
                            </label>
                            <input type="date" id="delivery_date" name="delivery_date" class="w-full md:w-1/2 text-lg md:text-xl px-4 py-3 rounded-lg border-2 border-yellow-500 shadow-sm focus:border-yellow-600 focus:ring-2 focus:ring-yellow-500 font-semibold">
                        </div>
                    </div>
                </div>

                <!-- Tab: Receita -->
                <div id="content-receita" class="tab-content hidden">
                    <div class="p-6 bg-purple-50 rounded-lg border-2 border-purple-400">
                        <h2 class="text-2xl md:text-3xl font-black text-slate-900 mb-6 pb-3 border-b-2 border-purple-500">
                            👁️ DADOS DA RECEITA
                        </h2>
                        <div id="prescription_fields" class="space-y-6">
                            <!-- LONGE -->
                            <div>
                                <h3 class="text-xl font-black mb-4 text-slate-800">LONGE</h3>
                                <div class="grid grid-cols-1 md:grid-cols-5 gap-3 text-lg">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">OD Esférico</label>
                                        <input type="text" id="prescription_longe_esferico_od" name="prescription[custom_longe_esferico_od]" placeholder="OD Esférico" class="w-full px-3 py-2 rounded-lg border-2 border-slate-400 font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">OD Cilíndrico</label>
                                        <input type="text" id="prescription_longe_cilindrico_od" name="prescription[custom_longe_cilindrico_od]" placeholder="OD Cilíndrico" class="w-full px-3 py-2 rounded-lg border-2 border-slate-400 font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">OD Eixo</label>
                                        <input type="text" id="prescription_longe_eixo_od" name="prescription[custom_longe_eixo_od]" placeholder="OD Eixo" class="w-full px-3 py-2 rounded-lg border-2 border-slate-400 font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">OD Altura</label>
                                        <input type="text" id="prescription_longe_altura_od" name="prescription[custom_longe_altura_od]" placeholder="OD Altura" class="w-full px-3 py-2 rounded-lg border-2 border-slate-400 font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">OD DNP</label>
                                        <input type="text" id="prescription_longe_dnp_od" name="prescription[custom_longe_dnp_od]" placeholder="OD DNP" class="w-full px-3 py-2 rounded-lg border-2 border-slate-400 font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">OE Esférico</label>
                                        <input type="text" id="prescription_longe_esferico_oe" name="prescription[custom_longe_esferico_oe]" placeholder="OE Esférico" class="w-full px-3 py-2 rounded-lg border-2 border-slate-400 font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">OE Cilíndrico</label>
                                        <input type="text" id="prescription_longe_cilindrico_oe" name="prescription[custom_longe_cilindrico_oe]" placeholder="OE Cilíndrico" class="w-full px-3 py-2 rounded-lg border-2 border-slate-400 font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">OE Eixo</label>
                                        <input type="text" id="prescription_longe_eixo_oe" name="prescription[custom_longe_eixo_oe]" placeholder="OE Eixo" class="w-full px-3 py-2 rounded-lg border-2 border-slate-400 font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">OE Altura</label>
                                        <input type="text" id="prescription_longe_altura_oe" name="prescription[custom_longe_altura_oe]" placeholder="OE Altura" class="w-full px-3 py-2 rounded-lg border-2 border-slate-400 font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">OE DNP</label>
                                        <input type="text" id="prescription_longe_dnp_oe" name="prescription[custom_longe_dnp_oe]" placeholder="OE DNP" class="w-full px-3 py-2 rounded-lg border-2 border-slate-400 font-semibold">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- PERTO -->
                            <div>
                                <h3 class="text-xl font-black mb-4 text-slate-800">PERTO</h3>
                                <div class="grid grid-cols-1 md:grid-cols-5 gap-3 text-lg">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">OD Esférico</label>
                                        <input type="text" id="prescription_perto_esferico_od" name="prescription[custom_perto_esferico_od]" placeholder="OD Esférico" class="w-full px-3 py-2 rounded-lg border-2 border-slate-400 font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">OD Cilíndrico</label>
                                        <input type="text" id="prescription_perto_cilindrico_od" name="prescription[custom_perto_cilindrico_od]" placeholder="OD Cilíndrico" class="w-full px-3 py-2 rounded-lg border-2 border-slate-400 font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">OD Eixo</label>
                                        <input type="text" id="prescription_perto_eixo_od" name="prescription[custom_perto_eixo_od]" placeholder="OD Eixo" class="w-full px-3 py-2 rounded-lg border-2 border-slate-400 font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">OD Altura</label>
                                        <input type="text" id="prescription_perto_altura_od" name="prescription[custom_perto_altura_od]" placeholder="OD Altura" class="w-full px-3 py-2 rounded-lg border-2 border-slate-400 font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">OD DNP</label>
                                        <input type="text" id="prescription_perto_dnp_od" name="prescription[custom_perto_dnp_od]" placeholder="OD DNP" class="w-full px-3 py-2 rounded-lg border-2 border-slate-400 font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">OE Esférico</label>
                                        <input type="text" id="prescription_perto_esferico_oe" name="prescription[custom_perto_esferico_oe]" placeholder="OE Esférico" class="w-full px-3 py-2 rounded-lg border-2 border-slate-400 font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">OE Cilíndrico</label>
                                        <input type="text" id="prescription_perto_cilindrico_oe" name="prescription[custom_perto_cilindrico_oe]" placeholder="OE Cilíndrico" class="w-full px-3 py-2 rounded-lg border-2 border-slate-400 font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">OE Eixo</label>
                                        <input type="text" id="prescription_perto_eixo_oe" name="prescription[custom_perto_eixo_oe]" placeholder="OE Eixo" class="w-full px-3 py-2 rounded-lg border-2 border-slate-400 font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">OE Altura</label>
                                        <input type="text" id="prescription_perto_altura_oe" name="prescription[custom_perto_altura_oe]" placeholder="OE Altura" class="w-full px-3 py-2 rounded-lg border-2 border-slate-400 font-semibold">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">OE DNP</label>
                                        <input type="text" id="prescription_perto_dnp_oe" name="prescription[custom_perto_dnp_oe]" placeholder="OE DNP" class="w-full px-3 py-2 rounded-lg border-2 border-slate-400 font-semibold">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Adição e Médico -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-lg font-bold text-slate-900 mb-2">Adição</label>
                                    <input type="text" id="prescription_adicao" name="prescription[custom_adicao]" class="w-full text-lg px-4 py-3 rounded-lg border-2 border-slate-400 font-semibold">
                                </div>
                                <div>
                                    <label class="block text-lg font-bold text-slate-900 mb-2">Médico</label>
                                    <input type="text" name="prescription[custom_doctor_name]" class="w-full text-lg px-4 py-3 rounded-lg border-2 border-slate-400 font-semibold">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Pagamento -->
                <div id="content-pagamento" class="tab-content hidden">
                    <div class="p-6 bg-green-50 rounded-lg border-2 border-green-400">
                        <h2 class="text-2xl md:text-3xl font-black text-slate-900 mb-6 pb-3 border-b-2 border-green-500">
                            💰 FORMA DE PAGAMENTO
                        </h2>
                        <div class="mb-6">
                            <div class="text-xl font-bold text-slate-700 mb-4">
                                Total da OS: <span id="payment_total_display" class="text-3xl font-black text-green-700">R$ 0,00</span>
                            </div>
                            <div class="text-lg font-semibold text-slate-700 mb-4 p-4 bg-blue-50 rounded-lg border-2 border-blue-300">
                                👤 Cliente: <span id="payment_client_display" class="font-black text-blue-700">Selecione um cliente na aba "Dados e Itens"</span>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-lg md:text-xl font-bold text-slate-900 mb-2">
                                    Tipo de Pagamento
                                </label>
                                <select id="payment_type" name="payment_type" class="w-full text-lg md:text-xl px-4 py-3 rounded-lg border-2 border-slate-400 shadow-sm focus:border-green-600 focus:ring-2 focus:ring-green-500 font-semibold" onchange="updatePaymentFields()">
                                    <option value="avista">💵 À Vista</option>
                                    <option value="sinal">💰 Sinal (Entrada)</option>
                                    <option value="parcelado">📅 Parcelado</option>
                                </select>
                            </div>
                            <div id="payment_method_field">
                                <label class="block text-lg md:text-xl font-bold text-slate-900 mb-2">
                                    Método de Pagamento *
                                </label>
                                <select id="payment_method" name="payment_method" required class="w-full text-lg md:text-xl px-4 py-3 rounded-lg border-2 border-slate-400 shadow-sm focus:border-green-600 focus:ring-2 focus:ring-green-500 font-semibold" onchange="updatePaymentFields()">
                                    <option value="">Selecione o método de pagamento</option>
                                    <option value="money">💵 Dinheiro</option>
                                    <option value="pix">📱 PIX</option>
                                    <option value="card_credit">💳 Cartão de Crédito</option>
                                    <option value="card_debit">💳 Cartão de Débito</option>
                                    <option value="boleto">📄 Boleto</option>
                                    <option value="carne">📋 Carnê</option>
                                </select>
                            </div>
                            <div id="sinal_amount_field" class="hidden">
                                <label class="block text-lg md:text-xl font-bold text-slate-900 mb-2">
                                    Valor do Sinal (Entrada)
                                </label>
                                <input type="number" step="0.01" id="sinal_amount" name="sinal_amount" value="0" min="0" class="w-full text-lg md:text-xl px-4 py-3 rounded-lg border-2 border-slate-400 font-semibold" onchange="calculatePaymentBalance()">
                                <div class="mt-2 text-lg font-semibold text-blue-700">
                                    Saldo Restante: <span id="payment_balance" class="font-black">R$ 0,00</span>
                                </div>
                            </div>
                            <div id="parcelas_field" class="hidden">
                                <label class="block text-lg md:text-xl font-bold text-slate-900 mb-2">
                                    Número de Parcelas
                                </label>
                                <input type="number" id="parcelas_count" name="parcelas_count" value="1" min="1" max="12" class="w-full text-lg md:text-xl px-4 py-3 rounded-lg border-2 border-slate-400 font-semibold">
                            </div>
                            <div id="carne_parcelas_field" class="hidden">
                                <label class="block text-lg md:text-xl font-bold text-slate-900 mb-2">
                                    Quantas Parcelas do Carnê?
                                </label>
                                <input type="number" id="carne_parcelas_count" name="carne_parcelas_count" value="1" min="1" max="24" class="w-full text-lg md:text-xl px-4 py-3 rounded-lg border-2 border-slate-400 font-semibold" onchange="calculateCarneParcelValue()">
                                <div class="mt-2 text-lg font-semibold text-blue-700">
                                    Valor de cada parcela: <span id="carne_parcela_value" class="font-black">R$ 0,00</span>
                                </div>
                                <p class="mt-2 text-sm text-slate-600">💡 O saldo restante será parcelado no carnê</p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <style>
        .tab-content { display: block; }
        .tab-content.hidden { display: none; }
        .tab-btn.active { 
            color: rgb(29 78 216); 
            border-color: rgb(29 78 216); 
        }
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            height: 40px;
            opacity: 1;
        }
    </style>

    <script>
        // Variáveis globais
        let items = [];
        let itemCounter = 0;

        // Função para submeter o formulário (chamada pelo botão) - DEFINIDA GLOBALMENTE
        window.submitOsForm = function() {
            console.log('🔍 [DEBUG] ========== submitOsForm() CHAMADA ==========');
            console.log('🔍 [DEBUG] Stack trace:', new Error().stack);
            
            const form = document.getElementById('osForm');
            if (!form) {
                console.error('❌ [DEBUG] Formulário não encontrado!');
                alert('Erro: Formulário não encontrado. Recarregue a página.');
                return false;
            }
            
            console.log('🔍 [DEBUG] Formulário encontrado, validando...');
            
            // Chamar validação manualmente primeiro
            const validationResult = validateForm({ preventDefault: function() {} });
            console.log('🔍 [DEBUG] Resultado da validação manual:', validationResult);
            
            if (!validationResult) {
                console.warn('⚠️ [DEBUG] Validação falhou, não submetendo');
                return false;
            }
            
            // Se passou na validação, processar preparação e submeter
            console.log('✅ [DEBUG] Validação OK, preparando submit...');
            
            // Preparar dados (remover items vazios, etc)
            if (items.length === 0) {
                const itemInputs = form.querySelectorAll('[name^="items["]');
                console.log('🔍 [DEBUG] Removendo', itemInputs.length, 'campos de items vazios');
                itemInputs.forEach(input => input.remove());
            }
            
            // Verificar payment_method
            const paymentMethod = document.getElementById('payment_method');
            console.log('🔍 [DEBUG] payment_method:', paymentMethod ? paymentMethod.value : 'não encontrado');
            if (paymentMethod && !paymentMethod.value) {
                alert('⚠️ Por favor, selecione o método de pagamento');
                switchTab('pagamento');
                return false;
            }
            
            // Desabilitar botão
            const submitBtn = document.getElementById('finalizarOsBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = '⏳ Processando...';
            }
            
            // Coletar dados finais para debug
            const finalFormData = new FormData(form);
            const finalData = {};
            for (let [key, value] of finalFormData.entries()) {
                finalData[key] = value;
            }
            console.log('🔍 [DEBUG] Dados finais do formulário:', finalData);
            console.log('🔍 [DEBUG] Action:', form.action);
            console.log('🔍 [DEBUG] Method:', form.method);
            
            // Marcar que já validamos
            form.dataset.validated = 'true';
            
            console.log('✅ [DEBUG] Submetendo formulário para o servidor...');
            
            // Submeter o formulário (form.submit() não dispara evento, mas já validamos)
            form.submit();
            
            return true;
        };
        
        // CONFIGURAÇÃO IMEDIATA DO BOTÃO - executa antes de tudo
        (function setupButtonImmediately() {
            console.log('🚀 [INIT] Inicializando configuração do botão...');
            
            function setupBtn() {
                const btn = document.getElementById('finalizarOsBtn');
                if (btn) {
                    console.log('🎯 [INIT] Botão encontrado, configurando...');
                    btn.type = 'button';
                    btn.removeAttribute('form');
                    btn.removeAttribute('onclick');
                    
                    if (!btn.hasAttribute('data-initialized')) {
                        btn.addEventListener('click', function(e) {
                            console.log('🔥 [CLICK] BOTÃO CLICADO - submitOsForm será chamado!');
                            e.preventDefault();
                            e.stopPropagation();
                            
                            if (window.submitOsForm) {
                                window.submitOsForm();
                            } else {
                                alert('Função não encontrada');
                            }
                        });
                        btn.setAttribute('data-initialized', 'true');
                        console.log('✅ [INIT] Botão inicializado!');
                    }
                    return true;
                }
                return false;
            }
            
            // Tentar imediatamente
            if (document.body) {
                setupBtn();
            }
            
            // Tentar no DOMContentLoaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', setupBtn);
            } else {
                setupBtn();
            }
            
            // Tentar várias vezes como fallback
            setTimeout(setupBtn, 50);
            setTimeout(setupBtn, 200);
            setTimeout(setupBtn, 500);
            setTimeout(setupBtn, 1000);
        })();

        // Função helper para obter o token CSRF
        function getCsrfToken() {
            const token = document.querySelector('meta[name="csrf-token"]');
            if (token) {
                return token.getAttribute('content');
            }
            // Fallback: tentar pegar do formulário
            const formToken = document.querySelector('input[name="_token"]');
            if (formToken) {
                return formToken.value;
            }
            console.error('❌ [DEBUG] Token CSRF não encontrado!');
            return null;
        }

        // Configurar Axios/Fetch para incluir CSRF token automaticamente (se usar Axios no futuro)
        // Interceptor para lidar com erros 419 (CSRF expired)
        if (typeof window.fetch !== 'undefined') {
            const originalFetch = window.fetch;
            window.fetch = function(...args) {
                const [url, options = {}] = args;
                
                // NUNCA interceptar requisições para select-store (sempre usar GET direto)
                const urlString = typeof url === 'string' ? url : (url?.url || url?.toString() || '');
                if (urlString.includes('select-store') || urlString.includes('selectStore')) {
                    console.warn('⚠️ [OS] Tentativa de usar fetch para select-store - ignorando interceptação');
                    // Deixar passar sem interceptação
                    return originalFetch.apply(this, args);
                }
                
                // Se for uma requisição POST/PUT/PATCH/DELETE, adicionar CSRF token
                if (options.method && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(options.method.toUpperCase())) {
                    const headers = options.headers || {};
                    
                    // Se não tiver Content-Type definido e tiver body, definir como application/json
                    if (!headers['Content-Type'] && !headers['content-type'] && options.body) {
                        headers['Content-Type'] = 'application/json';
                    }
                    
                    // Adicionar CSRF token se não tiver
                    if (!headers['X-CSRF-TOKEN'] && !headers['x-csrf-token']) {
                        const csrfToken = getCsrfToken();
                        if (csrfToken) {
                            headers['X-CSRF-TOKEN'] = csrfToken;
                        }
                    }
                    
                    // Adicionar header Accept para JSON se for AJAX
                    if (!headers['Accept'] && !headers['accept']) {
                        headers['Accept'] = 'application/json';
                    }
                    
                    // Adicionar header X-Requested-With para identificar como AJAX
                    if (!headers['X-Requested-With'] && !headers['x-requested-with']) {
                        headers['X-Requested-With'] = 'XMLHttpRequest';
                    }
                    
                    options.headers = headers;
                }
                
                // Log detalhado apenas para requisições POST/PUT/PATCH/DELETE ou se contiver select-store
                const isSelectStore = typeof url === 'string' && url.includes('select-store');
                if (isSelectStore || (options.method && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(options.method.toUpperCase()))) {
                    console.log('🔍 [DEBUG] Fetch chamado:', {
                        url: url,
                        method: options.method || 'GET',
                        headers: options.headers,
                        hasBody: !!options.body,
                        isSelectStore: isSelectStore
                    });
                }
                
                return originalFetch.apply(this, [url, options])
                    .then(response => {
                        // Tratar erro 419 (CSRF token expired) - log detalhado
                        if (response.status === 419) {
                            console.error('❌ [DEBUG] ========== ERRO 419 DETECTADO ==========');
                            console.error('❌ [DEBUG] URL da requisição:', url);
                            console.error('❌ [DEBUG] Método:', options.method || 'GET');
                            console.error('❌ [DEBUG] Headers enviados:', options.headers);
                            console.error('❌ [DEBUG] Token CSRF usado:', options.headers?.['X-CSRF-TOKEN'] || options.headers?.['x-csrf-token'] || 'NÃO ENVIADO');
                            console.error('❌ [DEBUG] Token CSRF disponível no meta:', getCsrfToken());
                            console.error('❌ [DEBUG] ============================================');
                            
                            // Não recarregar automaticamente se for select-store (pode ser desnecessário)
                            if (!isSelectStore) {
                                console.log('🔍 [DEBUG] Recarregando página...');
                                alert('⚠️ Sua sessão expirou. A página será recarregada.');
                                window.location.reload();
                            } else {
                                console.warn('⚠️ [DEBUG] Erro 419 em select-store - ignorando (pode ser requisição antiga)');
                            }
                            
                            return Promise.reject(new Error('CSRF token expired'));
                        }
                        
                        // Log de sucesso para requisições importantes
                        if (isSelectStore) {
                            console.log('✅ [DEBUG] Requisição select-store bem-sucedida:', response.status);
                        }
                        
                        return response;
                    })
                    .catch(error => {
                        console.error('❌ [DEBUG] Erro na requisição:', error);
                        throw error;
                    });
            };
        }

        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('active', 'border-blue-600', 'text-blue-600');
                b.classList.add('border-transparent', 'text-slate-500');
            });
            document.getElementById('content-' + tabName).classList.remove('hidden');
            const btn = document.getElementById('tab-' + tabName);
            btn.classList.add('active', 'border-blue-600', 'text-blue-600');
            btn.classList.remove('border-transparent', 'text-slate-500');
            
            if (tabName === 'pagamento') {
                updatePaymentTotal();
                updatePaymentClientDisplay();
            }
        }
        
        function updatePaymentClientDisplay() {
            const paymentClientDisplay = document.getElementById('payment_client_display');
            if (!paymentClientDisplay) return;
            const isConserto = document.getElementById('is_conserto')?.value === '1';
            if (isConserto) {
                const consertoName = document.getElementById('conserto_client_name')?.value?.trim();
                paymentClientDisplay.textContent = consertoName || 'Informe o nome na aba "Dados e Itens"';
            } else {
                const clientSelected = document.getElementById('client_selected');
                const clientText = clientSelected?.textContent?.trim() || '';
                if (clientText && clientText.startsWith('✅')) {
                    paymentClientDisplay.textContent = clientText.replace('✅ ', '');
                } else {
                    paymentClientDisplay.textContent = 'Selecione um cliente na aba "Dados e Itens"';
                }
            }
        }

        // Função para alternar entre OS e Conserto
        function setTipoOs(isConserto) {
            const input = document.getElementById('is_conserto');
            const osBtn = document.getElementById('tipo-os-btn');
            const consertoBtn = document.getElementById('tipo-conserto-btn');
            const clientSearchWrapper = document.getElementById('client-search-wrapper');
            const consertoWrapper = document.getElementById('conserto-client-wrapper');
            const clientIdInput = document.getElementById('client_id');
            const consertoName = document.getElementById('conserto_client_name');
            const consertoContact = document.getElementById('conserto_client_contact');
            if (!input || !osBtn || !consertoBtn) return;
            input.value = isConserto ? '1' : '0';
            if (isConserto) {
                osBtn.classList.remove('active', 'bg-blue-600', 'text-white', 'border-blue-700');
                osBtn.classList.add('bg-slate-200', 'text-slate-700', 'border-slate-400');
                consertoBtn.classList.remove('bg-slate-200', 'text-slate-700', 'border-slate-400');
                consertoBtn.classList.add('active', 'bg-amber-600', 'text-white', 'border-amber-700');
                if (clientSearchWrapper) clientSearchWrapper.classList.add('hidden');
                if (consertoWrapper) consertoWrapper.classList.remove('hidden');
                if (clientIdInput) { clientIdInput.removeAttribute('required'); clientIdInput.value = ''; }
                if (consertoName) consertoName.setAttribute('required', 'required');
                document.getElementById('client_search').value = '';
                document.getElementById('client_selected').textContent = '';
                document.getElementById('client_results').classList.add('hidden');
            } else {
                consertoBtn.classList.remove('active', 'bg-amber-600', 'text-white', 'border-amber-700');
                consertoBtn.classList.add('bg-slate-200', 'text-slate-700', 'border-slate-400');
                osBtn.classList.remove('bg-slate-200', 'text-slate-700', 'border-slate-400');
                osBtn.classList.add('active', 'bg-blue-600', 'text-white', 'border-blue-700');
                if (clientSearchWrapper) clientSearchWrapper.classList.remove('hidden');
                if (consertoWrapper) consertoWrapper.classList.add('hidden');
                if (clientIdInput) clientIdInput.setAttribute('required', 'required');
                if (consertoName) { consertoName.removeAttribute('required'); consertoName.value = ''; }
                if (consertoContact) consertoContact.value = '';
            }
            loadOsNumber();
        }

        // Função chamada quando o select de loja muda
        function onStoreIdChange(selectElement) {
            console.log('🔍 [DEBUG] onStoreIdChange() chamada');
            loadOsNumber();
        }

        // Carregar número da OS
        function loadOsNumber() {
            console.log('🔍 [DEBUG] loadOsNumber() chamada');
            
            // Buscar store_id de múltiplas formas
            let storeIdElement = document.getElementById('store_id');
            
            // Se não encontrou, tentar buscar input hidden
            if (!storeIdElement) {
                storeIdElement = document.querySelector('input[name="store_id"]');
                console.log('🔍 [DEBUG] Tentando buscar input[name="store_id"]:', storeIdElement);
            }
            
            // Se ainda não encontrou, tentar select
            if (!storeIdElement) {
                storeIdElement = document.querySelector('select[name="store_id"]');
                console.log('🔍 [DEBUG] Tentando buscar select[name="store_id"]:', storeIdElement);
            }
            
            console.log('🔍 [DEBUG] Elemento store_id encontrado:', storeIdElement);
            
            if (!storeIdElement) {
                console.error('❌ [DEBUG] Elemento store_id não encontrado no DOM!');
                document.getElementById('os_number_display').textContent = 'Erro: campo loja não encontrado';
                return;
            }
            
            let storeId = storeIdElement.value;
            console.log('🔍 [DEBUG] Valor de store_id:', storeId, '(tipo:', typeof storeId, ')');
            
            // Se storeId estiver vazio, tentar buscar da sessão (para admin)
            if (!storeId || storeId === '') {
                console.warn('⚠️ [DEBUG] store_id está vazio, tentando buscar da sessão...');
                // Fazer requisição sem store_id, o backend tentará buscar da sessão
            }
            
            const isConserto = document.getElementById('is_conserto')?.value === '1';
            const url = storeId 
                ? `{{ route('os.generate-number') }}?store_id=${encodeURIComponent(storeId)}&is_conserto=${isConserto}`
                : `{{ route('os.generate-number') }}?is_conserto=${isConserto}`;
            
            console.log('🔍 [DEBUG] Fazendo requisição para:', url);
            
            document.getElementById('os_number_display').textContent = 'Carregando...';
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin' // Incluir cookies de sessão
            })
                .then(async r => {
                    console.log('🔍 [DEBUG] Resposta recebida, status:', r.status, r.statusText);
                    
                    if (r.status === 419) {
                        console.error('❌ [DEBUG] Erro 419: Token CSRF expirado!');
                        document.getElementById('os_number_display').textContent = 'Erro: sessão expirada';
                        alert('⚠️ Sua sessão expirou. Por favor, recarregue a página.');
                        return Promise.reject(new Error('CSRF token expired'));
                    }
                    
                    if (!r.ok) {
                        const errorText = await r.text();
                        console.error('❌ [DEBUG] Resposta não OK:', r.status, r.statusText);
                        console.error('❌ [DEBUG] Corpo da resposta de erro:', errorText);
                        
                        // Tentar parsear como JSON
                        let errorData;
                        try {
                            errorData = JSON.parse(errorText);
                        } catch (e) {
                            errorData = { error: errorText || `Erro ${r.status}: ${r.statusText}` };
                        }
                        
                        throw new Error(errorData.error || `Erro ${r.status}: ${r.statusText}`);
                    }
                    
                    return r.json();
                })
                .then(data => {
                    console.log('🔍 [DEBUG] Dados JSON recebidos:', data);
                    if (data && data.os_number) {
                        console.log('✅ [DEBUG] Número da OS gerado com sucesso:', data.os_number);
                        document.getElementById('os_number_display').textContent = data.os_number;
                    } else if (data && data.error) {
                        console.error('❌ [DEBUG] Erro retornado pela API:', data.error);
                        document.getElementById('os_number_display').textContent = 'Erro: ' + data.error;
                        alert('⚠️ ' + data.error);
                    } else {
                        console.warn('⚠️ [DEBUG] Resposta não contém os_number:', data);
                        document.getElementById('os_number_display').textContent = 'Erro ao gerar';
                    }
                })
                .catch(error => {
                    console.error('❌ [DEBUG] Erro na requisição fetch:', error);
                    console.error('❌ [DEBUG] Tipo do erro:', error.name);
                    console.error('❌ [DEBUG] Mensagem do erro:', error.message);
                    console.error('❌ [DEBUG] Stack do erro:', error.stack);
                    
                    const errorMsg = error.message || 'Erro ao conectar com o servidor';
                    document.getElementById('os_number_display').textContent = errorMsg;
                    alert('⚠️ Erro ao carregar número da OS: ' + errorMsg);
                });
        }

        // Busca de cliente em tempo real
        let clientSearchTimeout;
        document.getElementById('client_search')?.addEventListener('input', function() {
            clearTimeout(clientSearchTimeout);
            const query = this.value.trim();
            const resultsDiv = document.getElementById('client_results');
            
            if (!resultsDiv) {
                console.error('Elemento client_results não encontrado');
                return;
            }
            
            if (query.length < 1) {
                // Limpar seleção se campo estiver vazio
                resultsDiv.classList.add('hidden');
                document.getElementById('client_id').value = '';
                document.getElementById('client_selected').textContent = '';
                document.getElementById('payment_client_display').textContent = 'Selecione um cliente na aba "Dados e Itens"';
                return;
            }
            
            // Aguardar 300ms após parar de digitar antes de buscar
            clientSearchTimeout = setTimeout(() => {
                fetch(`/os/buscar-cliente?q=${encodeURIComponent(query)}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.length > 0) {
                            // Salvar resultados globalmente
                            window.clientSearchResults = data;
                            // Mostrar lista de resultados
                            resultsDiv.innerHTML = data.map((client, index) => {
                                const clientText = (client.text || 'Sem nome').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                                const clientCode = (client.code || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                                const clientCpfCnpj = (client.cpf_cnpj || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                                return `
                                    <div class="p-4 border-b border-slate-200 hover:bg-blue-50 cursor-pointer" onclick="selectClientFromList(${index})">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="font-bold text-lg text-slate-900">${clientText}</div>
                                                ${clientCode ? `<div class="text-sm text-slate-600">Código: ${clientCode}</div>` : ''}
                                            </div>
                                            <button type="button" class="px-4 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700">
                                                ✓ Selecionar
                                            </button>
                                        </div>
                                    </div>
                                `;
                            }).join('');
                            resultsDiv.classList.remove('hidden');
                        } else {
                            resultsDiv.innerHTML = '<div class="p-4 text-center text-slate-600 font-semibold">❌ Nenhum cliente encontrado</div>';
                            resultsDiv.classList.remove('hidden');
                        }
                    })
                    .catch(err => {
                        console.error('Erro na busca:', err);
                        if (resultsDiv) {
                            resultsDiv.innerHTML = '<div class="p-4 text-center text-red-600 font-semibold">❌ Erro ao buscar clientes</div>';
                            resultsDiv.classList.remove('hidden');
                        }
                    });
            }, 300);
        });

        function selectClientFromList(index) {
            if (window.clientSearchResults && window.clientSearchResults[index]) {
                const client = window.clientSearchResults[index];
                document.getElementById('client_id').value = client.id;
                document.getElementById('client_selected').textContent = '✅ ' + client.text;
                document.getElementById('payment_client_display').textContent = client.text;
                document.getElementById('client_search').value = '';
                const resultsDiv = document.getElementById('client_results');
                if (resultsDiv) {
                    resultsDiv.classList.add('hidden');
                }
            }
        }

        // Busca de produto em tempo real
        let productSearchTimeout;
        document.getElementById('product_search')?.addEventListener('input', function() {
            clearTimeout(productSearchTimeout);
            const query = this.value.trim();
            const storeId = document.getElementById('store_id').value;
            const resultsDiv = document.getElementById('product_results');
            
            if (!resultsDiv) {
                console.error('Elemento product_results não encontrado');
                return;
            }
            
            // Se não houver query ou loja, ocultar resultados
            if (!query || query.length < 2 || !storeId) {
                resultsDiv.classList.add('hidden');
                return;
            }
            
            // Aguardar 300ms após parar de digitar antes de buscar
            productSearchTimeout = setTimeout(() => {
                fetch(`/os/buscar-produto?q=${encodeURIComponent(query)}&store_id=${storeId}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.length > 0) {
                            // Salvar resultados globalmente
                            window.productSearchResults = data;
                            // Mostrar lista de resultados
                            resultsDiv.innerHTML = data.map((product, index) => {
                                const productName = (product.name || 'Sem nome').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                                const productRef = (product.ref || 'N/A').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                                const productPrice = parseFloat(product.unit_price || 0).toFixed(2).replace('.', ',');
                                return `
                                    <div class="p-4 border-b border-slate-200 hover:bg-blue-50 cursor-pointer" onclick="selectProductFromList(${index})">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="font-bold text-lg text-slate-900">${productName}</div>
                                                <div class="text-sm text-slate-600">Ref: ${productRef} | Preço: R$ ${productPrice}</div>
                                            </div>
                                            <button type="button" class="px-4 py-2 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700">
                                                ➕ Adicionar
                                            </button>
                                        </div>
                                    </div>
                                `;
                            }).join('');
                            resultsDiv.classList.remove('hidden');
                        } else {
                            resultsDiv.innerHTML = '<div class="p-4 text-center text-slate-600 font-semibold">❌ Nenhum produto encontrado</div>';
                            resultsDiv.classList.remove('hidden');
                        }
                    })
                    .catch(err => {
                        console.error('Erro na busca:', err);
                        if (resultsDiv) {
                            resultsDiv.innerHTML = '<div class="p-4 text-center text-red-600 font-semibold">❌ Erro ao buscar produtos</div>';
                            resultsDiv.classList.remove('hidden');
                        }
                    });
            }, 300);
        });

        function selectProductFromList(index) {
            if (window.productSearchResults && window.productSearchResults[index]) {
                addItem(window.productSearchResults[index]);
                document.getElementById('product_search').value = '';
                const resultsDiv = document.getElementById('product_results');
                if (resultsDiv) {
                    resultsDiv.classList.add('hidden');
                }
            }
        }

        function addItem(product) {
            const item = {
                id: itemCounter++,
                product_id: product.id,
                type: 'PRODUTO',
                ref: product.ref || '',
                name: product.name,
                unit: product.unit || 'UN',
                qty: 1,
                unit_price: product.unit_price || 0,
                price_adjust: 0,
                add_disc_percent: 0,
            };
            items.push(item);
            updateItemsTable();
            calculateTotals();
        }

        function removeItem(id) {
            if (confirm('⚠️ Deseja remover este item?')) {
                items = items.filter(i => i.id !== id);
                updateItemsTable();
                calculateTotals();
            }
        }

        function updateItemsTable() {
            const tbody = document.getElementById('items_table_body');
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-6 text-center text-slate-600 text-xl font-semibold">Nenhum item adicionado.</td></tr>';
                return;
            }
            
            tbody.innerHTML = items.map((item, idx) => {
                const unitPriceNet = item.unit_price + (item.price_adjust || 0);
                const lineTotal = item.qty * unitPriceNet * (1 + (item.add_disc_percent || 0) / 100);
                const itemName = (item.name || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                return `
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-4 border-r-2 border-slate-300">
                            <input type="text" name="items[${idx}][name]" value="${itemName}" required class="text-lg rounded-lg border-2 border-slate-400 w-full px-3 py-2 font-semibold">
                            <input type="hidden" name="items[${idx}][type]" value="${item.type || 'PRODUTO'}">
                            <input type="hidden" name="items[${idx}][product_id]" value="${item.product_id || ''}">
                            <input type="hidden" name="items[${idx}][ref]" value="${item.ref || ''}">
                        </td>
                        <td class="px-4 py-4 border-r-2 border-slate-300">
                            <input type="number" step="0.001" name="items[${idx}][qty]" value="${item.qty}" min="0.001" onchange="updateItem(${item.id}, 'qty', this.value)" class="text-lg rounded-lg border-2 border-slate-400 w-20 px-2 py-2 font-semibold text-center">
                        </td>
                        <td class="px-4 py-4 border-r-2 border-slate-300">
                            <input type="number" step="0.01" name="items[${idx}][unit_price]" value="${item.unit_price}" min="0" onchange="updateItem(${item.id}, 'unit_price', this.value)" class="text-lg rounded-lg border-2 border-slate-400 w-32 px-2 py-2 font-semibold">
                        </td>
                        <td class="px-4 py-4 border-r-2 border-slate-300 font-bold text-xl">R$ ${lineTotal.toFixed(2).replace('.', ',')}</td>
                        <td class="px-4 py-4">
                            <button type="button" onclick="removeItem(${item.id})" class="px-4 py-2 bg-red-600 text-white text-lg font-bold rounded-lg hover:bg-red-700">
                                ❌ Remover
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function updateItem(id, field, value) {
            const item = items.find(i => i.id === id);
            if (item) {
                item[field] = parseFloat(value) || 0;
                updateItemsTable();
                calculateTotals();
            }
        }

        function calculateTotals() {
            let subtotal = 0;
            items.forEach(item => {
                const unitPriceNet = item.unit_price + (item.price_adjust || 0);
                const lineTotal = item.qty * unitPriceNet * (1 + (item.add_disc_percent || 0) / 100);
                subtotal += lineTotal;
            });
            
            const discount = parseFloat(document.getElementById('discount_value').value) || 0;
            const total = subtotal - discount;
            
            document.getElementById('subtotal_display').textContent = 'R$ ' + subtotal.toFixed(2).replace('.', ',');
            document.getElementById('total_display').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
            updatePaymentTotal();
        }

        function updatePaymentTotal() {
            const total = parseFloat(document.getElementById('total_display').textContent.replace('R$ ', '').replace(',', '.')) || 0;
            document.getElementById('payment_total_display').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
            calculatePaymentBalance();
        }

        function updatePaymentFields() {
            const paymentType = document.getElementById('payment_type').value;
            const paymentMethod = document.getElementById('payment_method').value;
            const sinalField = document.getElementById('sinal_amount_field');
            const parcelasField = document.getElementById('parcelas_field');
            const carneParcelasField = document.getElementById('carne_parcelas_field');
            
            // Esconder campos de carnê por padrão
            carneParcelasField.classList.add('hidden');
            
            if (paymentType === 'sinal') {
                sinalField.classList.remove('hidden');
                parcelasField.classList.add('hidden');
            } else if (paymentType === 'parcelado') {
                sinalField.classList.add('hidden');
                parcelasField.classList.remove('hidden');
            } else {
                sinalField.classList.add('hidden');
                parcelasField.classList.add('hidden');
            }
            
            // Mostrar campo de parcelas do carnê se método for carnê
            if (paymentMethod === 'carne') {
                carneParcelasField.classList.remove('hidden');
                calculateCarneParcelValue();
            }
            
            calculatePaymentBalance();
        }

        function calculatePaymentBalance() {
            const total = parseFloat(document.getElementById('total_display').textContent.replace('R$ ', '').replace(',', '.')) || 0;
            const sinalAmount = parseFloat(document.getElementById('sinal_amount').value) || 0;
            const balance = total - sinalAmount;
            document.getElementById('payment_balance').textContent = 'R$ ' + Math.max(0, balance).toFixed(2).replace('.', ',');
            calculateCarneParcelValue();
        }
        
        function calculateCarneParcelValue() {
            const paymentMethod = document.getElementById('payment_method').value;
            if (paymentMethod === 'carne') {
                const total = parseFloat(document.getElementById('total_display').textContent.replace('R$ ', '').replace(',', '.')) || 0;
                const sinalAmount = parseFloat(document.getElementById('sinal_amount').value) || 0;
                const balance = Math.max(0, total - sinalAmount);
                const parcelasCount = parseFloat(document.getElementById('carne_parcelas_count').value) || 1;
                const valorParcela = balance / parcelasCount;
                document.getElementById('carne_parcela_value').textContent = 'R$ ' + valorParcela.toFixed(2).replace('.', ',');
            }
        }

        function validateForm(e) {
            console.log('🔍 [DEBUG] validateForm() iniciada');
            
            const isConserto = document.getElementById('is_conserto')?.value === '1';
            
            // Validar cliente (modo OS) ou nome (modo Conserto)
            if (isConserto) {
                const consertoName = document.getElementById('conserto_client_name');
                const name = consertoName ? consertoName.value?.trim() : '';
                if (!name) {
                    console.error('❌ [DEBUG] Nome não informado no modo Conserto');
                    if (e && typeof e.preventDefault === 'function') {
                        e.preventDefault();
                    }
                    alert('⚠️ Por favor, informe o nome do cliente');
                    switchTab('dados-itens');
                    return false;
                }
                // Limpar client_id para não enviar valor antigo
                const clientIdEl = document.getElementById('client_id');
                if (clientIdEl) clientIdEl.value = '';
            } else {
                const clientIdElement = document.getElementById('client_id');
                const clientId = clientIdElement ? clientIdElement.value : null;
                console.log('🔍 [DEBUG] client_id:', clientId);
                if (!clientId) {
                    console.error('❌ [DEBUG] Cliente não selecionado');
                    if (e && typeof e.preventDefault === 'function') {
                        e.preventDefault();
                    }
                    alert('⚠️ Por favor, selecione um cliente na aba "Dados e Itens"');
                    switchTab('dados-itens');
                    return false;
                }
            }

            // Validar loja (para não-admin)
            const storeId = document.getElementById('store_id');
            console.log('🔍 [DEBUG] Elemento store_id na validação:', storeId);
            console.log('🔍 [DEBUG] Valor store_id =', storeId?.value);
            if (storeId && !storeId.value) {
                console.error('❌ [DEBUG] Loja não selecionada');
                if (e && e.preventDefault) {
                    e.preventDefault();
                }
                alert('⚠️ Por favor, selecione uma loja');
                switchTab('dados-itens');
                return false;
            }

            // Validar tipo de pagamento
            const paymentTypeElement = document.getElementById('payment_type');
            const paymentType = paymentTypeElement ? paymentTypeElement.value : null;
            console.log('🔍 [DEBUG] payment_type =', paymentType);
            if (!paymentType) {
                console.error('❌ [DEBUG] Tipo de pagamento não selecionado');
                if (e && e.preventDefault) {
                    e.preventDefault();
                }
                alert('⚠️ Por favor, selecione o tipo de pagamento na aba "Pagamento"');
                switchTab('pagamento');
                return false;
            }

            // Validar método de pagamento (sempre obrigatório)
            const paymentMethodElement = document.getElementById('payment_method');
            const paymentMethod = paymentMethodElement ? paymentMethodElement.value : null;
            console.log('🔍 [DEBUG] payment_method =', paymentMethod);
            if (!paymentMethod) {
                console.error('❌ [DEBUG] Método de pagamento não selecionado');
                if (e && e.preventDefault) {
                    e.preventDefault();
                }
                alert('⚠️ Por favor, selecione o método de pagamento');
                switchTab('pagamento');
                return false;
            }

            // Validar sinal se necessário
            if (paymentType === 'sinal') {
                const sinalAmount = parseFloat(document.getElementById('sinal_amount').value) || 0;
                const total = parseFloat(document.getElementById('total_display').textContent.replace('R$ ', '').replace(',', '.')) || 0;
                if (sinalAmount <= 0) {
                    if (e && e.preventDefault) {
                        e.preventDefault();
                    }
                    alert('⚠️ Por favor, informe o valor do sinal (entrada)');
                    switchTab('pagamento');
                    return false;
                }
                if (sinalAmount >= total) {
                    if (e && e.preventDefault) {
                        e.preventDefault();
                    }
                    alert('⚠️ O valor do sinal não pode ser maior ou igual ao total da OS. Use "À Vista" se o pagamento for completo.');
                    switchTab('pagamento');
                    return false;
                }
            }

            // Validar parcelas se necessário
            if (paymentType === 'parcelado') {
                const parcelasCount = parseFloat(document.getElementById('parcelas_count').value) || 0;
                if (parcelasCount < 1) {
                    if (e && e.preventDefault) {
                        e.preventDefault();
                    }
                    alert('⚠️ Por favor, informe o número de parcelas');
                    switchTab('pagamento');
                    return false;
                }
            }

            // Validar parcelas do carnê se necessário
            if (paymentMethod === 'carne') {
                const carneParcelasCount = parseFloat(document.getElementById('carne_parcelas_count').value) || 0;
                if (carneParcelasCount < 1) {
                    if (e && e.preventDefault) {
                        e.preventDefault();
                    }
                    alert('⚠️ Por favor, informe o número de parcelas do carnê');
                    switchTab('pagamento');
                    return false;
                }
            }

            console.log('✅ [DEBUG] Todas as validações passaram!');
            return true;
        }

        // FUNÇÃO GLOBAL PARA CONFIGURAR BOTÃO - executa múltiplas vezes para garantir
        window.setupSubmitButton = function() {
            console.log('🔧 [SETUP] Configurando botão de submit...');
            
            const btn = document.getElementById('finalizarOsBtn');
            if (!btn) {
                console.warn('⚠️ [SETUP] Botão não encontrado ainda');
                return false;
            }
            
            // Forçar correção
            btn.type = 'button';
            btn.removeAttribute('form');
            btn.removeAttribute('onclick');
            
            // Se já tem listener, não adicionar novamente
            if (btn.hasAttribute('data-listener-setup')) {
                console.log('✅ [SETUP] Listener já configurado');
                return true;
            }
            
            // Adicionar listener
            btn.addEventListener('click', function(e) {
                console.log('🎯 [CLICK] Botão clicado!');
                e.preventDefault();
                e.stopPropagation();
                
                if (typeof window.submitOsForm === 'function') {
                    console.log('✅ [CLICK] Chamando submitOsForm()...');
                    window.submitOsForm();
                } else {
                    console.error('❌ [CLICK] submitOsForm não encontrada!');
                    alert('Erro: Função não encontrada. Recarregue a página.');
                }
            });
            
            btn.setAttribute('data-listener-setup', 'true');
            console.log('✅ [SETUP] Botão configurado com sucesso!');
            return true;
        };
        
        // Executar imediatamente e várias vezes
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            window.setupSubmitButton();
        }
        
        document.addEventListener('DOMContentLoaded', window.setupSubmitButton);
        window.addEventListener('load', window.setupSubmitButton);
        
        // Tentar várias vezes com delay
        setTimeout(window.setupSubmitButton, 100);
        setTimeout(window.setupSubmitButton, 500);
        setTimeout(window.setupSubmitButton, 1000);
        setTimeout(window.setupSubmitButton, 2000);
        
        // Listener no submit do formulário
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔍 [DEBUG] ========== INICIANDO CONFIGURAÇÃO ==========');
            console.log('🔍 [DEBUG] Configurando listener do formulário...');
            
            const form = document.getElementById('osForm');
            const submitBtn = document.getElementById('finalizarOsBtn');
            
            console.log('🔍 [DEBUG] Form encontrado:', form);
            console.log('🔍 [DEBUG] Botão submit encontrado:', submitBtn);
            if (submitBtn) {
                console.log('🔍 [DEBUG] Botão type:', submitBtn.type);
                console.log('🔍 [DEBUG] Botão form attribute:', submitBtn.getAttribute('form'));
                console.log('🔍 [DEBUG] Botão onclick:', submitBtn.getAttribute('onclick'));
            }
            
            if (!form) {
                console.error('❌ [DEBUG] Formulário osForm não encontrado!');
                return;
            }
            
            // Verificar se submitOsForm está acessível
            console.log('🔍 [DEBUG] Verificando submitOsForm...');
            console.log('🔍 [DEBUG] typeof window.submitOsForm:', typeof window.submitOsForm);
            if (typeof window.submitOsForm !== 'function') {
                console.error('❌ [DEBUG] submitOsForm não está definida!');
                console.error('❌ [DEBUG] window.submitOsForm:', window.submitOsForm);
            } else {
                console.log('✅ [DEBUG] submitOsForm está disponível');
                console.log('✅ [DEBUG] submitOsForm é uma função:', typeof window.submitOsForm === 'function');
            }
            
            // Adicionar listener direto no botão (ONLY METHOD - sem onclick inline)
            if (submitBtn) {
                console.log('🔍 [DEBUG] Preparando para adicionar listener no botão...');
                
                // Remover atributos que podem interferir
                submitBtn.removeAttribute('onclick');
                submitBtn.removeAttribute('form');
                submitBtn.type = 'button'; // Forçar type button
                console.log('🔍 [DEBUG] Botão modificado - type:', submitBtn.type, '| form:', submitBtn.getAttribute('form'));
                
                // Remover listeners anteriores se existirem (evitar duplicação)
                const newBtn = submitBtn.cloneNode(true);
                submitBtn.parentNode.replaceChild(newBtn, submitBtn);
                const finalSubmitBtn = document.getElementById('finalizarOsBtn');
                
                finalSubmitBtn.addEventListener('click', function(e) {
                    console.log('🔍 [DEBUG] ========== CLIQUE NO BOTÃO DETECTADO ==========');
                    console.log('🔍 [DEBUG] Evento:', e.type, '| Target:', e.target.id);
                    
                    // Prevenir comportamento padrão
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Verificar e chamar a função
                    console.log('🔍 [DEBUG] Verificando submitOsForm...');
                    console.log('🔍 [DEBUG] typeof window.submitOsForm:', typeof window.submitOsForm);
                    
                    if (typeof window.submitOsForm === 'function') {
                        console.log('✅ [DEBUG] Chamando window.submitOsForm()...');
                        try {
                            const result = window.submitOsForm();
                            console.log('🔍 [DEBUG] Resultado de submitOsForm:', result);
                        } catch (error) {
                            console.error('❌ [DEBUG] ERRO ao executar submitOsForm:', error);
                            console.error('❌ [DEBUG] Mensagem:', error.message);
                            console.error('❌ [DEBUG] Stack:', error.stack);
                            alert('Erro ao processar formulário: ' + error.message);
                            // Reabilitar botão em caso de erro
                            finalBtn.disabled = false;
                            finalBtn.textContent = '✅ Finalizar OS';
                        }
                    } else {
                        console.error('❌ [DEBUG] submitOsForm não está disponível!');
                        console.error('❌ [DEBUG] window.submitOsForm:', window.submitOsForm);
                        alert('Erro: Função de submit não encontrada. Recarregue a página.');
                    }
                }, false); // Usar bubble phase
                console.log('✅ [DEBUG] Listener de clique adicionado ao botão');
                
                // Forçar correção do botão após um pequeno delay (garantir que não seja sobrescrito)
                setTimeout(function() {
                    const btn = document.getElementById('finalizarOsBtn');
                    if (btn) {
                        if (btn.type !== 'button') {
                            console.log('⚠️ [DEBUG] Botão ainda está como submit, corrigindo...');
                            btn.type = 'button';
                        }
                        if (btn.getAttribute('form')) {
                            console.log('⚠️ [DEBUG] Botão ainda tem atributo form, removendo...');
                            btn.removeAttribute('form');
                        }
                        console.log('✅ [DEBUG] Botão verificado - type:', btn.type, '| form:', btn.getAttribute('form'));
                    }
                }, 100);
            } else {
                console.error('❌ [DEBUG] Botão finalizarOsBtn não encontrado para adicionar listener!');
            }
            
            // Listener no submit - processar quando o evento submit for disparado
            form.addEventListener('submit', function(e) {
                console.log('🔍 [DEBUG] ========== EVENTO SUBMIT DISPARADO ==========');
                console.log('🔍 [DEBUG] Form já validado:', form.dataset.validated === 'true');
                
                // Se já foi validado pela função submitOsForm, apenas permitir (não fazer nada)
                if (form.dataset.validated === 'true') {
                    console.log('✅ [DEBUG] Form já validado pela função submitOsForm, permitindo submit');
                    delete form.dataset.validated; // Limpar flag
                    // Não fazer preventDefault, deixar submit acontecer
                    return true;
                }
                
                // Se chegou aqui, o submit foi disparado de outra forma (ex: Enter em campo)
                console.log('⚠️ [DEBUG] Submit não veio de submitOsForm, validando agora...');
                
                // Validar e processar
                const isValid = validateForm(e);
                if (!isValid) {
                    console.error('❌ [DEBUG] Validação falhou no listener');
                    e.preventDefault();
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = '✅ Finalizar OS';
                    }
                    return false;
                }
                
                // Preparar dados
                if (items.length === 0) {
                    const itemInputs = form.querySelectorAll('[name^="items["]');
                    console.log('🔍 [DEBUG] Removendo', itemInputs.length, 'campos de items vazios');
                    itemInputs.forEach(input => input.remove());
                }
                
                const paymentMethod = document.getElementById('payment_method');
                if (paymentMethod && !paymentMethod.value) {
                    e.preventDefault();
                    alert('⚠️ Por favor, selecione o método de pagamento');
                    switchTab('pagamento');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = '✅ Finalizar OS';
                    }
                    return false;
                }
                
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = '⏳ Processando...';
                }
                
                console.log('✅ [DEBUG] Listener permitindo submit');
                return true;
            });
        });

        // Carregar número da OS ao carregar a página se loja já estiver selecionada
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔍 [DEBUG] DOMContentLoaded - página carregada');
            
            // Verificar token CSRF ao carregar
            const csrfToken = getCsrfToken();
            console.log('🔍 [DEBUG] Token CSRF ao carregar:', csrfToken ? '✅ Disponível' : '❌ Não encontrado');
            if (!csrfToken) {
                console.error('❌ [DEBUG] ATENÇÃO: Token CSRF não encontrado! Isso pode causar erros 419.');
            }
            
            // Verificar meta tag CSRF
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            console.log('🔍 [DEBUG] Meta tag CSRF encontrada:', csrfMeta ? 'Sim' : 'Não');
            if (csrfMeta) {
                console.log('🔍 [DEBUG] Valor do meta CSRF:', csrfMeta.getAttribute('content') ? 'Presente' : 'Vazio');
            }
            
            // Verificar token no formulário
            const formToken = document.querySelector('input[name="_token"]');
            console.log('🔍 [DEBUG] Token no formulário:', formToken ? 'Sim' : 'Não');
            
            // Buscar store_id de múltiplas formas
            let storeIdInput = document.getElementById('store_id');
            if (!storeIdInput) {
                storeIdInput = document.querySelector('input[name="store_id"]');
            }
            if (!storeIdInput) {
                storeIdInput = document.querySelector('select[name="store_id"]');
            }
            
            console.log('🔍 [DEBUG] Elemento store_id no DOMContentLoaded:', storeIdInput);
            console.log('🔍 [DEBUG] Tipo do elemento:', storeIdInput ? storeIdInput.tagName : 'não encontrado');
            
            if (storeIdInput) {
                const storeId = storeIdInput.value;
                console.log('🔍 [DEBUG] Valor inicial do store_id:', storeId);
                console.log('🔍 [DEBUG] Atributos do elemento:', {
                    id: storeIdInput.id,
                    name: storeIdInput.name,
                    type: storeIdInput.type || storeIdInput.tagName.toLowerCase(),
                    required: storeIdInput.required
                });
                
                // Sempre tentar carregar o número, mesmo se storeId estiver vazio
                // O backend tentará buscar da sessão se for admin
                console.log('✅ [DEBUG] Carregando número da OS...');
                loadOsNumber();
            } else {
                console.error('❌ [DEBUG] Elemento store_id não encontrado no DOM!');
                console.log('🔍 [DEBUG] Tentando encontrar elementos relacionados...');
                const allSelects = document.querySelectorAll('select');
                const allInputs = document.querySelectorAll('input[name="store_id"]');
                console.log('🔍 [DEBUG] Total de elementos select encontrados:', allSelects.length);
                console.log('🔍 [DEBUG] Total de elementos input[name="store_id"] encontrados:', allInputs.length);
                
                // Mesmo sem encontrar, tentar carregar (backend pode buscar da sessão)
                console.log('⚠️ [DEBUG] Tentando carregar número mesmo sem elemento encontrado...');
                loadOsNumber();
            }
            
            // Adicionar comportamento de Enter para confirmar dados (blur) em todos os inputs
            document.addEventListener('keydown', function(e) {
                // Se pressionar Enter em um input ou textarea
                if (e.key === 'Enter' && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA')) {
                    const target = e.target;
                    
                    // Ignorar campos de busca e outros campos especiais
                    if (target.id === 'product_search' || target.id === 'client_search') {
                        return; // Deixar comportamento padrão para busca
                    }
                    
                    // Para textarea, permitir quebra de linha com Shift+Enter
                    if (target.tagName === 'TEXTAREA') {
                        if (!e.shiftKey) {
                            e.preventDefault();
                            target.blur();
                        }
                        return;
                    }
                    
                    // Para inputs, disparar blur para confirmar o dado
                    if (target.tagName === 'INPUT' && target.type !== 'submit' && target.type !== 'button') {
                        e.preventDefault();
                        target.blur();
                    }
                }
            });
        });

        // ========== CÁLCULO AUTOMÁTICO DA RECEITA ==========
        function parseGrau(value) {
            if (!value || value.trim() === '' || value === '-') return null;
            // Remove espaços e converte vírgula para ponto
            const cleaned = value.toString().trim().replace(',', '.');
            const num = parseFloat(cleaned);
            return isNaN(num) ? null : num;
        }

        function formatGrau(value) {
            if (value === null || isNaN(value)) return '';
            // Formata com 2 casas decimais, substituindo ponto por vírgula
            return value.toFixed(2).replace('.', ',');
        }

        function calcularReceitaPerto() {
            const adicaoField = document.getElementById('prescription_adicao');
            if (!adicaoField) return;

            const adicao = parseGrau(adicaoField.value);
            if (adicao === null || isNaN(adicao)) {
                return; // Não calcular se adição não estiver preenchida
            }

            console.log('🔄 Calculando receita de perto com adição:', adicao);

            // Calcular OD (Olho Direito)
            const esfLongeOD = parseGrau(document.getElementById('prescription_longe_esferico_od')?.value);
            const cilLongeOD = parseGrau(document.getElementById('prescription_longe_cilindrico_od')?.value);
            const eixoLongeOD = parseGrau(document.getElementById('prescription_longe_eixo_od')?.value);

            if (esfLongeOD !== null && !isNaN(esfLongeOD)) {
                // Perto ESF = Longe ESF + Adição
                const esfPertoOD = esfLongeOD + adicao;
                const pertoEsfODField = document.getElementById('prescription_perto_esferico_od');
                if (pertoEsfODField && !pertoEsfODField.dataset.manual) {
                    pertoEsfODField.value = formatGrau(esfPertoOD);
                    pertoEsfODField.classList.add('bg-green-50', 'border-green-500');
                    setTimeout(() => {
                        pertoEsfODField.classList.remove('bg-green-50', 'border-green-500');
                    }, 2000);
                }

                // Perto CIL = Longe CIL (mantém o mesmo)
                if (cilLongeOD !== null) {
                    const pertoCilODField = document.getElementById('prescription_perto_cilindrico_od');
                    if (pertoCilODField && !pertoCilODField.dataset.manual) {
                        pertoCilODField.value = formatGrau(cilLongeOD);
                        pertoCilODField.classList.add('bg-green-50', 'border-green-500');
                        setTimeout(() => {
                            pertoCilODField.classList.remove('bg-green-50', 'border-green-500');
                        }, 2000);
                    }
                }

                // Perto EIXO = Longe EIXO (mantém o mesmo)
                if (eixoLongeOD !== null) {
                    const pertoEixoODField = document.getElementById('prescription_perto_eixo_od');
                    if (pertoEixoODField && !pertoEixoODField.dataset.manual) {
                        pertoEixoODField.value = eixoLongeOD.toString();
                        pertoEixoODField.classList.add('bg-green-50', 'border-green-500');
                        setTimeout(() => {
                            pertoEixoODField.classList.remove('bg-green-50', 'border-green-500');
                        }, 2000);
                    }
                }
            }

            // Calcular OE (Olho Esquerdo)
            const esfLongeOE = parseGrau(document.getElementById('prescription_longe_esferico_oe')?.value);
            const cilLongeOE = parseGrau(document.getElementById('prescription_longe_cilindrico_oe')?.value);
            const eixoLongeOE = parseGrau(document.getElementById('prescription_longe_eixo_oe')?.value);

            if (esfLongeOE !== null && !isNaN(esfLongeOE)) {
                // Perto ESF = Longe ESF + Adição
                const esfPertoOE = esfLongeOE + adicao;
                const pertoEsfOEField = document.getElementById('prescription_perto_esferico_oe');
                if (pertoEsfOEField && !pertoEsfOEField.dataset.manual) {
                    pertoEsfOEField.value = formatGrau(esfPertoOE);
                    pertoEsfOEField.classList.add('bg-green-50', 'border-green-500');
                    setTimeout(() => {
                        pertoEsfOEField.classList.remove('bg-green-50', 'border-green-500');
                    }, 2000);
                }

                // Perto CIL = Longe CIL (mantém o mesmo)
                if (cilLongeOE !== null) {
                    const pertoCilOEField = document.getElementById('prescription_perto_cilindrico_oe');
                    if (pertoCilOEField && !pertoCilOEField.dataset.manual) {
                        pertoCilOEField.value = formatGrau(cilLongeOE);
                        pertoCilOEField.classList.add('bg-green-50', 'border-green-500');
                        setTimeout(() => {
                            pertoCilOEField.classList.remove('bg-green-50', 'border-green-500');
                        }, 2000);
                    }
                }

                // Perto EIXO = Longe EIXO (mantém o mesmo)
                if (eixoLongeOE !== null) {
                    const pertoEixoOEField = document.getElementById('prescription_perto_eixo_oe');
                    if (pertoEixoOEField && !pertoEixoOEField.dataset.manual) {
                        pertoEixoOEField.value = eixoLongeOE.toString();
                        pertoEixoOEField.classList.add('bg-green-50', 'border-green-500');
                        setTimeout(() => {
                            pertoEixoOEField.classList.remove('bg-green-50', 'border-green-500');
                        }, 2000);
                    }
                }
            }
        }

        // Configurar listeners para cálculo automático
        document.addEventListener('DOMContentLoaded', function() {
            // Listener para campo de adição
            const adicaoField = document.getElementById('prescription_adicao');
            if (adicaoField) {
                adicaoField.addEventListener('input', calcularReceitaPerto);
                adicaoField.addEventListener('change', calcularReceitaPerto);
                adicaoField.addEventListener('blur', calcularReceitaPerto);
            }

            // Listeners para campos de longe (recalcular quando mudarem)
            const camposLonge = [
                'prescription_longe_esferico_od',
                'prescription_longe_cilindrico_od',
                'prescription_longe_eixo_od',
                'prescription_longe_esferico_oe',
                'prescription_longe_cilindrico_oe',
                'prescription_longe_eixo_oe'
            ];

            camposLonge.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', calcularReceitaPerto);
                    field.addEventListener('change', calcularReceitaPerto);
                    field.addEventListener('blur', calcularReceitaPerto);
                }
            });

            // Permitir edição manual dos campos de perto (marcar como manual)
            const camposPerto = [
                'prescription_perto_esferico_od',
                'prescription_perto_cilindrico_od',
                'prescription_perto_eixo_od',
                'prescription_perto_esferico_oe',
                'prescription_perto_cilindrico_oe',
                'prescription_perto_eixo_oe'
            ];

            camposPerto.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', function() {
                        this.dataset.manual = 'true';
                        this.classList.remove('bg-green-50', 'border-green-500');
                    });
                    field.addEventListener('focus', function() {
                        this.dataset.manual = 'true';
                    });
                }
            });
        });
    </script>
</x-app-layout>
