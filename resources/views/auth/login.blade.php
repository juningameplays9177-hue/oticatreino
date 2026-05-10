<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <!-- Card de Login -->
            <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl p-6 sm:p-8 border border-slate-200/50">
                <!-- Logo e Título -->
                <div class="text-center mb-8">
                    <h1 class="text-3xl sm:text-4xl font-bold text-slate-800 mb-2">
                        Hospital dos Óculos
                    </h1>
                    <p class="text-sm sm:text-base text-slate-500 font-medium">
                        Acesso ao Painel
                    </p>
                </div>

                <!-- Formulário -->
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Email -->
                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-2">
                            Email
                        </label>
                        <input
                            id="email"
                            class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors duration-200 px-4 py-3 text-base"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="seu@email.com"
                        />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <!-- Senha -->
                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-2">
                            Senha
                        </label>
                        <input
                            id="password"
                            class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors duration-200 px-4 py-3 text-base"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <!-- Data de Trabalho -->
                    <div class="mb-6">
                        <label for="work_date" class="block text-sm font-medium text-slate-700 mb-2">
                            Data de Trabalho <span class="text-xs text-slate-500">(para lançamentos retroativos)</span>
                        </label>
                        <input
                            id="work_date"
                            class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors duration-200 px-4 py-3 text-base"
                            type="date"
                            name="work_date"
                            value="{{ date('Y-m-d') }}"
                            min="{{ \Carbon\Carbon::now('America/Sao_Paulo')->startOfMonth()->format('Y-m-d') }}"
                            max="{{ \Carbon\Carbon::now('America/Sao_Paulo')->format('Y-m-d') }}"
                            required
                        />
                        <p class="mt-1 text-xs text-slate-500">
                            Selecione uma data entre {{ \Carbon\Carbon::now('America/Sao_Paulo')->startOfMonth()->format('d/m/Y') }} e {{ \Carbon\Carbon::now('America/Sao_Paulo')->format('d/m/Y') }}
                        </p>
                        <x-input-error :messages="$errors->get('work_date')" class="mt-2" />
                    </div>

                    <!-- Lembrar-me e Esqueci senha -->
                    <div class="flex items-center justify-between mb-6">
                        <label for="remember_me" class="inline-flex items-center">
                            <input
                                id="remember_me"
                                type="checkbox"
                                class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                name="remember"
                            >
                            <span class="ml-2 text-sm text-slate-600">Lembrar-me</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a class="underline text-sm text-blue-600 hover:text-blue-500 transition-colors" href="{{ route('password.request') }}">
                                Esqueceu a senha?
                            </a>
                        @endif
                    </div>

                    <!-- Botão de Login -->
                    <button
                        type="submit"
                        class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 px-4 rounded-lg shadow-md hover:shadow-lg transform hover:scale-[1.02] transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        Entrar
                    </button>
                </form>

                <!-- Mensagens de Erro Gerais -->
                @if ($errors->any())
                    <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-sm text-red-800 font-medium">Verifique os erros acima e tente novamente.</p>
                    </div>
                @endif
            </div>

            <!-- Rodapé -->
            <p class="mt-6 text-center text-xs sm:text-sm text-slate-500">
                © {{ date('Y') }} Hospital dos Óculos. Todos os direitos reservados.
            </p>
        </div>
    </div>
</x-guest-layout>

