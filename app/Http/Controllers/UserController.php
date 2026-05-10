<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::orderBy('name')->paginate(10);
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stores = Store::where('active', true)->orderBy('name')->get();
        return view('users.create', compact('stores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:admin,gerente'],
            'store_id' => ['required_if:role,gerente', 'nullable', 'exists:stores,id'],
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'email_verified_at' => now(),
        ];

        // Se for gerente, adicionar loja
        if ($request->role === 'gerente' && $request->store_id) {
            $data['store_id'] = $request->store_id;
            // Buscar company_id da loja
            $store = Store::find($request->store_id);
            if ($store && $store->company_id) {
                $data['company_id'] = $store->company_id;
            }
        }

        User::create($data);

        return redirect()->route('users.index')
            ->with('success', 'Usuário criado com sucesso!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $stores = Store::where('active', true)->orderBy('name')->get();
        return view('users.edit', compact('user', 'stores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:admin,gerente'],
            'store_id' => ['required_if:role,gerente', 'nullable', 'exists:stores,id'],
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;
        
        // Se for gerente, atualizar loja
        if ($request->role === 'gerente' && $request->store_id) {
            $user->store_id = $request->store_id;
            // Buscar company_id da loja
            $store = Store::find($request->store_id);
            if ($store && $store->company_id) {
                $user->company_id = $store->company_id;
            }
        } else {
            // Se mudou de gerente para admin, remover loja
            if ($user->role === 'gerente' && $request->role === 'admin') {
                $user->store_id = null;
            }
        }
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('users.index')
            ->with('success', 'Usuário atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Não permitir deletar o próprio usuário logado
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'Você não pode deletar seu próprio usuário!');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Usuário deletado com sucesso!');
    }
}

