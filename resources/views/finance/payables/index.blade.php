@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-8">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Contas a Pagar</h1>
                <p class="mt-2 text-sm text-gray-600">Gerencie as contas a pagar</p>
            </div>
            <a href="{{ route('finance.payables.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nova Conta
            </a>
        </div>

        <!-- Resumo Financeiro -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-sm font-medium text-gray-500">Total Aberto</div>
                <div class="text-2xl font-bold text-red-600">R$ {{ number_format($totalOpen ?? 0, 2, ',', '.') }}</div>
            </div>
            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-sm font-medium text-gray-500">Total Parcial</div>
                <div class="text-2xl font-bold text-yellow-600">R$ {{ number_format($totalPartial ?? 0, 2, ',', '.') }}</div>
            </div>
            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-sm font-medium text-gray-500">Total Pago</div>
                <div class="text-2xl font-bold text-green-600">R$ {{ number_format($totalPaid ?? 0, 2, ',', '.') }}</div>
            </div>
            <div class="bg-white shadow rounded-lg p-4">
                <div class="text-sm font-medium text-gray-500">Vencidas</div>
                <div class="text-2xl font-bold text-red-800">R$ {{ number_format($totalOverdue ?? 0, 2, ',', '.') }}</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white shadow rounded-lg p-4 mb-4">
            <form method="GET" action="{{ route('finance.payables.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Aberto</option>
                        <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Parcial</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Pago</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Visualização</label>
                    <select name="group" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="auto" {{ (request('group', 'auto') ?? 'auto') === 'auto' ? 'selected' : '' }}>Agrupado (Recomendado)</option>
                        <option value="grouped" {{ (request('group') ?? '') === 'grouped' ? 'selected' : '' }}>Apenas Primeiras Parcelas</option>
                        <option value="all" {{ (request('group') ?? '') === 'all' ? 'selected' : '' }}>Lista Completa</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">De</label>
                    <input type="date" name="from" value="{{ request('from') }}" 
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Até</label>
                    <input type="date" name="to" value="{{ request('to') }}" 
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        🔍 Filtrar
                    </button>
                    <a href="{{ route('finance.payables.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        🔄
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white shadow rounded-lg">
            <div class="p-6">
                @if(isset($error))
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                        <p class="text-yellow-800">{{ $error }}</p>
                    </div>
                @endif

                @php
                    $viewMode = request('view', 'table') ?? 'table'; // table ou cards
                    $groupedPayables = $groupedPayables ?? [];
                @endphp
                
                <div class="flex justify-between items-center mb-4">
                    <div class="text-sm text-gray-600">
                        Mostrando {{ $payables->firstItem() ?? 0 }} - {{ $payables->lastItem() ?? 0 }} de {{ $payables->total() }} contas
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600">Visualização:</span>
                        <a href="{{ request()->url() . '?' . http_build_query(array_merge(request()->query(), ['view' => 'table'])) }}" 
                           class="px-3 py-1 rounded {{ $viewMode === 'table' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                            📊 Tabela
                        </a>
                        <a href="{{ request()->url() . '?' . http_build_query(array_merge(request()->query(), ['view' => 'cards'])) }}" 
                           class="px-3 py-1 rounded {{ $viewMode === 'cards' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                            🎴 Cards
                        </a>
                    </div>
                </div>

                @if(isset($payables) && $payables->count() > 0)
                    @if($viewMode === 'cards')
                        <!-- Visualização em Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($payables as $payable)
                                @php
                                    $recurringGroupId = $payable->recurring_group_id ?? null;
                                    $hasGroup = $recurringGroupId && isset($groupedPayables[$recurringGroupId]);
                                    $groupPayables = $hasGroup ? $groupedPayables[$recurringGroupId] : collect([$payable]);
                                    $totalGroupAmount = $hasGroup ? $groupPayables->sum('original_amount') : $payable->original_amount;
                                    $totalGroupBalance = $hasGroup ? $groupPayables->sum('balance_amount') : $payable->balance_amount;
                                    $paidCount = $hasGroup ? $groupPayables->where('status', 'paid')->count() : ($payable->status === 'paid' ? 1 : 0);
                                    $totalInstallments = $hasGroup ? $groupPayables->count() : ($payable->installments ?? 1);
                                    $isRecurring = $payable->is_recurring ?? false;
                                @endphp
                                
                                <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-lg transition-shadow">
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-900">
                                                @if($payable->supplier)
                                                    {{ $payable->supplier->trade_name ?? $payable->supplier->legal_name ?? 'N/A' }}
                                                @else
                                                    N/A
                                                @endif
                                            </h3>
                                            @if($hasGroup && $totalInstallments > 1)
                                                <span class="text-xs text-blue-600 font-semibold">
                                                    📋 {{ $totalInstallments }} parcelas
                                                </span>
                                            @endif
                                            @if($isRecurring)
                                                <span class="text-xs text-blue-600" title="Recorrente">🔄</span>
                                            @endif
                                        </div>
                                        @if($payable->status === 'paid')
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Pago</span>
                                        @elseif($payable->status === 'partial')
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">Parcial</span>
                                        @else
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">Aberto</span>
                                        @endif
                                    </div>
                                    
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Vencimento:</span>
                                            <span class="font-medium">
                                                @if($payable->due_date)
                                                    {{ $payable->due_date->format('d/m/Y') }}
                                                    @if(method_exists($payable, 'isOverdue') && $payable->isOverdue())
                                                        <span class="text-red-600">⚠️</span>
                                                    @endif
                                                @else
                                                    N/A
                                                @endif
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Valor:</span>
                                            <span class="font-semibold">R$ {{ number_format($totalGroupAmount, 2, ',', '.') }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Saldo:</span>
                                            <span class="font-bold text-red-600">R$ {{ number_format($totalGroupBalance, 2, ',', '.') }}</span>
                                        </div>
                                        @if($hasGroup && $totalInstallments > 1)
                                        <div class="flex justify-between text-xs text-gray-500 pt-2 border-t">
                                            <span>Progresso:</span>
                                            <span>{{ $paidCount }}/{{ $totalInstallments }} pagas</span>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <div class="mt-4 flex space-x-2">
                                        @php
                                            $canPay = $payable->status !== 'paid';
                                            if ($hasGroup) {
                                                $canPay = $canPay && $groupPayables->filter(function($p) { return $p->status !== 'paid'; })->count() > 0;
                                            }
                                        @endphp
                                        @if($canPay)
                                            <a href="{{ route('finance.payables.show', $payable) }}" 
                                               class="flex-1 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-center text-sm font-semibold">
                                                💰 Pagar
                                            </a>
                                        @endif
                                        <a href="{{ route('finance.payables.show', $payable) }}" 
                                           class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
                                            👁️ Ver
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <!-- Visualização em Tabela -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fornecedor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimento</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor Original</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saldo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($payables as $payable)
                                    @php
                                        $recurringGroupId = $payable->recurring_group_id ?? null;
                                        $hasGroup = $recurringGroupId && isset($groupedPayables[$recurringGroupId]);
                                        $groupPayables = $hasGroup ? $groupedPayables[$recurringGroupId] : collect([$payable]);
                                        $totalGroupAmount = $hasGroup ? $groupPayables->sum('original_amount') : $payable->original_amount;
                                        $totalGroupBalance = $hasGroup ? $groupPayables->sum('balance_amount') : $payable->balance_amount;
                                        $paidCount = $hasGroup ? $groupPayables->where('status', 'paid')->count() : ($payable->status === 'paid' ? 1 : 0);
                                        $totalInstallments = $hasGroup ? $groupPayables->count() : ($payable->installments ?? 1);
                                        $isRecurring = $payable->is_recurring ?? false;
                                    @endphp
                                    
                                    <tr class="hover:bg-gray-50" data-group-id="{{ $recurringGroupId ?? 'single-' . $payable->id }}">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div class="flex items-center">
                                                @if($payable->supplier)
                                                    {{ $payable->supplier->trade_name ?? $payable->supplier->legal_name ?? 'N/A' }}
                                                @else
                                                    N/A
                                                @endif
                                                @if($hasGroup && $totalInstallments > 1)
                                                    <span class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-xs font-semibold">
                                                        {{ $totalInstallments }}x
                                                    </span>
                                                @endif
                                                @if($isRecurring)
                                                    <span class="ml-2 text-blue-600" title="Conta Recorrente">🔄</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if($hasGroup && $totalInstallments > 1)
                                                <div>
                                                    <div>
                                                        @if($payable->due_date)
                                                            {{ $payable->due_date->format('d/m/Y') }}
                                                        @else
                                                            N/A
                                                        @endif
                                                        <span class="text-xs text-gray-400">(1ª)</span>
                                                    </div>
                                                    <div class="text-xs text-gray-400">
                                                        @if($groupPayables->last() && $groupPayables->last()->due_date)
                                                            até {{ $groupPayables->last()->due_date->format('d/m/Y') }}
                                                        @else
                                                            até N/A
                                                        @endif
                                                    </div>
                                                </div>
                                            @else
                                                @if($payable->due_date)
                                                    {{ $payable->due_date->format('d/m/Y') }}
                                                    @if(method_exists($payable, 'isOverdue') && $payable->isOverdue())
                                                        <span class="ml-1 text-red-600 font-semibold" title="Vencida há {{ $payable->due_date->diffInDays(now()) }} dia(s)">⚠️</span>
                                                    @endif
                                                @else
                                                    N/A
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            @if($hasGroup && $totalInstallments > 1)
                                                <div>
                                                    <div class="font-semibold">R$ {{ number_format($totalGroupAmount, 2, ',', '.') }}</div>
                                                    <div class="text-xs text-gray-500">R$ {{ number_format($payable->original_amount, 2, ',', '.') }}/parcela</div>
                                                </div>
                                            @else
                                                R$ {{ number_format($payable->original_amount, 2, ',', '.') }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            @if($hasGroup && $totalInstallments > 1)
                                                <div>
                                                    <div class="font-semibold text-red-600">R$ {{ number_format($totalGroupBalance, 2, ',', '.') }}</div>
                                                    <div class="text-xs text-gray-500">{{ $paidCount }}/{{ $totalInstallments }} pagas</div>
                                                </div>
                                            @else
                                                <span class="font-semibold @if($payable->balance_amount > 0) text-red-600 @else text-green-600 @endif">
                                                    R$ {{ number_format($payable->balance_amount, 2, ',', '.') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($hasGroup && $totalInstallments > 1)
                                                @php
                                                    $allPaid = $groupPayables->filter(function($p) { return $p->status === 'paid'; })->count() === $groupPayables->count();
                                                    $anyPartial = $groupPayables->filter(function($p) { return $p->status === 'partial'; })->count() > 0;
                                                @endphp
                                                @if($allPaid)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Todas Pagas</span>
                                                @elseif($anyPartial)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Parcial</span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Aberto</span>
                                                @endif
                                            @else
                                                @if($payable->status === 'paid')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Pago</span>
                                                @elseif($payable->status === 'partial')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Parcial</span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Aberto</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center space-x-2">
                                                @if($hasGroup && $totalInstallments > 1 && $recurringGroupId)
                                                    <button onclick="toggleGroup('{{ $recurringGroupId }}')" 
                                                            class="text-blue-600 hover:text-blue-900 px-2 py-1 rounded hover:bg-blue-50 text-xs"
                                                            title="Ver todas as parcelas">
                                                        📋 {{ $totalInstallments }} parcelas
                                                    </button>
                                                @endif
                                                @php
                                            $canPay = $payable->status !== 'paid';
                                            if ($hasGroup) {
                                                $canPay = $canPay && $groupPayables->filter(function($p) { return $p->status !== 'paid'; })->count() > 0;
                                            }
                                        @endphp
                                        @if($canPay)
                                                    <a href="{{ route('finance.payables.show', $payable) }}" 
                                                       class="text-blue-600 hover:text-blue-900 px-2 py-1 rounded hover:bg-blue-50">
                                                        💰 Pagar
                                                    </a>
                                                @endif
                                                <a href="{{ route('finance.payables.show', $payable) }}" 
                                                   class="text-gray-600 hover:text-gray-900 px-2 py-1 rounded hover:bg-gray-50">
                                                    👁️ Ver
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- Parcelas do grupo (ocultas por padrão) -->
                                    @if($hasGroup && $totalInstallments > 1 && request('group') === 'all' && $recurringGroupId)
                                        @foreach($groupPayables->skip(1) as $installment)
                                            <tr class="bg-gray-50 installment-row installment-{{ $payable->recurring_group_id }}" style="display: none;">
                                                <td class="px-6 py-3 text-sm text-gray-600 pl-12">
                                                    ↳ Parcela {{ $installment->installment_number }}/{{ $totalInstallments }}
                                                </td>
                                                <td class="px-6 py-3 text-sm text-gray-500">
                                                    @if($installment->due_date)
                                                        {{ $installment->due_date->format('d/m/Y') }}
                                                        @if(method_exists($installment, 'isOverdue') && $installment->isOverdue())
                                                            <span class="ml-1 text-red-600">⚠️</span>
                                                        @endif
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                                <td class="px-6 py-3 text-sm text-gray-600">
                                                    R$ {{ number_format($installment->original_amount, 2, ',', '.') }}
                                                </td>
                                                <td class="px-6 py-3 text-sm text-gray-600">
                                                    <span class="@if($installment->balance_amount > 0) text-red-600 @else text-green-600 @endif">
                                                        R$ {{ number_format($installment->balance_amount, 2, ',', '.') }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-3">
                                                    @if($installment->status === 'paid')
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Pago</span>
                                                    @elseif($installment->status === 'partial')
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Parcial</span>
                                                    @else
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Aberto</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-3 text-sm font-medium">
                                                    <div class="flex items-center space-x-2">
                                                        @if($installment->status !== 'paid')
                                                            <a href="{{ route('finance.payables.show', $installment) }}" 
                                                               class="text-blue-600 hover:text-blue-900 px-2 py-1 rounded hover:bg-blue-50 text-xs">
                                                                💰 Pagar
                                                            </a>
                                                        @endif
                                                        <a href="{{ route('finance.payables.show', $installment) }}" 
                                                           class="text-gray-600 hover:text-gray-900 px-2 py-1 rounded hover:bg-gray-50 text-xs">
                                                            👁️ Ver
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    @endif

                    <div class="mt-4">
                        {{ $payables->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <p class="text-gray-500">Nenhuma conta a pagar encontrada.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function toggleGroup(groupId) {
    const rows = document.querySelectorAll(`.installment-${groupId}`);
    rows.forEach(row => {
        row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
    });
}
</script>
@endsection
