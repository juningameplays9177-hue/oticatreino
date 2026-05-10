<x-app-layout title="Produtos">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header com ações -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-900 mb-4 sm:mb-0">Produtos</h1>
            <div class="flex flex-col sm:flex-row gap-2">
                <a href="{{ route('products.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Novo Produto
                </a>
                <a href="{{ route('products.import.show') }}" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                    Importar .xlsx
                </a>
            </div>
        </div>

        <!-- Mensagens -->
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">{{ session('error') }}</div>
        @endif

        <!-- Filtros -->
        <form method="GET" action="{{ route('products.index') }}" class="mb-6 bg-white rounded-lg shadow-sm border border-slate-200 p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Busca -->
                <div class="md:col-span-2">
                    <label for="q" class="block text-sm font-medium text-slate-700 mb-1">Busca</label>
                    <input type="text" id="q" name="q" value="{{ request('q') }}" placeholder="Descrição, referência, código de barras..." class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Grupo -->
                <div>
                    <label for="group_id" class="block text-sm font-medium text-slate-700 mb-1">Grupo</label>
                    <select id="group_id" name="group_id" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Grife -->
                <div>
                    <label for="brand_id" class="block text-sm font-medium text-slate-700 mb-1">Grife</label>
                    <select id="brand_id" name="brand_id" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todas</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Fornecedor -->
                <div>
                    <label for="supplier_id" class="block text-sm font-medium text-slate-700 mb-1">Fornecedor</label>
                    <select id="supplier_id" name="supplier_id" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->trade_name ?: $supplier->legal_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Mostrar somente -->
                <div>
                    <label for="archived_mode" class="block text-sm font-medium text-slate-700 mb-1">Mostrar somente</label>
                    <select id="archived_mode" name="archived_mode" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="nao_arquivados" {{ request('archived_mode', 'nao_arquivados') == 'nao_arquivados' ? 'selected' : '' }}>Não arquivados</option>
                        <option value="nao_arquivados_com_fotos" {{ request('archived_mode') == 'nao_arquivados_com_fotos' ? 'selected' : '' }}>Não arquivados com fotos</option>
                        <option value="vitrine" {{ request('archived_mode') == 'vitrine' ? 'selected' : '' }}>Disponíveis na Vitrine</option>
                        <option value="arquivados" {{ request('archived_mode') == 'arquivados' ? 'selected' : '' }}>Arquivados</option>
                        <option value="todos" {{ request('archived_mode') == 'todos' ? 'selected' : '' }}>Todos</option>
                    </select>
                </div>

                <!-- Período -->
                <div>
                    <label for="period" class="block text-sm font-medium text-slate-700 mb-1">Período</label>
                    <select id="period" name="period" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="hoje" {{ request('period') == 'hoje' ? 'selected' : '' }}>Hoje</option>
                        <option value="ontem" {{ request('period') == 'ontem' ? 'selected' : '' }}>Ontem</option>
                        <option value="esta_semana" {{ request('period') == 'esta_semana' ? 'selected' : '' }}>Esta Semana</option>
                        <option value="ultimos_7" {{ request('period') == 'ultimos_7' ? 'selected' : '' }}>Últimos 7 dias</option>
                        <option value="ultimos_30" {{ request('period') == 'ultimos_30' ? 'selected' : '' }}>Últimos 30 dias</option>
                        <option value="este_mes" {{ request('period') == 'este_mes' ? 'selected' : '' }}>Este Mês</option>
                        <option value="mes_anterior" {{ request('period') == 'mes_anterior' ? 'selected' : '' }}>Mês Anterior</option>
                        <option value="este_ano" {{ request('period') == 'este_ano' ? 'selected' : '' }}>Este Ano</option>
                    </select>
                </div>

                <!-- Ordenação -->
                <div>
                    <label for="sort" class="block text-sm font-medium text-slate-700 mb-1">Ordenar por</label>
                    <select id="sort" name="sort" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="created_desc" {{ request('sort', 'created_desc') == 'created_desc' ? 'selected' : '' }}>Data (Recente)</option>
                        <option value="created_asc" {{ request('sort') == 'created_asc' ? 'selected' : '' }}>Data (Antigo)</option>
                        <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Nome (A-Z)</option>
                        <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Nome (Z-A)</option>
                        <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Preço (Menor)</option>
                        <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Preço (Maior)</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">Filtrar</button>
            </div>
        </form>

        <!-- Resultados -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                <p class="text-sm text-slate-600">Foram encontrados <strong>{{ $products->total() }}</strong> produto(s). Mostrando página <strong>{{ $products->currentPage() }}</strong> de <strong>{{ $products->lastPage() }}</strong>.</p>
            </div>

            <!-- Tabela Desktop -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">REF/Descrição</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Marca</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Grupo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse($products as $product)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $product->ref ?? 'N/A' }}</div>
                                    <div class="text-sm text-slate-600">{{ $product->name }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $product->brand?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $product->group?->name ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @if(!$product->control_stock)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Não controla estoque</span>
                                        @endif
                                        @if($product->showcase_enabled)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Vitrine</span>
                                        @endif
                                        @if($product->archived)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Arquivado</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('products.edit', $product) }}" class="text-blue-600 hover:text-blue-800 font-medium">Editar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">Nenhum produto encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Cards Mobile -->
            <div class="md:hidden divide-y divide-slate-200">
                @forelse($products as $product)
                    <div class="p-4">
                        <div class="font-medium text-slate-900 mb-1">{{ $product->ref ?? 'N/A' }} - {{ $product->name }}</div>
                        <div class="text-sm text-slate-600 mb-2">
                            @if($product->brand) {{ $product->brand->name }} @endif
                            @if($product->group) • {{ $product->group->name }} @endif
                        </div>
                        <div class="flex flex-wrap gap-1 mb-2">
                            @if(!$product->control_stock)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Não controla estoque</span>
                            @endif
                            @if($product->showcase_enabled)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Vitrine</span>
                            @endif
                            @if($product->archived)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Arquivado</span>
                            @endif
                        </div>
                        <a href="{{ route('products.edit', $product) }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm">Editar</a>
                    </div>
                @empty
                    <div class="p-8 text-center text-slate-500">Nenhum produto encontrado.</div>
                @endforelse
            </div>

            <!-- Paginação -->
            @if($products->hasPages())
                <div class="px-4 py-3 bg-slate-50 border-t border-slate-200">{{ $products->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>

