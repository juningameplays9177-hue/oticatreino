<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceCategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = \App\Helpers\CompanyHelper::getCompanyId($user);
        
        $categories = FinanceCategory::where('company_id', $companyId)
            ->when($request->nature, fn($q) => $q->where('nature', $request->nature))
            ->with('parent')
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:finance_categories,id',
            'name' => 'required|string|max:190',
            'nature' => 'required|in:revenue,expense,asset,liability,equity',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
        ]);

        $user = auth()->user();
        $companyId = \App\Helpers\CompanyHelper::getCompanyId($user);
        
        $category = FinanceCategory::create([
            'company_id' => $companyId,
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'nature' => $validated['nature'],
            'cost_center_id' => $validated['cost_center_id'] ?? null,
        ]);

        return response()->json(['success' => true, 'category' => $category]);
    }

    public function update(Request $request, FinanceCategory $category)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:190',
            'is_active' => 'sometimes|boolean',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
        ]);

        $category->update($validated);

        return response()->json(['success' => true, 'category' => $category]);
    }
}

