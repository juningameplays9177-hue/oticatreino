<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'abbreviation',
        'active',
        'company_id',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function counters(): HasMany
    {
        return $this->hasMany(StoreCounter::class);
    }

    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
    }

    /**
     * Gera código e sigla baseado no slug da empresa
     */
    public static function generateCodeAndAbbreviation($companySlug, $companyId = null): array
    {
        // Gerar código baseado no slug (máximo 25 caracteres)
        $baseCode = !empty($companySlug) 
            ? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $companySlug), 0, 25))
            : 'LOJA' . ($companyId ?? time());
        
        if (empty($baseCode)) {
            $baseCode = 'LOJA' . ($companyId ?? time());
        }
        
        // Gerar sigla (máximo 10 caracteres, preferencialmente 3-5)
        // Pegar primeiras letras de cada palavra do slug
        $words = preg_split('/[\s\-_]+/', $companySlug);
        $abbreviation = '';
        
        if (count($words) > 1) {
            // Se tiver múltiplas palavras, pegar primeira letra de cada
            foreach ($words as $word) {
                if (!empty($word)) {
                    $abbreviation .= strtoupper(substr($word, 0, 1));
                    if (strlen($abbreviation) >= 5) break; // Limitar a 5 caracteres
                }
            }
        } else {
            // Se for uma palavra só, pegar primeiras 3-5 letras
            $abbreviation = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $companySlug), 0, 5));
        }
        
        // Garantir que sigla tenha pelo menos 2 caracteres
        if (strlen($abbreviation) < 2) {
            $abbreviation = strtoupper(substr($baseCode, 0, 3));
        }
        
        // Limitar sigla a 10 caracteres
        $abbreviation = substr($abbreviation, 0, 10);
        
        // Garantir código único
        $code = $baseCode;
        $counter = 1;
        while (self::where('code', $code)->exists()) {
            $code = $baseCode . $counter;
            $counter++;
            if ($counter > 100) break; // Proteção contra loop infinito
        }
        
        return [
            'code' => $code,
            'abbreviation' => $abbreviation
        ];
    }

    /**
     * Retorna o nome formatado com sigla na frente
     */
    public function getDisplayNameAttribute(): string
    {
        if (!empty($this->abbreviation)) {
            return "[{$this->abbreviation}] {$this->name}";
        }
        return $this->name;
    }
}

