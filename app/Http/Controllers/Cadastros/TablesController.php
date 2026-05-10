<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Controllers\Controller;
use App\Models\JobFunction;
use App\Models\ClientSource;
use App\Models\Lab;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class TablesController extends Controller
{
    public function index()
    {
        return view('cadastros.tables.index');
    }

    // Job Functions
    public function jobFunctions()
    {
        $items = JobFunction::orderBy('name')->paginate(25);
        return view('cadastros.tables.job-functions', compact('items'));
    }

    public function storeJobFunction(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:job_functions,name',
        ]);

        JobFunction::create($validated);
        return redirect()->back()->with('success', 'Função criada com sucesso!');
    }

    public function updateJobFunction(Request $request, JobFunction $jobFunction)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:job_functions,name,' . $jobFunction->id,
            'is_active' => 'boolean',
        ]);

        $jobFunction->update($validated);
        return redirect()->back()->with('success', 'Função atualizada com sucesso!');
    }

    // Client Sources
    public function clientSources()
    {
        $items = ClientSource::orderBy('name')->paginate(25);
        return view('cadastros.tables.client-sources', compact('items'));
    }

    public function storeClientSource(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:120|unique:client_sources,name',
            ], [
                'name.required' => 'O nome da origem é obrigatório.',
                'name.unique' => 'Esta origem já está cadastrada.',
            ]);

            $source = ClientSource::create($validated);
            
            // Se for requisição AJAX, retornar JSON
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Origem criada com sucesso!',
                    'data' => [
                        'id' => $source->id,
                        'name' => $source->name,
                    ]
                ], 200);
            }
            
            return redirect()->back()->with('success', 'Origem criada com sucesso!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao criar origem: ' . $e->getMessage(),
                ], 500);
            }
            throw $e;
        }
    }

    public function updateClientSource(Request $request, ClientSource $clientSource)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:client_sources,name,' . $clientSource->id,
            'is_active' => 'boolean',
        ]);

        $clientSource->update($validated);
        return redirect()->back()->with('success', 'Origem atualizada com sucesso!');
    }

    // Payment Methods
    public function paymentMethods()
    {
        $items = PaymentMethod::orderBy('name')->paginate(25);
        return view('cadastros.tables.payment-methods', compact('items'));
    }

    public function storePaymentMethod(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:payment_methods,name',
        ]);

        PaymentMethod::create($validated);
        return redirect()->back()->with('success', 'Forma de pagamento criada com sucesso!');
    }

    public function updatePaymentMethod(Request $request, PaymentMethod $paymentMethod)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:payment_methods,name,' . $paymentMethod->id,
            'is_active' => 'boolean',
        ]);

        $paymentMethod->update($validated);
        return redirect()->back()->with('success', 'Forma de pagamento atualizada com sucesso!');
    }
}

