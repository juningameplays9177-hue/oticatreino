@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Conciliação Bancária</h1>
            <p class="mt-2 text-sm text-gray-600">Concilie extratos bancários com as transações do sistema</p>
        </div>

        <div class="bg-white shadow rounded-lg">
            <div class="p-6">
                @if(isset($error))
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                        <p class="text-yellow-800">{{ $error }}</p>
                    </div>
                @endif

                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Conciliações</h2>
                    <button onclick="importStatement()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Importar Extrato
                    </button>
                </div>

                @if($reconciliations->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Conta</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saldo Inicial</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saldo Final</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($reconciliations as $reconciliation)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $reconciliation->account->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $reconciliation->statement_date->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            R$ {{ number_format($reconciliation->starting_balance, 2, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            R$ {{ number_format($reconciliation->ending_balance, 2, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($reconciliation->status === 'closed')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Fechada</span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Aberta</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="#" class="text-blue-600 hover:text-blue-900">
                                                Ver Detalhes
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $reconciliations->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <p class="text-gray-500">Nenhuma conciliação encontrada.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function importStatement() {
    alert('Funcionalidade de importar extrato será implementada');
}
</script>
@endsection

