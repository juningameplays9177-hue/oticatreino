<?php

namespace App\Services;

use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;

class StockReservationService
{
    public function reserveStock(ServiceOrder $serviceOrder): void
    {
        $items = $serviceOrder->items()->where('type', 'PRODUTO')->with(['product.productType'])->get();

        foreach ($items as $item) {
            if (!$item->product || !$item->product->control_stock) {
                continue;
            }
            
            // Ignorar serviços (item_type = SERVICO) e produtos do tipo Conserto
            if ($item->product->item_type === 'SERVICO') {
                continue;
            }
            
            // Verificar se é produto do tipo Conserto
            $productName = strtolower($item->product->name ?? '');
            $productTypeName = $item->product->productType ? strtolower($item->product->productType->name ?? '') : '';
            if (strpos($productName, 'conserto') !== false || strpos($productTypeName, 'conserto') !== false) {
                continue;
            }

            $stock = ProductStock::where('product_id', $item->product_id)
                ->where('store_id', $serviceOrder->store_id)
                ->first();

            if ($stock && $stock->qty >= $item->qty) {
                $stock->qty -= $item->qty;
                $stock->save();
            }
        }
    }

    public function releaseStock(ServiceOrder $serviceOrder): void
    {
        $items = $serviceOrder->items()->where('type', 'PRODUTO')->with(['product.productType'])->get();

        foreach ($items as $item) {
            if (!$item->product || !$item->product->control_stock) {
                continue;
            }
            
            // Ignorar serviços (item_type = SERVICO) e produtos do tipo Conserto
            if ($item->product->item_type === 'SERVICO') {
                continue;
            }
            
            // Verificar se é produto do tipo Conserto
            $productName = strtolower($item->product->name ?? '');
            $productTypeName = $item->product->productType ? strtolower($item->product->productType->name ?? '') : '';
            if (strpos($productName, 'conserto') !== false || strpos($productTypeName, 'conserto') !== false) {
                continue;
            }

            $stock = ProductStock::where('product_id', $item->product_id)
                ->where('store_id', $serviceOrder->store_id)
                ->first();

            if ($stock) {
                $stock->qty += $item->qty;
                $stock->save();
            }
        }
    }
}

