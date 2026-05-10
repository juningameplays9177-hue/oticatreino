@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-4 sm:py-6">
        <div class="mb-4 sm:mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">💰 Nova Conta a Receber</h1>
                <p class="mt-1 text-xs sm:text-sm text-gray-600">Registre um valor que você vai receber</p>
            </div>
            <a href="{{ route('finance.receivables.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-sm sm:text-base">
                ← Voltar
            </a>
        </div>

        <div class="bg-white shadow rounded-lg p-4 sm:p-6">
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                    <p class="text-red-800 text-sm">{{ session('error') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('finance.receivables.store') }}" enctype="multipart/form-data" id="receivableForm">
                @csrf

                <!-- Accordion para seções -->
                <div class="space-y-3">
                    <!-- Seção 1: Informações Principais -->
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <button type="button" onclick="toggleSection('section1')" class="w-full px-4 py-3 bg-blue-50 hover:bg-blue-100 flex items-center justify-between transition-colors">
                            <div class="flex items-center">
                                <span class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3">1</span>
                                <span class="font-semibold text-gray-900">Informações Principais</span>
                            </div>
                            <svg id="icon-section1" class="w-5 h-5 text-gray-600 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="section1" class="hidden px-4 py-4 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        📍 Loja <span class="text-red-500">*</span>
                                    </label>
                                    <select name="store_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm sm:text-base">
                                        <option value="">Escolha...</option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store->id }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>
                                                @if($store->abbreviation)[{{ $store->abbreviation }}]@endif {{ $store->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('store_id')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        👤 Cliente
                                    </label>
                                    <select name="customer_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm sm:text-base">
                                        <option value="">Sem cliente</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}" {{ old('customer_id') == $client->id ? 'selected' : '' }}>
                                                {{ $client->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_id')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        📄 Nº Documento
                                    </label>
                                    <input type="text" name="document_no" value="{{ old('document_no') }}" 
                                           placeholder="Ex: 001234"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm sm:text-base">
                                    @error('document_no')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        🏷️ Tipo de Cobrança
                                    </label>
                                    <select name="billing_type" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm sm:text-base">
                                        <option value="">Selecione...</option>
                                        <option value="crediario" {{ old('billing_type') == 'crediario' ? 'selected' : '' }}>💳 Crediário</option>
                                        <option value="carne" {{ old('billing_type') == 'carne' ? 'selected' : '' }}>📋 Carnê</option>
                                        <option value="boleto" {{ old('billing_type') == 'boleto' ? 'selected' : '' }}>🧾 Boleto</option>
                                        <option value="cartao_prazo" {{ old('billing_type') == 'cartao_prazo' ? 'selected' : '' }}>💳 Cartão Prazo</option>
                                        <option value="convenio" {{ old('billing_type') == 'convenio' ? 'selected' : '' }}>🏢 Convênio</option>
                                        <option value="mensalidade" {{ old('billing_type') == 'mensalidade' ? 'selected' : '' }}>📅 Mensalidade</option>
                                        <option value="fiado" {{ old('billing_type') == 'fiado' ? 'selected' : '' }}>📝 Fiado</option>
                                    </select>
                                    @error('billing_type')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seção 2: Valores e Datas -->
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <button type="button" onclick="toggleSection('section2')" class="w-full px-4 py-3 bg-green-50 hover:bg-green-100 flex items-center justify-between transition-colors">
                            <div class="flex items-center">
                                <span class="w-6 h-6 bg-green-600 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3">2</span>
                                <span class="font-semibold text-gray-900">Valores e Datas</span>
                            </div>
                            <svg id="icon-section2" class="w-5 h-5 text-gray-600 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="section2" class="hidden px-4 py-4 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        📅 Emissão <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" name="issue_date" value="{{ old('issue_date', now()->format('Y-m-d')) }}" required
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm sm:text-base">
                                    @error('issue_date')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        ⏰ Vencimento <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" name="due_date" value="{{ old('due_date') }}" required
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm sm:text-base">
                                    @error('due_date')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        💵 Valor <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">R$</span>
                                        <input type="number" step="0.01" min="0.01" name="original_amount" value="{{ old('original_amount') }}" required
                                               placeholder="0,00" id="original_amount"
                                               class="w-full pl-10 pr-4 py-2 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base font-semibold">
                                    </div>
                                    @error('original_amount')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        📊 Parcelas
                                    </label>
                                    <input type="number" min="1" max="360" name="installments" value="{{ old('installments', 1) }}" 
                                           id="installments"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm sm:text-base">
                                    <p class="mt-1 text-xs text-gray-500" id="installment-info">1 parcela única</p>
                                    @error('installments')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Valores Adicionais (Colapsável) -->
                            <div class="mt-4">
                                <button type="button" onclick="toggleSection('values-extra')" class="w-full text-left text-sm font-medium text-gray-700 hover:text-blue-600 flex items-center justify-between py-2">
                                    <span>➕ Adicionar Juros, Multa ou Desconto</span>
                                    <svg id="icon-values-extra" class="w-4 h-4 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div id="values-extra" class="hidden grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">⚠️ Juros (R$)</label>
                                        <div class="relative">
                                            <span class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs">R$</span>
                                            <input type="number" step="0.01" min="0" name="interest_amount" value="{{ old('interest_amount', 0) }}" 
                                                   placeholder="0,00" id="interest_amount"
                                                   class="w-full pl-8 pr-2 py-2 rounded border-gray-300 text-sm">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">🚫 Multa (R$)</label>
                                        <div class="relative">
                                            <span class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs">R$</span>
                                            <input type="number" step="0.01" min="0" name="fine_amount" value="{{ old('fine_amount', 0) }}" 
                                                   placeholder="0,00" id="fine_amount"
                                                   class="w-full pl-8 pr-2 py-2 rounded border-gray-300 text-sm">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">✅ Desconto (R$)</label>
                                        <div class="relative">
                                            <span class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs">R$</span>
                                            <input type="number" step="0.01" min="0" name="discount_amount" value="{{ old('discount_amount', 0) }}" 
                                                   placeholder="0,00" id="discount_amount"
                                                   class="w-full pl-8 pr-2 py-2 rounded border-gray-300 text-sm">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-blue-50 rounded-lg p-3 border border-blue-200">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700">💰 Total a Receber</span>
                                    <span class="text-lg font-bold text-blue-600" id="total_amount_display">R$ 0,00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seção 3: Classificação -->
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <button type="button" onclick="toggleSection('section3')" class="w-full px-4 py-3 bg-purple-50 hover:bg-purple-100 flex items-center justify-between transition-colors">
                            <div class="flex items-center">
                                <span class="w-6 h-6 bg-purple-600 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3">3</span>
                                <span class="font-semibold text-gray-900">Classificação</span>
                            </div>
                            <svg id="icon-section3" class="w-5 h-5 text-gray-600 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="section3" class="hidden px-4 py-4 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        📂 Categoria <span class="text-red-500">*</span>
                                    </label>
                                    <select name="category_id" required id="category_select" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm sm:text-base">
                                        <option value="">Carregando categorias...</option>
                                    </select>
                                    @error('category_id')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        💳 Forma de Pagamento
                                    </label>
                                    <select name="method" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm sm:text-base">
                                        <option value="">Selecione...</option>
                                        <option value="pix" {{ old('method') == 'pix' ? 'selected' : '' }}>💸 PIX</option>
                                        <option value="card" {{ old('method') == 'card' ? 'selected' : '' }}>💳 Cartão</option>
                                        <option value="money" {{ old('method') == 'money' ? 'selected' : '' }}>💵 Dinheiro</option>
                                        <option value="boleto" {{ old('method') == 'boleto' ? 'selected' : '' }}>🧾 Boleto</option>
                                        <option value="transfer" {{ old('method') == 'transfer' ? 'selected' : '' }}>🏦 Transferência</option>
                                        <option value="check" {{ old('method') == 'check' ? 'selected' : '' }}>📝 Cheque</option>
                                    </select>
                                    @error('method')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        🏢 Centro de Custo
                                    </label>
                                    <select name="cost_center_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm sm:text-base">
                                        <option value="">Sem centro de custo</option>
                                        @if(isset($costCenters) && $costCenters->count() > 0)
                                            @foreach($costCenters as $costCenter)
                                                <option value="{{ $costCenter->id }}" {{ old('cost_center_id') == $costCenter->id ? 'selected' : '' }}>
                                                    {{ $costCenter->name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('cost_center_id')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seção 4: Observações (Opcional) -->
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <button type="button" onclick="toggleSection('section4')" class="w-full px-4 py-3 bg-gray-50 hover:bg-gray-100 flex items-center justify-between transition-colors">
                            <div class="flex items-center">
                                <span class="w-6 h-6 bg-gray-600 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3">4</span>
                                <span class="font-semibold text-gray-900">Observações <span class="text-xs font-normal text-gray-500">(Opcional)</span></span>
                            </div>
                            <svg id="icon-section4" class="w-5 h-5 text-gray-600 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="section4" class="hidden px-4 py-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">📝 Observações</label>
                                <textarea name="note" rows="3" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                          placeholder="Ex: Cliente combinou de pagar em 2x...">{{ old('note') }}</textarea>
                                @error('note')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">📎 Anexar Documento</label>
                                <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <p class="mt-1 text-xs text-gray-500">PDF, JPG, PNG, DOC (máx. 5MB)</p>
                                @error('attachment')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex flex-col sm:flex-row justify-end gap-3 border-t pt-4">
                    <a href="{{ route('finance.receivables.index') }}" class="w-full sm:w-auto px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium text-center text-sm sm:text-base">
                        Cancelar
                    </a>
                    <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium text-base shadow-md hover:shadow-lg transition-all">
                        ✅ Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Categorias em JavaScript para melhor performance
const categories = @json($categories ?? []);

document.addEventListener('DOMContentLoaded', function() {
    // Carregar categorias no select
    const categorySelect = document.getElementById('category_select');
    if (categorySelect) {
        // Filtrar apenas categorias de receita (nature = 'revenue')
        const revenueCategories = categories.filter(c => c.nature === 'revenue' || !c.nature); // Incluir se não tiver nature definido
        
        if (revenueCategories.length > 0) {
            categorySelect.innerHTML = '<option value="">Escolha uma categoria...</option>';
            
            const parentCategories = revenueCategories.filter(c => !c.parent_id);
            const subCategories = revenueCategories.filter(c => c.parent_id);
            
            parentCategories.forEach(parent => {
                const children = subCategories.filter(c => c.parent_id == parent.id);
                if (children.length > 0) {
                    const optgroup = document.createElement('optgroup');
                    optgroup.label = parent.name;
                    children.forEach(child => {
                        const option = document.createElement('option');
                        option.value = child.id;
                        option.textContent = child.name;
                        if ('{{ old("category_id") }}' == child.id) {
                            option.selected = true;
                        }
                        optgroup.appendChild(option);
                    });
                    categorySelect.appendChild(optgroup);
                } else {
                    const option = document.createElement('option');
                    option.value = parent.id;
                    option.textContent = parent.name;
                    if ('{{ old("category_id") }}' == parent.id) {
                        option.selected = true;
                    }
                    categorySelect.appendChild(option);
                }
            });
        } else {
            categorySelect.innerHTML = '<option value="">⚠️ Nenhuma categoria de RECEITA disponível</option><option value="" disabled>Execute o seeder: php artisan db:seed --class=FinanceCategoriesSeeder</option>';
        }
    }

    // Cálculo de valores
    const originalAmount = document.getElementById('original_amount');
    const interestAmount = document.getElementById('interest_amount');
    const fineAmount = document.getElementById('fine_amount');
    const discountAmount = document.getElementById('discount_amount');
    const totalAmountDisplay = document.getElementById('total_amount_display');
    const installments = document.getElementById('installments');
    const installmentInfo = document.getElementById('installment-info');

    function calculateTotal() {
        const original = parseFloat(originalAmount?.value) || 0;
        const interest = parseFloat(interestAmount?.value) || 0;
        const fine = parseFloat(fineAmount?.value) || 0;
        const discount = parseFloat(discountAmount?.value) || 0;
        
        const total = original + interest + fine - discount;
        if (totalAmountDisplay) {
            totalAmountDisplay.textContent = 'R$ ' + total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }

    function updateInstallmentInfo() {
        if (!installments || !installmentInfo) return;
        const num = parseInt(installments.value) || 1;
        if (num === 1) {
            installmentInfo.textContent = '1 parcela única';
        } else {
            const original = parseFloat(originalAmount?.value) || 0;
            const perInstallment = original / num;
            installmentInfo.textContent = `${num} parcelas de R$ ${perInstallment.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }
    }

    [originalAmount, interestAmount, fineAmount, discountAmount].forEach(input => {
        if (input) input.addEventListener('input', calculateTotal);
    });

    [originalAmount, installments].forEach(input => {
        if (input) input.addEventListener('input', updateInstallmentInfo);
    });

    calculateTotal();
    updateInstallmentInfo();

    // Abrir primeira seção por padrão
    toggleSection('section2', true);
});

function toggleSection(sectionId, forceOpen = false) {
    const section = document.getElementById(sectionId);
    const icon = document.getElementById('icon-' + sectionId);
    
    if (!section) return;
    
    const isHidden = section.classList.contains('hidden');
    
    if (forceOpen || isHidden) {
        section.classList.remove('hidden');
        if (icon) icon.style.transform = 'rotate(180deg)';
    } else {
        section.classList.add('hidden');
        if (icon) icon.style.transform = 'rotate(0deg)';
    }
}
</script>
@endsection
