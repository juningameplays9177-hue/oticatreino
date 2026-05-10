@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Fluxo de Caixa</h1>
            <p class="mt-2 text-sm text-gray-600">Relatório de entradas e saídas</p>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            @if(isset($error))
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <p class="text-yellow-800">{{ $error }}</p>
                </div>
            @endif

            <div class="mb-4">
                <form method="GET" class="flex gap-4">
                    <input type="date" name="from" value="{{ $from }}" class="border rounded px-3 py-2">
                    <input type="date" name="to" value="{{ $to }}" class="border rounded px-3 py-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Filtrar</button>
                </form>
            </div>

            @if($transactions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Débito</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Crédito</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($transactions as $transaction)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $transaction->txn_date->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $transaction->description }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $transaction->drAccount->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $transaction->crAccount->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-gray-500">Nenhuma transação encontrada no período.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

