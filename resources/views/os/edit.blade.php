<x-app-layout title="Editar O.S. {{ $o->os_number }}">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 md:p-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-xl md:text-2xl font-bold text-slate-900">Editar O.S. {{ $o->os_number }}</h1>
                <div class="flex gap-2">
                    <a href="{{ route('os.index') }}" class="px-3 py-1.5 text-sm bg-slate-100 text-slate-700 font-medium rounded-lg hover:bg-slate-200">Cancelar</a>
                    <button type="submit" form="osForm" class="px-3 py-1.5 text-sm bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700">Atualizar</button>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">{{ session('success') }}</div>
            @endif
            @if(session('warning'))
                <div class="mb-6 p-8 bg-gradient-to-r from-yellow-100 to-orange-100 border-4 border-yellow-500 rounded-xl shadow-lg">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl flex-shrink-0">⚠️</div>
                        <div class="flex-1">
                            <div class="text-2xl font-black text-yellow-900 mb-2" style="font-size: 1.75rem; letter-spacing: 0.05em;">ATENÇÃO!</div>
                            <div class="text-xl font-bold text-yellow-900 leading-relaxed" style="font-size: 1.25rem;">{{ session('warning') }}</div>
                        </div>
                    </div>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="list-disc list-inside text-red-800 text-xs md:text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('os.update', $o) }}" enctype="multipart/form-data" id="osForm">
                @csrf
                @method('PUT')

                <!-- Status e ações rápidas -->
                <div class="mb-4 p-3 bg-slate-50 rounded-lg flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <span class="text-sm font-medium text-slate-700">Status:</span>
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ $o->status }}</span>
                    </div>
                    <div class="flex gap-2">
                        @if($o->status === 'REGISTRADA')
                            <form method="POST" action="{{ route('os.status.update', $o) }}" class="inline">
                                @csrf
                                <input type="hidden" name="status" value="EM_PRODUCAO">
                                <button type="submit" class="px-3 py-1 text-xs bg-yellow-600 text-white rounded hover:bg-yellow-700">Enviar p/ Produção</button>
                            </form>
                        @endif
                        @if($o->status === 'EM_PRODUCAO')
                            <form method="POST" action="{{ route('os.status.update', $o) }}" class="inline">
                                @csrf
                                <input type="hidden" name="status" value="PRONTA">
                                <button type="submit" class="px-3 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">Marcar Pronta</button>
                            </form>
                        @endif
                        @if($o->status === 'PRONTA')
                            <form method="POST" action="{{ route('os.status.update', $o) }}" class="inline" onsubmit="return confirmMarcarEntregue({{ $o->id }});">
                                @csrf
                                <input type="hidden" name="status" value="ENTREGUE">
                                <button type="submit" class="px-3 py-1 text-xs bg-slate-600 text-white rounded hover:bg-slate-700">Entregar</button>
                            </form>
                        @endif
                        @if($o->status === 'ENTREGUE')
                            <form method="POST" action="{{ route('os.status.update', $o) }}" class="inline" onsubmit="return confirm('Voltar esta OS para o status PRONTA?');">
                                @csrf
                                <input type="hidden" name="status" value="PRONTA">
                                <button type="submit" class="px-6 py-3 bg-yellow-400 border-3 border-yellow-600 text-gray-900 rounded-lg hover:bg-yellow-500 font-black shadow-lg transition-colors" style="font-size: 1.25rem; min-width: 200px; font-weight: 900;">
                                    ↩️ VOLTAR STATUS
                                </button>
                            </form>
                        @endif
                        <div class="relative inline-block">
                            <button onclick="togglePrintMenu({{ $o->id }})" class="px-3 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">Imprimir ▼</button>
                            <div id="print-menu-{{ $o->id }}" class="hidden absolute right-0 mt-1 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                <a href="{{ route('os.print', ['o' => $o, 'tipo' => 'cliente']) }}" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Via do Cliente</a>
                                <a href="{{ route('os.print', ['o' => $o, 'tipo' => 'controle']) }}" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Via de Controle</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs (similar ao create) -->
                <div class="mb-4 border-b border-slate-200">
                    <nav class="flex space-x-4 overflow-x-auto">
                        <button type="button" onclick="switchTab('basic')" id="tab-basic" class="tab-btn active px-4 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600 whitespace-nowrap">Dados</button>
                        <button type="button" onclick="switchTab('items')" id="tab-items" class="tab-btn px-4 py-2 text-sm font-medium text-slate-500 border-b-2 border-transparent whitespace-nowrap">Itens</button>
                        <button type="button" onclick="switchTab('prescription')" id="tab-prescription" class="tab-btn px-4 py-2 text-sm font-medium text-slate-500 border-b-2 border-transparent whitespace-nowrap">Receita</button>
                        <button type="button" onclick="switchTab('images')" id="tab-images" class="tab-btn px-4 py-2 text-sm font-medium text-slate-500 border-b-2 border-transparent whitespace-nowrap">Imagens</button>
                    </nav>
                </div>

                <!-- Tab: Dados Básicos -->
                <div id="content-basic" class="tab-content">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
                        <div>
                            <label class="block text-xs md:text-sm font-medium text-slate-700 mb-1">Nº O.S.</label>
                            <input type="text" value="{{ $o->os_number }}" disabled class="w-full text-sm rounded-lg border-slate-200 bg-slate-50 text-slate-500">
                        </div>
                        <div>
                            <label class="block text-xs md:text-sm font-medium text-slate-700 mb-1">Loja</label>
                            <input type="text" value="{{ $o->store?->name ?? '-' }}" disabled class="w-full text-sm rounded-lg border-slate-200 bg-slate-50 text-slate-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs md:text-sm font-medium text-slate-700 mb-1">Cliente</label>
                            <input type="text" value="{{ $o->client?->name ?? '-' }}" disabled class="w-full text-sm rounded-lg border-slate-200 bg-slate-50 text-slate-500">
                            <input type="hidden" name="client_id" value="{{ $o->client_id }}">
                        </div>
                        <div>
                            <label for="delivery_date" class="block text-xs md:text-sm font-medium text-slate-700 mb-1">Data Entrega</label>
                            <input type="date" id="delivery_date" name="delivery_date" value="{{ $o->delivery_date?->format('Y-m-d') }}" class="w-full text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="delivery_time" class="block text-xs md:text-sm font-medium text-slate-700 mb-1">Hora Entrega</label>
                            <input type="time" id="delivery_time" name="delivery_time" value="{{ $o->delivery_time?->format('H:i') }}" class="w-full text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="source" class="block text-xs md:text-sm font-medium text-slate-700 mb-1">Origem</label>
                            <input type="text" id="source" name="source" value="{{ $o->source }}" class="w-full text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="md:col-span-3">
                            <label for="notes" class="block text-xs md:text-sm font-medium text-slate-700 mb-1">Observações</label>
                            <textarea id="notes" name="notes" rows="3" class="w-full text-sm rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ $o->notes }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Tab: Itens (similar ao create, mas com itens existentes) -->
                <div id="content-items" class="tab-content hidden">
                    <div class="mb-4">
                        <div class="flex gap-2 relative">
                            <input type="text" id="product_search" placeholder="Buscar produto..." class="flex-1 text-sm rounded-lg border-slate-300 shadow-sm">
                            <button type="button" onclick="searchProduct()" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">Buscar</button>
                        </div>
                        <!-- Área de resultados da busca -->
                        <div id="product_results" class="hidden absolute z-50 w-full mt-1 bg-white border border-slate-300 rounded-lg shadow-lg max-h-96 overflow-y-auto" style="max-width: 600px;"></div>
                    </div>
                    <div class="overflow-x-auto mb-4">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-slate-500 uppercase">Tipo</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-slate-500 uppercase">Produto</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-slate-500 uppercase">Qtd</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-slate-500 uppercase">Unit.</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-slate-500 uppercase">Total</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-slate-500 uppercase"></th>
                                </tr>
                            </thead>
                            <tbody id="items_table_body">
                                @foreach($o->items as $idx => $item)
                                    <tr>
                                        <td class="px-2 py-2">
                                            <select name="items[{{ $idx }}][type]" class="text-xs rounded border-slate-300" {{ !$o->canEdit() ? 'disabled' : '' }}>
                                                <option value="PRODUTO" {{ $item->type === 'PRODUTO' ? 'selected' : '' }}>Produto</option>
                                                <option value="SERVICO" {{ $item->type === 'SERVICO' ? 'selected' : '' }}>Serviço</option>
                                            </select>
                                            <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $item->id }}">
                                            <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $item->product_id }}">
                                            <input type="hidden" name="items[{{ $idx }}][ref]" value="{{ $item->ref }}">
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="text" name="items[{{ $idx }}][name]" value="{{ $item->name }}" required class="text-xs rounded border-slate-300 w-full" {{ !$o->canEdit() ? 'disabled' : '' }}>
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" step="0.001" name="items[{{ $idx }}][qty]" value="{{ $item->qty }}" min="0.001" class="text-xs rounded border-slate-300 w-16" {{ !$o->canEdit() ? 'disabled' : '' }}>
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" step="0.01" name="items[{{ $idx }}][unit_price]" value="{{ $item->unit_price }}" min="0" class="text-xs rounded border-slate-300 w-20" {{ !$o->canEdit() ? 'disabled' : '' }}>
                                        </td>
                                        <td class="px-2 py-2 font-medium">R$ {{ number_format($item->line_total, 2, ',', '.') }}</td>
                                        <td class="px-2 py-2">
                                            @if($o->canEdit())
                                                <button type="button" onclick="removeItemRow(this)" class="text-red-600 hover:text-red-800 text-xs">Remover</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-slate-600">Subtotal:</span>
                                <span class="ml-2 font-semibold">R$ {{ number_format($o->subtotal, 2, ',', '.') }}</span>
                            </div>
                            <div>
                                <label class="block text-xs text-slate-600 mb-1">Desconto:</label>
                                <input type="number" step="0.01" name="discount_value" value="{{ $o->discount_value }}" min="0" class="w-full text-sm rounded border-slate-300" {{ !$o->canEdit() ? 'disabled' : '' }}>
                            </div>
                            <div>
                                <span class="text-slate-600">Total:</span>
                                <span class="ml-2 font-bold text-lg">R$ {{ number_format($o->total_value, 2, ',', '.') }}</span>
                            </div>
                            <div>
                                <span class="text-slate-600">Adiantamento:</span>
                                <span class="ml-2 font-medium">{{ $o->advance_type }} - R$ {{ number_format($o->advance_value, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Receita -->
                <div id="content-prescription" class="tab-content hidden">
                    @php
                        $prescription = $o->prescription;
                        $presc = $prescription?->prescription;
                    @endphp
                    @if($prescription && $prescription->use_custom)
                        <!-- Campos customizados -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                            <div class="md:col-span-2">
                                <h3 class="text-sm font-semibold mb-2">LONGE</h3>
                                <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                                    <input type="text" value="{{ $prescription->custom_longe_esferico_od }}" placeholder="OD Esférico" disabled class="rounded border-slate-200 bg-slate-50">
                                    <!-- ... outros campos ... -->
                                </div>
                            </div>
                        </div>
                    @elseif($presc)
                        <!-- Dados da receita vinculada -->
                        <div class="p-4 bg-blue-50 rounded-lg">
                            <p class="text-sm font-medium">Receita vinculada: Dr(a). {{ $presc->doctor_name ?? 'N/A' }}</p>
                            <p class="text-xs text-slate-600 mt-1">Válida até: {{ $presc->valid_until?->format('d/m/Y') ?? 'N/A' }}</p>
                        </div>
                    @else
                        <p class="text-sm text-slate-500">Nenhuma receita vinculada.</p>
                    @endif
                </div>

                <!-- Tab: Imagens -->
                <div id="content-images" class="tab-content hidden">
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2 md:gap-3 max-w-3xl mx-auto">
                        @foreach($o->images as $image)
                            <div class="relative border-2 border-slate-300 rounded-lg overflow-hidden bg-white" style="aspect-ratio: 1; max-height: 120px;">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($image->path) }}" alt="Imagem {{ $image->position }}" class="w-full h-full object-cover">
                                @if($o->canEdit())
                                    <button type="button" onclick="deleteOsImage({{ $image->id }})" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center hover:bg-red-600 z-10">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                        @if($o->canEdit())
                            @for($i = count($o->images) + 1; $i <= 5; $i++)
                                <div class="relative border-2 border-dashed border-slate-300 rounded-lg overflow-hidden bg-slate-50" style="aspect-ratio: 1; max-height: 120px;">
                                    <input type="file" id="os_image_{{ $i }}" name="images[]" accept="image/*" class="hidden" onchange="previewOsImage(this, {{ $i }})">
                                    <label for="os_image_{{ $i }}" class="cursor-pointer block h-full w-full flex flex-col items-center justify-center text-center p-2">
                                        <svg class="w-5 h-5 text-slate-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="text-xs text-slate-500">{{ $i }}</span>
                                    </label>
                                    <img id="preview_os_{{ $i }}" class="hidden absolute inset-0 w-full h-full object-cover" alt="Preview">
                                    <button type="button" onclick="removeOsImage({{ $i }})" class="hidden absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center hover:bg-red-600 z-10" id="remove_os_{{ $i }}">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            @endfor
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Função para confirmar marcação como ENTREGUE com verificação de saldo
        async function confirmMarcarEntregue(osId) {
            try {
                // Buscar dados da OS para verificar saldo pendente
                const response = await fetch(`{{ url('os') }}/${osId}?ajax=1`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    const os = await response.json();
                    
                    // Verificar se há receivables pendentes
                    if (os.receivables && os.receivables.length > 0) {
                        const receivablesPendentes = os.receivables.filter(r => r.status !== 'paid');
                        const totalPendente = receivablesPendentes.reduce((sum, r) => sum + (parseFloat(r.balance_amount) || 0), 0);
                        
                        if (totalPendente > 0) {
                            const mensagem = `⚠️ ATENÇÃO!\n\nEsta OS possui saldo pendente de R$ ${totalPendente.toFixed(2).replace('.', ',')} em contas a receber.\n\nDeseja continuar mesmo assim?`;
                            if (!confirm(mensagem)) {
                                return false;
                            }
                        }
                    }
                }
            } catch (error) {
                console.error('Erro ao verificar saldo:', error);
                // Se houver erro, continuar com confirmação normal
            }
            
            // Confirmação final
            return confirm('Marcar esta OS como Entregue?');
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
        }

        function removeItemRow(btn) {
            btn.closest('tr').remove();
        }

        function previewOsImage(input, index) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview_os_' + index).src = e.target.result;
                    document.getElementById('preview_os_' + index).classList.remove('hidden');
                    document.getElementById('remove_os_' + index).classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        }

        function removeOsImage(index) {
            document.getElementById('os_image_' + index).value = '';
            document.getElementById('preview_os_' + index).classList.add('hidden');
            document.getElementById('remove_os_' + index).classList.add('hidden');
        }

        function togglePrintMenu(osId) {
            const menu = document.getElementById(`print-menu-${osId}`);
            if (menu) {
                menu.classList.toggle('hidden');
                // Fechar outros menus abertos
                document.querySelectorAll('[id^="print-menu-"]').forEach(m => {
                    if (m.id !== `print-menu-${osId}`) {
                        m.classList.add('hidden');
                    }
                });
            }
        }
        
        // Fechar menus ao clicar fora
        document.addEventListener('click', function(event) {
            if (!event.target.closest('[id^="print-menu-"]') && !event.target.closest('button[onclick*="togglePrintMenu"]')) {
                document.querySelectorAll('[id^="print-menu-"]').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });
        
        function deleteOsImage(imageId) {
            if (confirm('Deseja remover esta imagem?')) {
                fetch(`/os/imagens/${imageId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                }).then(() => location.reload());
            }
        }

        // Função para buscar produtos
        function searchProduct() {
            const query = document.getElementById('product_search').value.trim();
            const storeId = @json($o->store_id);
            const resultsDiv = document.getElementById('product_results');
            
            if (!resultsDiv) {
                console.error('Elemento product_results não encontrado');
                return;
            }
            
            if (!query || query.length < 2) {
                alert('Por favor, digite pelo menos 2 caracteres para buscar.');
                return;
            }
            
            if (!storeId) {
                alert('Erro: Loja não identificada.');
                return;
            }
            
            // Mostrar loading
            resultsDiv.innerHTML = '<div class="p-4 text-center text-slate-600 font-semibold">🔍 Buscando...</div>';
            resultsDiv.classList.remove('hidden');
            
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
                    resultsDiv.innerHTML = '<div class="p-4 text-center text-red-600 font-semibold">❌ Erro ao buscar produtos</div>';
                    resultsDiv.classList.remove('hidden');
                });
        }

        // Função para selecionar produto da lista
        function selectProductFromList(index) {
            if (window.productSearchResults && window.productSearchResults[index]) {
                addProductToTable(window.productSearchResults[index]);
                document.getElementById('product_search').value = '';
                const resultsDiv = document.getElementById('product_results');
                if (resultsDiv) {
                    resultsDiv.classList.add('hidden');
                }
            }
        }

        // Função para adicionar produto à tabela
        function addProductToTable(product) {
            const tbody = document.getElementById('items_table_body');
            if (!tbody) {
                console.error('Tabela de itens não encontrada');
                return;
            }
            
            // Contar itens existentes para gerar índice único
            const existingItems = tbody.querySelectorAll('tr').length;
            const newIndex = existingItems;
            
            // Criar nova linha
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td class="px-2 py-2">
                    <select name="items[${newIndex}][type]" class="text-xs rounded border-slate-300">
                        <option value="PRODUTO" selected>Produto</option>
                        <option value="SERVICO">Serviço</option>
                    </select>
                    <input type="hidden" name="items[${newIndex}][product_id]" value="${product.id}">
                    <input type="hidden" name="items[${newIndex}][ref]" value="${product.ref || ''}">
                </td>
                <td class="px-2 py-2">
                    <input type="text" name="items[${newIndex}][name]" value="${(product.name || '').replace(/"/g, '&quot;')}" required class="text-xs rounded border-slate-300 w-full">
                </td>
                <td class="px-2 py-2">
                    <input type="number" step="0.001" name="items[${newIndex}][qty]" value="1" min="0.001" class="text-xs rounded border-slate-300 w-16" onchange="calculateItemTotal(this)">
                </td>
                <td class="px-2 py-2">
                    <input type="number" step="0.01" name="items[${newIndex}][unit_price]" value="${product.unit_price || 0}" min="0" class="text-xs rounded border-slate-300 w-20" onchange="calculateItemTotal(this)">
                </td>
                <td class="px-2 py-2 font-medium item-total">R$ ${parseFloat(product.unit_price || 0).toFixed(2).replace('.', ',')}</td>
                <td class="px-2 py-2">
                    <button type="button" onclick="removeItemRow(this)" class="text-red-600 hover:text-red-800 text-xs">Remover</button>
                </td>
            `;
            
            tbody.appendChild(newRow);
        }

        // Função para calcular total do item
        function calculateItemTotal(input) {
            const row = input.closest('tr');
            const qty = parseFloat(row.querySelector('[name*="[qty]"]').value) || 0;
            const unitPrice = parseFloat(row.querySelector('[name*="[unit_price]"]').value) || 0;
            const total = qty * unitPrice;
            const totalCell = row.querySelector('.item-total');
            if (totalCell) {
                totalCell.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
            }
        }

        // Fechar resultados ao clicar fora
        document.addEventListener('click', function(event) {
            const resultsDiv = document.getElementById('product_results');
            const searchInput = document.getElementById('product_search');
            if (resultsDiv && searchInput && !resultsDiv.contains(event.target) && event.target !== searchInput) {
                resultsDiv.classList.add('hidden');
            }
        });
    </script>
</x-app-layout>

