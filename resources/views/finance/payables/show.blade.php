@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Detalhes da Conta a Pagar</h1>
            <div class="flex space-x-2">
                <a href="{{ route('finance.payables.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    ← Voltar
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                <p class="text-green-800">{{ session('success') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Informações Principais -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Informações da Conta</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Dados Básicos</h3>
                            <div class="space-y-2">
                                <p><strong>Número:</strong> #{{ $payable->id }}</p>
                                <p><strong>Fornecedor:</strong> {{ $payable->supplier->trade_name ?? $payable->supplier->legal_name ?? 'N/A' }}</p>
                                @if($payable->document_no)
                                <p><strong>Documento:</strong> {{ $payable->document_no }}</p>
                                @endif
                                @if($payable->store)
                                <p><strong>Loja:</strong> {{ $payable->store->name }}</p>
                                @endif
                                @if($payable->category)
                                <p><strong>Categoria:</strong> {{ $payable->category->name }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Valores e Status</h3>
                            <div class="space-y-2">
                                <p><strong>Data de Emissão:</strong> {{ $payable->issue_date->format('d/m/Y') }}</p>
                                <p><strong>Data de Vencimento:</strong> {{ $payable->due_date->format('d/m/Y') }}</p>
                                <p><strong>Valor Original:</strong> R$ {{ number_format($payable->original_amount, 2, ',', '.') }}</p>
                                <p><strong>Valor Pago:</strong> R$ {{ number_format($payable->getPaidAmount(), 2, ',', '.') }}</p>
                                <p><strong>Saldo Devedor:</strong> <span class="font-bold text-red-600">R$ {{ number_format($payable->balance_amount, 2, ',', '.') }}</span></p>
                                <p><strong>Status:</strong> 
                                    @if($payable->status === 'paid')
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">PAGO</span>
                                    @elseif($payable->status === 'partial')
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">PAGO PARCIALMENTE</span>
                                    @else
                                        <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">ABERTO</span>
                                    @endif
                                </p>
                                @if($payable->isOverdue())
                                <p class="text-red-600 font-semibold">⚠️ Vencida há {{ $payable->due_date->diffInDays(now()) }} dia(s)</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($payable->is_recurring || $payable->installments > 1)
                    <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <h3 class="text-sm font-semibold text-blue-900 mb-2">📅 Informações de Parcelamento/Recorrência</h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            @if($payable->installments > 1)
                            <div>
                                <strong>Parcela:</strong> {{ $payable->installment_number }}/{{ $payable->installments }}
                            </div>
                            @endif
                            @if($payable->is_recurring)
                            <div>
                                <strong>Recorrente:</strong> 
                                @php
                                    $types = [
                                        'daily' => 'Diária',
                                        'weekly' => 'Semanal',
                                        'biweekly' => 'Quinzenal',
                                        'monthly' => 'Mensal',
                                        'bimonthly' => 'Bimestral',
                                        'quarterly' => 'Trimestral',
                                        'semiannual' => 'Semestral',
                                        'yearly' => 'Anual',
                                    ];
                                @endphp
                                {{ $types[$payable->recurring_type] ?? $payable->recurring_type }}
                            </div>
                            @if($payable->recurring_end_date)
                            <div>
                                <strong>Término:</strong> {{ $payable->recurring_end_date->format('d/m/Y') }}
                            </div>
                            @else
                            <div>
                                <strong>Término:</strong> Indefinido
                            </div>
                            @endif
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($payable->note)
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-700 mb-1">Observações</h3>
                        <p class="text-sm text-gray-600">{{ $payable->note }}</p>
                    </div>
                    @endif
                </div>

                <!-- Parcelas Relacionadas -->
                @if($payable->recurring_group_id)
                @php
                    $relatedPayables = \App\Models\Finance\Payable::where('recurring_group_id', $payable->recurring_group_id)
                        ->where('id', '!=', $payable->id)
                        ->orderBy('due_date')
                        ->get();
                @endphp
                @if($relatedPayables->count() > 0)
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Outras Parcelas do Grupo</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parcela</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimento</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($relatedPayables as $related)
                                <tr>
                                    <td class="px-4 py-3 text-sm">{{ $related->installment_number }}/{{ $related->installments }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $related->due_date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold">R$ {{ number_format($related->original_amount, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($related->status === 'paid')
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Pago</span>
                                        @elseif($related->status === 'partial')
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Parcial</span>
                                        @else
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Aberto</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <a href="{{ route('finance.payables.show', $related) }}" class="text-blue-600 hover:text-blue-900">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
                @endif

                <!-- Histórico de Pagamentos -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Histórico de Pagamentos</h2>
                    @if($payable->payments->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data/Hora</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Método</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Conta</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($payable->payments as $payment)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $payment->paid_at->format('d/m/Y H:i') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">R$ {{ number_format($payment->amount, 2, ',', '.') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ucfirst($payment->method) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $payment->account->name ?? 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8 bg-gray-50 rounded-lg">
                            <p class="text-gray-500">Nenhum pagamento registrado ainda.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar - Ações -->
            <div class="space-y-6">
                @if($payable->status !== 'paid')
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Ações</h3>
                    <button onclick="openPayModal({{ $payable->id }})" 
                            class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold mb-3">
                        💰 Registrar Pagamento
                    </button>
                    <p class="text-xs text-gray-500 text-center">
                        Saldo: R$ {{ number_format($payable->balance_amount, 2, ',', '.') }}
                    </p>
                </div>
                @endif

                <!-- Resumo Financeiro -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Resumo</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Valor Original:</span>
                            <span class="text-sm font-semibold">R$ {{ number_format($payable->original_amount, 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Total Pago:</span>
                            <span class="text-sm font-semibold text-green-600">R$ {{ number_format($payable->getPaidAmount(), 2, ',', '.') }}</span>
                        </div>
                        <div class="border-t pt-3 flex justify-between">
                            <span class="text-sm font-semibold text-gray-700">Saldo Devedor:</span>
                            <span class="text-sm font-bold text-red-600">R$ {{ number_format($payable->balance_amount, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Pagar -->
<div id="payModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Registrar Pagamento</h2>
                <button onclick="closePayModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <form id="payForm" onsubmit="submitPayment(event)">
                @csrf
                <input type="hidden" id="payable_id" name="payable_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor *</label>
                    <input type="number" step="0.01" min="0.01" id="payment_amount" name="amount" required
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-lg"
                           placeholder="0,00">
                    <p class="text-xs text-gray-500 mt-1">Saldo disponível: <span id="available-balance" class="font-semibold">R$ 0,00</span></p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Conta *</label>
                    <select id="payment_account" name="account_id" required
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Selecione uma conta</option>
                        @php
                            $accounts = \App\Models\Finance\Account::where('company_id', auth()->user()->company_id ?? 1)
                                ->where('is_active', true)
                                ->get();
                        @endphp
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }} ({{ ucfirst($account->type) }})</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Método de Pagamento *</label>
                    <select id="payment_method" name="method" required
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Selecione</option>
                        <option value="pix">📱 PIX</option>
                        <option value="ted">🏦 TED</option>
                        <option value="boleto">📄 Boleto</option>
                        <option value="cash">💵 Dinheiro</option>
                        <option value="card">💳 Cartão</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data do Pagamento *</label>
                    <input type="date" id="payment_date" name="paid_at" required
                           value="{{ now()->format('Y-m-d') }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea id="payment_note" name="note" rows="3"
                              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                              placeholder="Observações sobre o pagamento"></textarea>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closePayModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
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
function openPayModal(payableId) {
    document.getElementById('payable_id').value = payableId;
    const balance = {{ $payable->balance_amount }};
    document.getElementById('available-balance').textContent = 'R$ ' + balance.toFixed(2).replace('.', ',');
    document.getElementById('payment_amount').max = balance;
    document.getElementById('payment_amount').value = balance;
    document.getElementById('payModal').classList.remove('hidden');
}

function closePayModal() {
    document.getElementById('payModal').classList.add('hidden');
    document.getElementById('payForm').reset();
}

async function submitPayment(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const payableId = formData.get('payable_id');
    
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processando...';
    
    try {
        const response = await fetch(`{{ url('finance/payables') }}/${payableId}/pay`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                amount: parseFloat(formData.get('amount')),
                account_id: parseInt(formData.get('account_id')),
                method: formData.get('method'),
                paid_at: formData.get('paid_at'),
                note: formData.get('note')
            })
        });
        
        const data = await response.json();
        
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
        alert('❌ Erro ao registrar pagamento. Verifique sua conexão.');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}
</script>
@endsection
