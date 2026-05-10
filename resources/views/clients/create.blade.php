<x-app-layout title="Novo Cliente">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h1 class="text-2xl font-bold text-slate-900 mb-6">Novo Cliente</h1>

            @if($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="list-disc list-inside text-red-800 text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('clients.store') }}" id="clientForm">
                @csrf
                
                <!-- Debug: Mostrar a URL do formulário -->
                <script>
                    console.log('🔍 [FORM DEBUG] Action URL:', '{{ route('clients.store') }}');
                    console.log('🔍 [FORM DEBUG] CSRF Token:', document.querySelector('input[name="_token"]')?.value);
                </script>
                
                <!-- Campo hidden para garantir que active seja enviado -->
                <input type="hidden" name="active" value="1">
                
                @if(session('error'))
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-red-800 text-sm">{{ session('error') }}</p>
                    </div>
                @endif
                
                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-green-800 text-sm">{{ session('success') }}</p>
                    </div>
                @endif

                <!-- Dados Básicos -->
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Dados Básicos</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nome *</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="cpf_cnpj" class="block text-sm font-medium text-slate-700 mb-1">CPF/CNPJ</label>
                            <input type="text" id="cpf_cnpj" name="cpf_cnpj" value="{{ old('cpf_cnpj') }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="birth_date" class="block text-sm font-medium text-slate-700 mb-1">Data de Nascimento</label>
                            <input type="date" id="birth_date" name="birth_date" value="{{ old('birth_date') }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Endereço -->
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Endereço</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="cep" class="block text-sm font-medium text-slate-700 mb-1">CEP</label>
                            <div class="flex gap-2">
                                <input type="text" id="cep" name="cep" value="{{ old('cep') }}" maxlength="9" 
                                       class="flex-1 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="00000-000"
                                       onblur="searchCEP(this.value)">
                                <button type="button" onclick="searchCEP(document.getElementById('cep').value)" 
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                                    Buscar
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Digite o CEP e clique em Buscar ou aguarde</p>
                        </div>
                        <div>
                            <label for="state" class="block text-sm font-medium text-slate-700 mb-1">Estado (UF)</label>
                            <input type="text" id="state" name="state" value="{{ old('state') }}" maxlength="2" 
                                   class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 uppercase"
                                   placeholder="UF" style="text-transform: uppercase;">
                        </div>
                        <div>
                            <label for="city" class="block text-sm font-medium text-slate-700 mb-1">Cidade</label>
                            <input type="text" id="city" name="city" value="{{ old('city') }}" 
                                   class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   readonly>
                        </div>
                        <div>
                            <label for="district" class="block text-sm font-medium text-slate-700 mb-1">Bairro</label>
                            <input type="text" id="district" name="district" value="{{ old('district') }}" 
                                   class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   readonly>
                        </div>
                        <div>
                            <label for="address" class="block text-sm font-medium text-slate-700 mb-1">Endereço</label>
                            <input type="text" id="address" name="address" value="{{ old('address') }}" 
                                   class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   readonly>
                        </div>
                        <div>
                            <label for="number" class="block text-sm font-medium text-slate-700 mb-1">Número</label>
                            <input type="text" id="number" name="number" value="{{ old('number') }}" 
                                   class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="md:col-span-2">
                            <label for="complement" class="block text-sm font-medium text-slate-700 mb-1">Complemento</label>
                            <input type="text" id="complement" name="complement" value="{{ old('complement') }}" 
                                   class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                        <div class="phone-item grid grid-cols-1 md:grid-cols-3 gap-4 mb-2">
                            <input type="text" name="phones[0][phone]" placeholder="Telefone" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <input type="text" name="phones[0][label]" placeholder="Rótulo (ex: Celular, Residencial)" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <button type="button" onclick="removePhone(this)" class="text-red-600 hover:text-red-800 text-sm hidden remove-phone-btn">Remover</button>
                        </div>
                    </div>
                </div>

                <!-- E-mails -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-slate-800">E-mails</h2>
                        <button type="button" onclick="addEmail()" class="text-sm text-blue-600 hover:text-blue-800 font-medium">+ Adicionar E-mail</button>
                    </div>
                    <div id="emails-container">
                        <div class="email-item grid grid-cols-1 md:grid-cols-3 gap-4 mb-2">
                            <input type="email" name="emails[0][email]" placeholder="E-mail" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <input type="text" name="emails[0][label]" placeholder="Rótulo (ex: Principal, Trabalho)" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <button type="button" onclick="removeEmail(this)" class="text-red-600 hover:text-red-800 text-sm hidden remove-email-btn">Remover</button>
                        </div>
                    </div>
                </div>

                <!-- Observações -->
                <div class="mb-6">
                    <label for="notes" class="block text-sm font-medium text-slate-700 mb-1">Observações</label>
                    <textarea id="notes" name="notes" rows="4" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
                </div>

                <!-- Botões -->
                <div class="flex justify-end gap-3">
                    <a href="{{ route('clients.index') }}" class="px-4 py-2 bg-slate-200 text-slate-700 font-medium rounded-lg hover:bg-slate-300 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" id="submitBtn" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors" onclick="console.log('🔍 [CLIENT FORM] onclick disparado'); return true;">
                        Salvar
                    </button>
                    <!-- Botão de teste direto -->
                    <button type="button" onclick="document.getElementById('clientForm').submit(); console.log('🔍 [CLIENT FORM] Submit forçado via onclick');" class="px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                        Testar Submit Direto
                    </button>
                    
                    <!-- Botão de teste alternativo (removido - usar o botão normal) -->
                </div>
            </form>
        </div>
    </div>

    <script>
        // Log imediato quando o script carrega
        console.log('🔍 [CLIENT FORM] Script carregado');
        
        // Aguardar DOM estar pronto
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔍 [CLIENT FORM] DOM carregado');
            
            const form = document.getElementById('clientForm');
            const submitBtn = document.getElementById('submitBtn');
            
            console.log('🔍 [CLIENT FORM] Form encontrado:', !!form);
            console.log('🔍 [CLIENT FORM] SubmitBtn encontrado:', !!submitBtn);
            
            if (!form) {
                console.error('❌ [CLIENT FORM] Formulário não encontrado!');
                return;
            }
            
            // Log do action do formulário
            console.log('🔍 [CLIENT FORM] Action URL:', form.action);
            console.log('🔍 [CLIENT FORM] Method:', form.method);
            console.log('🔍 [CLIENT FORM] CSRF Token presente:', !!document.querySelector('input[name="_token"]'));
            
            // Adicionar listener no botão para log
            if (submitBtn) {
                submitBtn.addEventListener('click', function(e) {
                    console.log('🔍 [CLIENT FORM] ========== BOTÃO CLICADO ==========');
                    const nameField = form.querySelector('[name="name"]');
                    console.log('🔍 [CLIENT FORM] Nome preenchido:', nameField ? nameField.value : 'campo não encontrado');
                    console.log('🔍 [CLIENT FORM] Form action:', form.action);
                    console.log('🔍 [CLIENT FORM] Form method:', form.method);
                }, true); // Usar capture phase
            }
            
            // REMOVER TODA INTERCEPTAÇÃO - Deixar o formulário fazer submit puro
            // Apenas logar quando o submit acontecer
            form.addEventListener('submit', function(e) {
                console.log('🔍 [CLIENT FORM] ========== EVENTO SUBMIT DISPARADO ==========');
                console.log('🔍 [CLIENT FORM] Action:', form.action);
                console.log('🔍 [CLIENT FORM] Method:', form.method);
                console.log('🔍 [CLIENT FORM] NÃO PREVENINDO - Permitindo submit normal');
                // NÃO fazer preventDefault() - deixar submit acontecer normalmente
            }, false);
            
            console.log('✅ [CLIENT FORM] Event listeners adicionados');
        });
        let phoneCount = 1;
        let emailCount = 1;

        // Máscara de CEP
        document.getElementById('cep')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 5) {
                value = value.substring(0, 5) + '-' + value.substring(5, 8);
            }
            e.target.value = value;
        });

        // Variável para controlar se já está buscando
        let isSearchingCEP = false;

        // Buscar CEP via API ViaCEP
        async function searchCEP(cep) {
            // Evitar múltiplas buscas simultâneas
            if (isSearchingCEP) {
                return;
            }

            // Remove caracteres não numéricos
            cep = cep.replace(/\D/g, '');
            
            if (cep.length !== 8) {
                alert('CEP deve conter 8 dígitos');
                return;
            }

            // Mostrar loading
            const cepInput = document.getElementById('cep');
            const originalValue = cepInput.value;
            isSearchingCEP = true;
            cepInput.disabled = true;
            cepInput.value = 'Buscando...';

            try {
                const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                
                if (!response.ok) {
                    throw new Error('Erro na requisição');
                }
                
                const data = await response.json();

                if (data.erro) {
                    alert('CEP não encontrado. Verifique o CEP digitado.');
                    cepInput.value = originalValue;
                    cepInput.disabled = false;
                    isSearchingCEP = false;
                    return;
                }

                // Preencher campos automaticamente
                document.getElementById('address').value = data.logradouro || '';
                document.getElementById('district').value = data.bairro || '';
                document.getElementById('city').value = data.localidade || '';
                const stateField = document.getElementById('state');
                if (stateField) {
                    stateField.value = (data.uf || '').toUpperCase();
                }

                // Remover readonly dos campos preenchidos (exceto state que já é editável)
                document.getElementById('address').removeAttribute('readonly');
                document.getElementById('district').removeAttribute('readonly');
                document.getElementById('city').removeAttribute('readonly');

                // Restaurar valor original do CEP formatado
                cepInput.value = originalValue;

                // Focar no campo número
                document.getElementById('number').focus();

            } catch (error) {
                console.error('Erro ao buscar CEP:', error);
                alert('Erro ao buscar CEP. Tente novamente.');
                cepInput.value = originalValue;
            } finally {
                cepInput.disabled = false;
                isSearchingCEP = false;
            }
        }

        // Buscar CEP automaticamente quando o usuário sair do campo (se tiver 8 dígitos)
        document.getElementById('cep')?.addEventListener('blur', function(e) {
            const cep = e.target.value.replace(/\D/g, '');
            if (cep.length === 8 && !isSearchingCEP) {
                // Aguardar um pouco antes de buscar (para não conflitar com o botão)
                setTimeout(() => {
                    if (document.activeElement !== document.getElementById('cep') && !isSearchingCEP) {
                        searchCEP(cep);
                    }
                }, 300);
            }
        });

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
