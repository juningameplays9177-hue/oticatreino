<x-app-layout title="Cliente: {{ $client->name }}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Cabeçalho -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $client->name }}</h1>
                <p class="text-sm text-gray-600 mt-1">
                    @if($client->cpf_cnpj)
                        CPF/CNPJ: {{ $client->cpf_cnpj }}
                    @endif
                    @if($client->type)
                        | Tipo: {{ ucfirst($client->type) }}
                    @endif
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('clients.edit', $client) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    ✏️ Editar
                </a>
                <a href="{{ route('clients.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    ← Voltar
                </a>
            </div>
        </div>

        <!-- Cards de Resumo -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-sm font-medium text-gray-500">Total em Vendas</div>
                <div class="text-2xl font-bold text-green-600">R$ {{ number_format($totalSales ?? 0, 2, ',', '.') }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ count($sales) }} venda(s)</div>
            </div>
            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-sm font-medium text-gray-500">Total em O.S.</div>
                <div class="text-2xl font-bold text-blue-600">R$ {{ number_format($totalServiceOrders ?? 0, 2, ',', '.') }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ count($serviceOrders) }} O.S.</div>
            </div>
            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-sm font-medium text-gray-500">Pendências</div>
                <div class="text-2xl font-bold text-red-600">R$ {{ number_format($totalReceivables ?? 0, 2, ',', '.') }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ count($receivables) }} conta(s)</div>
            </div>
            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-sm font-medium text-gray-500">Vencidas</div>
                <div class="text-2xl font-bold text-red-800">R$ {{ number_format($totalOverdue ?? 0, 2, ',', '.') }}</div>
                <div class="text-xs text-red-600 mt-1">⚠️ Atenção</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Coluna Esquerda: Informações Básicas -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">📋 Informações Básicas</h2>
                    
                    <div class="space-y-3">
                        @if($client->code)
                            <div>
                                <span class="text-sm font-medium text-gray-500">Código:</span>
                                <span class="ml-2 text-gray-900 font-semibold text-blue-600">{{ $client->code }}</span>
                            </div>
                        @endif
                        
                        @if($client->type)
                            <div>
                                <span class="text-sm font-medium text-gray-500">Tipo:</span>
                                <span class="ml-2 text-gray-900">{{ ucfirst($client->type) }}</span>
                            </div>
                        @endif
                        
                        @if($client->birth_date)
                            <div>
                                <span class="text-sm font-medium text-gray-500">Data de Nascimento:</span>
                                <span class="ml-2 text-gray-900">{{ $client->birth_date->format('d/m/Y') }}</span>
                            </div>
                        @endif
                        
                        @if($client->sex)
                            <div>
                                <span class="text-sm font-medium text-gray-500">Sexo:</span>
                                <span class="ml-2 text-gray-900">{{ ucfirst($client->sex) }}</span>
                            </div>
                        @endif
                        
                        @if($client->address)
                            <div>
                                <span class="text-sm font-medium text-gray-500">Endereço:</span>
                                <div class="ml-2 text-gray-900">
                                    {{ $client->address }}
                                    @if($client->number), {{ $client->number }}@endif
                                    @if($client->complement) - {{ $client->complement }}@endif
                                </div>
                                @if($client->district || $client->city)
                                    <div class="ml-2 text-sm text-gray-600">
                                        @if($client->district){{ $client->district }}@endif
                                        @if($client->district && $client->city) - @endif
                                        @if($client->city){{ $client->city }}@endif
                                        @if($client->state) / {{ $client->state }}@endif
                                        @if($client->cep) - CEP: {{ $client->cep }}@endif
                                    </div>
                                @endif
                            </div>
                        @endif
                        
                        @if($client->phones->count() > 0)
                            <div>
                                <span class="text-sm font-medium text-gray-500">Telefones:</span>
                                <div class="ml-2 space-y-1">
                                    @foreach($client->phones as $phone)
                                        <div class="text-gray-900">
                                            {{ $phone->phone }}
                                            @if($phone->label) <span class="text-xs text-gray-500">({{ $phone->label }})</span>@endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        
                        @if($client->emails->count() > 0)
                            <div>
                                <span class="text-sm font-medium text-gray-500">E-mails:</span>
                                <div class="ml-2 space-y-1">
                                    @foreach($client->emails as $email)
                                        <div class="text-gray-900">
                                            {{ $email->email }}
                                            @if($email->label) <span class="text-xs text-gray-500">({{ $email->label }})</span>@endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        
                        @if($client->notes)
                            <div>
                                <span class="text-sm font-medium text-gray-500">Observações:</span>
                                <div class="ml-2 text-gray-900 whitespace-pre-wrap">{{ $client->notes }}</div>
                            </div>
                        @endif
                        
                        <div>
                            <span class="text-sm font-medium text-gray-500">Status:</span>
                            @if($client->active)
                                <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Ativo</span>
                            @else
                                <span class="ml-2 px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">Inativo</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coluna Direita: Histórico -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Pendências Financeiras -->
                @if(count($receivables) > 0)
                    <div class="bg-white shadow rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold text-gray-900">💰 Pendências Financeiras</h2>
                            <a href="{{ route('finance.receivables.index', ['customer_id' => $client->id]) }}" class="text-sm text-blue-600 hover:text-blue-800">
                                Ver todas →
                            </a>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimento</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($receivables->take(10) as $receivable)
                                        @php
                                            $isOverdue = $receivable->isOverdue();
                                            $daysUntilDue = now()->diffInDays($receivable->due_date, false);
                                            $isDueToday = $daysUntilDue === 0;
                                            $isDueSoon = $daysUntilDue > 0 && $daysUntilDue <= 3;
                                        @endphp
                                        <tr class="{{ $isOverdue ? 'bg-red-50' : ($isDueToday ? 'bg-orange-50' : ($isDueSoon ? 'bg-yellow-50' : '')) }}">
                                            <td class="px-4 py-3 text-sm {{ $isOverdue ? 'text-red-700 font-semibold' : ($isDueToday ? 'text-orange-700 font-semibold' : ($isDueSoon ? 'text-yellow-700 font-semibold' : 'text-gray-500')) }}">
                                                {{ $receivable->due_date->format('d/m/Y') }}
                                                @if($isOverdue)
                                                    <span class="text-xs text-red-600 font-bold">({{ abs($daysUntilDue) }}d atrasado)</span>
                                                @elseif($isDueToday)
                                                    <span class="text-xs text-orange-600 font-bold">(Vence hoje!)</span>
                                                @elseif($isDueSoon)
                                                    <span class="text-xs text-yellow-600 font-bold">(Vence em {{ $daysUntilDue }}d)</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                                                R$ {{ number_format($receivable->balance_amount, 2, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                @if($receivable->status === 'paid')
                                                    <span class="px-2 py-1 bg-gray-200 text-gray-700 rounded-full text-xs font-semibold">✓ Pago</span>
                                                @elseif($receivable->status === 'partial')
                                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">Parcial</span>
                                                @elseif($isOverdue)
                                                    <span class="px-2 py-1 bg-red-200 text-red-900 rounded-full text-xs font-semibold font-bold">⚠ Vencido</span>
                                                @else
                                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">Aberto</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <a href="{{ route('finance.receivables.show', $receivable) }}" class="text-blue-600 hover:text-blue-800">
                                                    Ver →
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Histórico de Vendas -->
                @if(count($sales) > 0)
                    <div class="bg-white shadow rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold text-gray-900">🛍️ Histórico de Vendas</h2>
                            <span class="text-sm text-gray-500">{{ count($sales) }} venda(s)</span>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nº Venda</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loja</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($sales->take(10) as $sale)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-500">
                                                {{ $sale->sale_date->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                                {{ $sale->sale_number ?? '#' . $sale->id }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500">
                                                {{ $sale->store->name ?? 'N/A' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">
                                                R$ {{ number_format($sale->total_net, 2, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <a href="#" class="text-blue-600 hover:text-blue-800">Ver →</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Histórico de Ordens de Serviço -->
                @if(count($serviceOrders) > 0)
                    <div class="bg-white shadow rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold text-gray-900">🔧 Histórico de O.S.</h2>
                            <span class="text-sm text-gray-500">{{ count($serviceOrders) }} O.S.</span>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nº O.S.</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($serviceOrders->take(10) as $os)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-500">
                                                {{ $os->registered_at->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                                {{ $os->os_number }}
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                @php
                                                    $statusColors = [
                                                        'REGISTRADA' => 'bg-blue-100 text-blue-800',
                                                        'EM_PRODUCAO' => 'bg-yellow-100 text-yellow-800',
                                                        'PRONTA' => 'bg-purple-100 text-purple-800',
                                                        'ENTREGUE' => 'bg-green-100 text-green-800',
                                                        'CANCELADA' => 'bg-red-100 text-red-800',
                                                    ];
                                                    $color = $statusColors[$os->status] ?? 'bg-gray-100 text-gray-800';
                                                @endphp
                                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $color }}">
                                                    {{ $os->status }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">
                                                R$ {{ number_format($os->total_value, 2, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <a href="{{ route('os.show', $os) }}" class="text-blue-600 hover:text-blue-800">Ver →</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Mensagem se não houver dados -->
                @if(count($sales) === 0 && count($serviceOrders) === 0 && count($receivables) === 0)
                    <div class="bg-white shadow rounded-lg p-12 text-center">
                        <p class="text-gray-500 text-lg">Nenhum histórico encontrado para este cliente.</p>
                        <p class="text-gray-400 text-sm mt-2">Vendas, O.S. e pendências financeiras aparecerão aqui.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

