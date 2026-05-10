<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conta a Receber #{{ $receivable->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }
        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-info {
            text-align: center;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .document-title {
            font-size: 18px;
            color: #666;
            margin-top: 10px;
        }
        .info-section {
            margin-bottom: 25px;
        }
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }
        .info-value {
            flex: 1;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .table th {
            background-color: #f3f4f6;
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        .table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        .text-right {
            text-align: right;
        }
        .total-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #2563eb;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .total-label {
            font-weight: bold;
        }
        .total-value {
            font-weight: bold;
            font-size: 16px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 11px;
        }
        .status-open {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-partial {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-paid {
            background-color: #d1fae5;
            color: #065f46;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <div class="company-name">{{ $receivable->company->trade_name ?? $receivable->company->legal_name ?? 'Hospital dos Óculos' }}</div>
            <div class="document-title">CONTA A RECEBER</div>
        </div>
    </div>

    <div class="info-section">
        <div class="info-row">
            <div class="info-label">Número:</div>
            <div class="info-value">#{{ $receivable->id }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Cliente:</div>
            <div class="info-value">{{ $receivable->customer->name ?? 'N/A' }}</div>
        </div>
        @if($receivable->customer && $receivable->customer->cpf_cnpj)
        <div class="info-row">
            <div class="info-label">CPF/CNPJ:</div>
            <div class="info-value">{{ $receivable->customer->cpf_cnpj }}</div>
        </div>
        @endif
        @if($receivable->store)
        <div class="info-row">
            <div class="info-label">Loja:</div>
            <div class="info-value">{{ $receivable->store->name }}</div>
        </div>
        @endif
        @if($receivable->sale)
        <div class="info-row">
            <div class="info-label">Venda:</div>
            <div class="info-value">#{{ $receivable->sale->sale_number ?? $receivable->sale->id }}</div>
        </div>
        @endif
        <div class="info-row">
            <div class="info-label">Data de Emissão:</div>
            <div class="info-value">{{ $receivable->issue_date->format('d/m/Y') }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Data de Vencimento:</div>
            <div class="info-value">{{ $receivable->due_date->format('d/m/Y') }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Status:</div>
            <div class="info-value">
                @if($receivable->status === 'paid')
                    <span class="status-badge status-paid">PAGO</span>
                @elseif($receivable->status === 'partial')
                    <span class="status-badge status-partial">PAGO PARCIALMENTE</span>
                @else
                    <span class="status-badge status-open">ABERTO</span>
                @endif
            </div>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Descrição</th>
                <th class="text-right">Valor Original</th>
                <th class="text-right">Valor Pago</th>
                <th class="text-right">Saldo Devedor</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Conta a Receber #{{ $receivable->id }}</td>
                <td class="text-right">R$ {{ number_format($receivable->original_amount, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($receivable->getPaidAmount(), 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($receivable->balance_amount, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    @if($receivable->payments->count() > 0)
    <h3 style="margin-top: 30px; margin-bottom: 15px; font-size: 14px; font-weight: bold;">Histórico de Pagamentos</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Valor</th>
                <th>Método</th>
                <th>Conta</th>
            </tr>
        </thead>
        <tbody>
            @foreach($receivable->payments as $payment)
            <tr>
                <td>{{ $payment->paid_at->format('d/m/Y H:i') }}</td>
                <td class="text-right">R$ {{ number_format($payment->amount, 2, ',', '.') }}</td>
                <td>{{ ucfirst($payment->method) }}</td>
                <td>{{ $payment->account->name ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="total-section">
        <div class="total-row">
            <div class="total-label">Valor Original:</div>
            <div class="total-value">R$ {{ number_format($receivable->original_amount, 2, ',', '.') }}</div>
        </div>
        <div class="total-row">
            <div class="total-label">Total Pago:</div>
            <div class="total-value">R$ {{ number_format($receivable->getPaidAmount(), 2, ',', '.') }}</div>
        </div>
        <div class="total-row" style="font-size: 18px; color: #1e40af;">
            <div class="total-label">Saldo Devedor:</div>
            <div class="total-value">R$ {{ number_format($receivable->balance_amount, 2, ',', '.') }}</div>
        </div>
    </div>

    @if($receivable->isOverdue())
    <div style="margin-top: 30px; padding: 15px; background-color: #fee2e2; border-left: 4px solid #dc2626; border-radius: 4px;">
        <strong style="color: #991b1b;">⚠️ CONTA VENCIDA</strong>
        <p style="color: #991b1b; margin-top: 5px;">Esta conta está vencida há {{ $receivable->getDaysOverdue() }} dia(s).</p>
    </div>
    @endif

    <div class="footer">
        <p>Documento gerado em {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>{{ $receivable->company->trade_name ?? $receivable->company->legal_name ?? 'Hospital dos Óculos' }}</p>
    </div>
</body>
</html>

