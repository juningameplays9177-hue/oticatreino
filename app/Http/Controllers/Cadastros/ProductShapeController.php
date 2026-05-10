<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Models\ProductShape;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductShapeController extends Controller
{
    public function storeAjax(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:product_shapes,name',
            ]);

            $shape = ProductShape::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Formato criado com sucesso!',
                'data' => [
                    'id' => $shape->id,
                    'name' => $shape->name,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao criar formato via AJAX: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar formato: ' . $e->getMessage()
            ], 500);
        }
    }
}

