<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientRef extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'ref_name',
        'ref_phone',
    ];

    /**
     * Relacionamento com cliente
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
