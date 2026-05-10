<x-app-layout title="Novo Produto">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 md:p-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-xl md:text-2xl font-bold text-slate-900">Novo Produto</h1>
                <div class="flex gap-2">
                    <a href="{{ route('products.index') }}" class="px-3 py-1.5 text-sm bg-slate-100 text-slate-700 font-medium rounded-lg hover:bg-slate-200 transition-colors">Cancelar</a>
                    <button type="submit" form="productForm" class="px-3 py-1.5 text-sm bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">Salvar</button>
                </div>
            </div>

            @if($errors->any())
                <div class="mb-4 p-4 bg-red-50 border-2 border-red-300 rounded-lg shadow-sm">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-sm font-bold text-red-800">Erros de Validação:</h3>
                    </div>
                    <ul class="list-disc list-inside text-red-800 text-sm space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-50 border-2 border-red-300 rounded-lg shadow-sm">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm font-semibold text-red-800">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" id="productForm" onsubmit="prepareFormForSubmit(event)">
                @csrf

                <!-- Tabs Navigation -->
                <div class="mb-4 border-b border-slate-200">
                    <nav class="flex space-x-4 overflow-x-auto" role="tablist">
                        <button type="button" onclick="switchTab('basic')" id="tab-basic" class="tab-button active px-4 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600 whitespace-nowrap">
                            Dados Básicos
                        </button>
                        <button type="button" onclick="switchTab('prices')" id="tab-prices" class="tab-button px-4 py-2 text-sm font-medium text-slate-500 border-b-2 border-transparent hover:text-slate-700 whitespace-nowrap">
                            Preços & Estoque
                        </button>
                        <button type="button" onclick="switchTab('images')" id="tab-images" class="tab-button px-4 py-2 text-sm font-medium text-slate-500 border-b-2 border-transparent hover:text-slate-700 whitespace-nowrap">
                            Imagens
                        </button>
                    </nav>
                </div>

                <!-- Tab Content: Dados Básicos -->
                <div id="content-basic" class="tab-content">
                    <div class="space-y-4">
                        <!-- Linha 1: Tipo de Produto, Tipo de Item e Código -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label for="product_type_id" class="block text-sm font-medium text-slate-700 mb-1">Tipo de Produto *</label>
                                <div class="flex gap-1">
                                    <select id="product_type_id" name="product_type_id" required class="flex-1 px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="updateCodePreview()">
                                        <option value="">Selecione</option>
                                        @foreach($productTypes as $type)
                                            <option value="{{ $type->id }}" {{ old('product_type_id') == $type->id ? 'selected' : '' }} data-prefix="{{ $type->code_prefix }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" onclick="openQuickModal('product-type')" class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-bold shadow-md" title="Cadastrar novo tipo" style="min-width: 40px;">+</button>
                                </div>
                            </div>
                            <div>
                                <label for="item_type" class="block text-sm font-medium text-slate-700 mb-1">Tipo de Item *</label>
                                <select id="item_type" name="item_type" required class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="toggleServiceFields()">
                                    <option value="PRODUTO" {{ old('item_type', 'PRODUTO') == 'PRODUTO' ? 'selected' : '' }}>PRODUTO</option>
                                    <option value="SERVICO" {{ old('item_type') == 'SERVICO' ? 'selected' : '' }}>SERVIÇO</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Código</label>
                                <input type="text" id="code_preview" value="Selecione o tipo" disabled class="w-full px-3 py-2 text-sm rounded-lg border-slate-200 bg-slate-50 text-slate-600 font-semibold">
                            </div>
                        </div>

                        <!-- Linha 2: Descrição (largura total) -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Descrição <span class="description-required">*</span></label>
                            <textarea id="description" name="description" rows="2" class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Descrição do produto...">{{ old('description') }}</textarea>
                            <p class="mt-1 text-xs text-slate-500 service-note hidden">Para serviços, a descrição é opcional</p>
                        </div>

                        <!-- Linha 3: Marca, Modelo e Cor -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label for="brand_id" class="block text-sm font-medium text-slate-700 mb-1">Marca</label>
                                <div class="flex gap-1">
                                    <select id="brand_id" name="brand_id" class="flex-1 px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Selecione</option>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" onclick="openQuickModal('brand')" class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-bold shadow-md" title="Cadastrar nova marca" style="min-width: 40px;">+</button>
                                </div>
                            </div>
                            <div>
                                <label for="model" class="block text-sm font-medium text-slate-700 mb-1">Modelo</label>
                                <input type="text" id="model" name="model" value="{{ old('model') }}" placeholder="Ex: Aviador, Wayfarer" class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="color" class="block text-sm font-medium text-slate-700 mb-1">Cor</label>
                                <div class="flex gap-1">
                                    <select id="color" name="color" class="flex-1 px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Selecione ou digite</option>
                                        @foreach($colors as $color)
                                            <option value="{{ $color->name }}" {{ old('color') == $color->name ? 'selected' : '' }}>{{ $color->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" id="color_custom" name="color_custom" value="{{ old('color') }}" placeholder="Ou digite" class="flex-1 px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" style="display: none;">
                                    <button type="button" onclick="toggleColorInput()" class="px-2 py-2 bg-slate-400 text-white rounded-lg hover:bg-slate-500 transition-colors text-xs" title="Alternar">↔</button>
                                    <button type="button" onclick="openQuickModal('color')" class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-bold shadow-md" title="Cadastrar nova cor" style="min-width: 40px;">+</button>
                                </div>
                            </div>
                        </div>

                        <!-- Linha 4: Fornecedor, Categoria e Unidade -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label for="supplier_id" class="block text-sm font-medium text-slate-700 mb-1">Fornecedor</label>
                                <div class="flex gap-1">
                                    <select id="supplier_id" name="supplier_id" class="flex-1 px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Selecione</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->trade_name ?: $supplier->legal_name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" onclick="openQuickModal('supplier')" class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-bold shadow-md" title="Cadastrar novo fornecedor" style="min-width: 40px;">+</button>
                                </div>
                            </div>
                            <div>
                                <label for="group_id" class="block text-sm font-medium text-slate-700 mb-1">Categoria</label>
                                <div class="flex gap-1">
                                    <select id="group_id" name="group_id" class="flex-1 px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Selecione</option>
                                        @foreach($groups as $group)
                                            <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" onclick="openQuickModal('category')" class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-bold shadow-md" title="Cadastrar nova categoria" style="min-width: 40px;">+</button>
                                </div>
                            </div>
                            <div>
                                <label for="unit" class="block text-sm font-medium text-slate-700 mb-1">Unidade <span class="unit-required">*</span></label>
                                <select id="unit" name="unit" class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="UN" {{ old('unit', 'UN') == 'UN' ? 'selected' : '' }}>UN - Unidade</option>
                                    <option value="PAR" {{ old('unit') == 'PAR' ? 'selected' : '' }}>PAR - Par</option>
                                    <option value="FR" {{ old('unit') == 'FR' ? 'selected' : '' }}>FR - Frasco</option>
                                    <option value="KIT" {{ old('unit') == 'KIT' ? 'selected' : '' }}>KIT - Kit</option>
                                    <option value="PC" {{ old('unit') == 'PC' ? 'selected' : '' }}>PC - Peça</option>
                                </select>
                                <p class="mt-1 text-xs text-slate-500 service-note hidden">Para serviços, a unidade é opcional</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Preços & Estoque -->
                <div id="content-prices" class="tab-content hidden">
                    @if($stores->count() > 0)
                        <!-- Seletor de Loja para Administradores -->
                        @if(auth()->user()->role === 'admin')
                            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <label for="selected_store_id" class="block text-sm font-semibold text-blue-900 mb-2">
                                    <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    Selecionar Loja (Administrador)
                                </label>
                                <select id="selected_store_id" onchange="filterStore(this.value)" class="w-full md:w-auto px-4 py-2 text-sm rounded-lg border-blue-300 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="all">Todas as Lojas</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}">@if($store->abbreviation)[{{ $store->abbreviation }}]@endif {{ $store->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-2 text-xs text-blue-700">Selecione uma loja para cadastrar preço e estoque diretamente</p>
                            </div>
                        @endif
                        
                        <div class="space-y-4" id="stores-container">
                            @foreach($stores as $store)
                                <div class="store-card border border-slate-200 rounded-lg p-4 bg-white hover:shadow-md transition-shadow" data-store-id="{{ $store->id }}">
                                    <h3 class="text-base font-semibold text-slate-800 mb-4 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        @if($store->abbreviation)[{{ $store->abbreviation }}]@endif {{ $store->name }}
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <input type="hidden" name="prices[{{ $store->id }}][store_id]" value="{{ $store->id }}">
                                        
                                        <!-- Preço de Custo -->
                                        <div class="price-field">
                                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Preço de Custo <span class="cost-required">*</span></label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-600 font-medium pointer-events-none z-10">R$</span>
                                                <input type="text" name="prices[{{ $store->id }}][cost]" value="0,00" 
                                                       class="w-full pl-12 pr-3 py-2.5 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 cost-input price-mask"
                                                       onblur="formatPrice(this); calculateMarkupAndMargin({{ $store->id }})" 
                                                       onfocus="this.select()"
                                                       placeholder="0,00">
                                            </div>
                                            <p class="mt-1 text-xs text-slate-500 service-note hidden">Serviços não precisam de preço fixo</p>
                                        </div>
                                        
                                        <!-- Preço de Venda -->
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Preço de Venda</label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-600 font-medium pointer-events-none z-10">R$</span>
                                                <input type="text" name="prices[{{ $store->id }}][price]" value="0,00" 
                                                       class="w-full pl-12 pr-3 py-2.5 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 price-input price-mask"
                                                       onblur="formatPrice(this); calculateMarkupAndMargin({{ $store->id }})" 
                                                       onfocus="this.select()"
                                                       placeholder="0,00">
                                            </div>
                                        </div>
                                        
                                        <!-- % Markup -->
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 mb-1.5">% Markup</label>
                                            <div class="relative">
                                                <input type="text" id="markup_{{ $store->id }}" readonly 
                                                       class="w-full pr-10 py-2.5 text-sm rounded-lg border-slate-300 bg-slate-100 text-slate-700 font-medium" 
                                                       value="0,00"
                                                       placeholder="0,00">
                                                <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-500">%</span>
                                            </div>
                                            <p class="mt-1 text-xs text-slate-500">Calculado automaticamente</p>
                                        </div>
                                        
                                        <!-- % Margem de Lucro -->
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 mb-1.5">% Margem de Lucro</label>
                                            <div class="relative">
                                                <input type="text" name="prices[{{ $store->id }}][margin_percent]" value="0,00" 
                                                       class="w-full pr-10 py-2.5 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 margin-input"
                                                       onblur="formatPercent(this); calculatePriceFromMargin({{ $store->id }})" 
                                                       onfocus="this.select()"
                                                       placeholder="0,00">
                                                <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-500">%</span>
                                            </div>
                                            <p class="mt-1 text-xs text-slate-500">Ou digite para calcular preço</p>
                                        </div>
                                        
                                        <!-- Quantidade Atual -->
                                        <div class="stock-field">
                                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Quantidade no Estoque</label>
                                            <input type="number" name="prices[{{ $store->id }}][qty]" value="0" min="0" 
                                                   class="w-full py-2.5 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 stock-input"
                                                   placeholder="0">
                                            <p class="mt-1 text-xs text-slate-500 stock-note">Quantidade será salva automaticamente</p>
                                            <p class="mt-1 text-xs text-slate-500 service-note hidden">Serviços não têm estoque</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-slate-500 bg-slate-50 rounded-lg border border-slate-200 p-6">
                            <svg class="mx-auto h-12 w-12 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <p class="text-sm font-medium text-slate-700 mb-2">Nenhuma loja cadastrada</p>
                            <p class="text-xs text-slate-500 mb-4">Você pode criar o produto mesmo sem lojas. Os preços podem ser definidos depois.</p>
                            <a href="{{ route('cadastros.companies.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                Cadastrar Empresa/Loja
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Tab Content: Imagens -->
                <div id="content-images" class="tab-content hidden">
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2 md:gap-3 max-w-3xl mx-auto">
                        @for($i = 1; $i <= 5; $i++)
                            <div class="relative group border-2 border-dashed border-slate-300 rounded-lg overflow-hidden bg-slate-50 hover:border-blue-400 hover:bg-blue-50 transition-colors" style="aspect-ratio: 1; max-height: 120px;">
                                <input type="file" id="image_{{ $i }}" name="images[]" accept="image/*" class="hidden" onchange="previewImage(this, {{ $i }})">
                                <label for="image_{{ $i }}" class="cursor-pointer block h-full w-full flex flex-col items-center justify-center text-center p-2">
                                    <svg class="w-5 h-5 text-slate-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-xs text-slate-500 font-medium leading-tight">{{ $i }}</span>
                                </label>
                                <img id="preview_{{ $i }}" class="hidden absolute inset-0 w-full h-full object-cover" alt="Preview">
                                <button type="button" onclick="removeImage({{ $i }})" class="hidden absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center hover:bg-red-600 shadow-md z-10" id="remove_{{ $i }}" title="Remover">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        @endfor
                    </div>
                    <p class="mt-3 text-xs text-slate-500 text-center">Máximo 5 imagens (JPG, PNG, WEBP - até 2MB cada)</p>
                </div>

            </form>
        </div>
    </div>

    <style>
        .tab-content { display: block; }
        .tab-content.hidden { display: none; }
        .tab-button.active { color: rgb(37 99 235); border-color: rgb(37 99 235); }
    </style>

    <script>
        function switchTab(tabName) {
            // Esconder todos os conteúdos
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remover active de todos os botões
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active', 'border-blue-600', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-slate-500');
            });
            
            // Mostrar conteúdo selecionado
            const content = document.getElementById('content-' + tabName);
            if (content) {
                content.classList.remove('hidden');
            }
            
            // Ativar botão selecionado
            const activeBtn = document.getElementById('tab-' + tabName);
            if (activeBtn) {
                activeBtn.classList.add('active', 'border-blue-600', 'text-blue-600');
                activeBtn.classList.remove('border-transparent', 'text-slate-500');
            }
        }

        // Atualizar preview do código quando tipo mudar
        async function updateCodePreview() {
            const typeSelect = document.getElementById('product_type_id');
            const codePreview = document.getElementById('code_preview');
            const selectedOption = typeSelect.options[typeSelect.selectedIndex];
            
            if (selectedOption.value && selectedOption.dataset.prefix) {
                const prefix = selectedOption.dataset.prefix;
                codePreview.value = 'Carregando...';
                codePreview.classList.add('text-slate-400');
                
                try {
                    // Buscar próximo código via AJAX
                    const response = await fetch(`/api/products/next-code/${selectedOption.value}`, {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        codePreview.value = data.code;
                        codePreview.classList.remove('text-slate-400');
                        codePreview.classList.add('text-blue-600', 'font-bold');
                    } else {
                        // Fallback: calcular localmente
                        codePreview.value = prefix + '001';
                        codePreview.classList.remove('text-slate-400');
                        codePreview.classList.add('text-blue-600', 'font-bold');
                    }
                } catch (error) {
                    // Fallback: calcular localmente
                    codePreview.value = prefix + '001';
                    codePreview.classList.remove('text-slate-400');
                    codePreview.classList.add('text-blue-600', 'font-bold');
                }
            } else {
                codePreview.value = 'Selecione o tipo';
                codePreview.classList.remove('text-blue-600', 'font-bold', 'text-slate-400');
                codePreview.classList.add('text-slate-500');
            }
        }

        // Carregar subgrupos quando grupo mudar
        document.getElementById('group_id')?.addEventListener('change', function() {
            const groupId = this.value;
            const subgroupSelect = document.getElementById('subgroup_id');
            subgroupSelect.innerHTML = '<option value="">Selecione o Grupo</option>';
            
            if (groupId) {
                fetch(`/api/subgroups/${groupId}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(subgroup => {
                            const option = document.createElement('option');
                            option.value = subgroup.id;
                            option.textContent = subgroup.name;
                            subgroupSelect.appendChild(option);
                        });
                    })
                    .catch(() => {});
            }
        });

        // Controlar estoque
        document.getElementById('control_stock')?.addEventListener('change', function() {
            const stockInputs = document.querySelectorAll('.stock-input');
            stockInputs.forEach(input => {
                input.disabled = !this.checked;
                if (!this.checked) {
                    input.value = 0;
                }
            });
        });

        function previewImage(input, index) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const container = input.closest('div');
                    const preview = document.getElementById('preview_' + index);
                    const removeBtn = document.getElementById('remove_' + index);
                    const label = container.querySelector('label');
                    
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    removeBtn.classList.remove('hidden');
                    label.classList.add('hidden');
                    container.classList.remove('border-dashed', 'bg-slate-50');
                    container.classList.add('border-solid', 'border-slate-400', 'bg-white');
                };
                reader.readAsDataURL(file);
            }
        }

        function removeImage(index) {
            const input = document.getElementById('image_' + index);
            const container = input.closest('div');
            const preview = document.getElementById('preview_' + index);
            const removeBtn = document.getElementById('remove_' + index);
            const label = container.querySelector('label');
            
            input.value = '';
            preview.classList.add('hidden');
            removeBtn.classList.add('hidden');
            label.classList.remove('hidden');
            container.classList.remove('border-solid', 'border-slate-400', 'bg-white');
            container.classList.add('border-dashed', 'bg-slate-50');
        }

        // Formatação de preços brasileiros
        function formatPrice(input) {
            let value = input.value.replace(/\D/g, '');
            if (value === '') value = '0';
            value = (parseInt(value) / 100).toFixed(2);
            value = value.replace('.', ',');
            value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            input.value = value;
        }

        function formatPercent(input) {
            let value = input.value.replace(/\D/g, '');
            if (value === '') value = '0';
            value = (parseInt(value) / 100).toFixed(2);
            value = value.replace('.', ',');
            input.value = value;
        }

        // Converter formato brasileiro para número
        function parseBrazilianNumber(value) {
            if (!value) return 0;
            return parseFloat(value.replace(/\./g, '').replace(',', '.')) || 0;
        }

        // Converter número para formato brasileiro
        function formatBrazilianNumber(num, decimals = 2) {
            return num.toFixed(decimals).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        // Filtrar lojas por seleção do administrador
        function filterStore(storeId) {
            const storeCards = document.querySelectorAll('.store-card');
            storeCards.forEach(card => {
                if (storeId === 'all') {
                    card.style.display = 'block';
                } else {
                    const cardStoreId = card.getAttribute('data-store-id');
                    card.style.display = cardStoreId === storeId ? 'block' : 'none';
                }
            });
        }

        // Calcular Markup e Margem de Lucro
        function calculateMarkupAndMargin(storeId) {
            const costInput = document.querySelector(`input[name="prices[${storeId}][cost]"]`);
            const priceInput = document.querySelector(`input[name="prices[${storeId}][price]"]`);
            const markupInput = document.getElementById(`markup_${storeId}`);
            
            const cost = parseBrazilianNumber(costInput.value);
            const price = parseBrazilianNumber(priceInput.value);
            
            if (cost > 0 && price > 0) {
                // Calcular Markup: ((Preço - Custo) / Custo) * 100
                const markup = ((price - cost) / cost) * 100;
                markupInput.value = formatBrazilianNumber(markup, 2);
            } else {
                markupInput.value = '0,00';
            }
        }
        
        // Calcular Preço a partir da Margem de Lucro
        function calculatePriceFromMargin(storeId) {
            const costInput = document.querySelector(`input[name="prices[${storeId}][cost]"]`);
            const marginInput = document.querySelector(`input[name="prices[${storeId}][margin_percent]"]`);
            const priceInput = document.querySelector(`input[name="prices[${storeId}][price]"]`);
            const markupInput = document.getElementById(`markup_${storeId}`);
            
            const cost = parseBrazilianNumber(costInput.value);
            const margin = parseBrazilianNumber(marginInput.value);
            
            if (cost > 0 && margin > 0) {
                // Calcular preço: Custo * (1 + (Margem / 100))
                const calculatedPrice = cost * (1 + (margin / 100));
                priceInput.value = formatBrazilianNumber(calculatedPrice, 2);
                
                // Atualizar markup
                const markup = ((calculatedPrice - cost) / cost) * 100;
                markupInput.value = formatBrazilianNumber(markup, 2);
            }
        }

        // Toggle campos de serviço
        function toggleServiceFields() {
            const itemType = document.getElementById('item_type').value;
            const isService = itemType === 'SERVICO';
            
            // Mostrar/esconder campos de preço e estoque
            document.querySelectorAll('.price-field, .stock-field').forEach(field => {
                if (isService) {
                    field.style.display = 'none';
                } else {
                    field.style.display = 'block';
                }
            });
            
            // Remover required dos campos de preço quando for serviço
            document.querySelectorAll('.cost-input').forEach(input => {
                if (isService) {
                    input.removeAttribute('required');
                    input.closest('.price-field')?.querySelector('.cost-required')?.classList.add('hidden');
                } else {
                    input.setAttribute('required', 'required');
                    input.closest('.price-field')?.querySelector('.cost-required')?.classList.remove('hidden');
                }
            });
            
            // Tornar descrição e unidade opcionais quando for serviço
            const descriptionField = document.getElementById('description');
            const unitField = document.getElementById('unit');
            const descriptionRequired = document.querySelector('.description-required');
            const unitRequired = document.querySelector('.unit-required');
            
            if (isService) {
                if (descriptionField) descriptionField.removeAttribute('required');
                if (unitField) unitField.removeAttribute('required');
                if (descriptionRequired) descriptionRequired.classList.add('hidden');
                if (unitRequired) unitRequired.classList.add('hidden');
            } else {
                if (descriptionField) descriptionField.setAttribute('required', 'required');
                if (unitField) unitField.setAttribute('required', 'required');
                if (descriptionRequired) descriptionRequired.classList.remove('hidden');
                if (unitRequired) unitRequired.classList.remove('hidden');
            }
            
            // Mostrar/esconder notas de serviço
            document.querySelectorAll('.service-note').forEach(note => {
                if (isService) {
                    note.classList.remove('hidden');
                } else {
                    note.classList.add('hidden');
                }
            });
            
            document.querySelectorAll('.stock-note').forEach(note => {
                if (isService) {
                    note.classList.add('hidden');
                } else {
                    note.classList.remove('hidden');
                }
            });
        }

        // Preparar formulário antes de enviar
        function prepareFormForSubmit(event) {
            const itemType = document.getElementById('item_type').value;
            const isService = itemType === 'SERVICO';
            
            if (isService) {
                // Desabilitar campos de preço e estoque para não serem enviados
                document.querySelectorAll('.price-field input, .stock-field input, .price-field select, .stock-field select').forEach(input => {
                    if (input.name && input.name.includes('prices')) {
                        input.disabled = true;
                    }
                });
            }
            
            // Não prevenir o submit - permitir que o formulário seja enviado normalmente
            // Se houver erro de validação, o Laravel retornará com os erros
        }

        // Formatar preços ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.price-mask').forEach(input => {
                if (input.value === '0' || input.value === '0.00' || input.value === '') {
                    input.value = '0,00';
                } else if (input.value.includes('.')) {
                    formatPrice(input);
                }
            });
            
            // Aplicar toggle inicial
            toggleServiceFields();
        });
        
        // Calcular quando custo ou preço mudarem
        document.querySelectorAll('.cost-input, .price-input').forEach(input => {
            input.addEventListener('blur', function() {
                const name = this.name;
                const match = name.match(/\[(\d+)\]/);
                if (match) {
                    const storeId = match[1];
                    calculateMarkupAndMargin(storeId);
                }
            });
        });
    </script>

    <!-- Modais para Cadastro Rápido -->
    <div id="quickModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; display: none !important; align-items: center; justify-content: center;">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto" style="margin: auto;">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 id="modalTitle" class="text-lg font-bold text-slate-900">Cadastrar</h3>
                    <button type="button" onclick="closeQuickModal()" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="quickForm" onsubmit="saveQuick(event)">
                    <div id="modalContent" class="space-y-4">
                        <!-- Conteúdo será inserido via JavaScript -->
                    </div>
                    
                    <div class="mt-6 flex gap-3 justify-end">
                        <button type="button" onclick="closeQuickModal()" class="px-4 py-2 text-sm bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentModalType = null;

        function openQuickModal(type) {
            currentModalType = type;
            const modal = document.getElementById('quickModal');
            const title = document.getElementById('modalTitle');
            const content = document.getElementById('modalContent');
            const form = document.getElementById('quickForm');
            
            form.reset();
            
            switch(type) {
                case 'brand':
                    title.textContent = '➕ Nova Marca';
                    content.innerHTML = `
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nome da Marca *</label>
                            <input type="text" name="name" required class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ex: Ray-Ban">
                        </div>
                    `;
                    break;
                    
                case 'category':
                    title.textContent = '➕ Nova Categoria';
                    content.innerHTML = `
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nome da Categoria *</label>
                            <input type="text" name="name" required class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ex: Óculos de Sol">
                        </div>
                    `;
                    break;
                    
                case 'subgroup':
                    title.textContent = '➕ Novo Subgrupo';
                    content.innerHTML = `
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Categoria *</label>
                            <select name="group_id" id="modal_group_id" required class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Selecione...</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nome do Subgrupo *</label>
                            <input type="text" name="name" required class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ex: Aviador">
                        </div>
                    `;
                    break;
                    
                case 'supplier':
                    title.textContent = '➕ Novo Fornecedor';
                    content.innerHTML = `
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Tipo *</label>
                            <select name="tax_id_type" id="modal_tax_id_type" required class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="toggleTaxIdFields()">
                                <option value="">Selecione...</option>
                                <option value="CPF">CPF</option>
                                <option value="CNPJ">CNPJ</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nome Fantasia *</label>
                            <input type="text" name="trade_name" required class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ex: Fornecedor XYZ">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Razão Social</label>
                            <input type="text" name="legal_name" class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Opcional">
                        </div>
                        <div id="cpf_field" class="hidden">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">CPF *</label>
                            <input type="text" name="cpf" class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="000.000.000-00">
                        </div>
                        <div id="cnpj_field" class="hidden">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">CNPJ *</label>
                            <input type="text" name="cnpj" class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="00.000.000/0000-00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">E-mail</label>
                            <input type="email" name="email" class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="opcional@email.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Telefone</label>
                            <input type="text" name="phone" class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="(00) 00000-0000">
                        </div>
                    `;
                    break;
                    
                case 'color':
                    title.textContent = '➕ Nova Cor';
                    content.innerHTML = `
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nome da Cor *</label>
                            <input type="text" name="name" required class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ex: Preto, Azul">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Código Hex (Opcional)</label>
                            <input type="text" name="hex_code" maxlength="7" class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="#000000">
                            <p class="mt-1 text-xs text-slate-500">Formato: #RRGGBB</p>
                        </div>
                    `;
                    break;
                    
                case 'size':
                    title.textContent = '➕ Novo Tamanho';
                    content.innerHTML = `
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nome do Tamanho *</label>
                            <input type="text" name="name" required class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ex: P, M, G, GG">
                        </div>
                    `;
                    break;
                    
                case 'shape':
                    title.textContent = '➕ Novo Formato';
                    content.innerHTML = `
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nome do Formato *</label>
                            <input type="text" name="name" required class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ex: Redondo, Quadrado, Aviador">
                        </div>
                    `;
                    break;
                    
                case 'product-type':
                    title.textContent = '➕ Novo Tipo de Produto';
                    content.innerHTML = `
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nome do Tipo *</label>
                            <input type="text" name="name" required class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ex: Produto, Lente">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Prefixo do Código *</label>
                            <input type="text" name="code_prefix" maxlength="10" required class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ex: P, L">
                            <p class="mt-1 text-xs text-slate-500">Usado para gerar códigos (P para Produto, L para Lente)</p>
                        </div>
                    `;
                    break;
            }
            
            modal.classList.remove('hidden');
            // Forçar centralização
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
        }

        function closeQuickModal() {
            const modal = document.getElementById('quickModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
            currentModalType = null;
        }

        function toggleTaxIdFields() {
            const type = document.getElementById('modal_tax_id_type').value;
            const cpfField = document.getElementById('cpf_field');
            const cnpjField = document.getElementById('cnpj_field');
            
            if (type === 'CPF') {
                cpfField.classList.remove('hidden');
                cnpjField.classList.add('hidden');
                cpfField.querySelector('input').required = true;
                cnpjField.querySelector('input').required = false;
            } else if (type === 'CNPJ') {
                cpfField.classList.add('hidden');
                cnpjField.classList.remove('hidden');
                cpfField.querySelector('input').required = false;
                cnpjField.querySelector('input').required = true;
            } else {
                cpfField.classList.add('hidden');
                cnpjField.classList.add('hidden');
                cpfField.querySelector('input').required = false;
                cnpjField.querySelector('input').required = false;
            }
        }

        async function saveQuick(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            
            let url = '';
            let selectId = '';
            
            switch(currentModalType) {
                case 'brand':
                    url = '{{ route("cadastros.brands.storeAjax") }}';
                    selectId = 'brand_id';
                    break;
                case 'category':
                    url = '{{ route("cadastros.product-groups.storeAjax") }}';
                    selectId = 'group_id';
                    break;
                case 'subgroup':
                    url = '{{ route("cadastros.product-subgroups.storeAjax") }}';
                    selectId = 'subgroup_id';
                    break;
                case 'supplier':
                    url = '{{ route("cadastros.suppliers.storeAjax") }}';
                    selectId = 'supplier_id';
                    break;
                case 'color':
                    url = '{{ route("cadastros.product-colors.storeAjax") }}';
                    selectId = 'color';
                    break;
                case 'size':
                    url = '{{ route("cadastros.product-sizes.storeAjax") }}';
                    selectId = 'size';
                    break;
                case 'shape':
                    url = '{{ route("cadastros.product-shapes.storeAjax") }}';
                    selectId = 'shape';
                    break;
                case 'product-type':
                    url = '{{ route("cadastros.product-types.storeAjax") }}';
                    selectId = 'product_type_id';
                    break;
            }
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (!response.ok) {
                    // Se a resposta não foi OK, mostrar erro
                    const errorMsg = result.message || result.error || 'Erro ao salvar';
                    alert('❌ ' + errorMsg);
                    if (result.errors) {
                        console.error('Erros de validação:', result.errors);
                    }
                    return;
                }
                
                if (result.success) {
                    // Adicionar ao select
                    const select = document.getElementById(selectId);
                    if (select) {
                        const option = document.createElement('option');
                        if (currentModalType === 'product-type') {
                            option.value = result.data.id;
                            option.textContent = result.data.name;
                            option.dataset.prefix = result.data.code_prefix;
                        } else {
                            option.value = result.data.name;
                            option.textContent = result.data.name;
                        }
                        option.selected = true;
                        select.appendChild(option);
                        
                        // Se for tipo de produto, atualizar preview do código
                        if (currentModalType === 'product-type') {
                            updateCodePreview();
                        }
                        
                        // Se for cor, tamanho ou formato, também atualizar o campo custom se estiver visível
                        if (['color', 'size', 'shape'].includes(currentModalType)) {
                            const customInput = document.getElementById(selectId + '_custom');
                            if (customInput && customInput.style.display !== 'none') {
                                customInput.value = result.data.name;
                            }
                        }
                    }
                    
                    // Se for subgrupo, também atualizar o select de grupos se necessário
                    if (currentModalType === 'subgroup' && result.data.group_id) {
                        const groupSelect = document.getElementById('group_id');
                        if (groupSelect.value != result.data.group_id) {
                            groupSelect.value = result.data.group_id;
                            groupSelect.dispatchEvent(new Event('change'));
                        }
                        // Aguardar um pouco e então selecionar o subgrupo
                        setTimeout(() => {
                            const subgroupSelect = document.getElementById('subgroup_id');
                            subgroupSelect.value = result.data.id;
                        }, 300);
                    }
                    
                    closeQuickModal();
                    
                    // Mostrar mensagem de sucesso
                    alert('✅ ' + result.message);
                } else {
                    alert('❌ ' + (result.message || 'Erro ao salvar'));
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('❌ Erro ao salvar. Verifique o console para mais detalhes.');
            }
        }

        // Fechar modal ao clicar fora
        document.getElementById('quickModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeQuickModal();
            }
        });

        // Funções para alternar entre select e input custom
        function toggleColorInput() {
            const select = document.getElementById('color');
            const custom = document.getElementById('color_custom');
            if (select.style.display === 'none') {
                select.style.display = 'block';
                custom.style.display = 'none';
                custom.name = 'color_custom';
                select.name = 'color';
            } else {
                select.style.display = 'none';
                custom.style.display = 'block';
                select.name = 'color_select';
                custom.name = 'color';
            }
        }

        function toggleSizeInput() {
            const select = document.getElementById('size');
            const custom = document.getElementById('size_custom');
            if (select.style.display === 'none') {
                select.style.display = 'block';
                custom.style.display = 'none';
                custom.name = 'size_custom';
                select.name = 'size';
            } else {
                select.style.display = 'none';
                custom.style.display = 'block';
                select.name = 'size_select';
                custom.name = 'size';
            }
        }

        function toggleShapeInput() {
            const select = document.getElementById('shape');
            const custom = document.getElementById('shape_custom');
            if (select.style.display === 'none') {
                select.style.display = 'block';
                custom.style.display = 'none';
                custom.name = 'shape_custom';
                select.name = 'shape';
            } else {
                select.style.display = 'none';
                custom.style.display = 'block';
                select.name = 'shape_select';
                custom.name = 'shape';
            }
        }
    </script>
</x-app-layout>

