<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceOrderPrescription extends Model
{
    use HasFactory;

    protected $table = 'service_order_prescription';

    protected $fillable = [
        'service_order_id',
        'prescription_id',
        'use_custom',
        'custom_doctor_name',
        'custom_valid_until',
        'custom_attachment_path',
        'custom_longe_esferico_od', 'custom_longe_cilindrico_od', 'custom_longe_eixo_od', 'custom_longe_altura_od', 'custom_longe_dnp_od',
        'custom_longe_esferico_oe', 'custom_longe_cilindrico_oe', 'custom_longe_eixo_oe', 'custom_longe_altura_oe', 'custom_longe_dnp_oe',
        'custom_perto_esferico_od', 'custom_perto_cilindrico_od', 'custom_perto_eixo_od', 'custom_perto_altura_od', 'custom_perto_dnp_od',
        'custom_perto_esferico_oe', 'custom_perto_cilindrico_oe', 'custom_perto_eixo_oe', 'custom_perto_altura_oe', 'custom_perto_dnp_oe',
        'custom_adicao',
        'custom_notes',
    ];

    protected $casts = [
        'use_custom' => 'boolean',
        'custom_valid_until' => 'date',
    ];

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }
}

