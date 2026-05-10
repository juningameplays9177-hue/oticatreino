@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Detalhes da Conta a Receber</h1>
            <div class="flex space-x-2">
                <a href="{{ route('finance.receivables.pdf', $receivable) }}" target="_blank" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    Gerar PDF
                </a>
                <a href="{{ route('finance.receivables.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    ← Voltar
                </a>
            </div>
        </div>
        
        <div class="bg-white shadow rounded-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Informações Básicas</h3>
                    <div class="space-y-2">
                        <p><strong>Número:</strong> #{{ $receivable->id }}</p>
                        <p><strong>Cliente:</strong> {{ $receivable->customer->name ?? 'N/A' }}</p>
                        @if($receivable->customer && $receivable->customer->cpf_cnpj)
                        <p><strong>CPF/CNPJ:</strong> {{ $receivable->customer->cpf_cnpj }}</p>
                        @endif
                        @if($receivable->store)
                        <p><strong>Loja:</strong> {{ $receivable->store->name }}</p>
                        @endif
                        @if($receivable->sale)
                        <p><strong>Venda:</strong> #{{ $receivable->sale->sale_number ?? $receivable->sale->id }}</p>
                        @endif
                    </div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Valores e Status</h3>
                    <div class="space-y-2">
                        <p><strong>Data de Emissão:</strong> {{ $receivable->issue_date->format('d/m/Y') }}</p>
                        <p><strong>Data de Vencimento:</strong> {{ $receivable->due_date->format('d/m/Y') }}</p>
                        <p><strong>Valor Original:</strong> R$ {{ number_format($receivable->original_amount, 2, ',', '.') }}</p>
                        <p><strong>Valor Pago:</strong> R$ {{ number_format($receivable->getPaidAmount(), 2, ',', '.') }}</p>
                        <p><strong>Saldo Devedor:</strong> <span class="font-bold text-red-600">R$ {{ number_format($receivable->balance_amount, 2, ',', '.') }}</span></p>
                        <p><strong>Status:</strong> 
                            @if($receivable->status === 'paid')
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">PAGO</span>
                            @elseif($receivable->status === 'partial')
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">PAGO PARCIALMENTE</span>
                            @else
                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">ABERTO</span>
                            @endif
                        </p>
                        @if($receivable->isOverdue())
                        <p class="text-red-600 font-semibold">⚠️ Vencida há {{ $receivable->getDaysOverdue() }} dia(s)</p>
                        @endif
                    </div>
                </div>
            </div>
            
            @if($receivable->status !== 'canceled')
            <div class="mb-6">
                <button onclick="openReceiveModal({{ $receivable->id }})" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    💰 Registrar Pagamento
                </button>
                @if($receivable->status === 'paid')
                    <p class="text-sm text-gray-600 mt-2">💡 Você pode registrar pagamentos adicionais mesmo em contas já pagas (ex: antecipação de parcelas).</p>
                @endif
            </div>
            @endif

            <h2 class="text-xl font-semibold mb-4">Pagamentos</h2>
            @if($receivable->payments->count() > 0)
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Método</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($receivable->payments as $payment)
                            <tr>
                                <td class="px-6 py-4">{{ $payment->paid_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4">R$ {{ number_format($payment->amount, 2, ',', '.') }}</td>
                                <td class="px-6 py-4">{{ $payment->method }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-gray-500">Nenhum pagamento registrado.</p>
            @endif
        </div>
    </div>
</div>
@endsection

