<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'doctor_name',
        'valid_until',
        'attachment_path',
        'longe_esferico_od', 'longe_cilindrico_od', 'longe_eixo_od', 'longe_altura_od', 'longe_dnp_od',
        'longe_esferico_oe', 'longe_cilindrico_oe', 'longe_eixo_oe', 'longe_altura_oe', 'longe_dnp_oe',
        'perto_esferico_od', 'perto_cilindrico_od', 'perto_eixo_od', 'perto_altura_od', 'perto_dnp_od',
        'perto_esferico_oe', 'perto_cilindrico_oe', 'perto_eixo_oe', 'perto_altura_oe', 'perto_dnp_oe',
        'adicao',
        'notes',
    ];

    protected $casts = [
        'valid_until' => 'date',
        'longe_esferico_od' => 'decimal:2',
        'longe_cilindrico_od' => 'decimal:2',
        'longe_altura_od' => 'decimal:2',
        'longe_dnp_od' => 'decimal:2',
        'longe_esferico_oe' => 'decimal:2',
        'longe_cilindrico_oe' => 'decimal:2',
        'longe_altura_oe' => 'decimal:2',
        'longe_dnp_oe' => 'decimal:2',
        'perto_esferico_od' => 'decimal:2',
        'perto_cilindrico_od' => 'decimal:2',
        'perto_altura_od' => 'decimal:2',
        'perto_dnp_od' => 'decimal:2',
        'perto_esferico_oe' => 'decimal:2',
        'perto_cilindrico_oe' => 'decimal:2',
        'perto_altura_oe' => 'decimal:2',
        'perto_dnp_oe' => 'decimal:2',
        'adicao' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrderPrescription::class);
    }
}

