<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceOrderImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_order_id',
        'path',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }
}

