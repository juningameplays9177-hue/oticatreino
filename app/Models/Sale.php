<?php

namespace App\Models;

use App\Models\Finance\Account;
use App\Models\ServiceOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'store_id',
        'customer_id',
        'service_order_id',
        'sale_number',
        'sale_date',
        'total_gross',
        'total_discount',
        'total_net',
        'total_cost',
        'payment_summary',
        'status',
        'account_id',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'total_gross' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'total_net' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'payment_summary' => 'array',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'customer_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function receivables(): HasMany
    {
        return $this->hasMany(\App\Models\Finance\Receivable::class);
    }

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ServiceOrder::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('sale_date', [$from, $to]);
    }

    /**
     * Boot method para definir created_at usando data de trabalho
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            // Definir created_at usando a data de trabalho se disponível
            if (!$sale->created_at) {
                try {
                    $workDate = \App\Helpers\WorkDateHelper::getWorkDate();
                    $sale->created_at = $workDate;
                } catch (\Exception $e) {
                    // Se houver erro, deixar o Laravel definir automaticamente
                }
            }
        });
    }
}

