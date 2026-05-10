<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_id_type', 'cpf_clean', 'cnpj_clean', 'trade_name', 'legal_name',
        'is_lab', 'taxpayer_icms', 'ie', 'im', 'suframa', 'email', 'website',
        'zip_code', 'address', 'number', 'complement', 'district', 'city', 'state',
        'is_active', 'notes'
    ];

    protected $casts = [
        'is_lab' => 'boolean',
        'taxpayer_icms' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }

    public function representatives(): HasMany
    {
        return $this->hasMany(SupplierRepresentative::class);
    }
}
