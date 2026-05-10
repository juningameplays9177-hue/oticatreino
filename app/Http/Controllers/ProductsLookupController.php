<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductsLookupController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request->get('q', '');
            $storeId = $request->get('store_id', null);

            if (empty($search) || strlen(trim($search)) < 1) {
                return response()->json([]);
            }

            $search = trim($search);
            $normalized = preg_replace('/[^0-9]/', '', $search);
            
            Log::info('Buscando produtos', [
                'search' => $search,
                'normalized' => $normalized,
                'store_id' => $storeId
            ]);
            
            // Buscar produtos com busca melhorada (incluindo tipo de produto)
            $query = DB::table('products')
                ->leftJoin('product_types', 'products.product_type_id', '=', 'product_types.id')
                ->where(function($q) use ($search, $normalized) {
                    // Busca por nome (case insensitive)
                    $q->where('products.name', 'like', '%' . $search . '%')
                      // Busca por referência exata primeiro
                      ->orWhere('products.ref', 'like', $search . '%')
                      // Busca por referência parcial
                      ->orWhere('products.ref', 'like', '%' . $search . '%');
                    
                    // Busca por EAN se houver números
                    if (!empty($normalized) && strlen($normalized) >= 3) {
                        $q->orWhere('products.ean13', 'like', '%' . $normalized . '%');
                    }
                });
            
            // Filtrar apenas produtos não arquivados se a coluna existir
            if (DB::getSchemaBuilder()->hasColumn('products', 'archived')) {
                $query->where('products.archived', 0);
            }
            
            // Ordenar: primeiro por referência exata, depois por nome que começa com a busca
            $query->orderByRaw("CASE 
                WHEN products.ref = ? THEN 1 
                WHEN products.ref LIKE ? THEN 2 
                WHEN products.name LIKE ? THEN 3 
                WHEN products.ref LIKE ? THEN 4 
                WHEN products.name LIKE ? THEN 5 
                ELSE 6 
            END", [
                $search,                    // ref exato
                $search . '%',              // ref começa com
                $search . '%',              // name começa com
                '%' . $search . '%',        // ref contém
                '%' . $search . '%'         // name contém
            ])
            ->orderBy('products.name', 'asc')
            ->limit(50); // Aumentar limite para melhor filtragem

            $products = $query->get([
                'products.id', 
                'products.ref', 
                'products.name', 
                'products.unit', 
                'products.ean13', 
                'products.control_stock',
                'product_types.name as product_type_name'
            ]);
            
            Log::info('Produtos encontrados', [
                'count' => $products->count(),
                'first' => $products->first()
            ]);

            $results = [];
            foreach ($products as $product) {
                $unitPrice = 0;
                $cost = 0;
                
                // Buscar preço se houver loja
                if ($storeId) {
                    try {
                        $price = DB::table('product_prices')
                            ->where('product_id', $product->id)
                            ->where('store_id', $storeId)
                            ->first(['price', 'cost']);
                        
                        if ($price) {
                            $unitPrice = isset($price->price) ? (float) $price->price : 0;
                            $cost = isset($price->cost) ? (float) $price->cost : 0;
                        }
                    } catch (\Exception $e) {
                        // Ignorar erro de preço
                    }
                }

                $results[] = [
                    'id' => (int) $product->id,
                    'ref' => (string) ($product->ref ?? ''),
                    'name' => (string) ($product->name ?? 'Sem nome'),
                    'unit' => (string) ($product->unit ?? 'UN'),
                    'ean13' => (string) ($product->ean13 ?? ''),
                    'unit_price' => $unitPrice,
                    'cost' => $cost,
                    'control_stock' => (bool) (isset($product->control_stock) ? $product->control_stock : false),
                    'product_type_name' => (string) ($product->product_type_name ?? ''),
                ];
            }

            return response()->json($results);
        } catch (\Throwable $e) {
            Log::error('Erro em ProductsLookupController', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([]);
        }
    }
}
