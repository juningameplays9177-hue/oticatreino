@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Meu Perfil</h1>
            <p class="mt-2 text-sm text-gray-600">Gerencie suas informações pessoais e atualize sua senha</p>
        </div>

        @if (session('status') === 'profile-updated')
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                Perfil atualizado com sucesso!
            </div>
        @endif

        @if (session('status') === 'password-updated')
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                Senha atualizada com sucesso!
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Informações do Perfil -->
            <div class="bg-white shadow-xl sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Informações do Perfil</h2>
                    <p class="mt-1 text-sm text-gray-600">Atualize suas informações pessoais</p>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('patch')

                        <!-- Nome -->
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nome
                            </label>
                            <input 
                                type="text" 
                                name="name" 
                                id="name" 
                                value="{{ old('name', $user->name) }}"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email
                            </label>
                            <input 
                                type="email" 
                                name="email" 
                                id="email" 
                                value="{{ old('email', $user->email) }}"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror"
                            >
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Botão Salvar -->
                        <div class="flex justify-end">
                            <button 
                                type="submit"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold transition-colors"
                            >
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Atualizar Senha -->
            <div class="bg-white shadow-xl sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Atualizar Senha</h2>
                    <p class="mt-1 text-sm text-gray-600">Altere sua senha para manter sua conta segura</p>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('put')

                        <!-- Senha Atual -->
                        <div class="mb-4">
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                                Senha Atual
                            </label>
                            <input 
                                type="password" 
                                name="current_password" 
                                id="current_password"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('current_password', 'updatePassword') border-red-500 @enderror"
                            >
                            @error('current_password', 'updatePassword')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Nova Senha -->
                        <div class="mb-4">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                Nova Senha
                            </label>
                            <input 
                                type="password" 
                                name="password" 
                                id="password"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password', 'updatePassword') border-red-500 @enderror"
                            >
                            @error('password', 'updatePassword')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Confirmar Nova Senha -->
                        <div class="mb-4">
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                                Confirmar Nova Senha
                            </label>
                            <input 
                                type="password" 
                                name="password_confirmation" 
                                id="password_confirmation"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>

                        <!-- Botão Atualizar Senha -->
                        <div class="flex justify-end">
                            <button 
                                type="submit"
                                class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold transition-colors"
                            >
                                Atualizar Senha
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Excluir Conta -->
        <div class="mt-6 bg-white shadow-xl sm:rounded-lg">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-red-600">Zona de Perigo</h2>
                <p class="mt-1 text-sm text-gray-600">Excluir permanentemente sua conta</p>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">
                    Uma vez que sua conta seja excluída, todos os seus recursos e dados serão permanentemente excluídos. 
                    Antes de excluir sua conta, faça o download de todos os dados ou informações que deseja manter.
                </p>
                <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Tem certeza que deseja excluir sua conta? Esta ação não pode ser desfeita.');">
                    @csrf
                    @method('delete')

                    <div class="mb-4">
                        <label for="delete_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Digite sua senha para confirmar
                        </label>
                        <input 
                            type="password" 
                            name="password" 
                            id="delete_password"
                            required
                            placeholder="Sua senha atual"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('password') border-red-500 @enderror"
                        >
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button 
                        type="submit"
                        class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold transition-colors"
                    >
                        Excluir Conta
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

