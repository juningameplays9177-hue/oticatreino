<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\JobFunction;
use App\Models\Store;
use App\Services\TaxIdService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $query = Employee::with(['company', 'store', 'roleFunction']);

        if (request()->filled('q')) {
            $search = request('q');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('cpf_clean', 'like', "%{$search}%");
            });
        }

        if (request()->filled('company_id')) {
            $query->where('company_id', request('company_id'));
        }

        if (request()->filled('store_id')) {
            $query->where('store_id', request('store_id'));
        }

        $show = request('show', 'active');
        if ($show === 'active') {
            $query->where('is_active', true);
        } elseif ($show === 'inactive') {
            $query->where('is_active', false);
        }

        $employees = $query->orderBy('name')->paginate(25);
        $companies = Company::where('is_active', true)->orderBy('trade_name')->get();
        $stores = Store::where('active', true)->orderBy('name')->get();

        return view('cadastros.employees.index', compact('employees', 'companies', 'stores'));
    }

    public function create()
    {
        $companies = Company::where('is_active', true)->orderBy('trade_name')->get();
        $stores = Store::where('active', true)->orderBy('name')->get();
        $jobFunctions = JobFunction::where('is_active', true)->orderBy('name')->get();

        return view('cadastros.employees.create', compact('companies', 'stores', 'jobFunctions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'store_id' => 'nullable|exists:stores,id',
            'name' => 'required|string|max:190',
            'role_func_id' => 'nullable|exists:job_functions,id',
            'rg' => 'nullable|string|max:40',
            'cpf' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'mobile' => 'nullable|string|max:30',
            'zip_code' => 'nullable|string|max:8',
            'address' => 'nullable|string|max:190',
            'district' => 'nullable|string|max:120',
            'city' => 'nullable|string|max:120',
            'state' => 'nullable|string|max:2',
            'notes' => 'nullable|string',
        ]);

        if (!empty($validated['cpf'])) {
            $cpf = TaxIdService::normalizeCpf($validated['cpf']);
            if (!TaxIdService::validateCpf($cpf)) {
                return redirect()->back()->withInput()->with('error', 'CPF inválido.');
            }
            $validated['cpf_clean'] = $cpf;
            unset($validated['cpf']);
        }

        $validated['is_active'] = true;
        Employee::create($validated);

        return redirect()->route('cadastros.employees.index')
            ->with('success', 'Funcionário criado com sucesso!');
    }

    public function edit(Employee $employee)
    {
        $employee->load(['company', 'store', 'roleFunction']);
        $companies = Company::where('is_active', true)->orderBy('trade_name')->get();
        $stores = Store::where('active', true)->orderBy('name')->get();
        $jobFunctions = JobFunction::where('is_active', true)->orderBy('name')->get();

        return view('cadastros.employees.edit', compact('employee', 'companies', 'stores', 'jobFunctions'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'store_id' => 'nullable|exists:stores,id',
            'name' => 'required|string|max:190',
            'role_func_id' => 'nullable|exists:job_functions,id',
            'rg' => 'nullable|string|max:40',
            'cpf' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'mobile' => 'nullable|string|max:30',
            'zip_code' => 'nullable|string|max:8',
            'address' => 'nullable|string|max:190',
            'district' => 'nullable|string|max:120',
            'city' => 'nullable|string|max:120',
            'state' => 'nullable|string|max:2',
            'notes' => 'nullable|string',
        ]);

        if (!empty($validated['cpf'])) {
            $cpf = TaxIdService::normalizeCpf($validated['cpf']);
            if (!TaxIdService::validateCpf($cpf)) {
                return redirect()->back()->withInput()->with('error', 'CPF inválido.');
            }
            $validated['cpf_clean'] = $cpf;
            unset($validated['cpf']);
        }

        $validated['is_active'] = $request->has('is_active') && $request->is_active == '1';
        $employee->update($validated);

        return redirect()->route('cadastros.employees.index')
            ->with('success', 'Funcionário atualizado com sucesso!');
    }

    public function destroy(Employee $employee)
    {
        $employee->update(['is_active' => false]);
        return redirect()->back()->with('success', 'Funcionário inativado com sucesso!');
    }
}

