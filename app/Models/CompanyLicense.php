<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyLicense extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'license_status', 'cert_valid_from', 'cert_valid_to',
        'cert_status', 'last_check_at'
    ];

    protected $casts = [
        'cert_valid_from' => 'datetime',
        'cert_valid_to' => 'datetime',
        'last_check_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

