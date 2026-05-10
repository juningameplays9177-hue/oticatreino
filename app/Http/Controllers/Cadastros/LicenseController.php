<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyLicense;
use App\Services\LicenseService;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function index()
    {
        $licenses = CompanyLicense::with('company')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        $hasIssues = LicenseService::hasLicenseIssues();
        $issues = $hasIssues ? LicenseService::getLicenseIssues() : [];

        return view('cadastros.licenses.index', compact('licenses', 'hasIssues', 'issues'));
    }

    public function update(Request $request, CompanyLicense $license)
    {
        $validated = $request->validate([
            'cert_valid_from' => 'nullable|date',
            'cert_valid_to' => 'nullable|date|after_or_equal:cert_valid_from',
            'cert_status' => 'required|in:VALIDO,EXPIRADO,NAO_CONFIGURADO',
            'license_status' => 'required|in:ATIVA,PENDENTE,CANCELADA,EXPIRADA',
        ]);

        $license->update($validated);
        LicenseService::checkAndUpdateLicenseStatus($license);

        return redirect()->back()->with('success', 'Licença atualizada com sucesso!');
    }
}

