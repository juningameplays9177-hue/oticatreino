<?php

namespace App\Models\Finance;

use App\Models\Client;
use App\Models\Company;
use App\Models\ServiceOrder;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receivable extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'receivables';

    protected $fillable = [
        'company_id',
        'store_id',
        'customer_id',
        'sale_id',
        'os_id',
        'issue_date',
        'due_date',
        'original_amount',
        'balance_amount',
        'interest_amount',
        'fine_amount',
        'discount_amount',
        'status',
        'method',
        'billing_type',
        'gateway_id',
        'our_number',
        'document_no',
        'category_id',
        'cost_center_id',
        'installments',
        'installment_number',
        'parent_receivable_id',
        'renegotiated_from_id',
        'note',
        'attachment_path',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'original_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'fine_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'customer_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Sale::class, 'sale_id');
    }

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class, 'os_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ReceivablePayment::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'category_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function parentReceivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class, 'parent_receivable_id');
    }

    public function childReceivables(): HasMany
    {
        return $this->hasMany(Receivable::class, 'parent_receivable_id');
    }

    public function renegotiatedFrom(): BelongsTo
    {
        return $this->belongsTo(Receivable::class, 'renegotiated_from_id');
    }

    public function renegotiatedTo(): HasMany
    {
        return $this->hasMany(Receivable::class, 'renegotiated_from_id');
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

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('due_date', [$from, $to]);
    }

    public function scopeByBillingType($query, $type)
    {
        return $query->where('billing_type', $type);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeRenegotiated($query)
    {
        return $query->whereNotNull('renegotiated_from_id');
    }

    public function scopeNotRenegotiated($query)
    {
        return $query->whereNull('renegotiated_from_id');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
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

    public function getDaysOverdue(): ?int
    {
        if (!$this->isOverdue()) {
            return null;
        }
        return now()->diffInDays($this->due_date);
    }

    public function getTotalAmount(): float
    {
        return $this->original_amount + ($this->interest_amount ?? 0) + ($this->fine_amount ?? 0) - ($this->discount_amount ?? 0);
    }

    public function isRenegotiated(): bool
    {
        return !is_null($this->renegotiated_from_id);
    }

    public function hasInstallments(): bool
    {
        return ($this->installments ?? 1) > 1;
    }

    public function getAgingDays(): int
    {
        if ($this->isPaid()) {
            return 0;
        }
        
        $days = now()->diffInDays($this->due_date);
        
        if ($this->due_date < now()) {
            return -$days; // Negativo = atrasado
        }
        
        return $days; // Positivo = a vencer
    }

    public function getAgingCategory(): string
    {
        $days = $this->getAgingDays();
        
        if ($days < 0) {
            $daysOverdue = abs($days);
            if ($daysOverdue <= 30) {
                return '0-30';
            } elseif ($daysOverdue <= 60) {
                return '31-60';
            } elseif ($daysOverdue <= 90) {
                return '61-90';
            } else {
                return '90+';
            }
        }
        
        return 'a_vencer';
    }

    /**
     * Boot method para definir created_at usando data de trabalho
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($receivable) {
            // Definir created_at usando a data de trabalho se disponível
            if (!$receivable->created_at) {
                try {
                    $workDate = \App\Helpers\WorkDateHelper::getWorkDate();
                    $receivable->created_at = $workDate;
                } catch (\Exception $e) {
                    // Se houver erro, deixar o Laravel definir automaticamente
                }
            }
        });
    }
}

