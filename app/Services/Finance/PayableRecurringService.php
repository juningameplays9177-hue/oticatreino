<?php

namespace App\Services\Finance;

use App\Models\Finance\Payable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PayableRecurringService
{
    /**
     * Cria uma conta a pagar com parcelamento ou recorrência
     */
    public function createPayable(array $data): Payable
    {
        return DB::transaction(function () use ($data) {
            $installments = $data['installments'] ?? 1;
            $isRecurring = $data['is_recurring'] ?? false;
            $recurringType = $data['recurring_type'] ?? null;
            $recurringEndDate = $data['recurring_end_date'] ?? null;
            
            // Gerar ID de grupo para recorrência/parcelamento
            $groupId = $isRecurring || $installments > 1 
                ? 'GRP-' . time() . '-' . uniqid() 
                : null;
            
            $originalAmount = $data['original_amount'];
            $amountPerInstallment = $originalAmount / $installments;
            
            $firstPayable = null;
            $dueDate = Carbon::parse($data['due_date']);
            
            for ($i = 1; $i <= $installments; $i++) {
                $payableData = [
                    'company_id' => $data['company_id'],
                    'store_id' => $data['store_id'],
                    'supplier_id' => $data['supplier_id'] ?? null,
                    'document_no' => $data['document_no'] ?? null,
                    'issue_date' => $data['issue_date'],
                    'due_date' => $dueDate->format('Y-m-d'),
                    'original_amount' => $amountPerInstallment,
                    'balance_amount' => $amountPerInstallment,
                    'status' => 'open',
                    'category_id' => $data['category_id'] ?? null,
                    'cost_center_id' => $data['cost_center_id'] ?? null,
                    'note' => $data['note'] ?? null,
                    'is_recurring' => $isRecurring,
                    'recurring_type' => $recurringType,
                    'recurring_end_date' => $recurringEndDate,
                    'installments' => $installments,
                    'installment_number' => $i,
                    'parent_payable_id' => $firstPayable ? $firstPayable->id : null,
                    'recurring_group_id' => $groupId,
                ];
                
                // Adicionar created_by se a coluna existir e foi passado
                if (isset($data['created_by']) && \Schema::hasColumn('payables', 'created_by')) {
                    $payableData['created_by'] = $data['created_by'];
                }
                
                $payable = Payable::create($payableData);
                
                if ($i === 1) {
                    $firstPayable = $payable;
                }
                
                // Calcular próxima data de vencimento
                if ($isRecurring && $recurringType) {
                    $dueDate = $this->calculateNextDueDate($dueDate, $recurringType);
                } elseif ($installments > 1) {
                    // Parcelamento simples: adicionar 30 dias por parcela
                    $dueDate = $dueDate->copy()->addDays(30);
                }
            }
            
            Log::info('Conta a pagar criada com parcelamento/recorrência', [
                'group_id' => $groupId,
                'installments' => $installments,
                'is_recurring' => $isRecurring,
                'first_payable_id' => $firstPayable->id,
            ]);
            
            return $firstPayable;
        });
    }
    
    /**
     * Calcula próxima data de vencimento baseada no tipo de recorrência
     */
    protected function calculateNextDueDate(Carbon $currentDate, string $recurringType): Carbon
    {
        return match($recurringType) {
            'daily' => $currentDate->copy()->addDay(),
            'weekly' => $currentDate->copy()->addWeek(),
            'biweekly' => $currentDate->copy()->addWeeks(2),
            'monthly' => $currentDate->copy()->addMonth(),
            'bimonthly' => $currentDate->copy()->addMonths(2),
            'quarterly' => $currentDate->copy()->addMonths(3),
            'semiannual' => $currentDate->copy()->addMonths(6),
            'yearly' => $currentDate->copy()->addYear(),
            default => $currentDate->copy()->addMonth(),
        };
    }
    
    /**
     * Gera próxima parcela de uma conta recorrente
     */
    public function generateNextRecurring(Payable $payable): ?Payable
    {
        if (!$payable->is_recurring) {
            return null;
        }
        
        // Verificar se já existe próxima parcela
        $nextDueDate = $this->calculateNextDueDate(
            $payable->due_date,
            $payable->recurring_type
        );
        
        // Verificar se já passou da data de término
        if ($payable->recurring_end_date && $nextDueDate->gt($payable->recurring_end_date)) {
            return null;
        }
        
        // Verificar se já existe conta para esta data
        $existing = Payable::where('recurring_group_id', $payable->recurring_group_id)
            ->where('due_date', $nextDueDate->format('Y-m-d'))
            ->first();
        
        if ($existing) {
            return $existing;
        }
        
        // Criar nova parcela
        return Payable::create([
            'company_id' => $payable->company_id,
            'store_id' => $payable->store_id,
            'supplier_id' => $payable->supplier_id,
            'document_no' => $payable->document_no,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => $nextDueDate->format('Y-m-d'),
            'original_amount' => $payable->original_amount,
            'balance_amount' => $payable->original_amount,
            'status' => 'open',
            'category_id' => $payable->category_id,
            'cost_center_id' => $payable->cost_center_id,
            'note' => $payable->note,
            'is_recurring' => true,
            'recurring_type' => $payable->recurring_type,
            'recurring_end_date' => $payable->recurring_end_date,
            'installments' => $payable->installments,
            'installment_number' => $payable->installment_number + 1,
            'parent_payable_id' => $payable->parent_payable_id ?? $payable->id,
            'recurring_group_id' => $payable->recurring_group_id,
        ]);
    }
    
    /**
     * Processa todas as contas recorrentes que precisam gerar próxima parcela
     */
    public function processRecurringPayables(): int
    {
        $count = 0;
        
        // Buscar contas recorrentes pagas que precisam gerar próxima parcela
        $recurringPayables = Payable::where('is_recurring', true)
            ->where('status', 'paid')
            ->where(function($q) {
                $q->whereNull('recurring_end_date')
                  ->orWhere('recurring_end_date', '>=', now());
            })
            ->get();
        
        foreach ($recurringPayables as $payable) {
            $nextDueDate = $this->calculateNextDueDate(
                $payable->due_date,
                $payable->recurring_type
            );
            
            // Só gerar se a próxima data já passou ou está próxima (próximos 7 dias)
            if ($nextDueDate->lte(now()->addDays(7))) {
                $next = $this->generateNextRecurring($payable);
                if ($next) {
                    $count++;
                    Log::info('Próxima parcela recorrente gerada', [
                        'parent_id' => $payable->id,
                        'new_id' => $next->id,
                        'due_date' => $next->due_date,
                    ]);
                }
            }
        }
        
        return $count;
    }
}

