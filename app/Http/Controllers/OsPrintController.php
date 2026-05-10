<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use App\Services\PrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OsPrintController extends Controller
{
    protected $printService;

    public function __construct(PrintService $printService)
    {
        $this->printService = $printService;
    }

    public function show(ServiceOrder $o, Request $request)
    {
        try {
            // Carregar todas as relações necessárias, incluindo a receita e pagamentos
            $serviceOrder = ServiceOrder::with([
                'client',
                'store',
                'company',
                'employee',
                'items.product',
                'prescription',
                'sale.payments',
            ])->findOrFail($o->id);
            
            // Determinar o tipo de impressão: 'cliente' ou 'controle' (padrão: cliente)
            $tipo = $request->get('tipo', 'cliente');
            
            // Validar tipo
            if (!in_array($tipo, ['cliente', 'controle'])) {
                $tipo = 'cliente';
            }
            
            // Retornar view HTML para impressão no navegador
            $viewName = $tipo === 'controle' ? 'os.print-controle' : 'os.print-cliente';
            return view($viewName, compact('serviceOrder', 'tipo'));
        } catch (\Exception $e) {
            \Log::error('Erro ao imprimir OS: ' . $e->getMessage(), [
                'os_id' => $o->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Se for requisição AJAX, retornar JSON com erro
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'error' => 'Erro ao carregar OS para impressão: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Erro ao carregar OS para impressão: ' . $e->getMessage());
        }
    }
}

