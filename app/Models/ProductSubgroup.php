<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSubgroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'name',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ProductGroup::class, 'group_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'subgroup_id');
    }
}

