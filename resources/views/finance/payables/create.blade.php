@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-8">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Nova Conta a Pagar</h1>
                <p class="mt-2 text-sm text-gray-600">Cadastre uma nova conta a pagar</p>
            </div>
            <a href="{{ route('finance.payables.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                ← Voltar
            </a>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <p class="text-red-800">{{ session('error') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('finance.payables.store') }}">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @if(auth()->user()->isAdmin() && isset($selectedStoreId) && $selectedStoreId)
                        <!-- Admin: loja vem da sessão do dashboard, campo hidden -->
                        <input type="hidden" name="store_id" value="{{ $selectedStoreId }}">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Loja *</label>
                            <div class="w-full text-sm px-4 py-2 rounded-lg border-2 border-blue-400 bg-blue-50 font-semibold">
                                @php
                                    $selectedStore = $stores->firstWhere('id', $selectedStoreId);
                                @endphp
                                @if($selectedStore)
                                    @if($selectedStore->abbreviation)[{{ $selectedStore->abbreviation }}]@endif {{ $selectedStore->name }}
                                @else
                                    <span class="text-red-600 font-bold">⚠️ Nenhuma loja selecionada no dashboard</span>
                                @endif
                            </div>
                            <p class="mt-2 text-xs text-gray-600">
                                💡 Para alterar a loja, <a href="{{ route('dashboard') }}" class="text-blue-600 underline font-bold">selecione no dashboard</a>
                            </p>
                        </div>
                    @else
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Loja *</label>
                            <select name="store_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Selecione uma loja</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>
                                        @if($store->abbreviation)[{{ $store->abbreviation }}]@endif {{ $store->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('store_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fornecedor (Opcional)</label>
                        <select name="supplier_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Selecione um fornecedor</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->trade_name ?? $supplier->legal_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Número do Documento</label>
                        <input type="text" name="document_no" value="{{ old('document_no') }}" maxlength="50"
                               placeholder="Ex: NF 12345" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('document_no')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoria (Opcional)</label>
                        <select name="category_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Selecione uma categoria</option>
                            @if(isset($categories) && $categories->count() > 0)
                                @php
                                    // Separar categorias pais e filhas
                                    $parentCategories = $categories->whereNull('parent_id')->sortBy('name');
                                    $subCategories = $categories->whereNotNull('parent_id')->sortBy('name');
                                @endphp
                                @foreach($parentCategories as $parentCategory)
                                    @php
                                        // Buscar todas as subcategorias desta categoria pai
                                        $children = $subCategories->where('parent_id', $parentCategory->id);
                                    @endphp
                                    @if($children->count() > 0)
                                        {{-- Categoria pai com subcategorias --}}
                                        <optgroup label="{{ $parentCategory->name }}">
                                            @foreach($children as $subCategory)
                                                <option value="{{ $subCategory->id }}" {{ old('category_id') == $subCategory->id ? 'selected' : '' }}>
                                                    {{ $subCategory->name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @else
                                        {{-- Categoria pai sem subcategorias (pode ser selecionada diretamente) --}}
                                        <option value="{{ $parentCategory->id }}" {{ old('category_id') == $parentCategory->id ? 'selected' : '' }}>
                                            {{ $parentCategory->name }}
                                        </option>
                                    @endif
                                @endforeach
                            @else
                                <option value="" disabled>Nenhuma categoria disponível. Execute o seeder de categorias.</option>
                            @endif
                        </select>
                        @if(isset($categories) && $categories->count() > 0)
                            <p class="mt-1 text-xs text-gray-500">
                                {{ $categories->whereNotNull('parent_id')->count() }} subcategorias disponíveis
                            </p>
                        @endif
                        @error('category_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Emissão *</label>
                        <input type="date" name="issue_date" value="{{ old('issue_date', now()->format('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('issue_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Vencimento *</label>
                        <input type="date" name="due_date" value="{{ old('due_date') }}" required
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('due_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valor *</label>
                        <input type="number" step="0.01" min="0.01" name="original_amount" value="{{ old('original_amount') }}" required
                               placeholder="0,00" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('original_amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parcelas</label>
                        <input type="number" name="installments" value="{{ old('installments', 1) }}" min="1" max="360"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               onchange="updateInstallmentInfo()">
                        <p class="mt-1 text-xs text-gray-500">Número de parcelas (1 = à vista)</p>
                        @error('installments')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="is_recurring" value="1" {{ old('is_recurring') ? 'checked' : '' }}
                                   onchange="toggleRecurring()" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Conta Recorrente</span>
                        </label>
                        <p class="mt-1 text-xs text-gray-500">Marque se esta conta se repete automaticamente</p>
                        @error('is_recurring')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="recurring-options" class="md:col-span-2 {{ old('is_recurring') ? '' : 'hidden' }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Recorrência</label>
                                <select name="recurring_type" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Selecione</option>
                                    <option value="daily" {{ old('recurring_type') == 'daily' ? 'selected' : '' }}>Diária</option>
                                    <option value="weekly" {{ old('recurring_type') == 'weekly' ? 'selected' : '' }}>Semanal</option>
                                    <option value="biweekly" {{ old('recurring_type') == 'biweekly' ? 'selected' : '' }}>Quinzenal</option>
                                    <option value="monthly" {{ old('recurring_type') == 'monthly' ? 'selected' : '' }}>Mensal</option>
                                    <option value="bimonthly" {{ old('recurring_type') == 'bimonthly' ? 'selected' : '' }}>Bimestral</option>
                                    <option value="quarterly" {{ old('recurring_type') == 'quarterly' ? 'selected' : '' }}>Trimestral</option>
                                    <option value="semiannual" {{ old('recurring_type') == 'semiannual' ? 'selected' : '' }}>Semestral</option>
                                    <option value="yearly" {{ old('recurring_type') == 'yearly' ? 'selected' : '' }}>Anual</option>
                                </select>
                                @error('recurring_type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data de Término (Opcional)</label>
                                <input type="date" name="recurring_end_date" value="{{ old('recurring_end_date') }}"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-xs text-gray-500">Deixe em branco para recorrência infinita</p>
                                @error('recurring_end_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea name="note" rows="3" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                  placeholder="Observações sobre a conta a pagar">{{ old('note') }}</textarea>
                        @error('note')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="installment-preview" class="md:col-span-2 p-4 bg-gray-50 rounded-lg border border-gray-200 hidden">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">📋 Resumo do Parcelamento</h4>
                        <div id="installment-list" class="text-sm text-gray-600"></div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <a href="{{ route('finance.payables.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        Cancelar
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Salvar Conta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleRecurring() {
    const checkbox = document.querySelector('input[name="is_recurring"]');
    const options = document.getElementById('recurring-options');
    if (checkbox.checked) {
        options.classList.remove('hidden');
    } else {
        options.classList.add('hidden');
    }
    updateInstallmentInfo();
}

function updateInstallmentInfo() {
    const installments = parseInt(document.querySelector('input[name="installments"]').value) || 1;
    const isRecurring = document.querySelector('input[name="is_recurring"]').checked;
    const recurringType = document.querySelector('select[name="recurring_type"]').value;
    const dueDateInput = document.querySelector('input[name="due_date"]');
    const amountInput = document.querySelector('input[name="original_amount"]');
    const preview = document.getElementById('installment-preview');
    const list = document.getElementById('installment-list');
    
    if (installments > 1 && dueDateInput.value && amountInput.value) {
        preview.classList.remove('hidden');
        
        const totalAmount = parseFloat(amountInput.value) || 0;
        const amountPerInstallment = totalAmount / installments;
        const startDate = new Date(dueDateInput.value);
        
        let html = '<div class="space-y-1 max-h-40 overflow-y-auto">';
        
        for (let i = 1; i <= installments; i++) {
            const currentDate = new Date(startDate);
            
            if (isRecurring && recurringType) {
                // Calcular data baseada no tipo de recorrência
                switch(recurringType) {
                    case 'daily':
                        currentDate.setDate(currentDate.getDate() + (i - 1));
                        break;
                    case 'weekly':
                        currentDate.setDate(currentDate.getDate() + ((i - 1) * 7));
                        break;
                    case 'biweekly':
                        currentDate.setDate(currentDate.getDate() + ((i - 1) * 14));
                        break;
                    case 'monthly':
                        currentDate.setMonth(currentDate.getMonth() + (i - 1));
                        break;
                    case 'bimonthly':
                        currentDate.setMonth(currentDate.getMonth() + ((i - 1) * 2));
                        break;
                    case 'quarterly':
                        currentDate.setMonth(currentDate.getMonth() + ((i - 1) * 3));
                        break;
                    case 'semiannual':
                        currentDate.setMonth(currentDate.getMonth() + ((i - 1) * 6));
                        break;
                    case 'yearly':
                        currentDate.setFullYear(currentDate.getFullYear() + (i - 1));
                        break;
                    default:
                        currentDate.setDate(currentDate.getDate() + ((i - 1) * 30));
                }
            } else {
                // Parcelamento simples: 30 dias por parcela
                currentDate.setDate(currentDate.getDate() + ((i - 1) * 30));
            }
            
            const dateStr = currentDate.toLocaleDateString('pt-BR');
            html += `<div class="flex justify-between text-xs">
                <span>Parcela ${i}/${installments}</span>
                <span>${dateStr}</span>
                <span class="font-semibold">R$ ${amountPerInstallment.toFixed(2).replace('.', ',')}</span>
            </div>`;
        }
        
        html += '</div>';
        html += `<div class="mt-2 pt-2 border-t border-gray-300">
            <div class="flex justify-between font-semibold">
                <span>Total:</span>
                <span>R$ ${totalAmount.toFixed(2).replace('.', ',')}</span>
            </div>
        </div>`;
        
        list.innerHTML = html;
    } else {
        preview.classList.add('hidden');
    }
}

// Atualizar preview quando campos mudarem
document.addEventListener('DOMContentLoaded', function() {
    const installmentsInput = document.querySelector('input[name="installments"]');
    const dueDateInput = document.querySelector('input[name="due_date"]');
    const amountInput = document.querySelector('input[name="original_amount"]');
    const recurringTypeSelect = document.querySelector('select[name="recurring_type"]');
    
    if (installmentsInput) installmentsInput.addEventListener('input', updateInstallmentInfo);
    if (dueDateInput) dueDateInput.addEventListener('change', updateInstallmentInfo);
    if (amountInput) amountInput.addEventListener('input', updateInstallmentInfo);
    if (recurringTypeSelect) recurringTypeSelect.addEventListener('change', updateInstallmentInfo);
    
    // Inicializar estado
    toggleRecurring();
    updateInstallmentInfo();
});
</script>
@endsection

