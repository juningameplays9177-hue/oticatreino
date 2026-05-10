<?php

namespace App\Models\Finance;

use App\Models\Company;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'accounts';

    protected $fillable = [
        'company_id',
        'store_id',
        'name',
        'type',
        'bank_name',
        'agency',
        'number',
        'pix_key',
        'opening_balance',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean',
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

    public function debitTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'dr_account_id');
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'cr_account_id');
    }

    public function cashSessions(): HasMany
    {
        return $this->hasMany(CashSession::class);
    }

    public function receivablePayments(): HasMany
    {
        return $this->hasMany(ReceivablePayment::class);
    }

    public function payablePayments(): HasMany
    {
        return $this->hasMany(PayablePayment::class);
    }

    public function salePayments(): HasMany
    {
        return $this->hasMany(\App\Models\SalePayment::class, 'account_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }
}

