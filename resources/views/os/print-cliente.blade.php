<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>O.S. {{ $serviceOrder->os_number }} - 3 Vias</title>
    <script>
        // Abrir diálogo de impressão automaticamente ao carregar a página
        window.onload = function() {
            window.print();
        };
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 11px; 
            padding: 15px; 
            color: #000;
        }
        .via-container {
            margin-bottom: 30px;
            page-break-after: always;
            border: 1px solid #ccc;
            padding: 15px;
            position: relative;
        }
        .via-container:last-child {
            page-break-after: auto;
        }
        .header { 
            text-align: center; 
            margin-bottom: 15px; 
            border-bottom: 2px solid #000; 
            padding-bottom: 8px; 
        }
        .header h1 { 
            margin: 0; 
            font-size: 18px; 
            font-weight: bold;
        }
        .header p { 
            margin: 3px 0; 
            font-size: 10px;
        }
        .os-number {
            font-size: 14px;
            font-weight: bold;
            margin-top: 8px;
        }
        .via-label {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #000;
            color: #fff;
            padding: 4px 12px;
            font-size: 9px;
            font-weight: bold;
        }
        .info-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 15px; 
            margin-bottom: 15px; 
        }
        .section { 
            margin-bottom: 15px; 
            page-break-inside: avoid; 
        }
        .section h2 { 
            font-size: 12px; 
            border-bottom: 1px solid #333; 
            padding-bottom: 4px; 
            margin-bottom: 8px; 
            font-weight: bold; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 12px; 
            font-size: 10px;
        }
        table th, table td { 
            border: 1px solid #333; 
            padding: 5px; 
            text-align: left; 
        }
        table th { 
            background-color: #f0f0f0; 
            font-weight: bold; 
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals { 
            margin-top: 12px; 
        }
        .totals table { 
            width: 250px; 
            margin-left: auto; 
        }
        .signature { 
            margin-top: 30px; 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 40px; 
        }
        .signature div { 
            border-top: 1px solid #000; 
            padding-top: 8px; 
            text-align: center; 
            font-size: 9px;
        }
        .payment-info {
            margin-top: 10px;
            padding: 8px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        .payment-info p {
            margin: 3px 0;
        }
        @media print {
            body { padding: 10px; }
            .via-container { 
                page-break-after: always;
                border: 1px solid #ccc;
            }
            .via-container:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    @php
        $p = $serviceOrder->prescription;
        $hasPrescription = $p !== null;
        $sale = $serviceOrder->sale;
        $totalPaid = 0;
        $paymentMethods = [];
        
        // Mapeamento de métodos de pagamento
        $methodNames = [
            'money' => 'Dinheiro',
            'pix' => 'PIX',
            'card_credit' => 'Cartão de Crédito',
            'card_debit' => 'Cartão de Débito',
            'boleto' => 'Boleto',
            'carne' => 'Carnê',
            'sinal' => 'Sinal',
            'other' => 'Outros',
        ];
        
        if ($sale && $sale->payments) {
            foreach ($sale->payments as $payment) {
                $totalPaid += $payment->amount;
                $methodName = $methodNames[$payment->method] ?? ucfirst($payment->method);
                if (!isset($paymentMethods[$methodName])) {
                    $paymentMethods[$methodName] = 0;
                }
                $paymentMethods[$methodName] += $payment->amount;
            }
        } else {
            // Se não houver sale, usar advance_value como pagamento
            if ($serviceOrder->advance_value > 0) {
                $totalPaid = $serviceOrder->advance_value;
                $advanceType = $serviceOrder->advance_type ?? 'sinal';
                $methodName = $methodNames[$advanceType] ?? ucfirst($advanceType);
                $paymentMethods[$methodName] = $serviceOrder->advance_value;
            }
        }
    @endphp

    {{-- VIA DO CLIENTE --}}
    <div class="via-container">
        <div class="via-label">VIA DO CLIENTE</div>
        
        <div class="header">
            <h1>{{ $serviceOrder->company?->name ?? $serviceOrder->company?->trade_name ?? 'Hospital dos Óculos' }}</h1>
            <p>{{ $serviceOrder->store?->name ?? 'N/A' }}</p>
            @if($serviceOrder->store?->address)
                <p>{{ $serviceOrder->store->address }}</p>
            @endif
            @if($serviceOrder->store?->phone)
                <p>Tel: {{ $serviceOrder->store->phone }}</p>
            @endif
            <p class="os-number">ORDEM DE SERVIÇO Nº {{ $serviceOrder->os_number }}</p>
        </div>

        <div class="info-grid">
            <div>
                <p><strong>Cliente:</strong> {{ $serviceOrder->client?->name ?? 'N/A' }}</p>
                <p><strong>CPF/CNPJ:</strong> {{ $serviceOrder->client?->cpf_cnpj ?? 'N/A' }}</p>
                @if($serviceOrder->client?->phone)
                    <p><strong>Telefone:</strong> {{ $serviceOrder->client->phone }}</p>
                @endif
            </div>
            <div>
                <p><strong>Data de Registro:</strong> {{ $serviceOrder->registered_at ? $serviceOrder->registered_at->format('d/m/Y H:i') : 'N/A' }}</p>
                <p><strong>Previsão de Entrega:</strong> {{ $serviceOrder->delivery_date ? $serviceOrder->delivery_date->format('d/m/Y') . ($serviceOrder->delivery_time ? ' às ' . $serviceOrder->delivery_time->format('H:i') : '') : 'N/A' }}</p>
            </div>
        </div>

        @if($hasPrescription && $p)
            <div class="section">
                <h2>Dados da Receita Médica</h2>
                <table>
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
                <div style="margin-top: 10px;">
                    @if($p->custom_adicao)
                        <p><strong>Adição:</strong> {{ $p->custom_adicao }}</p>
                    @endif
                    @if($p->custom_doctor_name)
                        <p><strong>Médico:</strong> {{ $p->custom_doctor_name }}</p>
                    @endif
                    @if($p->custom_valid_until)
                        <p><strong>Válida até:</strong> 
                            @if(is_string($p->custom_valid_until))
                                {{ \Carbon\Carbon::parse($p->custom_valid_until)->format('d/m/Y') }}
                            @else
                                {{ $p->custom_valid_until->format('d/m/Y') }}
                            @endif
                        </p>
                    @endif
                    @if(!empty($p->custom_notes))
                        <p><strong>Observações:</strong> {{ $p->custom_notes }}</p>
                    @endif
                </div>
            </div>
        @endif

        <div class="section">
            <h2>Itens da Ordem de Serviço</h2>
            <table>
                <thead>
                    <tr>
                        <th>Produto/Serviço</th>
                        <th class="text-center">Qtd</th>
                        <th class="text-right">Valor Unit.</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($serviceOrder->items ?? [] as $item)
                        <tr>
                            <td>{{ $item->name ?? 'N/A' }}</td>
                            <td class="text-center">{{ number_format($item->qty ?? 0, 3, ',', '.') }} {{ $item->unit ?? 'UN' }}</td>
                            <td class="text-right">R$ {{ number_format($item->unit_price ?? 0, 2, ',', '.') }}</td>
                            <td class="text-right">R$ {{ number_format($item->line_total ?? 0, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">Nenhum item cadastrado nesta OS.</td>
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
                @if(($serviceOrder->discount_value ?? 0) > 0)
                <tr>
                    <th>Desconto</th>
                    <td class="text-right">R$ {{ number_format($serviceOrder->discount_value ?? 0, 2, ',', '.') }}</td>
                </tr>
                @endif
                <tr>
                    <th><strong>Total</strong></th>
                    <td class="text-right"><strong>R$ {{ number_format($serviceOrder->total_value ?? 0, 2, ',', '.') }}</strong></td>
                </tr>
            </table>
        </div>

        <div class="payment-info">
            <p><strong>Valor Pago:</strong> R$ {{ number_format($totalPaid, 2, ',', '.') }}</p>
            <p><strong>Forma de Pagamento:</strong>
                @if(count($paymentMethods) > 0)
                    @foreach($paymentMethods as $method => $amount)
                        {{ $method }}: R$ {{ number_format($amount, 2, ',', '.') }}@if(!$loop->last), @endif
                    @endforeach
                @else
                    Não informado
                @endif
            </p>
        </div>

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
    </div>

    {{-- VIA DA LOJA --}}
    <div class="via-container">
        <div class="via-label">VIA DA LOJA</div>
        
        <div class="header">
            <h1>{{ $serviceOrder->company?->name ?? $serviceOrder->company?->trade_name ?? 'Hospital dos Óculos' }}</h1>
            <p>{{ $serviceOrder->store?->name ?? 'N/A' }}</p>
            @if($serviceOrder->store?->address)
                <p>{{ $serviceOrder->store->address }}</p>
            @endif
            @if($serviceOrder->store?->phone)
                <p>Tel: {{ $serviceOrder->store->phone }}</p>
            @endif
            <p class="os-number">ORDEM DE SERVIÇO Nº {{ $serviceOrder->os_number }}</p>
        </div>

        <div class="info-grid">
            <div>
                <p><strong>Cliente:</strong> {{ $serviceOrder->client?->name ?? 'N/A' }}</p>
                <p><strong>CPF/CNPJ:</strong> {{ $serviceOrder->client?->cpf_cnpj ?? 'N/A' }}</p>
                @if($serviceOrder->client?->phone)
                    <p><strong>Telefone:</strong> {{ $serviceOrder->client->phone }}</p>
                @endif
            </div>
            <div>
                <p><strong>Data de Registro:</strong> {{ $serviceOrder->registered_at ? $serviceOrder->registered_at->format('d/m/Y H:i') : 'N/A' }}</p>
                <p><strong>Previsão de Entrega:</strong> {{ $serviceOrder->delivery_date ? $serviceOrder->delivery_date->format('d/m/Y') . ($serviceOrder->delivery_time ? ' às ' . $serviceOrder->delivery_time->format('H:i') : '') : 'N/A' }}</p>
            </div>
        </div>

        @if($hasPrescription && $p)
            <div class="section">
                <h2>Dados da Receita Médica</h2>
                <table>
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
                <div style="margin-top: 10px;">
                    @if($p->custom_adicao)
                        <p><strong>Adição:</strong> {{ $p->custom_adicao }}</p>
                    @endif
                    @if($p->custom_doctor_name)
                        <p><strong>Médico:</strong> {{ $p->custom_doctor_name }}</p>
                    @endif
                    @if($p->custom_valid_until)
                        <p><strong>Válida até:</strong> 
                            @if(is_string($p->custom_valid_until))
                                {{ \Carbon\Carbon::parse($p->custom_valid_until)->format('d/m/Y') }}
                            @else
                                {{ $p->custom_valid_until->format('d/m/Y') }}
                            @endif
                        </p>
                    @endif
                    @if(!empty($p->custom_notes))
                        <p><strong>Observações:</strong> {{ $p->custom_notes }}</p>
                    @endif
                </div>
            </div>
        @endif

        <div class="section">
            <h2>Itens da Ordem de Serviço</h2>
            <table>
                <thead>
                    <tr>
                        <th>Produto/Serviço</th>
                        <th class="text-center">Qtd</th>
                        <th class="text-right">Valor Unit.</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($serviceOrder->items ?? [] as $item)
                        <tr>
                            <td>{{ $item->name ?? 'N/A' }}</td>
                            <td class="text-center">{{ number_format($item->qty ?? 0, 3, ',', '.') }} {{ $item->unit ?? 'UN' }}</td>
                            <td class="text-right">R$ {{ number_format($item->unit_price ?? 0, 2, ',', '.') }}</td>
                            <td class="text-right">R$ {{ number_format($item->line_total ?? 0, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">Nenhum item cadastrado nesta OS.</td>
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
                @if(($serviceOrder->discount_value ?? 0) > 0)
                <tr>
                    <th>Desconto</th>
                    <td class="text-right">R$ {{ number_format($serviceOrder->discount_value ?? 0, 2, ',', '.') }}</td>
                </tr>
                @endif
                <tr>
                    <th><strong>Total</strong></th>
                    <td class="text-right"><strong>R$ {{ number_format($serviceOrder->total_value ?? 0, 2, ',', '.') }}</strong></td>
                </tr>
            </table>
        </div>

        <div class="payment-info">
            <p><strong>Valor Pago:</strong> R$ {{ number_format($totalPaid, 2, ',', '.') }}</p>
            <p><strong>Forma de Pagamento:</strong>
                @if(count($paymentMethods) > 0)
                    @foreach($paymentMethods as $method => $amount)
                        {{ $method }}: R$ {{ number_format($amount, 2, ',', '.') }}@if(!$loop->last), @endif
                    @endforeach
                @else
                    Não informado
                @endif
            </p>
        </div>

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
    </div>

    {{-- VIA DO LABORATÓRIO --}}
    <div class="via-container">
        <div class="via-label">VIA DO LABORATÓRIO</div>
        
        <div class="header">
            <h1>{{ $serviceOrder->company?->name ?? $serviceOrder->company?->trade_name ?? 'Hospital dos Óculos' }}</h1>
            <p>{{ $serviceOrder->store?->name ?? 'N/A' }}</p>
            @if($serviceOrder->store?->address)
                <p>{{ $serviceOrder->store->address }}</p>
            @endif
            @if($serviceOrder->store?->phone)
                <p>Tel: {{ $serviceOrder->store->phone }}</p>
            @endif
            <p class="os-number">ORDEM DE SERVIÇO Nº {{ $serviceOrder->os_number }}</p>
        </div>

        <div class="info-grid">
            <div>
                <p><strong>Cliente:</strong> {{ $serviceOrder->client?->name ?? 'N/A' }}</p>
                <p><strong>CPF/CNPJ:</strong> {{ $serviceOrder->client?->cpf_cnpj ?? 'N/A' }}</p>
                @if($serviceOrder->client?->phone)
                    <p><strong>Telefone:</strong> {{ $serviceOrder->client->phone }}</p>
                @endif
            </div>
            <div>
                <p><strong>Data de Registro:</strong> {{ $serviceOrder->registered_at ? $serviceOrder->registered_at->format('d/m/Y H:i') : 'N/A' }}</p>
                <p><strong>Previsão de Entrega:</strong> {{ $serviceOrder->delivery_date ? $serviceOrder->delivery_date->format('d/m/Y') . ($serviceOrder->delivery_time ? ' às ' . $serviceOrder->delivery_time->format('H:i') : '') : 'N/A' }}</p>
            </div>
        </div>

        @if($hasPrescription && $p)
            <div class="section">
                <h2>Dados da Receita Médica</h2>
                <table>
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
                <div style="margin-top: 10px;">
                    @if($p->custom_adicao)
                        <p><strong>Adição:</strong> {{ $p->custom_adicao }}</p>
                    @endif
                    @if($p->custom_doctor_name)
                        <p><strong>Médico:</strong> {{ $p->custom_doctor_name }}</p>
                    @endif
                    @if($p->custom_valid_until)
                        <p><strong>Válida até:</strong> 
                            @if(is_string($p->custom_valid_until))
                                {{ \Carbon\Carbon::parse($p->custom_valid_until)->format('d/m/Y') }}
                            @else
                                {{ $p->custom_valid_until->format('d/m/Y') }}
                            @endif
                        </p>
                    @endif
                    @if(!empty($p->custom_notes))
                        <p><strong>Observações:</strong> {{ $p->custom_notes }}</p>
                    @endif
                </div>
            </div>
        @endif

        <div class="section">
            <h2>Itens da Ordem de Serviço</h2>
            <table>
                <thead>
                    <tr>
                        <th>Produto/Serviço</th>
                        <th class="text-center">Qtd</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($serviceOrder->items ?? [] as $item)
                        <tr>
                            <td>{{ $item->name ?? 'N/A' }}</td>
                            <td class="text-center">{{ number_format($item->qty ?? 0, 3, ',', '.') }} {{ $item->unit ?? 'UN' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center">Nenhum item cadastrado nesta OS.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

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
    </div>
</body>
</html>
