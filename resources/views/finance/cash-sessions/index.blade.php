@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Tesouraria - Sessões de Caixa</h1>
        <p class="mt-2 text-sm text-gray-600">Gerencie as sessões de caixa da loja</p>
    </div>

    @if(isset($error) && $error)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <p class="text-yellow-800">{{ $error }}</p>
        </div>
    @endif

    <div class="bg-white shadow-xl rounded-lg overflow-hidden">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Sessões de Caixa</h2>
                <button onclick="openCashSessionModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Abrir Sessão
                </button>
            </div>

            @if(isset($sessions) && $sessions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loja</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conta</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aberto Por</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aberto Em</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Inicial</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($sessions as $session)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $session->store->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $session->account->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $session->openedBy->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $session->opened_at ? $session->opened_at->format('d/m/Y H:i') : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">
                                        R$ {{ number_format($session->opening_amount ?? 0, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($session->status === 'open')
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Aberto
                                            </span>
                                        @else
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                Fechado
                                            </span>
                                            @if($session->closed_at)
                                                <div class="text-xs text-gray-500 mt-1">
                                                    {{ $session->closed_at->format('d/m/Y H:i') }}
                                                </div>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            @if($session->status === 'open')
                                                <button onclick="closeCashSessionModal({{ $session->id }})" 
                                                        class="text-red-600 hover:text-red-900 font-medium">
                                                    Fechar
                                                </button>
                                            @endif
                                            <a href="{{ route('finance.cash.print', $session) }}" 
                                               target="_blank"
                                               class="text-blue-600 hover:text-blue-900 font-medium">
                                                Imprimir
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $sessions->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="mt-4 text-gray-500">Nenhuma sessão de caixa encontrada.</p>
                    <button onclick="openCashSessionModal()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Abrir Primeira Sessão
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Abrir Sessão -->
<div id="openModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Abrir Sessão de Caixa</h3>
                <button onclick="closeOpenModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="openSessionForm" onsubmit="submitOpenSession(event)">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Loja *</label>
                    <select name="store_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione uma loja</option>
                        @if(isset($stores))
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}">@if($store->abbreviation)[{{ $store->abbreviation }}]@endif {{ $store->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Conta *</label>
                    <select name="account_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione uma conta</option>
                        @if(isset($accounts))
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->type }})</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Valor Inicial (R$) *</label>
                    <input type="number" name="opening_amount" step="0.01" min="0" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                           placeholder="0.00">
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeOpenModal()" 
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Abrir Sessão
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Fechar Sessão -->
<div id="closeModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Fechar Sessão de Caixa</h3>
                <button onclick="closeCloseModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="closeSessionForm" onsubmit="submitCloseSession(event)">
                @csrf
                <div id="closeSessionMethods" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Contagem por Método de Pagamento *</label>
                    <div id="methodsContainer" class="space-y-2">
                        <div class="flex items-center space-x-2">
                            <select name="counted_by_method[0][method]" required 
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="money">Dinheiro</option>
                                <option value="pix">PIX</option>
                                <option value="card">Cartão</option>
                                <option value="boleto">Boleto</option>
                                <option value="other">Outros</option>
                            </select>
                            <input type="number" name="counted_by_method[0][amount]" step="0.01" min="0" required 
                                   placeholder="Valor" 
                                   class="w-32 px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                    <button type="button" onclick="addMethod()" 
                            class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                        + Adicionar Método
                    </button>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                    <textarea name="notes" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Observações sobre o fechamento..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeCloseModal()" 
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Fechar Sessão
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentSessionId = null;
let methodCount = 1;

function openCashSessionModal() {
    document.getElementById('openModal').classList.remove('hidden');
}

function closeOpenModal() {
    document.getElementById('openModal').classList.add('hidden');
    document.getElementById('openSessionForm').reset();
}

function closeCashSessionModal(sessionId) {
    currentSessionId = sessionId;
    document.getElementById('closeModal').classList.remove('hidden');
    // Reset form
    methodCount = 1;
    document.getElementById('methodsContainer').innerHTML = `
        <div class="flex items-center space-x-2">
            <select name="counted_by_method[0][method]" required 
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                <option value="money">Dinheiro</option>
                <option value="pix">PIX</option>
                <option value="card">Cartão</option>
                <option value="boleto">Boleto</option>
                <option value="other">Outros</option>
            </select>
            <input type="number" name="counted_by_method[0][amount]" step="0.01" min="0" required 
                   placeholder="Valor" 
                   class="w-32 px-3 py-2 border border-gray-300 rounded-lg">
        </div>
    `;
}

function closeCloseModal() {
    document.getElementById('closeModal').classList.add('hidden');
    currentSessionId = null;
}

function addMethod() {
    const container = document.getElementById('methodsContainer');
    const div = document.createElement('div');
    div.className = 'flex items-center space-x-2';
    div.innerHTML = `
        <select name="counted_by_method[${methodCount}][method]" required 
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
            <option value="money">Dinheiro</option>
            <option value="pix">PIX</option>
            <option value="card">Cartão</option>
            <option value="boleto">Boleto</option>
            <option value="other">Outros</option>
        </select>
        <input type="number" name="counted_by_method[${methodCount}][amount]" step="0.01" min="0" required 
               placeholder="Valor" 
               class="w-32 px-3 py-2 border border-gray-300 rounded-lg">
        <button type="button" onclick="this.parentElement.remove()" 
                class="text-red-600 hover:text-red-800">
            ×
        </button>
    `;
    container.appendChild(div);
    methodCount++;
}

async function submitOpenSession(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('{{ route("finance.cash.open") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Sessão aberta com sucesso!');
            window.location.reload();
        } else {
            alert('Erro: ' + (data.error || 'Erro ao abrir sessão'));
        }
    } catch (error) {
        alert('Erro ao abrir sessão: ' + error.message);
    }
}

async function submitCloseSession(event) {
    event.preventDefault();
    if (!currentSessionId) {
        alert('ID da sessão não encontrado');
        return;
    }
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Converter counted_by_method para JSON
    const methods = {};
    formData.getAll('counted_by_method').forEach((item, index) => {
        // Esta lógica precisa ser ajustada para o formato correto
    });
    
    const data = {};
    const methodInputs = form.querySelectorAll('[name^="counted_by_method"]');
    const methodsArray = [];
    
    for (let i = 0; i < methodInputs.length; i += 2) {
        const method = methodInputs[i].value;
        const amount = parseFloat(methodInputs[i + 1].value);
        if (method && amount) {
            methodsArray.push({ method, amount });
        }
    }
    
    data.counted_by_method = methodsArray;
    data.notes = formData.get('notes') || '';
    
    try {
        const response = await fetch(`/finance/cash-sessions/${currentSessionId}/close`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Sessão fechada com sucesso!');
            window.location.reload();
        } else {
            alert('Erro: ' + (result.error || 'Erro ao fechar sessão'));
        }
    } catch (error) {
        alert('Erro ao fechar sessão: ' + error.message);
    }
}
</script>
@endsection
