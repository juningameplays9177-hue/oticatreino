<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'plan_code', 'contract_type', 'activation_fee_total',
        'monthly_fee', 'started_at', 'ends_at', 'status', 'notes'
    ];

    protected $casts = [
        'activation_fee_total' => 'decimal:2',
        'monthly_fee' => 'decimal:2',
        'started_at' => 'date',
        'ends_at' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

