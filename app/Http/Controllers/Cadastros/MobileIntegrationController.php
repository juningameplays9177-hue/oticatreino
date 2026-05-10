<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\MobileIntegration;
use App\Models\Store;
use App\Services\ApiKeyService;
use Illuminate\Http\Request;

class MobileIntegrationController extends Controller
{
    public function index()
    {
        $integrations = MobileIntegration::with(['company', 'store'])
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('cadastros.mobile.index', compact('integrations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'store_id' => 'nullable|exists:stores,id',
            'device_name' => 'nullable|string|max:120',
            'token_label' => 'nullable|string|max:60',
            'scopes' => 'nullable|array',
            'scopes.*' => 'string',
        ]);

        $validated['api_key'] = ApiKeyService::generate();
        $validated['is_active'] = true;

        MobileIntegration::create($validated);

        return redirect()->route('cadastros.mobile.index')
            ->with('success', 'Integração criada com sucesso! A chave API foi gerada.');
    }

    public function update(Request $request, MobileIntegration $mobileIntegration)
    {
        $validated = $request->validate([
            'device_name' => 'nullable|string|max:120',
            'token_label' => 'nullable|string|max:60',
            'scopes' => 'nullable|array',
            'scopes.*' => 'string',
        ]);

        $mobileIntegration->update($validated);

        return redirect()->back()->with('success', 'Integração atualizada com sucesso!');
    }

    public function toggleActive(MobileIntegration $mobileIntegration)
    {
        $mobileIntegration->update(['is_active' => !$mobileIntegration->is_active]);
        return redirect()->back()->with('success', 'Status alterado com sucesso!');
    }

    public function regenerateKey(MobileIntegration $mobileIntegration)
    {
        $mobileIntegration->update(['api_key' => ApiKeyService::generate()]);
        return redirect()->back()->with('success', 'Chave API regenerada com sucesso!');
    }

    public function destroy(MobileIntegration $mobileIntegration)
    {
        $mobileIntegration->delete();
        return redirect()->route('cadastros.mobile.index')
            ->with('success', 'Integração removida com sucesso!');
    }
}

