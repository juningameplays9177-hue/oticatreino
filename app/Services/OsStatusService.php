<?php

namespace App\Services;

use App\Models\ServiceOrder;
use App\Models\ServiceOrderStatusHistory;
use App\Services\StockReservationService;
use Illuminate\Support\Facades\DB;

class OsStatusService
{
    protected $stockService;

    public function __construct(StockReservationService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function updateStatus(ServiceOrder $serviceOrder, string $newStatus, ?string $reason = null, ?string $note = null): ServiceOrder
    {
        $oldStatus = $serviceOrder->status;

        if ($oldStatus === $newStatus) {
            return $serviceOrder;
        }

        DB::beginTransaction();

        try {
            // Validar transição
            $this->validateStatusTransition($oldStatus, $newStatus);

            // Atualizar status
            $serviceOrder->update([
                'status' => $newStatus,
                'cancel_reason' => ($newStatus === 'CANCELADA') ? $reason : null,
                'loss_reason' => ($newStatus === 'PERDA') ? $reason : null,
            ]);

            // Registrar histórico
            ServiceOrderStatusHistory::create([
                'service_order_id' => $serviceOrder->id,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'changed_by' => auth()->id(),
                'changed_at' => now(),
                'note' => $note,
            ]);

            // Gerenciar estoque
            if ($newStatus === 'EM_PRODUCAO' && $oldStatus !== 'EM_PRODUCAO') {
                $this->stockService->reserveStock($serviceOrder);
            } elseif (in_array($oldStatus, ['EM_PRODUCAO', 'PRONTA']) && in_array($newStatus, ['CANCELADA', 'PERDA'])) {
                if ($newStatus === 'CANCELADA') {
                    $this->stockService->releaseStock($serviceOrder);
                }
                // PERDA não estorna estoque
            }

            DB::commit();
            return $serviceOrder->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function validateStatusTransition(string $from, string $to): void
    {
        $validTransitions = [
            'REGISTRADA' => ['EM_PRODUCAO', 'CANCELADA'],
            'EM_PRODUCAO' => ['PRONTA', 'CANCELADA', 'PERDA', 'REGISTRADA'], // Permite voltar
            'PRONTA' => ['ENTREGUE', 'CANCELADA', 'PERDA', 'EM_PRODUCAO'], // Permite voltar
            'ENTREGUE' => ['PRONTA'], // Permite voltar para PRONTA
            'CANCELADA' => [],
            'PERDA' => [],
            'VENDIDA' => [],
            'NAO_VENDIDA' => [],
        ];

        if (!in_array($to, $validTransitions[$from] ?? [])) {
            throw new \Exception("Transição de status inválida: {$from} → {$to}");
        }
    }
}

