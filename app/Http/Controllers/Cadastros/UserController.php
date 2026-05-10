<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        $query = User::with(['company', 'store', 'employee']);

        if (request()->filled('q')) {
            $search = request('q');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (request()->filled('company_id')) {
            $query->where('company_id', request('company_id'));
        }

        if (request()->filled('role')) {
            $query->where('role', request('role'));
        }

        $users = $query->orderBy('name')->paginate(25);
        $companies = Company::where('is_active', true)->orderBy('trade_name')->get();

        return view('cadastros.users.index', compact('users', 'companies'));
    }

    public function create()
    {
        $companies = Company::where('is_active', true)->orderBy('trade_name')->get();
        $stores = Store::where('active', true)->orderBy('name')->get();
        $employees = Employee::where('is_active', true)->orderBy('name')->get();

        return view('cadastros.users.create', compact('companies', 'stores', 'employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|in:admin,gestor,vendedor',
            'company_id' => 'required|exists:companies,id',
            'store_id' => 'nullable|exists:stores,id',
            'employee_id' => 'nullable|exists:employees,id',
            'send_email' => 'boolean',
        ]);

        // Validação: vendedor deve ter store_id
        if ($validated['role'] === 'vendedor' && empty($validated['store_id'])) {
            return redirect()->back()->withInput()->with('error', 'Vendedor deve ter uma loja vinculada.');
        }

        $password = Str::random(12);
        $validated['password'] = Hash::make($password);

        $user = User::create($validated);

        // TODO: Enviar e-mail com senha se solicitado

        return redirect()->route('cadastros.users.index')
            ->with('success', 'Usuário criado com sucesso! Senha: ' . $password);
    }

    public function edit(User $user)
    {
        $user->load(['company', 'store', 'employee']);
        $companies = Company::where('is_active', true)->orderBy('trade_name')->get();
        $stores = Store::where('active', true)->orderBy('name')->get();
        $employees = Employee::where('is_active', true)->orderBy('name')->get();

        return view('cadastros.users.edit', compact('user', 'companies', 'stores', 'employees'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,gestor,vendedor',
            'company_id' => 'required|exists:companies,id',
            'store_id' => 'nullable|exists:stores,id',
            'employee_id' => 'nullable|exists:employees,id',
        ]);

        // Validação: vendedor deve ter store_id
        if ($validated['role'] === 'vendedor' && empty($validated['store_id'])) {
            return redirect()->back()->withInput()->with('error', 'Vendedor deve ter uma loja vinculada.');
        }

        $user->update($validated);

        return redirect()->route('cadastros.users.index')
            ->with('success', 'Usuário atualizado com sucesso!');
    }

    public function resetPassword(User $user)
    {
        $password = Str::random(12);
        $user->update(['password' => Hash::make($password)]);

        return redirect()->back()->with('success', 'Senha resetada! Nova senha: ' . $password);
    }
}

