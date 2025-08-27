<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Recipe;
use App\Models\ProductionConsumption;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Production extends Model
{
    protected $table = 'production';

    protected $fillable = [
        'recipe_id',
        'quantity_to_produce',
        'price_for_product',
        'total_cost',
        'production_date',
    ];

    // Relaciones
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class, 'recipe_id');
    }

    public function productionConsumptions(): HasMany
    {
        return $this->hasMany(ProductionConsumption::class, 'production_id');
    }
}
