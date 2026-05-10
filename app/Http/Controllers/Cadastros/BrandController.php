<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BrandController extends Controller
{
    public function index()
    {
        try {
            if (!Schema::hasTable('brands')) {
                return view('cadastros.brands.index', ['brands' => collect([])->paginate(25)])
                    ->with('error', 'A tabela brands não existe. Execute as migrations.');
            }

            $query = Brand::query();

            if (request()->filled('q')) {
                $search = request('q');
                $query->where('name', 'like', "%{$search}%");
            }

            try {
                $brands = $query->orderBy('name')->paginate(25);
            } catch (\Exception $e) {
                Log::warning('Erro ao paginar marcas: ' . $e->getMessage());
                $brands = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25, 1);
            }

            return view('cadastros.brands.index', compact('brands'));
        } catch (\Exception $e) {
            Log::error('Erro ao listar marcas: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return view('cadastros.brands.index', ['brands' => collect([])->paginate(25)])
                ->with('error', 'Erro ao carregar marcas: ' . $e->getMessage());
        }
    }

    public function show(Brand $brand)
    {
        return redirect()->route('cadastros.brands.edit', $brand);
    }

    public function create()
    {
        try {
            if (!Schema::hasTable('brands')) {
                return redirect()->route('cadastros.brands.index')
                    ->with('error', 'A tabela brands não existe. Execute as migrations.');
            }
            return view('cadastros.brands.create');
        } catch (\Exception $e) {
            Log::error('Erro ao carregar formulário de marca: ' . $e->getMessage());
            return redirect()->route('cadastros.brands.index')
                ->with('error', 'Erro ao carregar formulário.');
        }
    }

    public function store(Request $request)
    {
        try {
            if (!Schema::hasTable('brands')) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'A tabela brands não existe. Execute as migrations.');
            }

            $validated = $request->validate([
                'name' => 'required|string|max:190|unique:brands,name',
            ]);

            Brand::create($validated);

            return redirect()->route('cadastros.brands.index')
                ->with('success', 'Marca criada com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao criar marca: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao criar marca: ' . $e->getMessage());
        }
    }

    public function edit(Brand $brand)
    {
        return view('cadastros.brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:190|unique:brands,name,' . $brand->id,
        ]);

        $brand->update($validated);

        return redirect()->route('cadastros.brands.index')
            ->with('success', 'Marca atualizada com sucesso!');
    }

    public function destroy(Brand $brand)
    {
        // Verificar se há produtos usando esta marca
        if ($brand->products()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Não é possível excluir esta marca pois existem produtos associados a ela.');
        }

        $brand->delete();

        return redirect()->route('cadastros.brands.index')
            ->with('success', 'Marca excluída com sucesso!');
    }

    public function storeAjax(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:190|unique:brands,name',
            ]);

            $brand = Brand::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Marca criada com sucesso!',
                'data' => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao criar marca via AJAX: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar marca: ' . $e->getMessage()
            ], 500);
        }
    }
}

