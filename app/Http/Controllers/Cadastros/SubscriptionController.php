<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index()
    {
        $query = Subscription::with('company');

        if (request()->filled('company_id')) {
            $query->where('company_id', request('company_id'));
        }

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        $subscriptions = $query->orderBy('created_at', 'desc')->paginate(25);
        $companies = Company::where('is_active', true)->orderBy('trade_name')->get();

        return view('cadastros.subscriptions.index', compact('subscriptions', 'companies'));
    }

    public function create()
    {
        $companies = Company::where('is_active', true)->orderBy('trade_name')->get();
        return view('cadastros.subscriptions.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'plan_code' => 'required|in:GESTAO,FISCAL,FISCAL_SAT,VENDA_MAIS,VENDA_MAIS_SAT',
            'contract_type' => 'required|in:ATIVACAO_1x400,ATIVACAO_2x300,ATIVACAO_3x200,ATIVACAO_4x150,ATIVACAO_6x100,SEM_ATIVACAO',
            'activation_fee_total' => 'nullable|numeric|min:0',
            'monthly_fee' => 'nullable|numeric|min:0',
            'started_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:started_at',
            'status' => 'required|in:ATIVA,PENDENTE,SUSPENSA,CANCELADA',
            'notes' => 'nullable|string',
        ]);

        Subscription::create($validated);

        return redirect()->route('cadastros.subscriptions.index')
            ->with('success', 'Assinatura criada com sucesso!');
    }

    public function edit(Subscription $subscription)
    {
        $subscription->load('company');
        $companies = Company::where('is_active', true)->orderBy('trade_name')->get();
        return view('cadastros.subscriptions.edit', compact('subscription', 'companies'));
    }

    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'plan_code' => 'required|in:GESTAO,FISCAL,FISCAL_SAT,VENDA_MAIS,VENDA_MAIS_SAT',
            'contract_type' => 'required|in:ATIVACAO_1x400,ATIVACAO_2x300,ATIVACAO_3x200,ATIVACAO_4x150,ATIVACAO_6x100,SEM_ATIVACAO',
            'activation_fee_total' => 'nullable|numeric|min:0',
            'monthly_fee' => 'nullable|numeric|min:0',
            'started_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:started_at',
            'status' => 'required|in:ATIVA,PENDENTE,SUSPENSA,CANCELADA',
            'notes' => 'nullable|string',
        ]);

        $subscription->update($validated);

        return redirect()->route('cadastros.subscriptions.index')
            ->with('success', 'Assinatura atualizada com sucesso!');
    }

    public function showForCompany(Company $company)
    {
        $subscriptions = $company->subscriptions()->orderBy('created_at', 'desc')->get();
        return view('cadastros.subscriptions.show-company', compact('company', 'subscriptions'));
    }
}

