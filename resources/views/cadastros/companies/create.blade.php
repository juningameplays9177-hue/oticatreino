<x-app-layout title="Nova Empresa">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 md:p-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-xl md:text-2xl font-bold text-slate-900">Nova Empresa</h1>
                <a href="{{ route('cadastros.companies.index') }}" class="px-3 py-1.5 text-sm bg-slate-100 text-slate-700 font-medium rounded-lg hover:bg-slate-200">Cancelar</a>
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

            <form method="POST" action="{{ route('cadastros.companies.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="tax_id_type" class="block text-sm font-medium text-slate-700 mb-1">Tipo de Documento *</label>
                        <select id="tax_id_type" name="tax_id_type" required onchange="toggleTaxId()" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('tax_id_type') ? 'border-red-500' : '' }}">
                            <option value="">Selecione</option>
                            <option value="CPF" {{ old('tax_id_type') === 'CPF' ? 'selected' : '' }}>CPF</option>
                            <option value="CNPJ" {{ old('tax_id_type') === 'CNPJ' ? 'selected' : '' }}>CNPJ</option>
                        </select>
                        @error('tax_id_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="cpf_field" class="{{ old('tax_id_type') === 'CPF' ? '' : 'hidden' }}">
                        <label for="cpf" class="block text-sm font-medium text-slate-700 mb-1">CPF *</label>
                        <input type="text" id="cpf" name="cpf" value="{{ old('cpf') }}" placeholder="000.000.000-00" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('cpf') ? 'border-red-500' : '' }}">
                        @error('cpf')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="cnpj_field" class="{{ old('tax_id_type') === 'CNPJ' ? '' : 'hidden' }}">
                        <label for="cnpj" class="block text-sm font-medium text-slate-700 mb-1">CNPJ *</label>
                        <input type="text" id="cnpj" name="cnpj" value="{{ old('cnpj') }}" placeholder="00.000.000/0000-00" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('cnpj') ? 'border-red-500' : '' }}">
                        @error('cnpj')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="legal_name" class="block text-sm font-medium text-slate-700 mb-1">Razão Social *</label>
                        <input type="text" id="legal_name" name="legal_name" value="{{ old('legal_name') }}" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('legal_name') ? 'border-red-500' : '' }}">
                        @error('legal_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="trade_name" class="block text-sm font-medium text-slate-700 mb-1">Nome Fantasia *</label>
                        <input type="text" id="trade_name" name="trade_name" value="{{ old('trade_name') }}" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('trade_name') ? 'border-red-500' : '' }}">
                        @error('trade_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="slug" class="block text-sm font-medium text-slate-700 mb-1">Identificação no Sistema (slug) *</label>
                        <input type="text" id="slug" name="slug" value="{{ old('slug') }}" required placeholder="Ex: Kids, Tijuca" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('slug') ? 'border-red-500' : '' }}">
                        <p class="mt-1 text-xs text-slate-500">Único por rede</p>
                        @error('slug')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">E-mail</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 {{ $errors->has('email') ? 'border-red-500' : '' }}">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">Telefone</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone') }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="mobile" class="block text-sm font-medium text-slate-700 mb-1">Celular</label>
                        <input type="text" id="mobile" name="mobile" value="{{ old('mobile') }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="contact_name" class="block text-sm font-medium text-slate-700 mb-1">Nome do Contato</label>
                        <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name') }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="logo" class="block text-sm font-medium text-slate-700 mb-1">Logo</label>
                        <input type="file" id="logo" name="logo" accept="image/*" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-slate-500">JPG, PNG - até 2MB</p>
                    </div>

                    <div class="md:col-span-2">
                        <label for="zip_code" class="block text-sm font-medium text-slate-700 mb-1">CEP</label>
                        <input type="text" id="zip_code" name="zip_code" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-slate-700 mb-1">Endereço</label>
                        <input type="text" id="address" name="address" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="number" class="block text-sm font-medium text-slate-700 mb-1">Número</label>
                        <input type="text" id="number" name="number" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="complement" class="block text-sm font-medium text-slate-700 mb-1">Complemento</label>
                        <input type="text" id="complement" name="complement" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="district" class="block text-sm font-medium text-slate-700 mb-1">Bairro</label>
                        <input type="text" id="district" name="district" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="city" class="block text-sm font-medium text-slate-700 mb-1">Cidade</label>
                        <input type="text" id="city" name="city" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="state" class="block text-sm font-medium text-slate-700 mb-1">Estado (UF)</label>
                        <input type="text" id="state" name="state" maxlength="2" placeholder="RJ" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <a href="{{ route('cadastros.companies.index') }}" class="px-4 py-2 bg-slate-100 text-slate-700 font-medium rounded-lg hover:bg-slate-200">Cancelar</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleTaxId() {
            const type = document.getElementById('tax_id_type').value;
            const cpfField = document.getElementById('cpf_field');
            const cnpjField = document.getElementById('cnpj_field');
            
            if (type === 'CPF') {
                cpfField.classList.remove('hidden');
                cnpjField.classList.add('hidden');
                document.getElementById('cpf').required = true;
                document.getElementById('cnpj').required = false;
                document.getElementById('cnpj').value = '';
            } else if (type === 'CNPJ') {
                cpfField.classList.add('hidden');
                cnpjField.classList.remove('hidden');
                document.getElementById('cpf').required = false;
                document.getElementById('cnpj').required = true;
                document.getElementById('cpf').value = '';
            } else {
                cpfField.classList.add('hidden');
                cnpjField.classList.add('hidden');
                document.getElementById('cpf').required = false;
                document.getElementById('cnpj').required = false;
            }
        }
        
        // Inicializar campos quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            toggleTaxId();
        });
    </script>
</x-app-layout>

