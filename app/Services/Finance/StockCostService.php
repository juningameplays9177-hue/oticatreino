<?php

namespace App\Services\Finance;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class StockCostService
{
    /**
     * Baixa estoque e calcula custo médio para uma venda
     */
    public function consumeForSale(Sale $sale): float
    {
        return DB::transaction(function () use ($sale) {
            $totalCost = 0;

            foreach ($sale->items as $item) {
                $product = Product::with('productType')->lockForUpdate()->findOrFail($item->product_id);
                
                // Ignorar produtos que não controlam estoque
                if (!$product->control_stock) {
                    continue;
                }
                
                // Ignorar serviços (item_type = SERVICO) e produtos do tipo Conserto
                if ($product->item_type === 'SERVICO') {
                    continue;
                }
                
                // Verificar se é produto do tipo Conserto
                $productName = strtolower($product->name ?? '');
                $productTypeName = $product->productType ? strtolower($product->productType->name ?? '') : '';
                if (strpos($productName, 'conserto') !== false || strpos($productTypeName, 'conserto') !== false) {
                    continue;
                }
                
                // Ignorar lentes - não devem ter controle de estoque
                // Verificar pelo nome do produto, tipo de produto ou palavras-chave comuns
                $isLente = false;
                
                // Palavras-chave que indicam lente
                $lenteKeywords = [
                    'lente', 'lens', 'hoya', 'maxxe', 'essilor', 'zeiss', 
                    'varilux', 'crizal', 'transitions', 'photochromic',
                    'antirreflexo', 'antireflexo', 'hmc', 'ar', 'blue',
                    '1.50', '1.56', '1.60', '1.67', '1.74', // Graus comuns de lentes
                ];
                
                // Verificar no nome do produto
                foreach ($lenteKeywords as $keyword) {
                    if (strpos($productName, $keyword) !== false) {
                        $isLente = true;
                        break;
                    }
                }
                
                // Verificar no tipo de produto
                if (!$isLente && strpos($productTypeName, 'lente') !== false) {
                    $isLente = true;
                }
                
                if ($isLente) {
                    continue;
                }

                // Buscar estoque da loja
                $stock = ProductStock::lockForUpdate()
                    ->where('product_id', $product->id)
                    ->where('store_id', $sale->store_id)
                    ->first();

                if (!$stock) {
                    $stock = ProductStock::create([
                        'product_id' => $product->id,
                        'store_id' => $sale->store_id,
                        'qty' => 0,
                    ]);
                }

                // Verificar estoque disponível
                if ($stock->qty < $item->qty) {
                    // Verificar se permite estoque negativo (assumir false por padrão)
                    $allowNegative = false;
                    
                    if (!$allowNegative) {
                        throw new \Exception("Estoque insuficiente para produto {$product->name}. Disponível: {$stock->qty}, Necessário: {$item->qty}");
                    }
                }

                // Calcular custo médio (simplificado - assumindo que o custo está no ProductStock)
                // Em um sistema real, você teria uma tabela de movimentações de estoque com custos
                $unitCost = $this->getAverageCost($product, $sale->store_id);
                $itemCost = $unitCost * $item->qty;
                $totalCost += $itemCost;

                // Atualizar estoque
                $stock->qty -= $item->qty;
                $stock->save();

                // Atualizar total_cost do item
                $item->update(['total_cost' => $itemCost]);
            }

            return $totalCost;
        });
    }

    /**
     * Reverte baixa de estoque para uma devolução
     */
    public function revertForReturn(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            foreach ($sale->items as $item) {
                $product = Product::with('productType')->lockForUpdate()->findOrFail($item->product_id);
                
                // Ignorar produtos que não controlam estoque
                if (!$product->control_stock) {
                    continue;
                }
                
                // Ignorar serviços (item_type = SERVICO) e produtos do tipo Conserto
                if ($product->item_type === 'SERVICO') {
                    continue;
                }
                
                // Verificar se é produto do tipo Conserto
                $productName = strtolower($product->name ?? '');
                $productTypeName = $product->productType ? strtolower($product->productType->name ?? '') : '';
                if (strpos($productName, 'conserto') !== false || strpos($productTypeName, 'conserto') !== false) {
                    continue;
                }
                
                // Ignorar lentes - não devem ter controle de estoque
                // Verificar pelo nome do produto, tipo de produto ou palavras-chave comuns
                $isLente = false;
                
                // Palavras-chave que indicam lente
                $lenteKeywords = [
                    'lente', 'lens', 'hoya', 'maxxe', 'essilor', 'zeiss', 
                    'varilux', 'crizal', 'transitions', 'photochromic',
                    'antirreflexo', 'antireflexo', 'hmc', 'ar', 'blue',
                    '1.50', '1.56', '1.60', '1.67', '1.74', // Graus comuns de lentes
                ];
                
                // Verificar no nome do produto
                foreach ($lenteKeywords as $keyword) {
                    if (strpos($productName, $keyword) !== false) {
                        $isLente = true;
                        break;
                    }
                }
                
                // Verificar no tipo de produto
                if (!$isLente && strpos($productTypeName, 'lente') !== false) {
                    $isLente = true;
                }
                
                if ($isLente) {
                    continue;
                }

                $stock = ProductStock::lockForUpdate()
                    ->where('product_id', $product->id)
                    ->where('store_id', $sale->store_id)
                    ->first();

                if ($stock) {
                    $stock->qty += $item->qty;
                    $stock->save();
                }
            }
        });
    }

    /**
     * Obtém custo médio do produto (simplificado)
     * Em produção, isso viria de uma tabela de movimentações com custos
     */
    protected function getAverageCost(Product $product, int $storeId): float
    {
        // Simplificado: retornar um valor padrão ou buscar de ProductPrice
        // Em produção, implementar cálculo real de custo médio
        $price = $product->prices()->where('store_id', $storeId)->first();
        
        if ($price && $price->cost) {
            return $price->cost;
        }

        // Fallback: 50% do preço de venda (exemplo)
        $salePrice = $product->prices()->where('store_id', $storeId)->first()?->price ?? 0;
        return $salePrice * 0.5;
    }
}

