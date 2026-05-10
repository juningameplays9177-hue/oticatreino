<x-app-layout title="Estoque">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-slate-900">Controle de Estoque</h1>
            </div>

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-red-800">{{ session('error') }}</p>
                </div>
            @endif

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <p class="text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            <!-- Filtros -->
            <form method="GET" action="{{ route('stock.index') }}" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @if(auth()->user()->isAdmin() && isset($selectedStoreId) && $selectedStoreId && $storeId)
                        <!-- Admin: loja vem da sessão do dashboard, campo hidden -->
                        <input type="hidden" name="store_id" value="{{ $storeId }}">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Loja</label>
                            <div class="w-full text-sm px-4 py-2 rounded-lg border-2 border-blue-400 bg-blue-50 font-semibold">
                                @php
                                    $selectedStore = $stores->firstWhere('id', $storeId);
                                @endphp
                                @if($selectedStore)
                                    @if($selectedStore->abbreviation)[{{ $selectedStore->abbreviation }}]@endif {{ $selectedStore->name }}
                                @else
                                    <span class="text-red-600 font-bold">⚠️ Nenhuma loja selecionada no dashboard</span>
                                @endif
                            </div>
                            <p class="mt-2 text-xs text-slate-600">
                                💡 Para alterar a loja, <a href="{{ route('dashboard') }}" class="text-blue-600 underline font-bold">selecione no dashboard</a>
                            </p>
                        </div>
                    @else
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Loja</label>
                            <select name="store_id" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Todas as lojas</option>
                                @foreach($stores ?? [] as $store)
                                    <option value="{{ $store->id }}" {{ $storeId == $store->id ? 'selected' : '' }}>
                                        @if($store->abbreviation)[{{ $store->abbreviation }}]@endif {{ $store->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Buscar Produto</label>
                        <input 
                            type="text" 
                            name="q" 
                            value="{{ $search }}"
                            placeholder="Nome, código ou referência"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Filtrar
                        </button>
                    </div>
                </div>
            </form>

            @if(!$storeId)
                <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-yellow-800">Selecione uma loja para visualizar o estoque.</p>
                </div>
            @endif

            <!-- Tabela de Produtos -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Produto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Referência</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Estoque Atual</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse($products as $product)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-slate-900">{{ $product->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    {{ $product->ref ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($storeId)
                                        <span class="text-lg font-semibold {{ ($product->stock->qty ?? 0) > 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ number_format($product->stock->qty ?? 0, 2, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($storeId)
                                        <button 
                                            onclick="openStockModal({{ $product->id }}, '{{ $product->name }}', {{ $product->stock->qty ?? 0 }}, {{ $storeId }})"
                                            class="text-blue-600 hover:text-blue-800 font-medium"
                                        >
                                            Ajustar
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-slate-500">
                                    Nenhum produto encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div class="mt-4">
                {{ $products->links() }}
            </div>
        </div>
    </div>

    <!-- Modal de Ajuste de Estoque -->
    <div id="stock-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Ajustar Estoque</h2>
                    <button onclick="closeStockModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
                </div>
                <form id="stock-form" onsubmit="saveStock(event)">
                    <input type="hidden" id="stock-product-id">
                    <input type="hidden" id="stock-store-id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Produto</label>
                        <input type="text" id="stock-product-name" readonly class="w-full rounded-lg border-gray-300 bg-gray-50">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estoque Atual</label>
                        <input type="text" id="stock-current-qty" readonly class="w-full rounded-lg border-gray-300 bg-gray-50">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Ajuste</label>
                        <select id="stock-type" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="in">Entrada</option>
                            <option value="out">Saída</option>
                            <option value="set">Definir Quantidade</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantidade</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            min="0" 
                            id="stock-qty" 
                            required
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea 
                            id="stock-notes" 
                            rows="3"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                    </div>
                    
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeStockModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
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
        function openStockModal(productId, productName, currentQty, storeId) {
            document.getElementById('stock-product-id').value = productId;
            document.getElementById('stock-product-name').value = productName;
            document.getElementById('stock-current-qty').value = currentQty.toFixed(2);
            document.getElementById('stock-store-id').value = storeId;
            document.getElementById('stock-qty').value = '';
            document.getElementById('stock-notes').value = '';
            document.getElementById('stock-modal').classList.remove('hidden');
        }

        function closeStockModal() {
            document.getElementById('stock-modal').classList.add('hidden');
            document.getElementById('stock-form').reset();
        }

        async function saveStock(event) {
            event.preventDefault();
            
            const productId = document.getElementById('stock-product-id').value;
            const storeId = document.getElementById('stock-store-id').value;
            const type = document.getElementById('stock-type').value;
            const qty = parseFloat(document.getElementById('stock-qty').value);
            const notes = document.getElementById('stock-notes').value;
            
            if (type === 'set') {
                // Definir quantidade diretamente
                const formData = new FormData();
                formData.append('_method', 'PUT');
                formData.append('store_id', storeId);
                formData.append('qty', qty);
                
                const response = await fetch(`/stock/products/${productId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    alert('Estoque atualizado com sucesso!');
                    location.reload();
                } else {
                    alert(data.error || 'Erro ao atualizar estoque');
                }
            } else {
                // Ajuste (entrada/saída)
                const response = await fetch(`/stock/products/${productId}/adjust`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        store_id: storeId,
                        type: type,
                        qty: qty,
                        notes: notes
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    alert('Estoque ajustado com sucesso!');
                    location.reload();
                } else {
                    alert(data.error || 'Erro ao ajustar estoque');
                }
            }
        }
    </script>
</x-app-layout>

