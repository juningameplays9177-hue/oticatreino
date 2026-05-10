<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_order_id',
        'product_id',
        'type',
        'ref',
        'name',
        'unit',
        'qty',
        'unit_price',
        'price_adjust',
        'unit_price_net',
        'add_disc_percent',
        'line_total',
        'barcode',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'price_adjust' => 'decimal:2',
        'unit_price_net' => 'decimal:2',
        'add_disc_percent' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

