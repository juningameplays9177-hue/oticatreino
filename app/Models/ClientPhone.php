<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPhone extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'phone',
        'label',
    ];

    /**
     * Relacionamento com cliente
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Normalizar telefone (remover caracteres não numéricos)
     */
    public static function normalizePhone($value)
    {
        if (empty($value)) {
            return null;
        }
        return preg_replace('/\D/', '', $value);
    }
}
