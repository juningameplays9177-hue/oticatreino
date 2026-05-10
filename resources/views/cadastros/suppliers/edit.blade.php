<x-app-layout title="Editar Fornecedor">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 md:p-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-xl md:text-2xl font-bold text-slate-900">Editar Fornecedor</h1>
                <a href="{{ route('cadastros.suppliers.index') }}" class="px-3 py-1.5 text-sm bg-slate-100 text-slate-700 font-medium rounded-lg hover:bg-slate-200">Voltar</a>
            </div>

            @if(session('error'))
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-800">{{ session('error') }}</div>
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

            <form method="POST" action="{{ route('cadastros.suppliers.update', $supplier) }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Dados Básicos -->
                    <div class="md:col-span-2">
                        <h2 class="text-lg font-semibold text-slate-900 mb-3 border-b border-slate-200 pb-2">Dados Básicos</h2>
                    </div>

                    <div>
                        <label for="tax_id_type" class="block text-sm font-medium text-slate-700 mb-1">Tipo de Documento *</label>
                        <select id="tax_id_type" name="tax_id_type" required onchange="toggleTaxId()" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="CPF" {{ old('tax_id_type', $supplier->tax_id_type) === 'CPF' ? 'selected' : '' }}>CPF</option>
                            <option value="CNPJ" {{ old('tax_id_type', $supplier->tax_id_type) === 'CNPJ' ? 'selected' : '' }}>CNPJ</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="trade_name" class="block text-sm font-medium text-slate-700 mb-1">Nome Fantasia *</label>
                        <input type="text" id="trade_name" name="trade_name" value="{{ old('trade_name', $supplier->trade_name) }}" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="legal_name" class="block text-sm font-medium text-slate-700 mb-1">Razão Social</label>
                        <input type="text" id="legal_name" name="legal_name" value="{{ old('legal_name', $supplier->legal_name) }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div id="cpf_field" class="{{ old('tax_id_type', $supplier->tax_id_type) === 'CPF' ? '' : 'hidden' }}">
                        <label for="cpf" class="block text-sm font-medium text-slate-700 mb-1">CPF *</label>
                        <input type="text" id="cpf" name="cpf" value="{{ old('cpf', $supplier->cpf_clean ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $supplier->cpf_clean) : '') }}" placeholder="000.000.000-00" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div id="cnpj_field" class="{{ old('tax_id_type', $supplier->tax_id_type) === 'CNPJ' ? '' : 'hidden' }}">
                        <label for="cnpj" class="block text-sm font-medium text-slate-700 mb-1">CNPJ *</label>
                        <input type="text" id="cnpj" name="cnpj" value="{{ old('cnpj', $supplier->cnpj_clean ? preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $supplier->cnpj_clean) : '') }}" placeholder="00.000.000/0000-00" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Informações Fiscais -->
                    <div class="md:col-span-2 mt-2">
                        <h2 class="text-lg font-semibold text-slate-900 mb-3 border-b border-slate-200 pb-2">Informações Fiscais</h2>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_lab" value="1" {{ old('is_lab', $supplier->is_lab) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-slate-700">É Laboratório Óptico</span>
                        </label>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="taxpayer_icms" value="1" {{ old('taxpayer_icms', $supplier->taxpayer_icms) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-slate-700">Contribuinte ICMS</span>
                        </label>
                    </div>

                    <div>
                        <label for="ie" class="block text-sm font-medium text-slate-700 mb-1">Inscrição Estadual (IE)</label>
                        <input type="text" id="ie" name="ie" value="{{ old('ie', $supplier->ie) }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="im" class="block text-sm font-medium text-slate-700 mb-1">Inscrição Municipal (IM)</label>
                        <input type="text" id="im" name="im" value="{{ old('im', $supplier->im) }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="suframa" class="block text-sm font-medium text-slate-700 mb-1">SUFRAMA</label>
                        <input type="text" id="suframa" name="suframa" value="{{ old('suframa', $supplier->suframa) }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Contato -->
                    <div class="md:col-span-2 mt-2">
                        <h2 class="text-lg font-semibold text-slate-900 mb-3 border-b border-slate-200 pb-2">Contato</h2>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">E-mail</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $supplier->email) }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="website" class="block text-sm font-medium text-slate-700 mb-1">Website</label>
                        <input type="url" id="website" name="website" value="{{ old('website', $supplier->website) }}" placeholder="https://..." class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Endereço -->
                    <div class="md:col-span-2 mt-2">
                        <h2 class="text-lg font-semibold text-slate-900 mb-3 border-b border-slate-200 pb-2">Endereço</h2>
                    </div>

                    <div>
                        <label for="zip_code" class="block text-sm font-medium text-slate-700 mb-1">CEP</label>
                        <input type="text" id="zip_code" name="zip_code" value="{{ old('zip_code', $supplier->zip_code) }}" placeholder="00000-000" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-slate-700 mb-1">Endereço</label>
                        <input type="text" id="address" name="address" value="{{ old('address', $supplier->address) }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="number" class="block text-sm font-medium text-slate-700 mb-1">Número</label>
                        <input type="text" id="number" name="number" value="{{ old('number', $supplier->number) }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="complement" class="block text-sm font-medium text-slate-700 mb-1">Complemento</label>
                        <input type="text" id="complement" name="complement" value="{{ old('complement', $supplier->complement) }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="district" class="block text-sm font-medium text-slate-700 mb-1">Bairro</label>
                        <input type="text" id="district" name="district" value="{{ old('district', $supplier->district) }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="city" class="block text-sm font-medium text-slate-700 mb-1">Cidade</label>
                        <input type="text" id="city" name="city" value="{{ old('city', $supplier->city) }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="state" class="block text-sm font-medium text-slate-700 mb-1">Estado (UF)</label>
                        <input type="text" id="state" name="state" value="{{ old('state', $supplier->state) }}" maxlength="2" placeholder="RJ" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Observações -->
                    <div class="md:col-span-2 mt-2">
                        <label for="notes" class="block text-sm font-medium text-slate-700 mb-1">Observações</label>
                        <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $supplier->notes) }}</textarea>
                    </div>

                    <!-- Status -->
                    <div class="md:col-span-2 mt-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $supplier->is_active) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-slate-700">Fornecedor Ativo</span>
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <a href="{{ route('cadastros.suppliers.index') }}" class="px-4 py-2 bg-slate-100 text-slate-700 font-medium rounded-lg hover:bg-slate-200">Cancelar</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700">Atualizar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleTaxId() {
            const type = document.getElementById('tax_id_type').value;
            document.getElementById('cpf_field').classList.toggle('hidden', type !== 'CPF');
            document.getElementById('cnpj_field').classList.toggle('hidden', type !== 'CNPJ');
        }
    </script>
</x-app-layout>

