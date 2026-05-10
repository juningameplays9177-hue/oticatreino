<x-app-layout title="Sessão Expirada">
    <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center">
                <div class="text-6xl mb-4">⚠️</div>
                <h1 class="text-3xl font-bold text-gray-900 mb-4">Sessão Expirada</h1>
                <p class="text-gray-600 mb-6">
                    Sua sessão expirou por segurança. Por favor, recarregue a página e tente novamente.
                </p>
                <div class="space-y-3">
                    <a href="{{ route('os.create') }}" class="block w-full bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                        🔄 Recarregar Página
                    </a>
                    <a href="{{ route('dashboard') }}" class="block w-full bg-gray-200 text-gray-800 font-bold py-3 px-6 rounded-lg hover:bg-gray-300 transition-colors">
                        🏠 Voltar ao Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
