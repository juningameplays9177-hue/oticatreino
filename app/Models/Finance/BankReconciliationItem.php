<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'reconciliation_id',
        'transaction_id',
        'statement_amount',
        'matched',
    ];

    protected $casts = [
        'statement_amount' => 'decimal:2',
        'matched' => 'boolean',
    ];

    // Relationships
    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'reconciliation_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    // Scopes
    public function scopeMatched($query)
    {
        return $query->where('matched', true);
    }

    public function scopeUnmatched($query)
    {
        return $query->where('matched', false);
    }
}

