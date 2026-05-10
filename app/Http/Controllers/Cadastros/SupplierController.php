<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\TaxIdService;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $query = Supplier::query();

        if (request()->filled('q')) {
            $search = request('q');
            $query->where(function ($q) use ($search) {
                $q->where('trade_name', 'like', "%{$search}%")
                    ->orWhere('legal_name', 'like', "%{$search}%")
                    ->orWhere('cnpj_clean', 'like', "%{$search}%")
                    ->orWhere('cpf_clean', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (request()->filled('is_active')) {
            $query->where('is_active', request('is_active') === '1');
        }

        $suppliers = $query->orderBy('trade_name')->paginate(25);

        return view('cadastros.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('cadastros.suppliers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tax_id_type' => 'required|in:CPF,CNPJ',
            'trade_name' => 'required|string|max:190',
            'legal_name' => 'nullable|string|max:190',
            'cpf' => 'required_if:tax_id_type,CPF|nullable|string',
            'cnpj' => 'required_if:tax_id_type,CNPJ|nullable|string',
            'is_lab' => 'boolean',
            'taxpayer_icms' => 'boolean',
            'ie' => 'nullable|string|max:40',
            'im' => 'nullable|string|max:40',
            'suframa' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:190',
            'website' => 'nullable|url|max:190',
            'zip_code' => 'nullable|string|max:8',
            'address' => 'nullable|string|max:190',
            'number' => 'nullable|string|max:30',
            'complement' => 'nullable|string|max:120',
            'district' => 'nullable|string|max:120',
            'city' => 'nullable|string|max:120',
            'state' => 'nullable|string|max:2',
            'notes' => 'nullable|string',
        ]);

        // Validar CPF/CNPJ
        if ($validated['tax_id_type'] === 'CPF') {
            $cpf = TaxIdService::normalizeCpf($validated['cpf'] ?? '');
            if (!TaxIdService::validateCpf($cpf)) {
                return redirect()->back()->withInput()->with('error', 'CPF inválido.');
            }
            $validated['cpf_clean'] = $cpf;
            // Verificar unicidade
            if (Supplier::where('cpf_clean', $cpf)->exists()) {
                return redirect()->back()->withInput()->with('error', 'CPF já cadastrado.');
            }
        } else {
            $cnpj = TaxIdService::normalizeCnpj($validated['cnpj'] ?? '');
            if (!TaxIdService::validateCnpj($cnpj)) {
                return redirect()->back()->withInput()->with('error', 'CNPJ inválido.');
            }
            $validated['cnpj_clean'] = $cnpj;
            if (Supplier::where('cnpj_clean', $cnpj)->exists()) {
                return redirect()->back()->withInput()->with('error', 'CNPJ já cadastrado.');
            }
        }

        $validated['is_active'] = true;
        unset($validated['cpf'], $validated['cnpj']);

        $supplier = Supplier::create($validated);

        return redirect()->route('cadastros.suppliers.index')
            ->with('success', 'Fornecedor criado com sucesso!');
    }

    public function edit(Supplier $supplier)
    {
        $supplier->load(['contacts', 'representatives']);
        return view('cadastros.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'tax_id_type' => 'required|in:CPF,CNPJ',
            'trade_name' => 'required|string|max:190',
            'legal_name' => 'nullable|string|max:190',
            'cpf' => 'required_if:tax_id_type,CPF|nullable|string',
            'cnpj' => 'required_if:tax_id_type,CNPJ|nullable|string',
            'is_lab' => 'boolean',
            'taxpayer_icms' => 'boolean',
            'ie' => 'nullable|string|max:40',
            'im' => 'nullable|string|max:40',
            'suframa' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:190',
            'website' => 'nullable|url|max:190',
            'zip_code' => 'nullable|string|max:8',
            'address' => 'nullable|string|max:190',
            'number' => 'nullable|string|max:30',
            'complement' => 'nullable|string|max:120',
            'district' => 'nullable|string|max:120',
            'city' => 'nullable|string|max:120',
            'state' => 'nullable|string|max:2',
            'notes' => 'nullable|string',
        ]);

        // Validar CPF/CNPJ (similar ao store)
        if ($validated['tax_id_type'] === 'CPF') {
            $cpf = TaxIdService::normalizeCpf($validated['cpf'] ?? '');
            if (!TaxIdService::validateCpf($cpf)) {
                return redirect()->back()->withInput()->with('error', 'CPF inválido.');
            }
            $validated['cpf_clean'] = $cpf;
            $validated['cnpj_clean'] = null;
            // Verificar unicidade (exceto o próprio registro)
            if (Supplier::where('cpf_clean', $cpf)->where('id', '!=', $supplier->id)->exists()) {
                return redirect()->back()->withInput()->with('error', 'CPF já cadastrado.');
            }
        } else {
            $cnpj = TaxIdService::normalizeCnpj($validated['cnpj'] ?? '');
            if (!TaxIdService::validateCnpj($cnpj)) {
                return redirect()->back()->withInput()->with('error', 'CNPJ inválido.');
            }
            $validated['cnpj_clean'] = $cnpj;
            $validated['cpf_clean'] = null;
            // Verificar unicidade (exceto o próprio registro)
            if (Supplier::where('cnpj_clean', $cnpj)->where('id', '!=', $supplier->id)->exists()) {
                return redirect()->back()->withInput()->with('error', 'CNPJ já cadastrado.');
            }
        }

        unset($validated['cpf'], $validated['cnpj']);
        $validated['is_active'] = $request->has('is_active') && $request->is_active == '1';
        $supplier->update($validated);

        return redirect()->route('cadastros.suppliers.index')
            ->with('success', 'Fornecedor atualizado com sucesso!');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->update(['is_active' => false]);
        return redirect()->back()->with('success', 'Fornecedor inativado com sucesso!');
    }

    public function storeAjax(Request $request)
    {
        try {
            $validated = $request->validate([
                'trade_name' => 'required|string|max:190',
                'legal_name' => 'nullable|string|max:190',
                'tax_id_type' => 'required|in:CPF,CNPJ',
                'cpf' => 'required_if:tax_id_type,CPF|nullable|string',
                'cnpj' => 'required_if:tax_id_type,CNPJ|nullable|string',
                'email' => 'nullable|email|max:190',
                'phone' => 'nullable|string|max:20',
            ]);

            // Validar CPF/CNPJ
            if ($validated['tax_id_type'] === 'CPF') {
                $cpf = \App\Services\TaxIdService::normalizeCpf($validated['cpf'] ?? '');
                if (!\App\Services\TaxIdService::validateCpf($cpf)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'CPF inválido.'
                    ], 422);
                }
                $validated['cpf_clean'] = $cpf;
                if (Supplier::where('cpf_clean', $cpf)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'CPF já cadastrado.'
                    ], 422);
                }
            } else {
                $cnpj = \App\Services\TaxIdService::normalizeCnpj($validated['cnpj'] ?? '');
                if (!\App\Services\TaxIdService::validateCnpj($cnpj)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'CNPJ inválido.'
                    ], 422);
                }
                $validated['cnpj_clean'] = $cnpj;
                if (Supplier::where('cnpj_clean', $cnpj)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'CNPJ já cadastrado.'
                    ], 422);
                }
            }

            $validated['is_active'] = true;
            unset($validated['cpf'], $validated['cnpj']);

            $supplier = Supplier::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Fornecedor criado com sucesso!',
                'data' => [
                    'id' => $supplier->id,
                    'name' => $supplier->trade_name ?: $supplier->legal_name,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao criar fornecedor via AJAX: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar fornecedor: ' . $e->getMessage()
            ], 500);
        }
    }
}

