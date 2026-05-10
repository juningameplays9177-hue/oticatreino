<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'email',
        'label',
    ];

    /**
     * Relacionamento com cliente
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
