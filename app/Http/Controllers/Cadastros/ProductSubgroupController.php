<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Models\ProductGroup;
use App\Models\ProductSubgroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ProductSubgroupController extends Controller
{
    public function index()
    {
        try {
            if (!Schema::hasTable('product_subgroups')) {
                return view('cadastros.product-subgroups.index', [
                    'subgroups' => collect([])->paginate(25),
                    'groups' => collect([])
                ])->with('error', 'A tabela product_subgroups não existe. Execute as migrations.');
            }

            $query = ProductSubgroup::query();
            
            // Tentar adicionar relacionamentos
            if (Schema::hasTable('product_groups')) {
                try {
                    $query->with('group');
                } catch (\Exception $e) {
                    Log::warning('Erro ao carregar grupo: ' . $e->getMessage());
                }
            }

            if (request()->filled('q')) {
                $search = request('q');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                    if (Schema::hasTable('product_groups')) {
                        try {
                            $q->orWhereHas('group', function ($groupQuery) use ($search) {
                                $groupQuery->where('name', 'like', "%{$search}%");
                            });
                        } catch (\Exception $e) {
                            // Se houver erro na relação, continuar sem ela
                        }
                    }
                });
            }

            if (request()->filled('group_id')) {
                $query->where('group_id', request('group_id'));
            }

            try {
                $subgroups = $query->orderBy('name')->paginate(25);
                
                // Adicionar contadores manualmente após paginar
                if (Schema::hasTable('products')) {
                    foreach ($subgroups as $subgroup) {
                        try {
                            $subgroup->products_count = $subgroup->products()->count();
                        } catch (\Exception $e) {
                            $subgroup->products_count = 0;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Erro ao paginar subgrupos: ' . $e->getMessage());
                $subgroups = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25, 1);
            }
            
            $groups = collect([]);
            if (Schema::hasTable('product_groups')) {
                try {
                    $groups = ProductGroup::orderBy('name')->get();
                } catch (\Exception $e) {
                    Log::warning('Erro ao carregar grupos: ' . $e->getMessage());
                }
            }

            return view('cadastros.product-subgroups.index', compact('subgroups', 'groups'));
        } catch (\Exception $e) {
            Log::error('Erro ao listar subgrupos: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return view('cadastros.product-subgroups.index', [
                'subgroups' => collect([])->paginate(25),
                'groups' => collect([])
            ])->with('error', 'Erro ao carregar subgrupos: ' . $e->getMessage());
        }
    }

    public function show(ProductSubgroup $productSubgroup)
    {
        return redirect()->route('cadastros.product-subgroups.edit', $productSubgroup);
    }

    public function create()
    {
        try {
            if (!Schema::hasTable('product_subgroups')) {
                return redirect()->route('cadastros.product-subgroups.index')
                    ->with('error', 'A tabela product_subgroups não existe. Execute as migrations.');
            }

            $groups = collect([]);
            if (Schema::hasTable('product_groups')) {
                try {
                    $groups = ProductGroup::orderBy('name')->get();
                } catch (\Exception $e) {
                    Log::warning('Erro ao carregar grupos: ' . $e->getMessage());
                }
            }

            return view('cadastros.product-subgroups.create', compact('groups'));
        } catch (\Exception $e) {
            Log::error('Erro ao carregar formulário de subgrupo: ' . $e->getMessage());
            return redirect()->route('cadastros.product-subgroups.index')
                ->with('error', 'Erro ao carregar formulário.');
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_id' => 'required|exists:product_groups,id',
            'name' => 'required|string|max:190',
        ]);

        // Verificar unicidade do nome dentro do mesmo grupo
        if (ProductSubgroup::where('group_id', $validated['group_id'])
            ->where('name', $validated['name'])
            ->exists()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Já existe um subgrupo com este nome nesta categoria.');
        }

        ProductSubgroup::create($validated);

        return redirect()->route('cadastros.product-subgroups.index')
            ->with('success', 'Subgrupo criado com sucesso!');
    }

    public function edit(ProductSubgroup $productSubgroup)
    {
        $productSubgroup->load('group');
        $groups = ProductGroup::orderBy('name')->get();
        return view('cadastros.product-subgroups.edit', compact('productSubgroup', 'groups'));
    }

    public function update(Request $request, ProductSubgroup $productSubgroup)
    {
        $validated = $request->validate([
            'group_id' => 'required|exists:product_groups,id',
            'name' => 'required|string|max:190',
        ]);

        // Verificar unicidade do nome dentro do mesmo grupo (exceto o próprio subgrupo)
        if (ProductSubgroup::where('group_id', $validated['group_id'])
            ->where('name', $validated['name'])
            ->where('id', '!=', $productSubgroup->id)
            ->exists()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Já existe um subgrupo com este nome nesta categoria.');
        }

        $productSubgroup->update($validated);

        return redirect()->route('cadastros.product-subgroups.index')
            ->with('success', 'Subgrupo atualizado com sucesso!');
    }

    public function destroy(ProductSubgroup $productSubgroup)
    {
        // Verificar se há produtos usando este subgrupo
        if ($productSubgroup->products()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Não é possível excluir este subgrupo pois existem produtos associados a ele.');
        }

        $productSubgroup->delete();

        return redirect()->route('cadastros.product-subgroups.index')
            ->with('success', 'Subgrupo excluído com sucesso!');
    }

    public function storeAjax(Request $request)
    {
        try {
            $validated = $request->validate([
                'group_id' => 'required|exists:product_groups,id',
                'name' => 'required|string|max:190',
            ]);

            // Verificar unicidade do nome dentro do mesmo grupo
            if (ProductSubgroup::where('group_id', $validated['group_id'])
                ->where('name', $validated['name'])
                ->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Já existe um subgrupo com este nome nesta categoria.'
                ], 422);
            }

            $subgroup = ProductSubgroup::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Subgrupo criado com sucesso!',
                'data' => [
                    'id' => $subgroup->id,
                    'name' => $subgroup->name,
                    'group_id' => $subgroup->group_id,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao criar subgrupo via AJAX: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar subgrupo: ' . $e->getMessage()
            ], 500);
        }
    }
}

