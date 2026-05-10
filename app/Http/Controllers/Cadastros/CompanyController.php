<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\TaxIdService;
use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CompanyController extends Controller
{
    public function index()
    {
        try {
            $query = Company::query();

            if (request()->filled('q')) {
                $search = request('q');
                $query->where(function ($q) use ($search) {
                    $q->where('trade_name', 'like', "%{$search}%")
                        ->orWhere('legal_name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            }

            // Aplicar filtro de is_active apenas se explicitamente solicitado
            // Por padrão, mostrar todas as empresas (ativas e inativas)
            if (request()->filled('is_active')) {
                $query->where('is_active', request('is_active') === '1');
            }

            // Sempre listar todas as empresas (sem paginação) para garantir que nenhuma fique oculta
            // Se houver muitas empresas no futuro, pode-se adicionar paginação novamente
            $allCompanies = $query->orderBy('trade_name')->get();
            
            // Converter para paginator para manter compatibilidade com a view
            $companies = new \Illuminate\Pagination\LengthAwarePaginator(
                $allCompanies,
                $allCompanies->count(),
                max($allCompanies->count(), 1), // Limite igual ao total para mostrar todas
                1,
                ['path' => request()->url(), 'query' => request()->query()]
            );

            // Verificar licenças de forma segura
            $hasLicenseIssues = false;
            $licenseIssues = [];
            
            try {
                if (class_exists(LicenseService::class) && Schema::hasTable('company_licenses')) {
                    $hasLicenseIssues = LicenseService::hasLicenseIssues();
                    $licenseIssues = $hasLicenseIssues ? LicenseService::getLicenseIssues() : [];
                }
            } catch (\Exception $e) {
                Log::warning('Erro ao verificar licenças: ' . $e->getMessage());
            }

            return view('cadastros.companies.index', compact('companies', 'hasLicenseIssues', 'licenseIssues'));
        } catch (\Exception $e) {
            Log::error('Erro no CompanyController@index: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return response('Erro ao carregar empresas. Verifique o log para detalhes.', 500);
        }
    }

    public function create()
    {
        return view('cadastros.companies.create');
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tax_id_type' => 'required|in:CPF,CNPJ',
                'legal_name' => 'required|string|max:190',
                'trade_name' => 'required|string|max:190',
                'slug' => 'required|string|max:80|unique:companies,slug',
                'cpf' => 'required_if:tax_id_type,CPF|nullable|string',
                'cnpj' => 'required_if:tax_id_type,CNPJ|nullable|string',
                'email' => 'nullable|email|max:190',
                'phone' => 'nullable|string|max:30',
                'mobile' => 'nullable|string|max:30',
                'contact_name' => 'nullable|string|max:120',
                'zip_code' => 'nullable|string|max:8',
                'address' => 'nullable|string|max:190',
                'number' => 'nullable|string|max:30',
                'complement' => 'nullable|string|max:120',
                'district' => 'nullable|string|max:120',
                'city' => 'nullable|string|max:120',
                'state' => 'nullable|string|max:2',
                'logo' => 'nullable|image|max:2048',
            ]);

            // Validar CPF/CNPJ
            if ($validated['tax_id_type'] === 'CPF') {
                $cpf = TaxIdService::normalizeCpf($validated['cpf'] ?? '');
                if (empty($cpf)) {
                    return redirect()->back()->withInput()->with('error', 'CPF é obrigatório.');
                }
                if (!TaxIdService::validateCpf($cpf)) {
                    return redirect()->back()->withInput()->with('error', 'CPF inválido.');
                }
                $validated['cpf_clean'] = $cpf;
                $validated['cnpj_clean'] = null;
                // Verificar unicidade do CPF
                if (Company::where('cpf_clean', $cpf)->exists()) {
                    return redirect()->back()->withInput()->with('error', 'CPF já cadastrado.');
                }
            } else {
                $cnpj = TaxIdService::normalizeCnpj($validated['cnpj'] ?? '');
                if (empty($cnpj)) {
                    return redirect()->back()->withInput()->with('error', 'CNPJ é obrigatório.');
                }
                if (!TaxIdService::validateCnpj($cnpj)) {
                    return redirect()->back()->withInput()->with('error', 'CNPJ inválido.');
                }
                $validated['cnpj_clean'] = $cnpj;
                $validated['cpf_clean'] = null;
                // Verificar unicidade do CNPJ
                if (Company::where('cnpj_clean', $cnpj)->exists()) {
                    return redirect()->back()->withInput()->with('error', 'CNPJ já cadastrado.');
                }
            }

            // Upload logo
            if ($request->hasFile('logo')) {
                try {
                    $validated['logo_path'] = $request->file('logo')->store('companies/logos', 'public');
                } catch (\Exception $e) {
                    Log::warning('Erro ao fazer upload do logo: ' . $e->getMessage());
                    // Continuar sem o logo se houver erro
                }
            }

            $validated['is_active'] = true;
            unset($validated['cpf'], $validated['cnpj'], $validated['logo']);

            // Gerar código único baseado no slug
            $baseCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['slug']), 0, 25));
            if (empty($baseCode)) {
                $baseCode = 'EMP' . time();
            }
            
            $code = $baseCode;
            $counter = 1;
            while (Company::where('code', $code)->exists()) {
                $code = $baseCode . $counter;
                $counter++;
                if ($counter > 100) break; // Proteção contra loop infinito
            }
            $validated['code'] = $code;

            // Preparar dados para inserção (apenas campos permitidos no fillable)
            $companyData = array_intersect_key($validated, array_flip([
                'code', 'tax_id_type', 'cpf_clean', 'cnpj_clean', 'legal_name', 'trade_name', 'slug',
                'phone', 'mobile', 'contact_name', 'email', 'zip_code', 'address', 'number',
                'complement', 'district', 'city', 'state', 'logo_path', 'is_active'
            ]));

            $company = Company::create($companyData);

            // Criar licença padrão (se a tabela existir)
            try {
                if (Schema::hasTable('company_licenses')) {
                    $company->licenses()->create([
                        'license_status' => 'PENDENTE',
                        'cert_status' => 'NAO_CONFIGURADO',
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Erro ao criar licença padrão: ' . $e->getMessage());
                // Não impedir a criação da empresa se a licença falhar
            }

            return redirect()->route('cadastros.companies.index')
                ->with('success', 'Empresa criada com sucesso!');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', 'Por favor, corrija os erros no formulário.');
        } catch (\Exception $e) {
            Log::error('Erro ao criar empresa: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao criar empresa: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            // Validar se o ID é numérico
            if (!is_numeric($id)) {
                Log::warning('ID inválido para edição de empresa: ' . $id);
                return redirect()->route('cadastros.companies.index')
                    ->with('error', 'ID inválido.');
            }
            
            $company = Company::findOrFail($id);
            
            // Garantir que campos obrigatórios existam
            if (empty($company->tax_id_type)) {
                $company->tax_id_type = 'CNPJ';
            }
            
            return view('cadastros.companies.edit', compact('company'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Empresa não encontrada: ' . $id);
            return redirect()->route('cadastros.companies.index')
                ->with('error', 'Loja não encontrada.');
        } catch (\Throwable $e) {
            Log::error('Erro ao carregar empresa para edição: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()->route('cadastros.companies.index')
                ->with('error', 'Erro ao carregar loja. Verifique os logs para mais detalhes.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $company = Company::findOrFail($id);
            
            $validated = $request->validate([
                'tax_id_type' => 'required|in:CPF,CNPJ',
                'legal_name' => 'required|string|max:190',
                'trade_name' => 'required|string|max:190',
                'slug' => 'required|string|max:80|unique:companies,slug,' . $company->id,
                'cpf' => 'required_if:tax_id_type,CPF|nullable|string',
                'cnpj' => 'required_if:tax_id_type,CNPJ|nullable|string',
                'email' => 'nullable|email|max:190',
                'phone' => 'nullable|string|max:30',
                'mobile' => 'nullable|string|max:30',
                'contact_name' => 'nullable|string|max:120',
                'zip_code' => 'nullable|string|max:8',
                'address' => 'nullable|string|max:190',
                'number' => 'nullable|string|max:30',
                'complement' => 'nullable|string|max:120',
                'district' => 'nullable|string|max:120',
                'city' => 'nullable|string|max:120',
                'state' => 'nullable|string|max:2',
                'logo' => 'nullable|image|max:2048',
            ]);

            // Validar CPF/CNPJ
            if ($validated['tax_id_type'] === 'CPF') {
                $cpf = TaxIdService::normalizeCpf($validated['cpf'] ?? '');
                if (empty($cpf)) {
                    return redirect()->back()->withInput()->with('error', 'CPF é obrigatório.');
                }
                if (!TaxIdService::validateCpf($cpf)) {
                    return redirect()->back()->withInput()->with('error', 'CPF inválido.');
                }
                $validated['cpf_clean'] = $cpf;
                $validated['cnpj_clean'] = null;
                // Verificar unicidade do CPF (exceto a própria empresa)
                if (Company::where('cpf_clean', $cpf)->where('id', '!=', $company->id)->exists()) {
                    return redirect()->back()->withInput()->with('error', 'CPF já cadastrado.');
                }
            } else {
                $cnpj = TaxIdService::normalizeCnpj($validated['cnpj'] ?? '');
                if (empty($cnpj)) {
                    return redirect()->back()->withInput()->with('error', 'CNPJ é obrigatório.');
                }
                if (!TaxIdService::validateCnpj($cnpj)) {
                    return redirect()->back()->withInput()->with('error', 'CNPJ inválido.');
                }
                $validated['cnpj_clean'] = $cnpj;
                $validated['cpf_clean'] = null;
                // Verificar unicidade do CNPJ (exceto a própria empresa)
                if (Company::where('cnpj_clean', $cnpj)->where('id', '!=', $company->id)->exists()) {
                    return redirect()->back()->withInput()->with('error', 'CNPJ já cadastrado.');
                }
            }

            // Upload logo
            if ($request->hasFile('logo')) {
                try {
                    if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                        Storage::disk('public')->delete($company->logo_path);
                    }
                    $validated['logo_path'] = $request->file('logo')->store('companies/logos', 'public');
                } catch (\Exception $e) {
                    Log::warning('Erro ao fazer upload do logo: ' . $e->getMessage());
                    // Continuar sem atualizar o logo se houver erro
                }
            }

            unset($validated['cpf'], $validated['cnpj'], $validated['logo']);

            // Preparar dados para atualização (apenas campos permitidos no fillable)
            $companyData = array_intersect_key($validated, array_flip([
                'tax_id_type', 'cpf_clean', 'cnpj_clean', 'legal_name', 'trade_name', 'slug',
                'phone', 'mobile', 'contact_name', 'email', 'zip_code', 'address', 'number',
                'complement', 'district', 'city', 'state', 'logo_path', 'is_active'
            ]));

            $company->update($companyData);

            return redirect()->route('cadastros.companies.index')
                ->with('success', 'Empresa atualizada com sucesso!');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', 'Por favor, corrija os erros no formulário.');
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar empresa: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao atualizar empresa: ' . $e->getMessage());
        }
    }

    public function toggleActive($id)
    {
        try {
            $company = Company::findOrFail($id);
            $company->update(['is_active' => !$company->is_active]);
            return redirect()->back()->with('success', 'Status alterado com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao alterar status da empresa: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao alterar status.');
        }
    }
}
