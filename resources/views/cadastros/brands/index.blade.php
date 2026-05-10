<x-app-layout title="Marcas">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-900 mb-4 sm:mb-0">Marcas</h1>
            <a href="{{ route('cadastros.brands.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nova Marca
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">{{ session('error') }}</div>
        @endif

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <!-- Busca -->
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                <form method="GET" action="{{ route('cadastros.brands.index') }}" class="flex gap-2">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar marca..." 
                           class="flex-1 px-3 py-2 text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                        Buscar
                    </button>
                    @if(request('q'))
                        <a href="{{ route('cadastros.brands.index') }}" class="px-4 py-2 bg-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-300">
                            Limpar
                        </a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Nome</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse(isset($brands) ? $brands : [] as $brand)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm text-slate-900">{{ $brand->name }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex gap-2">
                                        <a href="{{ route('cadastros.brands.edit', $brand) }}" 
                                           class="text-blue-600 hover:text-blue-800 font-medium">Editar</a>
                                        <form method="POST" action="{{ route('cadastros.brands.destroy', $brand) }}" 
                                              onsubmit="return confirm('Tem certeza que deseja excluir esta marca?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-8 text-center text-slate-500">
                                    Nenhuma marca cadastrada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($brands) && $brands->hasPages())
                <div class="px-4 py-3 bg-slate-50 border-t border-slate-200">
                    {{ $brands->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

