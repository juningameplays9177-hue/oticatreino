@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-8">
        <div class="bg-white shadow rounded-lg p-6">
            <h1 class="text-2xl font-bold mb-6">Espelho de Caixa - Sessão #{{ $session->id }}</h1>
            
            <div class="mb-6">
                <p><strong>Loja:</strong> {{ $session->store->name ?? 'N/A' }}</p>
                <p><strong>Aberto por:</strong> {{ $session->openedBy->name ?? 'N/A' }}</p>
                <p><strong>Aberto em:</strong> {{ $session->opened_at->format('d/m/Y H:i') }}</p>
                <p><strong>Valor inicial:</strong> R$ {{ number_format($session->opening_amount, 2, ',', '.') }}</p>
                @if($session->closed_at)
                    <p><strong>Fechado em:</strong> {{ $session->closed_at->format('d/m/Y H:i') }}</p>
                    <p><strong>Valor final:</strong> R$ {{ number_format($session->closing_amount, 2, ',', '.') }}</p>
                @endif
            </div>

            <h2 class="text-xl font-semibold mb-4">Movimentos</h2>
            @if($session->movements->count() > 0)
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Método</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($session->movements as $movement)
                            <tr>
                                <td class="px-6 py-4">{{ $movement->type === 'in' ? 'Entrada' : 'Saída' }}</td>
                                <td class="px-6 py-4">{{ $movement->method }}</td>
                                <td class="px-6 py-4">R$ {{ number_format($movement->amount, 2, ',', '.') }}</td>
                                <td class="px-6 py-4">{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-gray-500">Nenhum movimento registrado.</p>
            @endif
        </div>
    </div>
</div>
@endsection

