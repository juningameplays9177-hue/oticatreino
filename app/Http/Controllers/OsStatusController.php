<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use App\Services\OsStatusService;
use Illuminate\Http\Request;

class OsStatusController extends Controller
{
    protected $statusService;

    public function __construct(OsStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    public function update(Request $request, ServiceOrder $o)
    {
        $request->validate([
            'status' => 'required|in:REGISTRADA,EM_PRODUCAO,PRONTA,ENTREGUE,CANCELADA,PERDA',
            'reason' => 'nullable|string|max:190',
            'note' => 'nullable|string|max:190',
        ]);

        try {
            $newStatus = $request->status;
            
            // Se está mudando para ENTREGUE, verificar se há saldo pendente
            if ($newStatus === 'ENTREGUE' && $o->status !== 'ENTREGUE') {
                $receivables = \App\Models\Finance\Receivable::where('os_id', $o->id)
                    ->where('status', '!=', 'paid')
                    ->get();
                
                $totalPendente = $receivables->sum('balance_amount');
                
                    if ($totalPendente > 0) {
                        // Continuar com a atualização, mas adicionar aviso grande na mensagem
                        $this->statusService->updateStatus(
                            $o,
                            $newStatus,
                            $request->reason,
                            $request->note
                        );
                        
                        $warningMessage = "⚠️ ATENÇÃO: Esta OS possui saldo pendente de R$ " . number_format($totalPendente, 2, ',', '.') . " em contas a receber. Verifique o recebimento antes de finalizar a entrega.";
                        
                        return redirect()->back()
                            ->with('success', 'Status atualizado para ENTREGUE com sucesso!')
                            ->with('warning', $warningMessage);
                    }
            }
            
            $this->statusService->updateStatus(
                $o,
                $newStatus,
                $request->reason,
                $request->note
            );

            return redirect()->back()
                ->with('success', 'Status atualizado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao atualizar status: ' . $e->getMessage());
        }
    }
}

