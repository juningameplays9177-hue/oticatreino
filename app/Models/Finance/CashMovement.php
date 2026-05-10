<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CashMovement extends Model
{
    use HasFactory;

    protected $table = 'cash_movements';

    protected $fillable = [
        'cash_session_id',
        'type',
        'method',
        'amount',
        'category_id',
        'origin_type',
        'origin_id',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class);
    }

    public function origin(): MorphTo
    {
        return $this->morphTo('origin');
    }

    // Scopes
    public function scopeIn($query)
    {
        return $query->where('type', 'in');
    }

    public function scopeOut($query)
    {
        return $query->where('type', 'out');
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('method', $method);
    }

    /**
     * Boot method para definir created_at usando data de trabalho
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cashMovement) {
            // Definir created_at usando a data de trabalho se disponível
            if (!$cashMovement->created_at) {
                try {
                    $workDate = \App\Helpers\WorkDateHelper::getWorkDate();
                    $cashMovement->created_at = $workDate;
                } catch (\Exception $e) {
                    // Se houver erro, deixar o Laravel definir automaticamente
                }
            }
        });
    }
}

