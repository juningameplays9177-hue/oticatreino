<x-app-layout title="Editar Categoria">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-xl font-bold text-slate-900">Editar Categoria</h1>
                <a href="{{ route('cadastros.product-groups.index') }}" class="px-3 py-1.5 text-sm bg-slate-100 text-slate-700 font-medium rounded-lg hover:bg-slate-200">
                    Cancelar
                </a>
            </div>

            @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="list-disc list-inside text-red-800 text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('cadastros.product-groups.update', $productGroup) }}">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nome da Categoria *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $productGroup->name) }}" required 
                           class="w-full px-3 py-2 text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="Ex: Óculos de Sol, Óculos de Grau">
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('cadastros.product-groups.index') }}" class="px-4 py-2 text-sm bg-slate-100 text-slate-700 font-medium rounded-lg hover:bg-slate-200">
                        Cancelar
                    </a>
                    <button type="submit" class="px-4 py-2 text-sm bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700">
                        Atualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

