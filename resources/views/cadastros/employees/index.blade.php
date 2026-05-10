<x-app-layout title="Funcionários">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-900 mb-4 sm:mb-0">Funcionários</h1>
            <a href="{{ route('cadastros.employees.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Novo Funcionário
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
            <form method="GET" action="{{ route('cadastros.employees.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="q" class="block text-sm font-medium text-slate-700 mb-1">Buscar</label>
                    <input type="text" id="q" name="q" value="{{ request('q') }}" placeholder="Nome ou CPF" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="company_id" class="block text-sm font-medium text-slate-700 mb-1">Empresa</label>
                    <select id="company_id" name="company_id" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todas</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>{{ $company->trade_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="store_id" class="block text-sm font-medium text-slate-700 mb-1">Loja</label>
                    <select id="store_id" name="store_id" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todas</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>@if($store->abbreviation)[{{ $store->abbreviation }}]@endif {{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="show" class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                    <select id="show" name="show" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="all" {{ request('show', 'active') === 'all' ? 'selected' : '' }}>Todos</option>
                        <option value="active" {{ request('show', 'active') === 'active' ? 'selected' : '' }}>Ativos</option>
                        <option value="inactive" {{ request('show') === 'inactive' ? 'selected' : '' }}>Inativos</option>
                    </select>
                </div>
                <div class="md:col-span-4 flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700">Filtrar</button>
                    <a href="{{ route('cadastros.employees.index') }}" class="px-4 py-2 bg-slate-100 text-slate-700 font-medium rounded-lg hover:bg-slate-200">Limpar</a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                <p class="text-sm text-slate-600">
                    @if($employees->total() > 0)
                        Foram encontrados <strong>{{ $employees->total() }}</strong> funcionários. Mostrando página <strong>{{ $employees->currentPage() }}</strong> de <strong>{{ $employees->lastPage() }}</strong>.
                    @else
                        Nenhum funcionário encontrado.
                    @endif
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Nome</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">CPF</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Empresa</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Loja</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Função</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse($employees as $employee)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $employee->name }}</div>
                                    @if($employee->phone || $employee->mobile)
                                        <div class="text-xs text-slate-500 mt-1">
                                            @if($employee->phone) Tel: {{ $employee->phone }} @endif
                                            @if($employee->mobile) | Cel: {{ $employee->mobile }} @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    @if($employee->cpf_clean)
                                        {{ preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $employee->cpf_clean) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $employee->company->trade_name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $employee->store->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $employee->roleFunction->name ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $employee->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $employee->is_active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('cadastros.employees.edit', $employee) }}" class="text-blue-600 hover:text-blue-800 text-sm">Editar</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500">Nenhum funcionário encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($employees->hasPages())
                <div class="px-4 py-3 bg-slate-50 border-t border-slate-200">{{ $employees->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>

