@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-8">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Contas a Receber</h1>
                <p class="mt-2 text-sm text-gray-600">Gerencie as contas a receber</p>
            </div>
            <a href="{{ route('finance.receivables.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nova Conta
            </a>
        </div>

        <!-- Resumo -->
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
            <form method="GET" action="{{ route('finance.receivables.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">De</label>
                    <input type="date" name="from" value="{{ request('from') }}" 
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Até</label>
                    <input type="date" name="to" value="{{ request('to') }}" 
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        🔍 Filtrar
                    </button>
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

                @if($receivables->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Origem</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimento</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor Original</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saldo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($receivables as $receivable)
                                    @php
                                        $isPaid = $receivable->status === 'paid';
                                        $isOverdue = $receivable->isOverdue();
                                        $daysUntilDue = now()->diffInDays($receivable->due_date, false);
                                        $isDueToday = $daysUntilDue === 0;
                                        $isDueSoon = $daysUntilDue > 0 && $daysUntilDue <= 3;
                                        
                                        // Determinar classe de cor da linha
                                        $rowClass = '';
                                        if ($isPaid) {
                                            $rowClass = 'bg-gray-50 text-gray-500';
                                        } elseif ($isOverdue) {
                                            $rowClass = 'bg-red-50 border-l-4 border-red-500';
                                        } elseif ($isDueToday) {
                                            $rowClass = 'bg-orange-50 border-l-4 border-orange-500';
                                        } elseif ($isDueSoon) {
                                            $rowClass = 'bg-yellow-50 border-l-4 border-yellow-500';
                                        }
                                    @endphp
                                    <tr class="{{ $rowClass }} hover:bg-opacity-80 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm {{ $isPaid ? 'text-gray-500' : 'text-gray-900' }}">
                                            {{ $receivable->customer->name ?? 'N/A' }}
                                            @if($receivable->installments > 1)
                                                <span class="text-xs text-gray-400">({{ $receivable->installment_number }}/{{ $receivable->installments }})</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm {{ $isPaid ? 'text-gray-500' : 'text-gray-700' }}">
                                            @if($receivable->os_id && $receivable->serviceOrder)
                                                <span class="text-blue-600 font-medium">
                                                    🔧 {{ $receivable->serviceOrder->os_number ?? 'OS-' . $receivable->os_id }}
                                                </span>
                                                @if(str_contains($receivable->note ?? '', 'Saldo de pagamento sinal'))
                                                    <span class="text-xs text-gray-500 block">Saldo de sinal</span>
                                                @endif
                                            @elseif($receivable->sale_id && $receivable->sale)
                                                <span class="text-green-600 font-medium">
                                                    💰 {{ $receivable->sale->sale_number ?? 'V-' . $receivable->sale_id }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">Manual</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm {{ $isPaid ? 'text-gray-500' : ($isOverdue ? 'text-red-700 font-semibold' : ($isDueToday ? 'text-orange-700 font-semibold' : ($isDueSoon ? 'text-yellow-700 font-semibold' : 'text-gray-500'))) }}">
                                            {{ $receivable->due_date->format('d/m/Y') }}
                                            @if($isOverdue)
                                                <span class="text-xs text-red-600 font-bold">({{ abs($daysUntilDue) }}d atrasado)</span>
                                            @elseif($isDueToday)
                                                <span class="text-xs text-orange-600 font-bold">(Vence hoje!)</span>
                                            @elseif($isDueSoon)
                                                <span class="text-xs text-yellow-600 font-bold">(Vence em {{ $daysUntilDue }}d)</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm {{ $isPaid ? 'text-gray-500' : 'text-gray-900' }}">
                                            R$ {{ number_format($receivable->original_amount, 2, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm {{ $isPaid ? 'text-gray-500' : ($isOverdue ? 'text-red-700 font-bold' : 'text-gray-900') }}">
                                            R$ {{ number_format($receivable->balance_amount, 2, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($receivable->status === 'paid')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-700">✓ Pago</span>
                                            @elseif($receivable->status === 'partial')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Parcial</span>
                                            @elseif($isOverdue)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-200 text-red-900 font-bold">⚠ Vencido</span>
                                            @elseif($isDueToday)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-200 text-orange-900 font-bold">Vence Hoje</span>
                                            @elseif($isDueSoon)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-200 text-yellow-900">Vence em {{ $daysUntilDue }}d</span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Aberto</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center space-x-2">
                                                @if($receivable->status !== 'canceled')
                                                    <button onclick="receivePayment({{ $receivable->id }})" class="text-blue-600 hover:text-blue-900 px-2 py-1 rounded hover:bg-blue-50 font-medium">
                                                        💰 Receber
                                                    </button>
                                                @endif
                                                <a href="{{ route('finance.receivables.show', $receivable) }}" class="text-gray-600 hover:text-gray-900 px-2 py-1 rounded hover:bg-gray-50">
                                                    👁️ Ver
                                                </a>
                                                <a href="{{ route('finance.receivables.pdf', $receivable) }}" target="_blank" class="text-red-600 hover:text-red-900 px-2 py-1 rounded hover:bg-red-50">
                                                    📄 PDF
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $receivables->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <p class="text-gray-500">Nenhuma conta a receber encontrada.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal para Receber Pagamento -->
<div id="receiveModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; display: none; align-items: center; justify-content: center; padding: 1rem; overflow-y: auto;">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full" style="margin: auto; max-height: calc(100vh - 2rem); overflow-y: auto; position: relative;">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Registrar Pagamento</h2>
                <button onclick="closeReceiveModal()" class="text-gray-500 hover:text-gray-700 text-2xl font-bold">&times;</button>
            </div>
            <form id="receiveForm" onsubmit="submitReceivePayment(event)">
                @csrf
                <input type="hidden" id="receivable_id" name="receivable_id">
                <input type="hidden" id="payment_account" name="account_id" value="">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor *</label>
                    <input type="number" step="0.01" min="0.01" id="payment_amount" name="amount" required
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="0,00">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Método de Pagamento *</label>
                    <select id="payment_method" name="method" required
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Selecione</option>
                        <option value="money">💵 Dinheiro</option>
                        <option value="pix">📱 PIX</option>
                        <option value="card">💳 Cartão</option>
                        <option value="boleto">📄 Boleto</option>
                        <option value="other">➕ Outros</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data do Pagamento *</label>
                    <input type="date" id="payment_date" name="paid_at" required
                           value="{{ now()->format('Y-m-d') }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Taxa (opcional)</label>
                    <input type="number" step="0.01" min="0" id="payment_fee" name="gateway_fee_amount"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="0,00">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea id="payment_note" name="note" rows="3"
                              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                              placeholder="Observações sobre o pagamento"></textarea>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeReceiveModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Registrar Pagamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
async function openReceiveModal(receivableId) {
    document.getElementById('receivable_id').value = receivableId;
    const modal = document.getElementById('receiveModal');
    
    // Buscar informações da conta para mostrar no modal
    try {
        const response = await fetch(`{{ url('finance/receivables') }}/${receivableId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data.receivable) {
                const receivable = data.receivable;
                // Atualizar placeholder do valor com o saldo atual
                const amountField = document.getElementById('payment_amount');
                if (amountField && receivable.balance_amount > 0) {
                    amountField.placeholder = `Saldo: R$ ${parseFloat(receivable.balance_amount).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                }
            }
        }
    } catch (error) {
        console.error('Erro ao buscar dados da conta:', error);
    }
    
    // Remover hidden e mostrar como flex
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    
    // Garantir que o modal esteja no topo da viewport
    modal.scrollTop = 0;
    window.scrollTo(0, 0);
    
    // Focar no primeiro campo após um pequeno delay
    setTimeout(() => {
        const amountField = document.getElementById('payment_amount');
        if (amountField) {
            amountField.focus();
            amountField.select();
        }
    }, 150);
}

function closeReceiveModal() {
    const modal = document.getElementById('receiveModal');
    modal.classList.add('hidden');
    modal.style.display = 'none';
    document.getElementById('receiveForm').reset();
}

async function submitReceivePayment(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const receivableId = formData.get('receivable_id');
    
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processando...';
    
    try {
        // Validar campos obrigatórios
        const amount = parseFloat(formData.get('amount'));
        const method = formData.get('method');
        const paidAt = formData.get('paid_at');
        
        if (!amount || amount <= 0) {
            alert('❌ Por favor, informe um valor válido.');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            return;
        }
        
        if (!method) {
            alert('❌ Por favor, selecione um método de pagamento.');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            return;
        }
        
        if (!paidAt) {
            alert('❌ Por favor, informe a data do pagamento.');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            return;
        }
        
        const response = await fetch(`{{ url('finance/receivables') }}/${receivableId}/receive`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                amount: amount,
                account_id: null, // Será buscado automaticamente no backend
                method: method,
                paid_at: paidAt,
                gateway_fee_amount: parseFloat(formData.get('gateway_fee_amount') || 0),
                note: formData.get('note') || null
            })
        });
        
        // Verificar se a resposta é JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Resposta não é JSON:', text);
            throw new Error('Resposta inválida do servidor');
        }
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || data.message || 'Erro ao registrar pagamento');
        }
        
        if (data.success) {
            alert('✅ Pagamento registrado com sucesso!');
            window.location.reload();
        } else {
            alert('❌ Erro: ' + (data.error || 'Erro ao registrar pagamento'));
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('❌ Erro ao registrar pagamento: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}


function receivePayment(id) {
    openReceiveModal(id);
}
</script>
@endsection

