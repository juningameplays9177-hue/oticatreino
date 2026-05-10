<?php

namespace App\Models\Finance;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceCategory extends Model
{
    use HasFactory;

    protected $table = 'finance_categories';

    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
        'nature',
        'is_system',
        'is_active',
        'cost_center_id',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(FinanceCategory::class, 'parent_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByNature($query, $nature)
    {
        return $query->where('nature', $nature);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
}

