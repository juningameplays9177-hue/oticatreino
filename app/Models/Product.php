<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'ref',
        'ean13',
        'name',
        'unit',
        'brand_id',
        'group_id',
        'subgroup_id',
        'supplier_id',
        'product_type_id',
        'color',
        'size',
        'shape',
        'ncm',
        'item_type',
        'model',
        'sell_only_with_os',
        'control_stock',
        'showcase_enabled',
        'archived',
        'description',
        'notes',
        'label_code',
    ];

    protected $casts = [
        'sell_only_with_os' => 'boolean',
        'control_stock' => 'boolean',
        'showcase_enabled' => 'boolean',
        'archived' => 'boolean',
    ];

    // Relationships
    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ProductGroup::class, 'group_id');
    }

    public function subgroup(): BelongsTo
    {
        return $this->belongsTo(ProductSubgroup::class, 'subgroup_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    // Scopes
    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        $searchTerm = '%' . $search . '%';

        return $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'like', $searchTerm)
                ->orWhere('ref', 'like', $searchTerm)
                ->orWhere('ean13', 'like', $searchTerm)
                ->orWhereHas('brand', function ($brandQuery) use ($searchTerm) {
                    $brandQuery->where('name', 'like', $searchTerm);
                })
                ->orWhereHas('group', function ($groupQuery) use ($searchTerm) {
                    $groupQuery->where('name', 'like', $searchTerm);
                })
                ->orWhereHas('supplier', function ($supplierQuery) use ($searchTerm) {
                    $supplierQuery->where('trade_name', 'like', $searchTerm)
                        ->orWhere('legal_name', 'like', $searchTerm);
                });
        });
    }

    public function scopeFilters($query, $filters)
    {
        if (!empty($filters['group_id'])) {
            $query->where('group_id', $filters['group_id']);
        }

        if (!empty($filters['subgroup_id'])) {
            $query->where('subgroup_id', $filters['subgroup_id']);
        }

        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['archived_mode'])) {
            switch ($filters['archived_mode']) {
                case 'nao_arquivados':
                    $query->where('archived', false);
                    break;
                case 'nao_arquivados_com_fotos':
                    $query->where('archived', false)
                        ->whereHas('images');
                    break;
                case 'vitrine':
                    $query->where('showcase_enabled', true)
                        ->where('archived', false);
                    break;
                case 'arquivados':
                    $query->where('archived', true);
                    break;
                case 'todos':
                    // Sem filtro
                    break;
            }
        }

        if (!empty($filters['from']) && !empty($filters['to'])) {
            $query->whereBetween('created_at', [$filters['from'], $filters['to']]);
        }

        return $query;
    }

    // Helper methods
    public static function generateRef($productTypeId = null)
    {
        // Se não tiver tipo, usar P como padrão
        $prefix = 'P';
        
        if ($productTypeId) {
            $productType = ProductType::find($productTypeId);
            if ($productType && $productType->code_prefix) {
                $prefix = $productType->code_prefix;
            }
        }
        
        // Buscar todos os produtos com o mesmo prefixo (suporta 3 e 4 dígitos)
        $existingRefs = self::where('ref', 'like', $prefix . '%')
            ->where(function ($query) use ($prefix) {
                $query->whereRaw('LENGTH(ref) = ?', [strlen($prefix) + 3]) // L001, P001
                      ->orWhereRaw('LENGTH(ref) = ?', [strlen($prefix) + 4]); // L1000, P1000
            })
            ->pluck('ref')
            ->map(function ($ref) use ($prefix) {
                $number = substr($ref, strlen($prefix));
                return (int) $number;
            })
            ->filter()
            ->sort()
            ->values();
        
        // Se não houver produtos existentes, começar do 1
        if ($existingRefs->isEmpty()) {
            $newNumber = 1;
        } else {
            // Encontrar o maior número e incrementar
            $maxNumber = $existingRefs->max();
            $newNumber = $maxNumber + 1;
        }
        
        // Gerar referência com 4 dígitos (L0001, L0002, etc)
        $newRef = $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        
        // Verificação final de segurança - garantir que seja único
        $attempts = 0;
        while (self::where('ref', $newRef)->exists() && $attempts < 1000) {
            $newNumber++;
            $newRef = $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
            $attempts++;
        }
        
        // Se ainda não for único após 1000 tentativas, usar timestamp como fallback
        if (self::where('ref', $newRef)->exists()) {
            $timestamp = time();
            $newRef = $prefix . substr($timestamp, -6); // Últimos 6 dígitos do timestamp
        }
        
        return $newRef;
    }

    public static function generateLabelCode()
    {
        // Gerar bigint único baseado em timestamp + random
        do {
            $code = (int)(time() . rand(1000, 9999));
        } while (self::where('label_code', $code)->exists());

        return $code;
    }

    public static function validateEan13($ean13)
    {
        if (strlen($ean13) !== 13 || !ctype_digit($ean13)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$ean13[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        $checkDigit = (10 - ($sum % 10)) % 10;

        return $checkDigit === (int)$ean13[12];
    }
}

