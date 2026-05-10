<x-app-layout title="Importar Produtos">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h1 class="text-2xl font-bold text-slate-900 mb-6">Importar Produtos</h1>

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <pre class="text-green-800 whitespace-pre-wrap text-sm">{{ session('success') }}</pre>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h3 class="font-semibold text-blue-900 mb-2">Instruções:</h3>
                <ul class="list-disc list-inside text-blue-800 text-sm space-y-1">
                    <li>O arquivo deve estar no formato <strong>.xlsx</strong></li>
                    <li>Máximo de <strong>1.000 linhas</strong> (sem contar o cabeçalho)</li>
                    <li>Tamanho máximo: <strong>5MB</strong></li>
                    <li>O arquivo será processado com upsert por <strong>REF</strong> ou <strong>EAN-13</strong></li>
                    <li>Grupos, subgrupos, grifes e fornecedores serão criados automaticamente se não existirem</li>
                </ul>
            </div>

            <div class="mb-6 p-4 bg-slate-50 border border-slate-200 rounded-lg">
                <h3 class="font-semibold text-slate-900 mb-2">Cabeçalhos Esperados:</h3>
                <p class="text-sm text-slate-700 mb-2">O arquivo deve conter as seguintes colunas (case-insensitive):</p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-xs text-slate-600 font-mono">
                    <div>name</div>
                    <div>ref</div>
                    <div>ean13</div>
                    <div>unit</div>
                    <div>group</div>
                    <div>subgroup</div>
                    <div>brand</div>
                    <div>supplier</div>
                    <div>color</div>
                    <div>size</div>
                    <div>shape</div>
                    <div>sell_only_with_os</div>
                    <div>control_stock</div>
                    <div>showcase_enabled</div>
                    <div>archived</div>
                    <div>description</div>
                    <div>notes</div>
                    <div>store:{code}:location</div>
                    <div>store:{code}:cost</div>
                    <div>store:{code}:margin_percent</div>
                    <div>store:{code}:price</div>
                    <div>store:{code}:qty</div>
                    <div>store:{code}:min_qty</div>
                </div>
                <p class="text-xs text-slate-600 mt-2">
                    <strong>Nota:</strong> Substitua {code} pelo código da loja (ex: store:kids:cost, store:tijuca:price)
                </p>
            </div>

            <form method="POST" action="{{ route('products.import.run') }}" enctype="multipart/form-data">
                @csrf

                <div class="mb-6">
                    <label for="file" class="block text-sm font-medium text-slate-700 mb-2">Selecionar Arquivo .xlsx</label>
                    <input type="file" 
                           id="file" 
                           name="file" 
                           accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                           required
                           class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    @error('file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('products.index') }}" class="px-4 py-2 bg-slate-200 text-slate-700 font-medium rounded-lg hover:bg-slate-300 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                        Importar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

