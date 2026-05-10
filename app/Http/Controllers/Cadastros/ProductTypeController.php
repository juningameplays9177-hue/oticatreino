<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Models\ProductType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductTypeController extends Controller
{
    public function storeAjax(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'code_prefix' => 'required|string|max:10|unique:product_types,code_prefix',
            ], [
                'name.required' => 'O nome do tipo é obrigatório.',
                'code_prefix.required' => 'O prefixo do código é obrigatório.',
                'code_prefix.unique' => 'Este prefixo já está em uso.',
            ]);

            $productType = ProductType::create([
                'name' => $validated['name'],
                'code_prefix' => strtoupper($validated['code_prefix']),
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tipo de produto cadastrado com sucesso!',
                'data' => [
                    'id' => $productType->id,
                    'name' => $productType->name,
                    'code_prefix' => $productType->code_prefix,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação: ' . implode(', ', $e->errors()['code_prefix'] ?? $e->errors()['name'] ?? ['Erro desconhecido']),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao cadastrar tipo de produto: ' . $e->getMessage(),
            ], 500);
        }
    }
}

