<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends Model
{
    protected $fillable = [
        'merchant_id',
        'name',
        'description',
        'slug',
        'is_active',
        'products_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'products_count' => 'integer',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'collection_product')
            ->withPivot(['position', 'added_at'])
            ->orderByPivot('position');
    }
}
