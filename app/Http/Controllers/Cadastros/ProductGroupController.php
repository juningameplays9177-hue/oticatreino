<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Models\ProductGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ProductGroupController extends Controller
{
    public function index()
    {
        try {
            if (!Schema::hasTable('product_groups')) {
                return view('cadastros.product-groups.index', ['groups' => collect([])->paginate(25)])
                    ->with('error', 'A tabela product_groups não existe. Execute as migrations.');
            }

            $query = ProductGroup::query();

            if (request()->filled('q')) {
                $search = request('q');
                $query->where('name', 'like', "%{$search}%");
            }

            try {
                $groups = $query->orderBy('name')->paginate(25);
                
                // Adicionar contadores manualmente após paginar
                if (Schema::hasTable('products') && Schema::hasTable('product_subgroups')) {
                    foreach ($groups as $group) {
                        try {
                            $group->subgroups_count = $group->subgroups()->count();
                            $group->products_count = $group->products()->count();
                        } catch (\Exception $e) {
                            $group->subgroups_count = 0;
                            $group->products_count = 0;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Erro ao paginar categorias: ' . $e->getMessage());
                $groups = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25, 1);
            }

            return view('cadastros.product-groups.index', compact('groups'));
        } catch (\Exception $e) {
            Log::error('Erro ao listar categorias: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return view('cadastros.product-groups.index', ['groups' => collect([])->paginate(25)])
                ->with('error', 'Erro ao carregar categorias: ' . $e->getMessage());
        }
    }

    public function show(ProductGroup $productGroup)
    {
        return redirect()->route('cadastros.product-groups.edit', $productGroup);
    }

    public function create()
    {
        try {
            if (!Schema::hasTable('product_groups')) {
                return redirect()->route('cadastros.product-groups.index')
                    ->with('error', 'A tabela product_groups não existe. Execute as migrations.');
            }
            return view('cadastros.product-groups.create');
        } catch (\Exception $e) {
            Log::error('Erro ao carregar formulário de categoria: ' . $e->getMessage());
            return redirect()->route('cadastros.product-groups.index')
                ->with('error', 'Erro ao carregar formulário.');
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:190|unique:product_groups,name',
        ]);

        ProductGroup::create($validated);

        return redirect()->route('cadastros.product-groups.index')
            ->with('success', 'Categoria criada com sucesso!');
    }

    public function edit(ProductGroup $productGroup)
    {
        $productGroup->load('subgroups');
        return view('cadastros.product-groups.edit', compact('productGroup'));
    }

    public function update(Request $request, ProductGroup $productGroup)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:190|unique:product_groups,name,' . $productGroup->id,
        ]);

        $productGroup->update($validated);

        return redirect()->route('cadastros.product-groups.index')
            ->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroy(ProductGroup $productGroup)
    {
        // Verificar se há produtos ou subgrupos usando esta categoria
        if ($productGroup->products()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Não é possível excluir esta categoria pois existem produtos associados a ela.');
        }

        if ($productGroup->subgroups()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Não é possível excluir esta categoria pois existem subgrupos associados a ela.');
        }

        $productGroup->delete();

        return redirect()->route('cadastros.product-groups.index')
            ->with('success', 'Categoria excluída com sucesso!');
    }

    public function storeAjax(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:190|unique:product_groups,name',
            ]);

            $group = ProductGroup::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Categoria criada com sucesso!',
                'data' => [
                    'id' => $group->id,
                    'name' => $group->name,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao criar categoria via AJAX: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar categoria: ' . $e->getMessage()
            ], 500);
        }
    }
}

