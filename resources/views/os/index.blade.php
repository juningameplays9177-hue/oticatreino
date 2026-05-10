<x-app-layout title="Ordens de Serviço">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-900 mb-4 sm:mb-0">Ordens de Serviço</h1>
            <a href="{{ route('os.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nova O.S.
            </a>
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
        @if(isset($error))
            <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-800">{{ $error }}</div>
        @endif

        <!-- Filtros -->
        <form method="GET" action="{{ route('os.index') }}" class="mb-6 bg-white rounded-lg shadow-sm border border-slate-200 p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label for="q" class="block text-sm font-medium text-slate-700 mb-1">Busca</label>
                    <input type="text" id="q" name="q" value="{{ request('q') }}" placeholder="Cliente, CPF, Nº O.S..." class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                    <label for="status" class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                    <select id="status" name="status" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="REGISTRADA" {{ request('status') == 'REGISTRADA' ? 'selected' : '' }}>Registrada</option>
                        <option value="EM_PRODUCAO" {{ request('status') == 'EM_PRODUCAO' ? 'selected' : '' }}>Em Produção</option>
                        <option value="PRONTA" {{ request('status') == 'PRONTA' ? 'selected' : '' }}>Pronta</option>
                        <option value="ENTREGUE" {{ request('status') == 'ENTREGUE' ? 'selected' : '' }}>Entregue</option>
                        <option value="CANCELADA" {{ request('status') == 'CANCELADA' ? 'selected' : '' }}>Cancelada</option>
                        <option value="PERDA" {{ request('status') == 'PERDA' ? 'selected' : '' }}>Perda</option>
                    </select>
                </div>
                <div>
                    <label for="period" class="block text-sm font-medium text-slate-700 mb-1">Período</label>
                    <select id="period" name="period" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="hoje" {{ request('period') == 'hoje' ? 'selected' : '' }}>Hoje</option>
                        <option value="ontem" {{ request('period') == 'ontem' ? 'selected' : '' }}>Ontem</option>
                        <option value="amanha" {{ request('period') == 'amanha' ? 'selected' : '' }}>Amanhã</option>
                        <option value="ultimos_7" {{ request('period') == 'ultimos_7' ? 'selected' : '' }}>Últimos 7 dias</option>
                        <option value="ultimos_30" {{ request('period') == 'ultimos_30' ? 'selected' : '' }}>Últimos 30 dias</option>
                        <option value="este_mes" {{ request('period') == 'este_mes' ? 'selected' : '' }}>Este Mês</option>
                        <option value="proximo_mes" {{ request('period') == 'proximo_mes' ? 'selected' : '' }}>Próximo Mês</option>
                        <option value="mes_anterior" {{ request('period') == 'mes_anterior' ? 'selected' : '' }}>Mês Anterior</option>
                        <option value="este_ano" {{ request('period') == 'este_ano' ? 'selected' : '' }}>Este Ano</option>
                    </select>
                </div>
                <div>
                    <label for="employee_id" class="block text-sm font-medium text-slate-700 mb-1">Funcionário</label>
                    <select id="employee_id" name="employee_id" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="advance_type" class="block text-sm font-medium text-slate-700 mb-1">Adiantamento</label>
                    <select id="advance_type" name="advance_type" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="SEM" {{ request('advance_type') == 'SEM' ? 'selected' : '' }}>Sem</option>
                        <option value="TOTAL" {{ request('advance_type') == 'TOTAL' ? 'selected' : '' }}>Total</option>
                        <option value="PARCIAL" {{ request('advance_type') == 'PARCIAL' ? 'selected' : '' }}>Parcial</option>
                    </select>
                </div>
                <div>
                    <label for="sort" class="block text-sm font-medium text-slate-700 mb-1">Ordenar por</label>
                    <select id="sort" name="sort" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="registered_desc" {{ request('sort', 'registered_desc') == 'registered_desc' ? 'selected' : '' }}>Data (Recente)</option>
                        <option value="registered_asc" {{ request('sort') == 'registered_asc' ? 'selected' : '' }}>Data (Antigo)</option>
                        <option value="value_desc" {{ request('sort') == 'value_desc' ? 'selected' : '' }}>Valor (Maior)</option>
                        <option value="value_asc" {{ request('sort') == 'value_asc' ? 'selected' : '' }}>Valor (Menor)</option>
                        <option value="delivery_asc" {{ request('sort') == 'delivery_asc' ? 'selected' : '' }}>Entrega (Próxima)</option>
                        <option value="delivery_desc" {{ request('sort') == 'delivery_desc' ? 'selected' : '' }}>Entrega (Distante)</option>
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
                <p class="text-sm text-slate-600">
                    @if($serviceOrders->total() > 0)
                        Foram encontradas <strong>{{ $serviceOrders->total() }}</strong> O.S. Mostrando página <strong>{{ $serviceOrders->currentPage() }}</strong> de <strong>{{ $serviceOrders->lastPage() }}</strong>.
                    @else
                        Não foi encontrada nenhuma O.S. usando o filtro selecionado!
                    @endif
                </p>
            </div>

            <!-- Tabela Desktop -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Nº O.S.</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Cliente</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Funcionário</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Data Registro</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Previsão Entrega</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Valor Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse($serviceOrders as $os)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $os->os_number }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $os->client?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $os->employee->name }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $statusColors = [
                                            'REGISTRADA' => 'bg-blue-100 text-blue-800',
                                            'EM_PRODUCAO' => 'bg-yellow-100 text-yellow-800',
                                            'PRONTA' => 'bg-green-100 text-green-800',
                                            'ENTREGUE' => 'bg-slate-100 text-slate-800',
                                            'CANCELADA' => 'bg-red-100 text-red-800',
                                            'PERDA' => 'bg-orange-100 text-orange-800',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$os->status] ?? 'bg-slate-100 text-slate-800' }}">
                                        {{ $os->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $os->registered_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $os->delivery_date ? $os->delivery_date->format('d/m/Y') . ($os->delivery_time ? ' ' . $os->delivery_time->format('H:i') : '') : '-' }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-slate-900">R$ {{ number_format($os->total_value, 2, ',', '.') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <button onclick="viewOsDetails({{ $os->id }})" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">Ver</button>
                                        <a href="{{ route('os.edit', $os) }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm">Editar</a>
                                        <div class="relative inline-block">
                                            <button onclick="togglePrintMenu({{ $os->id }})" class="text-green-600 hover:text-green-800 font-medium text-sm">Imprimir ▼</button>
                                            <div id="print-menu-{{ $os->id }}" class="hidden absolute right-0 mt-1 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                                <a href="{{ route('os.print', ['o' => $os, 'tipo' => 'cliente']) }}" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Via do Cliente</a>
                                                <a href="{{ route('os.print', ['o' => $os, 'tipo' => 'controle']) }}" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Via de Controle</a>
                                            </div>
                                        </div>
                                        <!-- Botões de atualização rápida de status -->
                                        @if($os->status === 'REGISTRADA')
                                            <form method="POST" action="{{ route('os.status.update', $os) }}" class="inline" onsubmit="return confirm('Enviar esta OS para produção?');">
                                                @csrf
                                                <input type="hidden" name="status" value="EM_PRODUCAO">
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-800 font-medium text-sm" title="Enviar para Produção">⚙️</button>
                                            </form>
                                        @endif
                                        @if($os->status === 'EM_PRODUCAO')
                                            <form method="POST" action="{{ route('os.status.update', $os) }}" class="inline" onsubmit="return confirm('Marcar esta OS como Pronta?');">
                                                @csrf
                                                <input type="hidden" name="status" value="PRONTA">
                                                <button type="submit" class="text-green-600 hover:text-green-800 font-medium text-sm" title="Marcar como Pronta">✅</button>
                                            </form>
                                        @endif
                                        @if($os->status === 'PRONTA')
                                            <form method="POST" action="{{ route('os.status.update', $os) }}" class="inline" onsubmit="return confirmMarcarEntregue({{ $os->id }});">
                                                @csrf
                                                <input type="hidden" name="status" value="ENTREGUE">
                                                <button type="submit" class="text-purple-600 hover:text-purple-800 font-medium text-sm" title="Marcar como Entregue">📦</button>
                                            </form>
                                        @endif
                                        @if($os->status === 'ENTREGUE')
                                            <form method="POST" action="{{ route('os.status.update', $os) }}" class="inline" onsubmit="return confirm('Voltar esta OS para o status PRONTA?');">
                                                @csrf
                                                <input type="hidden" name="status" value="PRONTA">
                                                <button type="submit" class="px-6 py-3 bg-yellow-400 border-3 border-yellow-600 text-gray-900 rounded-lg hover:bg-yellow-500 font-black shadow-lg transition-colors" title="Voltar para Pronta" style="font-size: 1.25rem; min-width: 200px; font-weight: 900;">
                                                    ↩️ VOLTAR STATUS
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-slate-500">Nenhuma O.S. encontrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Cards Mobile -->
            <div class="md:hidden divide-y divide-slate-200">
                @forelse($serviceOrders as $os)
                    <div class="p-4">
                        <div class="font-medium text-slate-900 mb-1">{{ $os->os_number }}</div>
                        <div class="text-sm text-slate-600 mb-2">{{ $os->client?->name ?? '-' }}</div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ $os->status }}</span>
                            <span class="text-sm font-medium">R$ {{ number_format($os->total_value, 2, ',', '.') }}</span>
                        </div>
                        <div class="flex gap-2 mt-2 flex-wrap">
                            <button onclick="viewOsDetails({{ $os->id }})" class="text-indigo-600 hover:text-indigo-800 text-sm">Ver</button>
                            <a href="{{ route('os.edit', $os) }}" class="text-blue-600 hover:text-blue-800 text-sm">Editar</a>
                            <div class="relative inline-block">
                                <button onclick="togglePrintMenu({{ $os->id }})" class="text-green-600 hover:text-green-800 text-sm">Imprimir ▼</button>
                                <div id="print-menu-{{ $os->id }}" class="hidden absolute right-0 mt-1 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                    <a href="{{ route('os.print', ['o' => $os, 'tipo' => 'cliente']) }}" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Via do Cliente</a>
                                    <a href="{{ route('os.print', ['o' => $os, 'tipo' => 'controle']) }}" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Via de Controle</a>
                                </div>
                            </div>
                            @if($os->status === 'REGISTRADA')
                                <form method="POST" action="{{ route('os.status.update', $os) }}" class="inline" onsubmit="return confirm('Enviar para produção?');">
                                    @csrf
                                    <input type="hidden" name="status" value="EM_PRODUCAO">
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-800 text-sm">⚙️ Produção</button>
                                </form>
                            @endif
                            @if($os->status === 'EM_PRODUCAO')
                                <form method="POST" action="{{ route('os.status.update', $os) }}" class="inline" onsubmit="return confirm('Marcar como Pronta?');">
                                    @csrf
                                    <input type="hidden" name="status" value="PRONTA">
                                    <button type="submit" class="text-green-600 hover:text-green-800 text-sm">✅ Pronta</button>
                                </form>
                            @endif
                            @if($os->status === 'PRONTA')
                                <form method="POST" action="{{ route('os.status.update', $os) }}" class="inline" onsubmit="return confirmMarcarEntregue({{ $os->id }});">
                                    @csrf
                                    <input type="hidden" name="status" value="ENTREGUE">
                                    <button type="submit" class="text-purple-600 hover:text-purple-800 text-sm">📦 Entregue</button>
                                </form>
                            @endif
                            @if($os->status === 'ENTREGUE')
                                <form method="POST" action="{{ route('os.status.update', $os) }}" class="inline" onsubmit="return confirm('Voltar esta OS para o status PRONTA?');">
                                    @csrf
                                    <input type="hidden" name="status" value="PRONTA">
                                    <button type="submit" class="px-6 py-3 bg-yellow-400 border-3 border-yellow-600 text-gray-900 rounded-lg hover:bg-yellow-500 font-black shadow-lg transition-colors" style="font-size: 1.25rem; min-width: 200px; font-weight: 900;">
                                        ↩️ VOLTAR STATUS
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-slate-500">Nenhuma O.S. encontrada.</div>
                @endforelse
            </div>

            @if($serviceOrders->hasPages())
                <div class="px-4 py-3 bg-slate-50 border-t border-slate-200">{{ $serviceOrders->links() }}</div>
            @endif
        </div>
    </div>

    <!-- Modal de Detalhes da OS -->
    <div id="os-details-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" onclick="if(event.target === this) closeOsDetailsModal()">
        <div class="bg-white rounded-lg shadow-xl max-w-5xl w-full max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 rounded-t-lg flex justify-between items-center">
                <h2 class="text-2xl font-bold">Detalhes da OS</h2>
                <button onclick="closeOsDetailsModal()" class="text-white hover:text-gray-200 text-3xl font-bold">&times;</button>
            </div>
            <div id="os-details-content" class="p-6">
                <div class="text-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-4 text-gray-600">Carregando detalhes...</p>
                </div>
            </div>
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
        
        async function viewOsDetails(osId) {
            const modal = document.getElementById('os-details-modal');
            const content = document.getElementById('os-details-content');
            
            modal.classList.remove('hidden');
            content.innerHTML = `
                <div class="text-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-4 text-gray-600">Carregando detalhes...</p>
                </div>
            `;
            
            try {
                const response = await fetch(`{{ url('os') }}/${osId}?ajax=1`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Erro ao carregar detalhes');
                }
                
                const os = await response.json();
                
                // Debug: verificar dados de receita
                console.log('Dados da OS recebidos:', os);
                console.log('Prescription:', os.prescription);
                
                // Formatar status
                const statusColors = {
                    'REGISTRADA': 'bg-blue-100 text-blue-800',
                    'EM_PRODUCAO': 'bg-yellow-100 text-yellow-800',
                    'PRONTA': 'bg-green-100 text-green-800',
                    'ENTREGUE': 'bg-slate-100 text-slate-800',
                    'CANCELADA': 'bg-red-100 text-red-800',
                    'PERDA': 'bg-orange-100 text-orange-800',
                };
                
                // Formatar data
                const formatDate = (date) => {
                    if (!date) return '-';
                    const d = new Date(date);
                    return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
                };
                
                // Formatar valor
                const formatMoney = (value) => {
                    return 'R$ ' + parseFloat(value || 0).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                };
                
                content.innerHTML = `
                    <!-- Informações Principais -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg border-2 border-blue-200">
                            <h3 class="font-semibold text-blue-900 mb-2">📋 Informações Básicas</h3>
                            <div class="space-y-2 text-sm">
                                <div><span class="font-medium">Nº OS:</span> <span class="text-blue-700 font-bold">${os.os_number || '-'}</span></div>
                                <div><span class="font-medium">Status:</span> <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${statusColors[os.status] || 'bg-gray-100 text-gray-800'}">${os.status || '-'}</span></div>
                                <div><span class="font-medium">Data Registro:</span> ${formatDate(os.registered_at)}</div>
                                <div><span class="font-medium">Previsão Entrega:</span> ${os.delivery_date ? formatDate(os.delivery_date) : '-'}</div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg border-2 border-green-200">
                            <h3 class="font-semibold text-green-900 mb-2">👤 Cliente</h3>
                            <div class="space-y-2 text-sm">
                                <div><span class="font-medium">Nome:</span> ${os.client?.name || '-'}</div>
                                <div><span class="font-medium">CPF/CNPJ:</span> ${os.client?.cpf_cnpj || '-'}</div>
                                <div><span class="font-medium">Telefone:</span> ${os.client?.phone || '-'}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Itens -->
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-800 mb-3 text-lg border-b-2 border-gray-200 pb-2">🛍️ Itens da OS</h3>
                        ${os.items && os.items.length > 0 ? `
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Produto</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Ref</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Qtd</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Preço Unit.</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        ${os.items.map(item => `
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2 text-sm">${item.name || '-'}</td>
                                                <td class="px-4 py-2 text-sm text-gray-600">${item.ref || '-'}</td>
                                                <td class="px-4 py-2 text-sm">${item.qty || 0}</td>
                                                <td class="px-4 py-2 text-sm">${formatMoney(item.unit_price)}</td>
                                                <td class="px-4 py-2 text-sm font-medium">${formatMoney((item.qty || 0) * (item.unit_price || 0))}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        ` : '<p class="text-gray-500 text-center py-4">Nenhum item cadastrado nesta OS.</p>'}
                    </div>
                    
                    <!-- Valores -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <div class="text-sm text-gray-600">Subtotal</div>
                            <div class="text-xl font-bold text-gray-900">${formatMoney(os.subtotal)}</div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <div class="text-sm text-gray-600">Desconto</div>
                            <div class="text-xl font-bold text-red-600">- ${formatMoney(os.discount_value)}</div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-lg border-2 border-purple-200">
                            <div class="text-sm text-purple-700 font-medium">Total</div>
                            <div class="text-2xl font-bold text-purple-900">${formatMoney(os.total_value)}</div>
                        </div>
                    </div>
                    
                    <!-- Recebíveis (Saldo de Sinal) -->
                    ${os.status === 'ENTREGUE' && os.receivables && os.receivables.length > 0 ? `
                        <div class="mb-6">
                            <h3 class="font-semibold text-gray-800 mb-3 text-lg border-b-2 border-gray-200 pb-2">💰 Pagamentos Pendentes</h3>
                            <div class="space-y-3">
                                ${os.receivables.map(receivable => {
                                    const statusColors = {
                                        'open': 'bg-yellow-100 text-yellow-800',
                                        'partial': 'bg-orange-100 text-orange-800',
                                        'paid': 'bg-green-100 text-green-800',
                                        'canceled': 'bg-red-100 text-red-800',
                                    };
                                    const statusNames = {
                                        'open': 'Aberto',
                                        'partial': 'Parcial',
                                        'paid': 'Pago',
                                        'canceled': 'Cancelado',
                                    };
                                    // Calcular valor já pago
                                    // Se for conta a receber de sinal, considerar o valor do sinal já pago
                                    let paidAmount = receivable.original_amount - receivable.balance_amount;
                                    
                                    // Se a nota indica que é saldo de pagamento sinal, adicionar o valor do sinal
                                    // O sinal já foi pago, então deve aparecer como "Já Pago"
                                    if (receivable.note && receivable.note.includes('Saldo de pagamento sinal') && os.sinal_amount && os.sinal_amount > 0) {
                                        // O valor do sinal já foi pago, então deve ser somado ao "Já Pago"
                                        paidAmount = (os.sinal_amount || 0) + (receivable.original_amount - receivable.balance_amount);
                                    }
                                    const formatDate = (date) => {
                                        if (!date) return '-';
                                        const d = new Date(date);
                                        return d.toLocaleDateString('pt-BR');
                                    };
                                    return `
                                        <div class="bg-gradient-to-br from-yellow-50 to-orange-50 p-4 rounded-lg border-2 border-yellow-200">
                                            <div class="flex justify-between items-start mb-2">
                                                <div>
                                                    <div class="text-sm font-semibold text-gray-700">Saldo de Pagamento Sinal</div>
                                                    <div class="text-xs text-gray-600">${receivable.note || 'Saldo restante da OS'}</div>
                                                </div>
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium ${statusColors[receivable.status] || 'bg-gray-100 text-gray-800'}">
                                                    ${statusNames[receivable.status] || receivable.status}
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3 text-sm">
                                                <div>
                                                    <div class="text-gray-600 text-xs">Valor Original</div>
                                                    <div class="font-bold text-gray-900">${formatMoney(receivable.original_amount)}</div>
                                                </div>
                                                <div>
                                                    <div class="text-gray-600 text-xs">Já Pago</div>
                                                    <div class="font-bold text-green-700">${formatMoney(paidAmount)}</div>
                                                </div>
                                                <div>
                                                    <div class="text-gray-600 text-xs">Saldo</div>
                                                    <div class="font-bold text-orange-700">${formatMoney(receivable.balance_amount)}</div>
                                                </div>
                                                <div>
                                                    <div class="text-gray-600 text-xs">Vencimento</div>
                                                    <div class="font-semibold text-gray-900">${formatDate(receivable.due_date)}</div>
                                                </div>
                                            </div>
                                            ${receivable.payments && receivable.payments.length > 0 ? `
                                                <div class="mt-3 pt-3 border-t border-yellow-300">
                                                    <div class="text-xs font-semibold text-gray-700 mb-2">Histórico de Pagamentos:</div>
                                                    <div class="space-y-1">
                                                        ${receivable.payments.map(payment => `
                                                            <div class="flex justify-between items-center text-xs bg-white p-2 rounded">
                                                                <span class="text-gray-700">${formatMoney(payment.amount)} - ${payment.method || 'N/A'}</span>
                                                                <span class="text-gray-500">${formatDate(payment.paid_at)}</span>
                                                            </div>
                                                        `).join('')}
                                                    </div>
                                                </div>
                                            ` : ''}
                                        </div>
                                    `;
                                }).join('')}
                            </div>
                        </div>
                    ` : ''}
                    
                    <!-- Receita -->
                    ${os.prescription ? (() => {
                        // Verificar se há dados customizados mesmo sem use_custom=true
                        // Verificar TODOS os campos, não apenas alguns
                        const hasCustomData = 
                            (os.prescription.custom_longe_esferico_od !== null && os.prescription.custom_longe_esferico_od !== '') ||
                            (os.prescription.custom_longe_esferico_oe !== null && os.prescription.custom_longe_esferico_oe !== '') ||
                            (os.prescription.custom_perto_esferico_od !== null && os.prescription.custom_perto_esferico_od !== '') ||
                            (os.prescription.custom_perto_esferico_oe !== null && os.prescription.custom_perto_esferico_oe !== '') ||
                            (os.prescription.custom_longe_cilindrico_od !== null && os.prescription.custom_longe_cilindrico_od !== '') ||
                            (os.prescription.custom_longe_cilindrico_oe !== null && os.prescription.custom_longe_cilindrico_oe !== '') ||
                            (os.prescription.custom_perto_cilindrico_od !== null && os.prescription.custom_perto_cilindrico_od !== '') ||
                            (os.prescription.custom_perto_cilindrico_oe !== null && os.prescription.custom_perto_cilindrico_oe !== '') ||
                            (os.prescription.custom_longe_eixo_od !== null && os.prescription.custom_longe_eixo_od !== '') ||
                            (os.prescription.custom_longe_eixo_oe !== null && os.prescription.custom_longe_eixo_oe !== '') ||
                            (os.prescription.custom_perto_eixo_od !== null && os.prescription.custom_perto_eixo_od !== '') ||
                            (os.prescription.custom_perto_eixo_oe !== null && os.prescription.custom_perto_eixo_oe !== '') ||
                            (os.prescription.custom_longe_altura_od !== null && os.prescription.custom_longe_altura_od !== '') ||
                            (os.prescription.custom_longe_altura_oe !== null && os.prescription.custom_longe_altura_oe !== '') ||
                            (os.prescription.custom_perto_altura_od !== null && os.prescription.custom_perto_altura_od !== '') ||
                            (os.prescription.custom_perto_altura_oe !== null && os.prescription.custom_perto_altura_oe !== '') ||
                            (os.prescription.custom_longe_dnp_od !== null && os.prescription.custom_longe_dnp_od !== '') ||
                            (os.prescription.custom_longe_dnp_oe !== null && os.prescription.custom_longe_dnp_oe !== '') ||
                            (os.prescription.custom_perto_dnp_od !== null && os.prescription.custom_perto_dnp_od !== '') ||
                            (os.prescription.custom_perto_dnp_oe !== null && os.prescription.custom_perto_dnp_oe !== '') ||
                            (os.prescription.custom_adicao !== null && os.prescription.custom_adicao !== '') ||
                            (os.prescription.custom_doctor_name !== null && os.prescription.custom_doctor_name !== '');
                        const shouldShowCustom = os.prescription.use_custom || hasCustomData;
                        return `
                        <div class="mb-6">
                            <h3 class="font-semibold text-gray-800 mb-3 text-lg border-b-2 border-gray-200 pb-2">👁️ Receita Médica</h3>
                            <div class="bg-gradient-to-br from-purple-50 to-blue-50 p-6 rounded-lg border-2 border-purple-200">
                                ${shouldShowCustom ? `
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                                        <!-- Longe OD -->
                                        <div class="bg-white p-4 rounded-lg border border-purple-200">
                                            <h4 class="font-bold text-purple-900 mb-3 text-base border-b-2 border-purple-300 pb-1">Longe - OD (Olho Direito)</h4>
                                            <div class="space-y-1">
                                                <div><span class="font-semibold text-gray-700">Esférico:</span> <span class="text-gray-900">${(os.prescription.custom_longe_esferico_od !== null && os.prescription.custom_longe_esferico_od !== '') ? os.prescription.custom_longe_esferico_od : '-'}</span></div>
                                                <div><span class="font-semibold text-gray-700">Cilíndrico:</span> <span class="text-gray-900">${(os.prescription.custom_longe_cilindrico_od !== null && os.prescription.custom_longe_cilindrico_od !== '') ? os.prescription.custom_longe_cilindrico_od : '-'}</span></div>
                                                <div><span class="font-semibold text-gray-700">Eixo:</span> <span class="text-gray-900">${(os.prescription.custom_longe_eixo_od !== null && os.prescription.custom_longe_eixo_od !== '') ? os.prescription.custom_longe_eixo_od : '-'}°</span></div>
                                                <div><span class="font-semibold text-gray-700">Altura:</span> <span class="text-gray-900">${(os.prescription.custom_longe_altura_od !== null && os.prescription.custom_longe_altura_od !== '') ? os.prescription.custom_longe_altura_od : '-'} mm</span></div>
                                                <div><span class="font-semibold text-gray-700">DNP:</span> <span class="text-gray-900">${(os.prescription.custom_longe_dnp_od !== null && os.prescription.custom_longe_dnp_od !== '') ? os.prescription.custom_longe_dnp_od : '-'} mm</span></div>
                                            </div>
                                        </div>
                                        <!-- Longe OE -->
                                        <div class="bg-white p-4 rounded-lg border border-purple-200">
                                            <h4 class="font-bold text-purple-900 mb-3 text-base border-b-2 border-purple-300 pb-1">Longe - OE (Olho Esquerdo)</h4>
                                            <div class="space-y-1">
                                                <div><span class="font-semibold text-gray-700">Esférico:</span> <span class="text-gray-900">${(os.prescription.custom_longe_esferico_oe !== null && os.prescription.custom_longe_esferico_oe !== '') ? os.prescription.custom_longe_esferico_oe : '-'}</span></div>
                                                <div><span class="font-semibold text-gray-700">Cilíndrico:</span> <span class="text-gray-900">${(os.prescription.custom_longe_cilindrico_oe !== null && os.prescription.custom_longe_cilindrico_oe !== '') ? os.prescription.custom_longe_cilindrico_oe : '-'}</span></div>
                                                <div><span class="font-semibold text-gray-700">Eixo:</span> <span class="text-gray-900">${(os.prescription.custom_longe_eixo_oe !== null && os.prescription.custom_longe_eixo_oe !== '') ? os.prescription.custom_longe_eixo_oe : '-'}°</span></div>
                                                <div><span class="font-semibold text-gray-700">Altura:</span> <span class="text-gray-900">${(os.prescription.custom_longe_altura_oe !== null && os.prescription.custom_longe_altura_oe !== '') ? os.prescription.custom_longe_altura_oe : '-'} mm</span></div>
                                                <div><span class="font-semibold text-gray-700">DNP:</span> <span class="text-gray-900">${(os.prescription.custom_longe_dnp_oe !== null && os.prescription.custom_longe_dnp_oe !== '') ? os.prescription.custom_longe_dnp_oe : '-'} mm</span></div>
                                            </div>
                                        </div>
                                        <!-- Perto OD -->
                                        <div class="bg-white p-4 rounded-lg border border-blue-200">
                                            <h4 class="font-bold text-blue-900 mb-3 text-base border-b-2 border-blue-300 pb-1">Perto - OD (Olho Direito)</h4>
                                            <div class="space-y-1">
                                                <div><span class="font-semibold text-gray-700">Esférico:</span> <span class="text-gray-900">${(os.prescription.custom_perto_esferico_od !== null && os.prescription.custom_perto_esferico_od !== '') ? os.prescription.custom_perto_esferico_od : '-'}</span></div>
                                                <div><span class="font-semibold text-gray-700">Cilíndrico:</span> <span class="text-gray-900">${(os.prescription.custom_perto_cilindrico_od !== null && os.prescription.custom_perto_cilindrico_od !== '') ? os.prescription.custom_perto_cilindrico_od : '-'}</span></div>
                                                <div><span class="font-semibold text-gray-700">Eixo:</span> <span class="text-gray-900">${(os.prescription.custom_perto_eixo_od !== null && os.prescription.custom_perto_eixo_od !== '') ? os.prescription.custom_perto_eixo_od : '-'}°</span></div>
                                                <div><span class="font-semibold text-gray-700">Altura:</span> <span class="text-gray-900">${(os.prescription.custom_perto_altura_od !== null && os.prescription.custom_perto_altura_od !== '') ? os.prescription.custom_perto_altura_od : '-'} mm</span></div>
                                                <div><span class="font-semibold text-gray-700">DNP:</span> <span class="text-gray-900">${(os.prescription.custom_perto_dnp_od !== null && os.prescription.custom_perto_dnp_od !== '') ? os.prescription.custom_perto_dnp_od : '-'} mm</span></div>
                                            </div>
                                        </div>
                                        <!-- Perto OE -->
                                        <div class="bg-white p-4 rounded-lg border border-blue-200">
                                            <h4 class="font-bold text-blue-900 mb-3 text-base border-b-2 border-blue-300 pb-1">Perto - OE (Olho Esquerdo)</h4>
                                            <div class="space-y-1">
                                                <div><span class="font-semibold text-gray-700">Esférico:</span> <span class="text-gray-900">${(os.prescription.custom_perto_esferico_oe !== null && os.prescription.custom_perto_esferico_oe !== '') ? os.prescription.custom_perto_esferico_oe : '-'}</span></div>
                                                <div><span class="font-semibold text-gray-700">Cilíndrico:</span> <span class="text-gray-900">${(os.prescription.custom_perto_cilindrico_oe !== null && os.prescription.custom_perto_cilindrico_oe !== '') ? os.prescription.custom_perto_cilindrico_oe : '-'}</span></div>
                                                <div><span class="font-semibold text-gray-700">Eixo:</span> <span class="text-gray-900">${(os.prescription.custom_perto_eixo_oe !== null && os.prescription.custom_perto_eixo_oe !== '') ? os.prescription.custom_perto_eixo_oe : '-'}°</span></div>
                                                <div><span class="font-semibold text-gray-700">Altura:</span> <span class="text-gray-900">${(os.prescription.custom_perto_altura_oe !== null && os.prescription.custom_perto_altura_oe !== '') ? os.prescription.custom_perto_altura_oe : '-'} mm</span></div>
                                                <div><span class="font-semibold text-gray-700">DNP:</span> <span class="text-gray-900">${(os.prescription.custom_perto_dnp_oe !== null && os.prescription.custom_perto_dnp_oe !== '') ? os.prescription.custom_perto_dnp_oe : '-'} mm</span></div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Informações Adicionais da Receita -->
                                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="bg-white p-3 rounded-lg border border-gray-200">
                                            <div class="font-semibold text-gray-700 mb-1">Adição</div>
                                            <div class="text-lg font-bold text-purple-900">${os.prescription.custom_adicao || '-'}</div>
                                        </div>
                                        <div class="bg-white p-3 rounded-lg border border-gray-200">
                                            <div class="font-semibold text-gray-700 mb-1">Médico</div>
                                            <div class="text-gray-900">${os.prescription.custom_doctor_name || '-'}</div>
                                        </div>
                                        <div class="bg-white p-3 rounded-lg border border-gray-200">
                                            <div class="font-semibold text-gray-700 mb-1">Válida até</div>
                                            <div class="text-gray-900">${os.prescription.custom_valid_until ? new Date(os.prescription.custom_valid_until).toLocaleDateString('pt-BR') : '-'}</div>
                                        </div>
                                    </div>
                                    ${os.prescription.custom_attachment_path ? `
                                        <div class="mt-4">
                                            <a href="/storage/${os.prescription.custom_attachment_path}" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors">
                                                📎 Ver Anexo da Receita
                                            </a>
                                        </div>
                                    ` : ''}
                                    ${os.prescription.custom_notes ? `
                                        <div class="mt-4 bg-white p-3 rounded-lg border border-gray-200">
                                            <div class="font-semibold text-gray-700 mb-1">Observações da Receita</div>
                                            <div class="text-gray-900 whitespace-pre-wrap">${os.prescription.custom_notes}</div>
                                        </div>
                                    ` : ''}
                                    ` : '<p class="text-gray-600">Receita padrão vinculada.</p>'}
                            </div>
                        </div>
                    `;
                    })() : ''}
                    
                    <!-- Observações -->
                    ${os.notes ? `
                        <div class="mb-6">
                            <h3 class="font-semibold text-gray-800 mb-2">📝 Observações</h3>
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">${os.notes}</p>
                            </div>
                        </div>
                    ` : ''}
                    
                    <!-- Ações -->
                    <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200">
                        <a href="/os/${osId}/edit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors">✏️ Editar</a>
                        <div class="relative inline-block">
                            <button onclick="togglePrintMenu(${osId})" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition-colors">🖨️ Imprimir ▼</button>
                            <div id="print-menu-${osId}" class="hidden absolute right-0 mt-1 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                <a href="/os/${osId}/imprimir?tipo=cliente" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Via do Cliente</a>
                                <a href="/os/${osId}/imprimir?tipo=controle" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Via de Controle</a>
                            </div>
                        </div>
                        ${os.status === 'REGISTRADA' ? `
                            <form method="POST" action="/os/${osId}/status" class="inline" onsubmit="event.preventDefault(); if(confirm('Enviar para produção?')) { this.submit(); }">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="status" value="EM_PRODUCAO">
                                <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 font-medium transition-colors">⚙️ Enviar p/ Produção</button>
                            </form>
                        ` : ''}
                        ${os.status === 'EM_PRODUCAO' ? `
                            <form method="POST" action="/os/${osId}/status" class="inline" onsubmit="event.preventDefault(); if(confirm('Marcar como Pronta?')) { this.submit(); }">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="status" value="PRONTA">
                                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition-colors">✅ Marcar Pronta</button>
                            </form>
                        ` : ''}
                        ${os.status === 'PRONTA' ? `
                            <form method="POST" action="/os/${osId}/status" class="inline" onsubmit="event.preventDefault(); return confirmMarcarEntregue(${osId});">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="status" value="ENTREGUE">
                                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium transition-colors">📦 Marcar Entregue</button>
                            </form>
                        ` : ''}
                        ${os.status === 'ENTREGUE' ? `
                            <form method="POST" action="/os/${osId}/status" class="inline" onsubmit="event.preventDefault(); if(confirm('Voltar esta OS para o status PRONTA?')) { this.submit(); }">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="status" value="PRONTA">
                                <button type="submit" class="px-6 py-3 bg-yellow-400 border-3 border-yellow-600 text-gray-900 rounded-lg hover:bg-yellow-500 font-black shadow-lg transition-colors" style="font-size: 1.25rem; min-width: 200px; font-weight: 900;">
                                    ↩️ VOLTAR STATUS
                                </button>
                            </form>
                        ` : ''}
                    </div>
                `;
            } catch (error) {
                console.error('Erro ao carregar detalhes:', error);
                content.innerHTML = `
                    <div class="text-center py-8">
                        <p class="text-red-600 font-medium">❌ Erro ao carregar detalhes da OS</p>
                        <p class="text-gray-500 text-sm mt-2">${error.message}</p>
                    </div>
                `;
            }
        }
        
        function closeOsDetailsModal() {
            document.getElementById('os-details-modal').classList.add('hidden');
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
    </script>
</x-app-layout>

