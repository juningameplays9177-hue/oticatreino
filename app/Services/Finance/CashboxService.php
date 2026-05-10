<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\CashMovement;
use App\Models\Finance\CashSession;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CashboxService
{
    /**
     * Abre uma sessão de caixa
     * 
     * @param \Carbon\Carbon|null $openedAt Data/hora de abertura (null = agora, apenas para admin)
     */
    public function openSession(
        int $companyId,
        int $storeId,
        int $accountId,
        float $openingAmount,
        User $user,
        ?\Carbon\Carbon $openedAt = null
    ): CashSession {
        return DB::transaction(function () use ($companyId, $storeId, $accountId, $openingAmount, $user, $openedAt) {
            // Se não for admin, usar data atual e aplicar validações normais
            $isAdmin = $user->isAdmin();
            $sessionDate = $openedAt ?? now();
            
            if (!$isAdmin) {
                // Para não-admins, sempre usar data atual e fechar caixas anteriores
                $this->closePreviousDaySessions($user->id, $storeId);
                
                // Verificar se já existe sessão aberta para este usuário nesta loja
                $existingSession = CashSession::where('store_id', $storeId)
                    ->where('opened_by', $user->id)
                    ->where('status', 'open')
                    ->first();

                if ($existingSession) {
                    // Verificar se é do mesmo dia
                    $today = now()->startOfDay();
                    $existingSessionDate = $existingSession->opened_at->startOfDay();
                    
                    if ($existingSessionDate->equalTo($today)) {
                        throw new \Exception('Você já possui uma sessão de caixa aberta para esta loja hoje.');
                    } else {
                        // Se for de outro dia, fechar automaticamente
                        $this->autoCloseSession($existingSession);
                    }
                }
            } else {
                // Para admin, permitir abertura retroativa
                // Verificar se já existe sessão aberta para a mesma data
                $sessionDateStart = $sessionDate->copy()->startOfDay();
                $sessionDateEnd = $sessionDate->copy()->endOfDay();
                
                $existingSession = CashSession::where('store_id', $storeId)
                    ->where('opened_by', $user->id)
                    ->where('status', 'open')
                    ->whereBetween('opened_at', [$sessionDateStart, $sessionDateEnd])
                    ->first();

                if ($existingSession) {
                    throw new \Exception('Já existe uma sessão de caixa aberta para esta loja nesta data.');
                }
            }

            // Verificar se a conta é do tipo cash
            $account = Account::findOrFail($accountId);
            if ($account->type !== 'cash') {
                throw new \Exception('A conta deve ser do tipo caixa.');
            }

            $session = CashSession::create([
                'company_id' => $companyId,
                'store_id' => $storeId,
                'account_id' => $accountId,
                'opened_by' => $user->id,
                'opened_at' => $sessionDate,
                'opening_amount' => $openingAmount,
                'status' => 'open',
            ]);

            return $session;
        });
    }
    
    /**
     * Fecha automaticamente sessões de dias anteriores
     * NOTA: Não fecha caixas retroativos abertos por admin
     */
    protected function closePreviousDaySessions(int $userId, int $storeId): void
    {
        $today = now()->startOfDay();
        
        // Buscar usuário para verificar se é admin
        $user = \App\Models\User::find($userId);
        $isAdmin = $user && $user->isAdmin();
        
        // Se for admin, não fechar caixas retroativos automaticamente
        // Admin pode ter caixas retroativos abertos intencionalmente
        if ($isAdmin) {
            return;
        }
        
        $oldSessions = CashSession::where('store_id', $storeId)
            ->where('opened_by', $userId)
            ->where('status', 'open')
            ->whereDate('opened_at', '<', $today)
            ->get();
        
        foreach ($oldSessions as $session) {
            $this->autoCloseSession($session);
        }
    }
    
    /**
     * Fecha automaticamente uma sessão (sem contagem física)
     */
    protected function autoCloseSession(CashSession $session): void
    {
        if ($session->status !== 'open') {
            return;
        }
        
        $expectedBalance = $session->getExpectedBalance();
        
        $session->update([
            'closed_by' => $session->opened_by, // Fechado pelo mesmo usuário que abriu
            'closed_at' => now(),
            'closing_amount' => $expectedBalance, // Usar saldo esperado como fechamento
            'status' => 'closed',
            'notes' => ($session->notes ? $session->notes . ' | ' : '') . 'Fechado automaticamente ao mudar o dia.',
        ]);
    }
    
    /**
     * Busca sessão aberta para um usuário em uma loja
     */
    public function getOpenSession(int $storeId, ?int $userId = null): ?CashSession
    {
        if (!$userId) {
            $userId = auth()->id();
        }
        
        if (!$userId) {
            return null;
        }
        
        // Fechar automaticamente caixas de dias anteriores
        $this->closePreviousDaySessions($userId, $storeId);
        
        $today = now()->startOfDay();
        
        return CashSession::where('store_id', $storeId)
            ->where('opened_by', $userId)
            ->where('status', 'open')
            ->whereDate('opened_at', $today)
            ->first();
    }

    /**
     * Fecha uma sessão de caixa
     */
    public function closeSession(
        CashSession $session,
        array $countedByMethod,
        ?string $notes = null
    ): CashSession {
        return DB::transaction(function () use ($session, $countedByMethod, $notes) {
            if ($session->status !== 'open') {
                throw new \Exception('A sessão não está aberta.');
            }

            $totalCounted = array_sum(array_column($countedByMethod, 'amount'));
            $expectedBalance = $session->getExpectedBalance();
            $difference = $totalCounted - $expectedBalance;

            $session->update([
                'closed_by' => auth()->id(),
                'closed_at' => now(),
                'closing_amount' => $totalCounted,
                'status' => 'closed',
                'notes' => $notes,
            ]);

            // Se houver diferença, criar transaction de ajuste
            if (abs($difference) > 0.01) {
                $this->createDifferenceTransaction($session, $difference);
            }

            return $session->fresh();
        });
    }

    /**
     * Cria transaction de diferença (quebra de caixa)
     */
    protected function createDifferenceTransaction(CashSession $session, float $difference): void
    {
        // Buscar categoria "Quebra de Caixa" ou criar
        $category = FinanceCategory::where('company_id', $session->company_id)
            ->where('name', 'Quebra de Caixa')
            ->where('nature', 'expense')
            ->first();

        if (!$category) {
            $category = FinanceCategory::create([
                'company_id' => $session->company_id,
                'name' => 'Quebra de Caixa',
                'nature' => 'expense',
                'is_system' => true,
            ]);
        }

        $description = $difference > 0 
            ? "Sobra de caixa - Sessão #{$session->id}"
            : "Falta de caixa - Sessão #{$session->id}";

        // Buscar conta de despesa (simplificado - usar a própria categoria como conta)
        // Em produção, criar conta específica para quebra de caixa
        $expenseAccount = Account::where('company_id', $session->company_id)
            ->where('name', 'like', '%Despesas%')
            ->first();

        if (!$expenseAccount) {
            $expenseAccount = Account::create([
                'company_id' => $session->company_id,
                'name' => 'Despesas Diversas',
                'type' => 'bank',
                'is_active' => true,
            ]);
        }

        Transaction::create([
            'company_id' => $session->company_id,
            'store_id' => $session->store_id,
            'txn_date' => now(),
            'description' => $description,
            'amount' => abs($difference),
            'dr_account_id' => $difference > 0 ? $session->account_id : $expenseAccount->id,
            'cr_account_id' => $difference > 0 ? $expenseAccount->id : $session->account_id,
            'category_id' => $category->id,
            'link_type' => 'cash_session',
            'link_id' => $session->id,
        ]);
    }

    /**
     * Registra um movimento de caixa
     */
    public function recordMovement(
        CashSession $session,
        string $type,
        string $method,
        float $amount,
        ?int $categoryId = null,
        ?string $originType = null,
        ?int $originId = null,
        ?string $note = null
    ): CashMovement {
        return DB::transaction(function () use ($session, $type, $method, $amount, $categoryId, $originType, $originId, $note) {
            // Log para debug
            \Log::info('recordMovement chamado', [
                'cash_session_id' => $session->id,
                'session_status' => $session->status,
                'type' => $type,
                'method' => $method,
                'amount' => $amount,
                'origin_type' => $originType,
                'origin_id' => $originId,
            ]);
            
            if ($session->status !== 'open') {
                \Log::error('Tentativa de criar movimento em sessão fechada', [
                    'cash_session_id' => $session->id,
                    'status' => $session->status
                ]);
                throw new \Exception('A sessão não está aberta.');
            }

            // Validar dados antes de criar
            if ($amount <= 0) {
                \Log::warning('Tentativa de criar movimento com valor zero ou negativo', [
                    'amount' => $amount,
                    'cash_session_id' => $session->id
                ]);
                throw new \Exception('O valor do movimento deve ser maior que zero.');
            }

            if (!$session->id) {
                \Log::error('Sessão de caixa sem ID', [
                    'session' => $session->toArray()
                ]);
                throw new \Exception('Sessão de caixa inválida (sem ID).');
            }

            // Normalizar método para o formato aceito pelo ENUM da tabela
            // A tabela aceita apenas: 'money','pix','card','boleto','other'
            $normalizedMethod = match($method) {
                'money' => 'money',
                'pix' => 'pix',
                'card_credit', 'card_debit', 'card' => 'card',
                'boleto', 'boleto_installment' => 'boleto',
                'sinal' => 'money', // Sinal sempre entra como money no caixa físico
                default => 'other'
            };
            
            if ($normalizedMethod !== $method) {
                \Log::info('Método normalizado para ENUM', [
                    'original_method' => $method,
                    'normalized_method' => $normalizedMethod
                ]);
            }

            try {
                $movement = CashMovement::create([
                    'cash_session_id' => $session->id,
                    'type' => $type,
                    'method' => $normalizedMethod, // Usar método normalizado
                    'amount' => $amount,
                    'category_id' => $categoryId,
                    'origin_type' => $originType,
                    'origin_id' => $originId,
                    'note' => $note,
                ]);
                
                \Log::info('Movimento de caixa criado no recordMovement', [
                    'movement_id' => $movement->id,
                    'cash_session_id' => $session->id,
                    'type' => $type,
                    'method' => $method,
                    'amount' => $amount
                ]);
                
                return $movement;
            } catch (\Exception $e) {
                \Log::error('Erro ao criar CashMovement', [
                    'error' => $e->getMessage(),
                    'cash_session_id' => $session->id,
                    'type' => $type,
                    'method' => $method,
                    'amount' => $amount,
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        });
    }
}

