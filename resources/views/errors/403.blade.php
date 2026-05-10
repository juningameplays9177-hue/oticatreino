<x-app-layout title="Acesso Negado">
    <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center">
                <div class="text-6xl mb-4">🚫</div>
                <h1 class="text-3xl font-bold text-gray-900 mb-4">403 - Acesso Negado</h1>
                <p class="text-gray-600 mb-2">
                    {{ $message ?? 'Você não tem permissão para acessar este recurso.' }}
                </p>
                <p class="text-sm text-gray-500 mb-6">
                    Se você acredita que isso é um erro, entre em contato com o administrador do sistema.
                </p>
                <div class="space-y-3">
                    <a href="{{ route('dashboard') }}" class="block w-full bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                        🏠 Voltar ao Dashboard
                    </a>
                    <a href="javascript:history.back()" class="block w-full bg-gray-200 text-gray-800 font-bold py-3 px-6 rounded-lg hover:bg-gray-300 transition-colors">
                        ← Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
