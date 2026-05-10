<x-app-layout title="Editar Loja">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 md:p-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-xl md:text-2xl font-bold text-slate-900">Editar Loja</h1>
                <a href="{{ route('cadastros.companies.index') }}" class="px-3 py-1.5 text-sm bg-slate-100 text-slate-700 font-medium rounded-lg hover:bg-slate-200">Voltar</a>
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

            <form method="POST" action="{{ route('cadastros.companies.update', $company->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="tax_id_type" class="block text-sm font-medium text-slate-700 mb-1">Tipo de Documento *</label>
                        <select id="tax_id_type" name="tax_id_type" required onchange="toggleTaxId()" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="CPF" {{ $company->tax_id_type === 'CPF' ? 'selected' : '' }}>CPF</option>
                            <option value="CNPJ" {{ $company->tax_id_type === 'CNPJ' ? 'selected' : '' }}>CNPJ</option>
                        </select>
                    </div>

                    <div id="cpf_field" class="{{ $company->tax_id_type === 'CPF' ? '' : 'hidden' }}">
                        <label for="cpf" class="block text-sm font-medium text-slate-700 mb-1">CPF *</label>
                        <input type="text" id="cpf" name="cpf" value="{{ $company->cpf_clean ?? '' }}" placeholder="000.000.000-00" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div id="cnpj_field" class="{{ $company->tax_id_type === 'CNPJ' ? '' : 'hidden' }}">
                        <label for="cnpj" class="block text-sm font-medium text-slate-700 mb-1">CNPJ *</label>
                        <input type="text" id="cnpj" name="cnpj" value="{{ $company->cnpj_clean ?? '' }}" placeholder="00.000.000/0000-00" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="legal_name" class="block text-sm font-medium text-slate-700 mb-1">Razão Social *</label>
                        <input type="text" id="legal_name" name="legal_name" value="{{ $company->legal_name ?? '' }}" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="trade_name" class="block text-sm font-medium text-slate-700 mb-1">Nome Fantasia *</label>
                        <input type="text" id="trade_name" name="trade_name" value="{{ $company->trade_name ?? '' }}" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="slug" class="block text-sm font-medium text-slate-700 mb-1">Identificação no Sistema (slug) *</label>
                        <input type="text" id="slug" name="slug" value="{{ $company->slug ?? '' }}" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">E-mail</label>
                        <input type="email" id="email" name="email" value="{{ $company->email }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">Telefone</label>
                        <input type="text" id="phone" name="phone" value="{{ $company->phone }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="mobile" class="block text-sm font-medium text-slate-700 mb-1">Celular</label>
                        <input type="text" id="mobile" name="mobile" value="{{ $company->mobile }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="contact_name" class="block text-sm font-medium text-slate-700 mb-1">Nome do Contato</label>
                        <input type="text" id="contact_name" name="contact_name" value="{{ $company->contact_name }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    @if(!empty($company->logo_path))
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Logo Atual</label>
                            <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Logo" class="w-20 h-20 rounded" onerror="this.style.display='none'">
                        </div>
                    @endif

                    <div class="md:col-span-2">
                        <label for="logo" class="block text-sm font-medium text-slate-700 mb-1">Nova Logo</label>
                        <input type="file" id="logo" name="logo" accept="image/*" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="zip_code" class="block text-sm font-medium text-slate-700 mb-1">CEP</label>
                        <input type="text" id="zip_code" name="zip_code" value="{{ $company->zip_code }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-slate-700 mb-1">Endereço</label>
                        <input type="text" id="address" name="address" value="{{ $company->address }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="number" class="block text-sm font-medium text-slate-700 mb-1">Número</label>
                        <input type="text" id="number" name="number" value="{{ $company->number }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="complement" class="block text-sm font-medium text-slate-700 mb-1">Complemento</label>
                        <input type="text" id="complement" name="complement" value="{{ $company->complement }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="district" class="block text-sm font-medium text-slate-700 mb-1">Bairro</label>
                        <input type="text" id="district" name="district" value="{{ $company->district }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="city" class="block text-sm font-medium text-slate-700 mb-1">Cidade</label>
                        <input type="text" id="city" name="city" value="{{ $company->city }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="state" class="block text-sm font-medium text-slate-700 mb-1">Estado (UF)</label>
                        <input type="text" id="state" name="state" value="{{ $company->state }}" maxlength="2" placeholder="RJ" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <a href="{{ route('cadastros.companies.index') }}" class="px-4 py-2 bg-slate-100 text-slate-700 font-medium rounded-lg hover:bg-slate-200">Cancelar</a>
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

