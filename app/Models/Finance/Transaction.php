<?php

namespace App\Models\Finance;

use App\Models\Company;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'company_id',
        'store_id',
        'txn_date',
        'description',
        'amount',
        'dr_account_id',
        'cr_account_id',
        'link_type',
        'link_id',
        'category_id',
        'cost_center_id',
        'tags',
    ];

    protected $casts = [
        'txn_date' => 'datetime',
        'amount' => 'decimal:2',
        'tags' => 'array',
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

    public function drAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'dr_account_id');
    }

    public function crAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cr_account_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function link(): MorphTo
    {
        return $this->morphTo('link');
    }

    // Scopes
    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('txn_date', [$from, $to]);
    }

    public function scopeByAccount($query, $accountId)
    {
        return $query->where(function ($q) use ($accountId) {
            $q->where('dr_account_id', $accountId)
              ->orWhere('cr_account_id', $accountId);
        });
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Boot method para definir created_at usando data de trabalho
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            // Definir created_at usando a data de trabalho se disponível
            if (!$transaction->created_at) {
                try {
                    $workDate = \App\Helpers\WorkDateHelper::getWorkDate();
                    $transaction->created_at = $workDate;
                } catch (\Exception $e) {
                    // Se houver erro, deixar o Laravel definir automaticamente
                }
            }
        });
    }
}

