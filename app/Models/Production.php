<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Console\Input\Input;

class Production extends Model
{
    use HasFactory;

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

    public function inputs()
    {
        return $this->hasManyThrough(
            Input::class,
            ProductionConsumption::class,
            'production_id',
            'id',
            'id',
            'input_id'
        );
    }

    // public function productProductions(): HasMany
    // {
    //     return $this->hasMany(ProductProduction::class, 'production_id');
    // }
}
