<?php

namespace App\Models\Finance;

use App\Models\Company;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payable extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'payables';

    protected $fillable = [
        'company_id',
        'created_by',
        'store_id',
        'supplier_id',
        'document_no',
        'issue_date',
        'due_date',
        'original_amount',
        'balance_amount',
        'status',
        'category_id',
        'cost_center_id',
        'note',
        'attachment_path',
        'is_recurring',
        'recurring_type',
        'recurring_end_date',
        'installments',
        'installment_number',
        'parent_payable_id',
        'recurring_group_id',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'recurring_end_date' => 'date',
        'original_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'is_recurring' => 'boolean',
        'installments' => 'integer',
        'installment_number' => 'integer',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PayablePayment::class);
    }

    public function parentPayable(): BelongsTo
    {
        return $this->belongsTo(Payable::class, 'parent_payable_id');
    }

    public function childPayables(): HasMany
    {
        return $this->hasMany(Payable::class, 'parent_payable_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function recurringGroupPayables()
    {
        if (!$this->recurring_group_id) {
            return collect([]);
        }
        return Payable::where('recurring_group_id', $this->recurring_group_id)
            ->where('id', '!=', $this->id)
            ->get();
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'partial']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereIn('status', ['open', 'partial']);
    }

    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('due_date', [$from, $to]);
    }

    // Helper methods
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->due_date < now() && !$this->isPaid();
    }

    public function getPaidAmount(): float
    {
        return $this->original_amount - $this->balance_amount;
    }

    /**
     * Boot method para definir created_at usando data de trabalho
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payable) {
            // Definir created_at usando a data de trabalho se disponível
            if (!$payable->created_at) {
                try {
                    $workDate = \App\Helpers\WorkDateHelper::getWorkDate();
                    $payable->created_at = $workDate;
                } catch (\Exception $e) {
                    // Se houver erro, deixar o Laravel definir automaticamente
                }
            }
        });
    }
}

