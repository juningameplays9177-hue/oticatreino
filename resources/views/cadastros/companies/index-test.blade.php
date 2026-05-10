<x-app-layout title="Teste - Minhas Empresas">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-slate-900 mb-6">Teste - Minhas Empresas</h1>
        <p class="text-slate-600">Se você está vendo esta página, a rota está funcionando!</p>
        <p class="text-slate-600 mt-2">Total de empresas: {{ $companies->total() ?? 0 }}</p>
    </div>
</x-app-layout>

