<x-app-layout title="Editar Cliente">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h1 class="text-2xl font-bold text-slate-900 mb-6">Editar Cliente</h1>

            @if($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="list-disc list-inside text-red-800 text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('clients.update', $client) }}" id="clientForm">
                @csrf
                @method('PUT')

                <!-- Dados Básicos -->
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Dados Básicos</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nome *</label>
                            <input type="text" id="name" name="name" value="{{ old('name', $client->name) }}" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="cpf_cnpj" class="block text-sm font-medium text-slate-700 mb-1">CPF/CNPJ</label>
                            <input type="text" id="cpf_cnpj" name="cpf_cnpj" value="{{ old('cpf_cnpj', $client->cpf_cnpj) }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="birth_date" class="block text-sm font-medium text-slate-700 mb-1">Data de Nascimento</label>
                            <input type="date" id="birth_date" name="birth_date" value="{{ old('birth_date', $client->birth_date?->format('Y-m-d')) }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Endereço -->
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Endereço</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="cep" class="block text-sm font-medium text-slate-700 mb-1">CEP</label>
                            <input type="text" id="cep" name="cep" value="{{ old('cep', $client->cep) }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="city" class="block text-sm font-medium text-slate-700 mb-1">Cidade</label>
                            <input type="text" id="city" name="city" value="{{ old('city', $client->city) }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="district" class="block text-sm font-medium text-slate-700 mb-1">Bairro</label>
                            <input type="text" id="district" name="district" value="{{ old('district', $client->district) }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="address" class="block text-sm font-medium text-slate-700 mb-1">Endereço</label>
                            <input type="text" id="address" name="address" value="{{ old('address', $client->address) }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="number" class="block text-sm font-medium text-slate-700 mb-1">Número</label>
                            <input type="text" id="number" name="number" value="{{ old('number', $client->number) }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="complement" class="block text-sm font-medium text-slate-700 mb-1">Complemento</label>
                            <input type="text" id="complement" name="complement" value="{{ old('complement', $client->complement) }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Telefones -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-slate-800">Telefones</h2>
                        <button type="button" onclick="addPhone()" class="text-sm text-blue-600 hover:text-blue-800 font-medium">+ Adicionar Telefone</button>
                    </div>
                    <div id="phones-container">
                        @forelse($client->phones as $index => $phone)
                            <div class="phone-item grid grid-cols-1 md:grid-cols-3 gap-4 mb-2">
                                <input type="text" name="phones[{{ $index }}][phone]" value="{{ old("phones.{$index}.phone", $phone->phone) }}" placeholder="Telefone" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <input type="text" name="phones[{{ $index }}][label]" value="{{ old("phones.{$index}.label", $phone->label) }}" placeholder="Rótulo (ex: Celular, Residencial)" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <button type="button" onclick="removePhone(this)" class="text-red-600 hover:text-red-800 text-sm {{ $client->phones->count() > 1 ? '' : 'hidden' }} remove-phone-btn">Remover</button>
                            </div>
                        @empty
                            <div class="phone-item grid grid-cols-1 md:grid-cols-3 gap-4 mb-2">
                                <input type="text" name="phones[0][phone]" placeholder="Telefone" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <input type="text" name="phones[0][label]" placeholder="Rótulo (ex: Celular, Residencial)" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <button type="button" onclick="removePhone(this)" class="text-red-600 hover:text-red-800 text-sm hidden remove-phone-btn">Remover</button>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- E-mails -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-slate-800">E-mails</h2>
                        <button type="button" onclick="addEmail()" class="text-sm text-blue-600 hover:text-blue-800 font-medium">+ Adicionar E-mail</button>
                    </div>
                    <div id="emails-container">
                        @forelse($client->emails as $index => $email)
                            <div class="email-item grid grid-cols-1 md:grid-cols-3 gap-4 mb-2">
                                <input type="email" name="emails[{{ $index }}][email]" value="{{ old("emails.{$index}.email", $email->email) }}" placeholder="E-mail" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <input type="text" name="emails[{{ $index }}][label]" value="{{ old("emails.{$index}.label", $email->label) }}" placeholder="Rótulo (ex: Principal, Trabalho)" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <button type="button" onclick="removeEmail(this)" class="text-red-600 hover:text-red-800 text-sm {{ $client->emails->count() > 1 ? '' : 'hidden' }} remove-email-btn">Remover</button>
                            </div>
                        @empty
                            <div class="email-item grid grid-cols-1 md:grid-cols-3 gap-4 mb-2">
                                <input type="email" name="emails[0][email]" placeholder="E-mail" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <input type="text" name="emails[0][label]" placeholder="Rótulo (ex: Principal, Trabalho)" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <button type="button" onclick="removeEmail(this)" class="text-red-600 hover:text-red-800 text-sm hidden remove-email-btn">Remover</button>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Observações -->
                <div class="mb-6">
                    <label for="notes" class="block text-sm font-medium text-slate-700 mb-1">Observações</label>
                    <textarea id="notes" name="notes" rows="4" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $client->notes) }}</textarea>
                </div>

                <!-- Botões -->
                <div class="flex justify-end gap-3">
                    <a href="{{ route('clients.index') }}" class="px-4 py-2 bg-slate-200 text-slate-700 font-medium rounded-lg hover:bg-slate-300 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let phoneCount = {{ $client->phones->count() }};
        let emailCount = {{ $client->emails->count() }};

        function addPhone() {
            const container = document.getElementById('phones-container');
            const div = document.createElement('div');
            div.className = 'phone-item grid grid-cols-1 md:grid-cols-3 gap-4 mb-2';
            div.innerHTML = `
                <input type="text" name="phones[${phoneCount}][phone]" placeholder="Telefone" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <input type="text" name="phones[${phoneCount}][label]" placeholder="Rótulo (ex: Celular, Residencial)" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <button type="button" onclick="removePhone(this)" class="text-red-600 hover:text-red-800 text-sm remove-phone-btn">Remover</button>
            `;
            container.appendChild(div);
            phoneCount++;
            updateRemoveButtons('phone');
        }

        function removePhone(btn) {
            btn.closest('.phone-item').remove();
            updateRemoveButtons('phone');
        }

        function addEmail() {
            const container = document.getElementById('emails-container');
            const div = document.createElement('div');
            div.className = 'email-item grid grid-cols-1 md:grid-cols-3 gap-4 mb-2';
            div.innerHTML = `
                <input type="email" name="emails[${emailCount}][email]" placeholder="E-mail" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <input type="text" name="emails[${emailCount}][label]" placeholder="Rótulo (ex: Principal, Trabalho)" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <button type="button" onclick="removeEmail(this)" class="text-red-600 hover:text-red-800 text-sm remove-email-btn">Remover</button>
            `;
            container.appendChild(div);
            emailCount++;
            updateRemoveButtons('email');
        }

        function removeEmail(btn) {
            btn.closest('.email-item').remove();
            updateRemoveButtons('email');
        }

        function updateRemoveButtons(type) {
            const containers = {
                'phone': document.getElementById('phones-container'),
                'email': document.getElementById('emails-container')
            };
            const container = containers[type];
            const items = container.querySelectorAll(`.${type}-item`);
            items.forEach((item, index) => {
                const btn = item.querySelector(`.remove-${type}-btn`);
                if (items.length > 1) {
                    btn.classList.remove('hidden');
                } else {
                    btn.classList.add('hidden');
                }
            });
        }
    </script>
</x-app-layout>
