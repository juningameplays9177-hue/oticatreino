<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Models\ProductStock;
use App\Traits\HasStoreFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class StockController extends Controller
{
    use HasStoreFilter;
    
    /**
     * Listar estoque
     */
    public function index(Request $request)
    {
        try {
            $stores = collect([]);
            $products = collect([]);
            
            $user = auth()->user();
            
            // Se for gerente, usar sempre a loja dele
            if ($user && $user->isGerente() && $user->store_id) {
                $storeId = $user->store_id;
            } elseif ($user && $user->isAdmin()) {
                // Admin: usar loja selecionada no dashboard
                $storeId = $request->session()->get('dashboard_store_id');
                
                if ($storeId) {
                    $storeId = (int) $storeId;
                    $stores = Store::where('id', $storeId)->where('active', true)->orderBy('name')->get();
                    
                    // Se a loja não foi encontrada, limpar sessão e mostrar todas
                    if ($stores->isEmpty()) {
                        $request->session()->forget('dashboard_store_id');
                        $request->session()->save();
                        $stores = Store::where('active', true)->orderBy('name')->get();
                        $storeId = null;
                    }
                } else {
                    // Se não houver loja selecionada, mostrar todas as lojas para o admin poder selecionar
                    if (Schema::hasTable('stores')) {
                        $stores = Store::where('active', true)->orderBy('name')->get();
                    }
                    $storeId = $request->get('store_id');
                }
            } else {
                // Admin pode escolher loja
                if (Schema::hasTable('stores')) {
                    $stores = Store::where('active', true)->orderBy('name')->get();
                }
                $storeId = $request->get('store_id');
            }
            $search = $request->get('q', '');
            
            $query = Product::query();
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('ref', 'like', "%{$search}%")
                      ->orWhere('ean13', 'like', "%{$search}%");
                });
            }
            
            $query->where('archived', false);
            $products = $query->orderBy('name')->paginate(50);
            
            // Carregar estoques se loja selecionada
            if ($storeId && Schema::hasTable('product_stocks')) {
                $stocks = ProductStock::where('store_id', $storeId)
                    ->whereIn('product_id', $products->pluck('id'))
                    ->get()
                    ->keyBy('product_id');
                
                foreach ($products as $product) {
                    $product->stock = $stocks->get($product->id);
                }
            }
            
            $selectedStoreId = ($user && $user->isAdmin()) ? $request->session()->get('dashboard_store_id') : null;
            
            return view('stock.index', compact('products', 'stores', 'storeId', 'search', 'selectedStoreId'));
        } catch (\Exception $e) {
            Log::error('Erro ao listar estoque: ' . $e->getMessage());
            return view('stock.index', [
                'products' => collect([])->paginate(50),
                'stores' => collect([]),
                'storeId' => null,
                'search' => ''
            ])->with('error', 'Erro ao carregar estoque: ' . $e->getMessage());
        }
    }

    /**
     * Atualizar estoque de um produto
     */
    public function update(Request $request, Product $product)
    {
        try {
            $validated = $request->validate([
                'store_id' => 'required|exists:stores,id',
                'qty' => 'required|numeric|min:0',
            ]);
            
            if (!Schema::hasTable('product_stocks')) {
                return response()->json(['error' => 'Tabela de estoque não existe'], 400);
            }
            
            $stock = ProductStock::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'store_id' => $validated['store_id']
                ],
                [
                    'qty' => $validated['qty']
                ]
            );
            
            return response()->json([
                'success' => true,
                'stock' => $stock
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar estoque: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao atualizar estoque: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Ajuste de estoque (entrada/saída)
     */
    public function adjust(Request $request, Product $product)
    {
        try {
            $validated = $request->validate([
                'store_id' => 'required|exists:stores,id',
                'qty' => 'required|numeric',
                'type' => 'required|in:in,out',
                'notes' => 'nullable|string|max:500'
            ]);
            
            if (!Schema::hasTable('product_stocks')) {
                return response()->json(['error' => 'Tabela de estoque não existe'], 400);
            }
            
            DB::beginTransaction();
            
            $stock = ProductStock::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'store_id' => $validated['store_id']
                ],
                ['qty' => 0]
            );
            
            if ($validated['type'] === 'in') {
                $stock->qty += abs($validated['qty']);
            } else {
                $stock->qty = max(0, $stock->qty - abs($validated['qty']));
            }
            
            $stock->save();
            
            // Aqui você pode registrar um histórico de movimentação se tiver uma tabela para isso
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'stock' => $stock
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao ajustar estoque: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao ajustar estoque: ' . $e->getMessage()], 400);
        }
    }
}

