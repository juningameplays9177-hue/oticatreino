<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreCounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'year',
        'current_os',
        'current_co',
    ];

    protected $casts = [
        'year' => 'integer',
        'current_os' => 'integer',
        'current_co' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}

