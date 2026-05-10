<x-app-layout title="Clientes">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header com ações -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-900 mb-4 sm:mb-0">Clientes</h1>
            <div class="flex flex-col sm:flex-row gap-2">
                <a href="{{ route('clients.create') }}" 
                   class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Novo Cliente
                </a>
                <a href="{{ route('clients.import.show') }}" 
                   class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    Importar .xlsx
                </a>
            </div>
        </div>

        <!-- Mensagens -->
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <!-- Filtros -->
        <form method="GET" action="{{ route('clients.index') }}" class="mb-6 bg-white rounded-lg shadow-sm border border-slate-200 p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Busca -->
                <div class="lg:col-span-2">
                    <label for="q" class="block text-sm font-medium text-slate-700 mb-1">Buscar</label>
                    <input type="text" 
                           id="q" 
                           name="q" 
                           value="{{ request('q') }}"
                           placeholder="Código, nome, CPF/CNPJ, e-mail ou telefone"
                           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Status -->
                <div>
                    <label for="active" class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                    <select id="active" name="active" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="1" {{ request('active') == '1' ? 'selected' : '' }}>Ativo</option>
                        <option value="0" {{ request('active') == '0' ? 'selected' : '' }}>Inativo</option>
                    </select>
                </div>


                <!-- Cidade -->
                <div>
                    <label for="city" class="block text-sm font-medium text-slate-700 mb-1">Cidade</label>
                    <input type="text" 
                           id="city" 
                           name="city" 
                           value="{{ request('city') }}"
                           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Bairro -->
                <div>
                    <label for="district" class="block text-sm font-medium text-slate-700 mb-1">Bairro</label>
                    <input type="text" 
                           id="district" 
                           name="district" 
                           value="{{ request('district') }}"
                           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Período -->
                <div>
                    <label for="period" class="block text-sm font-medium text-slate-700 mb-1">Período</label>
                    <select id="period" name="period" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Tudo</option>
                        <option value="today" {{ request('period') == 'today' ? 'selected' : '' }}>Hoje</option>
                        <option value="yesterday" {{ request('period') == 'yesterday' ? 'selected' : '' }}>Ontem</option>
                        <option value="this_week" {{ request('period') == 'this_week' ? 'selected' : '' }}>Esta Semana</option>
                        <option value="last_7" {{ request('period') == 'last_7' ? 'selected' : '' }}>Últimos 7 dias</option>
                        <option value="last_30" {{ request('period') == 'last_30' ? 'selected' : '' }}>Últimos 30 dias</option>
                        <option value="this_month" {{ request('period') == 'this_month' ? 'selected' : '' }}>Este Mês</option>
                        <option value="last_month" {{ request('period') == 'last_month' ? 'selected' : '' }}>Mês Anterior</option>
                        <option value="this_year" {{ request('period') == 'this_year' ? 'selected' : '' }}>Este Ano</option>
                    </select>
                </div>

                <!-- Data De -->
                <div>
                    <label for="from" class="block text-sm font-medium text-slate-700 mb-1">Data De</label>
                    <input type="date" 
                           id="from" 
                           name="from" 
                           value="{{ request('from') }}"
                           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Data Até -->
                <div>
                    <label for="to" class="block text-sm font-medium text-slate-700 mb-1">Data Até</label>
                    <input type="date" 
                           id="to" 
                           name="to" 
                           value="{{ request('to') }}"
                           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Filtrar
                </button>
                <a href="{{ route('clients.index') }}" 
                   class="ml-2 px-4 py-2 bg-slate-200 text-slate-700 font-medium rounded-lg hover:bg-slate-300 transition-colors">
                    Limpar
                </a>
            </div>
        </form>

        <!-- Resultados -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                <p class="text-sm text-slate-600">
                    Foram encontrados <strong>{{ $clients->total() }}</strong> cliente(s). 
                    Mostrando página <strong>{{ $clients->currentPage() }}</strong> de <strong>{{ $clients->lastPage() }}</strong>.
                </p>
            </div>

            <!-- Tabela Desktop -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Código</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Nome</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">CPF/CNPJ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Cidade/Bairro</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Cadastrado em</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse($clients as $client)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-semibold text-blue-600">{{ $client->code ?? '-' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('clients.show', $client) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                            {{ $client->name }}
                                        </a>
                                        <a href="{{ route('clients.show', $client) }}" class="text-gray-400 hover:text-gray-600" title="Ver detalhes">
                                            👁️
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    {{ $client->cpf_cnpj ? $client->cpf_cnpj : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    {{ ($client->city ?? '') . ($client->district ? ' / ' . $client->district : '') ?: '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $client->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $client->active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    {{ $client->created_at->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('clients.show', $client) }}" class="text-blue-600 hover:text-blue-900 mr-3">👁️ Ver</a>
                                    <a href="{{ route('clients.edit', $client) }}" class="text-blue-600 hover:text-blue-900 mr-3">Editar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-slate-500">
                                    Nenhum cliente encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Cards Mobile -->
            <div class="md:hidden divide-y divide-slate-200">
                @forelse($clients as $client)
                    <div class="p-4">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    @if($client->code)
                                        <span class="text-xs font-semibold text-blue-600">{{ $client->code }}</span>
                                    @endif
                                    <a href="{{ route('clients.show', $client) }}" class="text-blue-600 hover:text-blue-800 font-semibold">
                                        {{ $client->name }}
                                    </a>
                                    <a href="{{ route('clients.show', $client) }}" class="text-gray-400 hover:text-gray-600 text-sm" title="Ver detalhes">
                                        👁️
                                    </a>
                                </div>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $client->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $client->active ? 'Ativo' : 'Inativo' }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-sm text-slate-600 mt-2">
                            <div>
                                <span class="font-medium">CPF/CNPJ:</span> {{ $client->cpf_cnpj ?? '-' }}
                            </div>
                            @if($client->city)
                                <div class="col-span-2">
                                    <span class="font-medium">Cidade/Bairro:</span> {{ $client->city }}{{ $client->district ? ' / ' . $client->district : '' }}
                                </div>
                            @endif
                            <div class="col-span-2">
                                <span class="font-medium">Cadastrado em:</span> {{ $client->created_at->format('d/m/Y') }}
                            </div>
                        </div>
                        <div class="mt-3 flex gap-3">
                            <a href="{{ route('clients.show', $client) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">👁️ Ver Detalhes</a>
                            <a href="{{ route('clients.edit', $client) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Editar →</a>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-slate-500">
                        Nenhum cliente encontrado.
                    </div>
                @endforelse
            </div>

            <!-- Paginação -->
            @if($clients->hasPages())
                <div class="px-4 py-3 bg-slate-50 border-t border-slate-200">
                    {{ $clients->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
