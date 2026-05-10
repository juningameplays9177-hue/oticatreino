<?php

namespace App\Services;

use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServiceOrderImage;
use App\Models\ServiceOrderPrescription;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Client;
use App\Models\ClientPhone;
use App\Models\ClientEmail;
use App\Helpers\WorkDateHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class OsService
{
    public function create(array $data): ServiceOrder
    {
        \Illuminate\Support\Facades\Log::info('🔍 DEBUG: OsService::create iniciado', [
            'store_id' => $data['store_id'] ?? 'N/A',
            'client_id' => $data['client_id'] ?? 'N/A',
            'payment_type' => $data['payment_type'] ?? 'N/A',
            'items_count' => count($data['items'] ?? []),
        ]);
        
        DB::beginTransaction();

        try {
            // Usar is_conserto explícito do formulário; senão, verificar se há item Conserto nos itens
            $hasConserto = isset($data['is_conserto']) ? (bool) $data['is_conserto'] : false;
            if (!$hasConserto && !empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $product = Product::with('productType')->find($item['product_id'] ?? null);
                    if ($product) {
                        $productName = strtolower($product->name ?? '');
                        $productTypeName = $product->productType ? strtolower($product->productType->name ?? '') : '';
                        if (strpos($productName, 'conserto') !== false || strpos($productTypeName, 'conserto') !== false) {
                            $hasConserto = true;
                            break;
                        }
                    }
                }
            }
            
            // Gerar número da O.S. (Conserto = CO-2405+, OS = OS-2012+)
            $osNumber = ServiceOrder::generateOsNumber($data['store_id'], $hasConserto);

            // Obter data de trabalho (para lançamentos retroativos)
            $workDate = WorkDateHelper::getWorkDate();

            // Modo Conserto: criar cliente a partir do nome e contato informados
            $clientId = $data['client_id'] ?? null;
            if ($hasConserto && !empty($data['conserto_client_name'])) {
                $client = Client::create([
                    'name' => trim($data['conserto_client_name']),
                    'active' => true,
                    'notes' => !empty($data['conserto_client_contact'])
                        ? 'Contato: ' . trim($data['conserto_client_contact']) . ' (Conserto)'
                        : 'Cliente criado via Conserto',
                ]);
                $clientId = $client->id;
                if (!empty($data['conserto_client_contact'])) {
                    $contact = trim($data['conserto_client_contact']);
                    if (str_contains($contact, '@')) {
                        ClientEmail::create(['client_id' => $client->id, 'email' => substr($contact, 0, 190), 'label' => 'Conserto']);
                    } else {
                        ClientPhone::create(['client_id' => $client->id, 'phone' => substr($contact, 0, 30), 'label' => 'Conserto']);
                    }
                }
            }
            
            // Criar O.S.
            $serviceOrder = ServiceOrder::create([
                'os_number' => $osNumber,
                'company_id' => $data['company_id'] ?? \App\Helpers\CompanyHelper::getCompanyId(),
                'store_id' => $data['store_id'],
                'os_type' => $data['os_type'] ?? 'OTICA',
                'registered_at' => $data['registered_at'] ?? $workDate,
                'employee_id' => $data['employee_id'] ?? auth()->id(),
                'client_id' => $clientId,
                'source' => $data['source'] ?? null,
                'delivery_date' => $data['delivery_date'] ?? null,
                'delivery_time' => $data['delivery_time'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $data['status'] ?? 'REGISTRADA',
                'advance_type' => $data['advance_type'] ?? 'SEM',
                'advance_value' => $data['advance_value'] ?? 0,
                'sinal_amount' => $data['sinal_amount'] ?? 0,
                'sinal_method' => $data['sinal_method'] ?? null,
                'subtotal' => 0,
                'discount_value' => $data['discount_value'] ?? 0,
                'total_value' => 0,
            ]);

            // Processar itens
            $subtotal = $this->processItems($serviceOrder, $data['items'] ?? []);
            $total = $subtotal - ($data['discount_value'] ?? 0);

            $serviceOrder->update([
                'subtotal' => $subtotal,
                'total_value' => $total,
            ]);

            // Processar receita
            if (!empty($data['prescription'])) {
                $this->processPrescription($serviceOrder, $data['prescription']);
            }

            // Processar pagamento e criar venda/recebíveis (sempre processar se houver total)
            if ($total > 0 && !empty($data['payment_type'])) {
                $this->processPayment($serviceOrder, $data, $total);
            }

            // Upload de imagens (removido - não vamos mais usar)
            // if (!empty($data['images'])) {
            //     $this->processImages($serviceOrder, $data['images']);
            // }

            DB::commit();
            \Illuminate\Support\Facades\Log::info('🔍 DEBUG: Transação commitada com sucesso', [
                'os_id' => $serviceOrder->id,
                'os_number' => $serviceOrder->os_number,
            ]);
            return $serviceOrder->fresh(['items', 'images', 'prescription']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('❌ DEBUG: Erro na criação da OS - Rollback executado', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function update(ServiceOrder $serviceOrder, array $data): ServiceOrder
    {
        if (!$serviceOrder->canEdit()) {
            throw new \Exception('Esta O.S. não pode ser editada.');
        }

        DB::beginTransaction();

        try {
            // Atualizar cabeçalho
            $serviceOrder->update([
                'client_id' => $data['client_id'] ?? $serviceOrder->client_id,
                'source' => $data['source'] ?? $serviceOrder->source,
                'delivery_date' => $data['delivery_date'] ?? $serviceOrder->delivery_date,
                'delivery_time' => $data['delivery_time'] ?? $serviceOrder->delivery_time,
                'notes' => $data['notes'] ?? $serviceOrder->notes,
                'discount_value' => $data['discount_value'] ?? $serviceOrder->discount_value,
            ]);

            // Processar itens
            $subtotal = $this->processItems($serviceOrder, $data['items'] ?? []);
            $total = $subtotal - ($data['discount_value'] ?? $serviceOrder->discount_value);

            $serviceOrder->update([
                'subtotal' => $subtotal,
                'total_value' => $total,
            ]);

            // Processar receita
            if (isset($data['prescription'])) {
                $this->processPrescription($serviceOrder, $data['prescription']);
            }

            // Upload de novas imagens
            if (!empty($data['images'])) {
                $this->processImages($serviceOrder, $data['images']);
            }

            DB::commit();
            return $serviceOrder->fresh(['items', 'images', 'prescription']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function processItems(ServiceOrder $serviceOrder, array $items): float
    {
        $subtotal = 0;
        $existingItemIds = [];

        foreach ($items as $itemData) {
            $itemSubtotal = 0;

            // Calcular unit_price_net
            $unitPrice = floatval($itemData['unit_price'] ?? 0);
            $priceAdjust = floatval($itemData['price_adjust'] ?? 0);
            $unitPriceNet = $unitPrice + $priceAdjust;

            // Calcular line_total
            $qty = floatval($itemData['qty'] ?? 1);
            $addDiscPercent = floatval($itemData['add_disc_percent'] ?? 0);
            $lineTotal = $qty * $unitPriceNet * (1 + $addDiscPercent / 100);

            $itemData['unit_price_net'] = $unitPriceNet;
            $itemData['line_total'] = $lineTotal;
            $itemData['service_order_id'] = $serviceOrder->id;

            if (!empty($itemData['id'])) {
                // Update existing
                $item = ServiceOrderItem::find($itemData['id']);
                if ($item && $item->service_order_id == $serviceOrder->id) {
                    $item->update($itemData);
                    $existingItemIds[] = $item->id;
                    $subtotal += $lineTotal;
                    continue;
                }
            }

            // Create new
            $item = ServiceOrderItem::create($itemData);
            $existingItemIds[] = $item->id;
            $subtotal += $lineTotal;
        }

        // Remover itens não enviados
        ServiceOrderItem::where('service_order_id', $serviceOrder->id)
            ->whereNotIn('id', $existingItemIds)
            ->delete();

        return $subtotal;
    }

    private function processPrescription(ServiceOrder $serviceOrder, array $prescriptionData): void
    {
        $useCustom = $prescriptionData['use_custom'] ?? false;
        $prescriptionId = $prescriptionData['prescription_id'] ?? null;
        
        // Verificar se há dados customizados mesmo sem use_custom=true
        // IMPORTANTE: Verificar se há pelo menos um campo preenchido (não null e não vazio)
        $hasCustomData = false;
        foreach ($prescriptionData as $key => $value) {
            if (strpos($key, 'custom_') === 0) {
                // Verificar se o valor não é null, não é string vazia, e não é 0 (para campos numéricos, 0 pode ser válido)
                if ($value !== null && $value !== '') {
                    // Para campos numéricos, considerar 0 como válido apenas se for explicitamente 0
                    // Para strings, verificar se não está vazia após trim
                    $isValid = true;
                    if (is_string($value)) {
                        $isValid = trim($value) !== '';
                    }
                    if ($isValid) {
                        $hasCustomData = true;
                        break;
                    }
                }
            }
        }

        if ($useCustom || $hasCustomData || !$prescriptionId) {
            // Criar receita customizada
            $dataToSave = ['use_custom' => true];
            
            // Adicionar todos os campos custom_ que existirem
            foreach ($prescriptionData as $key => $value) {
                if (strpos($key, 'custom_') === 0) {
                    // Converter strings vazias para null e trim
                    $cleanValue = is_string($value) ? trim($value) : $value;
                    
                    // Salvar null apenas se for realmente vazio, caso contrário salvar o valor
                    if ($cleanValue === '' || $cleanValue === null) {
                        $dataToSave[$key] = null;
                    } else {
                        // Para campos numéricos, converter para o tipo apropriado
                        if (in_array($key, ['custom_longe_esferico_od', 'custom_longe_cilindrico_od', 'custom_longe_altura_od', 'custom_longe_dnp_od',
                            'custom_longe_esferico_oe', 'custom_longe_cilindrico_oe', 'custom_longe_altura_oe', 'custom_longe_dnp_oe',
                            'custom_perto_esferico_od', 'custom_perto_cilindrico_od', 'custom_perto_altura_od', 'custom_perto_dnp_od',
                            'custom_perto_esferico_oe', 'custom_perto_cilindrico_oe', 'custom_perto_altura_oe', 'custom_perto_dnp_oe',
                            'custom_adicao'])) {
                            $dataToSave[$key] = is_numeric($cleanValue) ? (float)$cleanValue : null;
                        } elseif (in_array($key, ['custom_longe_eixo_od', 'custom_longe_eixo_oe', 'custom_perto_eixo_od', 'custom_perto_eixo_oe'])) {
                            $dataToSave[$key] = is_numeric($cleanValue) ? (int)$cleanValue : null;
                        } else {
                            $dataToSave[$key] = $cleanValue;
                        }
                    }
                }
            }
            
            // Manter prescription_id se existir
            if ($prescriptionId) {
                $dataToSave['prescription_id'] = $prescriptionId;
            }
            
            $prescription = ServiceOrderPrescription::updateOrCreate(
                ['service_order_id' => $serviceOrder->id],
                $dataToSave
            );
        } else {
            // Vincular receita existente
            $prescription = ServiceOrderPrescription::updateOrCreate(
                ['service_order_id' => $serviceOrder->id],
                [
                    'prescription_id' => $prescriptionId,
                    'use_custom' => false,
                ]
            );
        }
    }

    private function processImages(ServiceOrder $serviceOrder, array $images): void
    {
        $existingPositions = $serviceOrder->images()->pluck('position')->toArray();
        $availablePositions = array_diff([1, 2, 3, 4, 5], $existingPositions);

        foreach ($images as $index => $image) {
            if (count($existingPositions) >= 5) {
                break;
            }

            $position = !empty($availablePositions) ? min($availablePositions) : count($existingPositions) + 1;
            $availablePositions = array_diff($availablePositions, [$position]);

            $path = $image->store("os/{$serviceOrder->id}", 'public');
            
            $serviceOrder->images()->create([
                'path' => $path,
                'position' => $position,
            ]);

            $existingPositions[] = $position;
        }
    }

    protected function processPayment(ServiceOrder $serviceOrder, array $data, float $total): void
    {
        try {
            $paymentType = $data['payment_type'] ?? 'avista';
            // Garantir que paymentMethod sempre tenha um valor válido (não vazio)
            $paymentMethod = !empty($data['payment_method']) ? trim($data['payment_method']) : 'money';
            $sinalAmount = floatval($data['sinal_amount'] ?? 0);
            
            // Gerar número da venda
            $saleNumber = $this->generateSaleNumber($serviceOrder->store_id);
            
            // Validar campos obrigatórios antes de criar a venda
            if (!$serviceOrder->company_id) {
                throw new \Exception('Company ID não encontrado na OS');
            }
            if (!$serviceOrder->store_id) {
                throw new \Exception('Store ID não encontrado na OS');
            }
            if (!$serviceOrder->client_id) {
                throw new \Exception('Client ID não encontrado na OS');
            }
            
            // Obter data de trabalho (para lançamentos retroativos)
            $workDate = WorkDateHelper::getWorkDate();
            
            // Criar venda vinculada à OS
            $sale = \App\Models\Sale::create([
                'company_id' => $serviceOrder->company_id,
                'store_id' => $serviceOrder->store_id,
                'customer_id' => $serviceOrder->client_id, // Usar customer_id, não client_id
                'service_order_id' => $serviceOrder->id,
                'sale_number' => $saleNumber,
                'sale_date' => $workDate,
                'total_gross' => floatval($serviceOrder->subtotal ?? 0), // Usar total_gross, não subtotal
                'total_discount' => floatval($serviceOrder->discount_value ?? 0), // Usar total_discount, não discount_value
                'total_net' => floatval($total), // Usar total_net, não total_value
                'status' => 'completed', // Usar 'completed', não 'FINALIZADA'
            ]);

            // Criar itens da venda (se houver itens na OS)
            if ($serviceOrder->items && $serviceOrder->items->count() > 0) {
                // Recarregar itens com valores calculados
                $serviceOrder->load('items');
                foreach ($serviceOrder->items as $osItem) {
                    $qty = floatval($osItem->qty ?? 1);
                    $unitPrice = floatval($osItem->unit_price ?? 0);
                    // Calcular subtotal baseado no unit_price_net e line_total se disponíveis
                    $unitPriceNet = floatval($osItem->unit_price_net ?? $unitPrice);
                    $lineTotal = floatval($osItem->line_total ?? ($qty * $unitPriceNet));
                    $discount = ($qty * $unitPrice) - $lineTotal;
                    if ($discount < 0) $discount = 0;
                    
                    $sale->items()->create([
                        'product_id' => $osItem->product_id,
                        'qty' => $qty,
                        'unit_price' => $unitPrice,
                        'discount' => $discount,
                        'subtotal' => $lineTotal,
                        'total_cost' => 0, // Pode ser calculado depois se necessário
                    ]);
                }
            }

            // Processar pagamento
            // IMPORTANTE: Se payment_method é 'carne', ele é um método de parcelamento, não um método de pagamento à vista
            // O carnê sempre cria recebíveis parcelados
            if ($paymentMethod === 'carne') {
                // Pagamento com carnê - criar recebíveis parcelados
                $carneParcelasCount = intval($data['carne_parcelas_count'] ?? 1);
                
                // Se for sinal + carnê, processar sinal primeiro
                if ($paymentType === 'sinal' && $sinalAmount > 0) {
                    // Quando payment_method é 'carne' mas payment_type é 'sinal',
                    // o sinal foi pago com um método diferente do carnê
                    // Usar sinal_method se disponível, senão 'money' como padrão
                    $sinalPaymentMethod = !empty($data['sinal_method']) ? $data['sinal_method'] : 'money';
                    
                    // Criar pagamento do sinal
                    $sale->payments()->create([
                        'method' => $sinalPaymentMethod,
                        'amount' => $sinalAmount,
                        'paid_at' => now(),
                    ]);
                    
                    // Atualizar OS com sinal
                    $serviceOrder->update([
                        'sinal_amount' => $sinalAmount,
                        'sinal_method' => $sinalPaymentMethod,
                    ]);
                    
                    $balance = $total - $sinalAmount;
                    $valorParcela = $balance / $carneParcelasCount;
                } else {
                    // Carnê sem sinal - parcelar o total
                    $valorParcela = $total / $carneParcelasCount;
                }
                
                // Criar recebíveis do carnê
                // Garantir que o método de pagamento seja válido (carnê usa 'money' como padrão)
                $carneMethod = !empty($paymentMethod) && $paymentMethod !== 'carne' ? $paymentMethod : 'money';
                
                for ($i = 1; $i <= $carneParcelasCount; $i++) {
                    \App\Models\Finance\Receivable::create([
                        'company_id' => $serviceOrder->company_id,
                        'store_id' => $serviceOrder->store_id,
                        'customer_id' => $serviceOrder->client_id,
                        'sale_id' => $sale->id,
                        'os_id' => $serviceOrder->id,
                        'issue_date' => $workDate->toDateString(),
                        'due_date' => now()->addDays(30 * $i)->toDateString(),
                        'original_amount' => $valorParcela,
                        'balance_amount' => $valorParcela,
                        'status' => 'open',
                        'method' => $carneMethod,
                        'note' => "Carnê Parcela {$i}/{$carneParcelasCount} - OS " . $serviceOrder->os_number,
                    ]);
                }
            } elseif ($paymentType === 'avista') {
                // Pagamento à vista - criar pagamento direto
                $sale->payments()->create([
                    'method' => $paymentMethod,
                    'amount' => $total,
                    'paid_at' => now(),
                ]);
            } elseif ($paymentType === 'sinal' && $sinalAmount > 0) {
                // Garantir que paymentMethod tenha um valor válido
                $validPaymentMethod = !empty($paymentMethod) ? $paymentMethod : 'money';
                
                // Pagamento com sinal
                $sale->payments()->create([
                    'method' => $validPaymentMethod,
                    'amount' => $sinalAmount,
                    'paid_at' => now(),
                ]);
                
                // Atualizar OS com sinal
                $serviceOrder->update([
                    'sinal_amount' => $sinalAmount,
                    'sinal_method' => $validPaymentMethod,
                ]);
                
                // Criar recebível para o saldo
                $balance = $total - $sinalAmount;
                if ($balance > 0) {
                    // Para o saldo, usar o mesmo método de pagamento do sinal, ou 'money' como padrão
                    $balanceMethod = !empty($paymentMethod) ? $paymentMethod : 'money';
                    
                    \App\Models\Finance\Receivable::create([
                        'company_id' => $serviceOrder->company_id,
                        'store_id' => $serviceOrder->store_id,
                        'customer_id' => $serviceOrder->client_id,
                        'sale_id' => $sale->id,
                        'os_id' => $serviceOrder->id,
                        'issue_date' => $workDate->toDateString(),
                        'due_date' => now()->addDays(30)->toDateString(),
                        'original_amount' => $balance,
                        'balance_amount' => $balance,
                        'status' => 'open',
                        'method' => $balanceMethod,
                        'note' => 'Saldo de pagamento sinal - OS ' . $serviceOrder->os_number,
                    ]);
                }
            } elseif ($paymentType === 'parcelado') {
                // Pagamento parcelado - criar recebíveis
                $parcelasCount = intval($data['parcelas_count'] ?? 1);
                $valorParcela = $total / $parcelasCount;
                
                // Garantir que o método de pagamento seja válido
                $parceladoMethod = !empty($paymentMethod) ? $paymentMethod : 'money';
                
                for ($i = 1; $i <= $parcelasCount; $i++) {
                    \App\Models\Finance\Receivable::create([
                        'company_id' => $serviceOrder->company_id,
                        'store_id' => $serviceOrder->store_id,
                        'customer_id' => $serviceOrder->client_id,
                        'sale_id' => $sale->id,
                        'os_id' => $serviceOrder->id,
                        'issue_date' => $workDate->toDateString(),
                        'due_date' => now()->addDays(30 * $i)->toDateString(),
                        'original_amount' => $valorParcela,
                        'balance_amount' => $valorParcela,
                        'status' => 'open',
                        'method' => $parceladoMethod,
                        'note' => "Parcela {$i}/{$parcelasCount} - OS " . $serviceOrder->os_number,
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao processar pagamento da OS', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'service_order_id' => $serviceOrder->id ?? null,
                'os_number' => $serviceOrder->os_number ?? null,
                'payment_type' => $paymentType ?? null,
                'payment_method' => $paymentMethod ?? null,
                'total' => $total ?? null,
                'data' => $data ?? null,
            ]);
            // Lançar exceção - a OS não deve ser criada se o pagamento falhar
            // Mas vamos melhorar a mensagem de erro
            throw new \Exception('Erro ao processar pagamento: ' . $e->getMessage() . ' (OS: ' . ($serviceOrder->os_number ?? 'N/A') . ')', 0, $e);
        }
    }
    
    /**
     * Gera número da venda
     */
    protected function generateSaleNumber(int $storeId): string
    {
        $store = \App\Models\Store::find($storeId);
        $storeCode = $store ? strtoupper(preg_replace('/[^A-Z0-9]/', '', $store->code ?? $store->abbreviation ?? 'ST')) : 'ST';
        
        $year = date('Y');
        
        // Buscar última venda do ano para esta loja
        $lastSale = \App\Models\Sale::where('store_id', $storeId)
            ->whereYear('sale_date', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = 1;
        if ($lastSale && $lastSale->sale_number) {
            // Extrair sequência do número da última venda
            $parts = explode('-', $lastSale->sale_number);
            if (count($parts) >= 3) {
                $lastSequence = intval(end($parts));
                $sequence = $lastSequence + 1;
            }
        }
        
        return "{$storeCode}-V-{$year}-" . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }
}

