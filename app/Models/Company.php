<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'tax_id_type', 'cpf_clean', 'cnpj_clean', 'legal_name', 'trade_name', 'slug',
        'phone', 'mobile', 'contact_name', 'email', 'zip_code', 'address', 'number',
        'complement', 'district', 'city', 'state', 'logo_path', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(CompanyLicense::class);
    }
    
    public function license(): HasOne
    {
        return $this->hasOne(CompanyLicense::class)->latestOfMany();
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function mobileIntegrations(): HasMany
    {
        return $this->hasMany(MobileIntegration::class);
    }

    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
    }
}

