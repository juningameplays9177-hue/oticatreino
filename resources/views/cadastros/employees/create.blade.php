<x-app-layout title="Novo Funcionário">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 md:p-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-xl md:text-2xl font-bold text-slate-900">Novo Funcionário</h1>
                <a href="{{ route('cadastros.employees.index') }}" class="px-3 py-1.5 text-sm bg-slate-100 text-slate-700 font-medium rounded-lg hover:bg-slate-200">Voltar</a>
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

            <form method="POST" action="{{ route('cadastros.employees.store') }}">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Dados Básicos -->
                    <div class="md:col-span-2">
                        <h2 class="text-lg font-semibold text-slate-900 mb-3 border-b border-slate-200 pb-2">Dados Básicos</h2>
                    </div>

                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nome Completo *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="cpf" class="block text-sm font-medium text-slate-700 mb-1">CPF</label>
                        <input type="text" id="cpf" name="cpf" value="{{ old('cpf') }}" placeholder="000.000.000-00" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="rg" class="block text-sm font-medium text-slate-700 mb-1">RG</label>
                        <input type="text" id="rg" name="rg" value="{{ old('rg') }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Empresa e Loja -->
                    <div class="md:col-span-2 mt-2">
                        <h2 class="text-lg font-semibold text-slate-900 mb-3 border-b border-slate-200 pb-2">Empresa e Loja</h2>
                    </div>

                    <div>
                        <label for="company_id" class="block text-sm font-medium text-slate-700 mb-1">Empresa *</label>
                        <select id="company_id" name="company_id" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Selecione...</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->trade_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="store_id" class="block text-sm font-medium text-slate-700 mb-1">Loja</label>
                        <select id="store_id" name="store_id" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Selecione...</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>@if($store->abbreviation)[{{ $store->abbreviation }}]@endif {{ $store->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="role_func_id" class="block text-sm font-medium text-slate-700 mb-1">Função (Cargo)</label>
                        <select id="role_func_id" name="role_func_id" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Selecione...</option>
                            @foreach($jobFunctions as $jobFunction)
                                <option value="{{ $jobFunction->id }}" {{ old('role_func_id') == $jobFunction->id ? 'selected' : '' }}>{{ $jobFunction->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Contato -->
                    <div class="md:col-span-2 mt-2">
                        <h2 class="text-lg font-semibold text-slate-900 mb-3 border-b border-slate-200 pb-2">Contato</h2>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">Telefone</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone') }}" placeholder="(00) 0000-0000" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="mobile" class="block text-sm font-medium text-slate-700 mb-1">Celular</label>
                        <input type="text" id="mobile" name="mobile" value="{{ old('mobile') }}" placeholder="(00) 00000-0000" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Endereço -->
                    <div class="md:col-span-2 mt-2">
                        <h2 class="text-lg font-semibold text-slate-900 mb-3 border-b border-slate-200 pb-2">Endereço</h2>
                    </div>

                    <div>
                        <label for="zip_code" class="block text-sm font-medium text-slate-700 mb-1">CEP</label>
                        <input type="text" id="zip_code" name="zip_code" value="{{ old('zip_code') }}" placeholder="00000-000" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-slate-700 mb-1">Endereço</label>
                        <input type="text" id="address" name="address" value="{{ old('address') }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="district" class="block text-sm font-medium text-slate-700 mb-1">Bairro</label>
                        <input type="text" id="district" name="district" value="{{ old('district') }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="city" class="block text-sm font-medium text-slate-700 mb-1">Cidade</label>
                        <input type="text" id="city" name="city" value="{{ old('city') }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="state" class="block text-sm font-medium text-slate-700 mb-1">Estado (UF)</label>
                        <input type="text" id="state" name="state" value="{{ old('state') }}" maxlength="2" placeholder="RJ" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Observações -->
                    <div class="md:col-span-2 mt-2">
                        <label for="notes" class="block text-sm font-medium text-slate-700 mb-1">Observações</label>
                        <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <a href="{{ route('cadastros.employees.index') }}" class="px-4 py-2 bg-slate-100 text-slate-700 font-medium rounded-lg hover:bg-slate-200">Cancelar</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

