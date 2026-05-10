<x-app-layout title="Fornecedores">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-900 mb-4 sm:mb-0">Fornecedores</h1>
            <a href="{{ route('cadastros.suppliers.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Novo Fornecedor
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">{{ session('error') }}</div>
        @endif

        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 mb-6">
            <form method="GET" action="{{ route('cadastros.suppliers.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="q" class="block text-sm font-medium text-slate-700 mb-1">Buscar</label>
                    <input type="text" id="q" name="q" value="{{ request('q') }}" placeholder="Nome, CNPJ/CPF ou E-mail" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="is_active" class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                    <select id="is_active" name="is_active" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Ativos</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inativos</option>
                    </select>
                </div>
                <div class="md:col-span-3 flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700">Filtrar</button>
                    <a href="{{ route('cadastros.suppliers.index') }}" class="px-4 py-2 bg-slate-100 text-slate-700 font-medium rounded-lg hover:bg-slate-200">Limpar</a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                <p class="text-sm text-slate-600">
                    @if($suppliers->total() > 0)
                        Foram encontrados <strong>{{ $suppliers->total() }}</strong> fornecedores. Mostrando página <strong>{{ $suppliers->currentPage() }}</strong> de <strong>{{ $suppliers->lastPage() }}</strong>.
                    @else
                        Nenhum fornecedor encontrado.
                    @endif
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Nome Fantasia</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Razão Social</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">CNPJ/CPF</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Tipo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">E-mail</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse($suppliers as $supplier)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $supplier->trade_name }}</div>
                                    @if($supplier->is_lab)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 mt-1">Laboratório</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $supplier->legal_name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    @if($supplier->cnpj_clean)
                                        {{ preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $supplier->cnpj_clean) }}
                                    @elseif($supplier->cpf_clean)
                                        {{ preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $supplier->cpf_clean) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $supplier->tax_id_type }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $supplier->email ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $supplier->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $supplier->is_active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('cadastros.suppliers.edit', $supplier) }}" class="text-blue-600 hover:text-blue-800 text-sm">Editar</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500">Nenhum fornecedor encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($suppliers->hasPages())
                <div class="px-4 py-3 bg-slate-50 border-t border-slate-200">{{ $suppliers->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>

