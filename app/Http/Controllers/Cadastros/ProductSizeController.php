<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Models\ProductSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductSizeController extends Controller
{
    public function storeAjax(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:50|unique:product_sizes,name',
            ]);

            $size = ProductSize::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Tamanho criado com sucesso!',
                'data' => [
                    'id' => $size->id,
                    'name' => $size->name,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao criar tamanho via AJAX: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar tamanho: ' . $e->getMessage()
            ], 500);
        }
    }
}

