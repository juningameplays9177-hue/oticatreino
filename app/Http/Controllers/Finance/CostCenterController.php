<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\CostCenter;
use Illuminate\Http\Request;

class CostCenterController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = \App\Helpers\CompanyHelper::getCompanyId($user);
        
        $costCenters = CostCenter::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return response()->json($costCenters);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:190',
        ]);

        $user = auth()->user();
        $companyId = \App\Helpers\CompanyHelper::getCompanyId($user);
        
        $costCenter = CostCenter::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
        ]);

        return response()->json(['success' => true, 'cost_center' => $costCenter]);
    }

    public function update(Request $request, CostCenter $costCenter)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:190',
            'is_active' => 'sometimes|boolean',
        ]);

        $costCenter->update($validated);

        return response()->json(['success' => true, 'cost_center' => $costCenter]);
    }
}

