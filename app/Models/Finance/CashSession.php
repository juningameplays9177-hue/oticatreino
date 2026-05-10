<?php

namespace App\Models\Finance;

use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use App\Models\Finance\Account;
use App\Models\Finance\CashMovement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cash_sessions';

    protected $fillable = [
        'company_id',
        'store_id',
        'account_id',
        'opened_by',
        'closed_by',
        'opened_at',
        'closed_at',
        'opening_amount',
        'closing_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_amount' => 'decimal:2',
        'closing_amount' => 'decimal:2',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('opened_by', $userId);
    }

    // Helper methods
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function getTotalMovementsIn(): float
    {
        return $this->movements()->where('type', 'in')->sum('amount');
    }

    public function getTotalMovementsOut(): float
    {
        return $this->movements()->where('type', 'out')->sum('amount');
    }

    public function getExpectedBalance(): float
    {
        return $this->opening_amount + $this->getTotalMovementsIn() - $this->getTotalMovementsOut();
    }

    public function getDifference(): ?float
    {
        if (!$this->closing_amount) {
            return null;
        }
        return $this->closing_amount - $this->getExpectedBalance();
    }

    /**
     * Boot method para definir created_at usando data de trabalho
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cashSession) {
            // Definir created_at usando a data de trabalho se disponível
            if (!$cashSession->created_at) {
                try {
                    $workDate = \App\Helpers\WorkDateHelper::getWorkDate();
                    $cashSession->created_at = $workDate;
                } catch (\Exception $e) {
                    // Se houver erro, deixar o Laravel definir automaticamente
                }
            }
        });
    }
}

