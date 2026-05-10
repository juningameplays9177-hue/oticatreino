@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Relatório PDV</h1>
            <p class="mt-2 text-sm text-gray-600">Relatório de vendas do PDV</p>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            @if(isset($error))
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <p class="text-yellow-800">{{ $error }}</p>
                </div>
            @endif

            @if($sales->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Número</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sales as $sale)
                                <tr>
                                    <td class="px-6 py-4">{{ $sale->sale_number ?? 'N/A' }}</td>
                                    <td class="px-6 py-4">{{ $sale->sale_date->format('d/m/Y H:i') }}</td>
                                    <td class="px-6 py-4">{{ $sale->customer->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4">R$ {{ number_format($sale->total_net, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500">Nenhuma venda encontrada no período.</p>
            @endif
        </div>
    </div>
</div>
@endsection

