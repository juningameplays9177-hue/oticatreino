<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'store_id', 'name', 'role_func_id', 'rg', 'cpf_clean',
        'phone', 'mobile', 'zip_code', 'address', 'district', 'city', 'state',
        'is_active', 'notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function roleFunction(): BelongsTo
    {
        return $this->belongsTo(JobFunction::class, 'role_func_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}

