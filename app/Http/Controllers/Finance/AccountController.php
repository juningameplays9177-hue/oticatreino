<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Helpers\CompanyHelper;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = CompanyHelper::getCompanyId($user);
        
        $accounts = Account::where('company_id', $companyId)
            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->orderBy('name')
            ->get();

        return response()->json($accounts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'nullable|exists:stores,id',
            'name' => 'required|string|max:190',
            'type' => 'required|in:cash,bank,credit_gateway',
            'bank_name' => 'nullable|string|max:120',
            'agency' => 'nullable|string|max:20',
            'number' => 'nullable|string|max:30',
            'pix_key' => 'nullable|string|max:120',
            'opening_balance' => 'nullable|numeric',
        ]);

        $user = auth()->user();
        $companyId = CompanyHelper::getCompanyId($user);
        
        $account = Account::create([
            'company_id' => $companyId,
            'store_id' => $validated['store_id'] ?? null,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'bank_name' => $validated['bank_name'] ?? null,
            'agency' => $validated['agency'] ?? null,
            'number' => $validated['number'] ?? null,
            'pix_key' => $validated['pix_key'] ?? null,
            'opening_balance' => $validated['opening_balance'] ?? 0,
        ]);

        return response()->json(['success' => true, 'account' => $account]);
    }

    public function update(Request $request, Account $account)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:190',
            'is_active' => 'sometimes|boolean',
            'bank_name' => 'nullable|string|max:120',
            'agency' => 'nullable|string|max:20',
            'number' => 'nullable|string|max:30',
            'pix_key' => 'nullable|string|max:120',
        ]);

        $account->update($validated);

        return response()->json(['success' => true, 'account' => $account]);
    }
}

