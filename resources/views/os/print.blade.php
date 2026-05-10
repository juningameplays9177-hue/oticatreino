<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>O.S. {{ $serviceOrder->os_number }}</title>
    <script>
        // Abrir diálogo de impressão automaticamente ao carregar a página
        window.onload = function() {
            window.print();
        };
    </script>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 5px 0; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .section { margin-bottom: 20px; page-break-inside: avoid; }
        .section h2 { font-size: 14px; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 10px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th, table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table th { background-color: #f3f4f6; font-weight: bold; }
        .text-right { text-align: right; }
        .totals { margin-top: 20px; }
        .totals table { width: 300px; margin-left: auto; }
        .signature { margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        .signature div { border-top: 1px solid #000; padding-top: 10px; text-align: center; }
        @media print {
            body { padding: 15px; }
            .section { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $serviceOrder->company?->name ?? $serviceOrder->company?->trade_name ?? 'Hospital dos Óculos' }}</h1>
        <p>{{ $serviceOrder->store?->name ?? 'N/A' }}</p>
        <p><strong>ORDEM DE SERVIÇO Nº {{ $serviceOrder->os_number }}</strong></p>
    </div>

    <div class="info-grid">
        <div>
            <p><strong>Cliente:</strong> {{ $serviceOrder->client?->name ?? 'N/A' }}</p>
            <p><strong>CPF/CNPJ:</strong> {{ $serviceOrder->client?->cpf_cnpj ?? 'N/A' }}</p>
            <p><strong>Data de Registro:</strong> {{ $serviceOrder->registered_at ? $serviceOrder->registered_at->format('d/m/Y H:i') : 'N/A' }}</p>
        </div>
        <div>
            <p><strong>Funcionário:</strong> {{ $serviceOrder->employee?->name ?? 'N/A' }}</p>
            <p><strong>Previsão de Entrega:</strong> {{ $serviceOrder->delivery_date ? $serviceOrder->delivery_date->format('d/m/Y') . ($serviceOrder->delivery_time ? ' ' . $serviceOrder->delivery_time->format('H:i') : '') : 'N/A' }}</p>
            <p><strong>Status:</strong> {{ $serviceOrder->status ?? 'N/A' }}</p>
        </div>
    </div>

    <div class="section">
        <h2>Itens da Ordem de Serviço</h2>
        <table>
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Produto/Serviço</th>
                    <th>Qtd</th>
                    <th>Unit.</th>
                    <th class="text-right">Valor Unit.</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($serviceOrder->items ?? [] as $item)
                    <tr>
                        <td>{{ $item->type ?? 'PRODUTO' }}</td>
                        <td>{{ $item->name ?? 'N/A' }}</td>
                        <td>{{ number_format($item->qty ?? 0, 3, ',', '.') }}</td>
                        <td>{{ $item->unit ?? 'UN' }}</td>
                        <td class="text-right">R$ {{ number_format($item->unit_price ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($item->line_total ?? 0, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Nenhum item cadastrado nesta OS.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="totals">
        <table>
            <tr>
                <th>Subtotal</th>
                <td class="text-right">R$ {{ number_format($serviceOrder->subtotal ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Desconto</th>
                <td class="text-right">R$ {{ number_format($serviceOrder->discount_value ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <th><strong>Total</strong></th>
                <td class="text-right"><strong>R$ {{ number_format($serviceOrder->total_value ?? 0, 2, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <th>Adiantamento</th>
                <td class="text-right">{{ $serviceOrder->advance_type ?? 'SEM' }} - R$ {{ number_format($serviceOrder->advance_value ?? 0, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    @php
        $p = $serviceOrder->prescription;
        // Sempre mostrar se houver receita associada
        $hasPrescription = $p !== null;
    @endphp
    @if($hasPrescription && $p)
        <div class="section">
            <h2>Dados da Receita Médica</h2>
            @php
                $presc = $p->prescription ?? null;
                $custom = $p->use_custom ?? false;
            @endphp
            <table style="margin-top: 10px;">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Campo</th>
                            <th style="width: 18.75%;">OD Longe</th>
                            <th style="width: 18.75%;">OE Longe</th>
                            <th style="width: 18.75%;">OD Perto</th>
                            <th style="width: 18.75%;">OE Perto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Esférico</strong></td>
                            <td>{{ $p->custom_longe_esferico_od ?? '-' }}</td>
                            <td>{{ $p->custom_longe_esferico_oe ?? '-' }}</td>
                            <td>{{ $p->custom_perto_esferico_od ?? '-' }}</td>
                            <td>{{ $p->custom_perto_esferico_oe ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Cilíndrico</strong></td>
                            <td>{{ $p->custom_longe_cilindrico_od ?? '-' }}</td>
                            <td>{{ $p->custom_longe_cilindrico_oe ?? '-' }}</td>
                            <td>{{ $p->custom_perto_cilindrico_od ?? '-' }}</td>
                            <td>{{ $p->custom_perto_cilindrico_oe ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Eixo</strong></td>
                            <td>{{ $p->custom_longe_eixo_od ?? '-' }}°</td>
                            <td>{{ $p->custom_longe_eixo_oe ?? '-' }}°</td>
                            <td>{{ $p->custom_perto_eixo_od ?? '-' }}°</td>
                            <td>{{ $p->custom_perto_eixo_oe ?? '-' }}°</td>
                        </tr>
                        <tr>
                            <td><strong>Altura</strong></td>
                            <td>{{ $p->custom_longe_altura_od ?? '-' }} mm</td>
                            <td>{{ $p->custom_longe_altura_oe ?? '-' }} mm</td>
                            <td>{{ $p->custom_perto_altura_od ?? '-' }} mm</td>
                            <td>{{ $p->custom_perto_altura_oe ?? '-' }} mm</td>
                        </tr>
                        <tr>
                            <td><strong>DNP</strong></td>
                            <td>{{ $p->custom_longe_dnp_od ?? '-' }} mm</td>
                            <td>{{ $p->custom_longe_dnp_oe ?? '-' }} mm</td>
                            <td>{{ $p->custom_perto_dnp_od ?? '-' }} mm</td>
                            <td>{{ $p->custom_perto_dnp_oe ?? '-' }} mm</td>
                        </tr>
                    </tbody>
                </table>
                <div style="margin-top: 15px;">
                    <p><strong>Adição:</strong> {{ $p->custom_adicao ?? '-' }}</p>
                    <p><strong>Médico:</strong> {{ $p->custom_doctor_name ?? '-' }}</p>
                    <p><strong>Válida até:</strong> 
                        @if($p->custom_valid_until)
                            @if(is_string($p->custom_valid_until))
                                {{ \Carbon\Carbon::parse($p->custom_valid_until)->format('d/m/Y') }}
                            @else
                                {{ $p->custom_valid_until->format('d/m/Y') }}
                            @endif
                        @else
                            -
                        @endif
                    </p>
                    @if(!empty($p->custom_notes))
                        <p><strong>Observações:</strong> {{ $p->custom_notes }}</p>
                    @endif
                </div>
            @endif
        </div>
    @endif

    @if($serviceOrder->notes)
        <div class="section">
            <h2>Observações</h2>
            <p>{{ $serviceOrder->notes }}</p>
        </div>
    @endif

    <div class="signature">
        <div>
            <p>_________________________________</p>
            <p>Assinatura do Cliente</p>
        </div>
        <div>
            <p>_________________________________</p>
            <p>Assinatura do Funcionário</p>
        </div>
    </div>
</body>
</html>

