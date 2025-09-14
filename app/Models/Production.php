<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Recipe;
use App\Models\ProductionConsumption;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ProductProduction;
/**
 * Modelo Production
 *
 * Representa un registro de producción en el sistema.
 * Contiene información sobre la receta utilizada, la cantidad a producir,
 * costos y relaciones con consumos de insumos.
 */
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

    /**
     * Relación: Obtiene la receta asociada a la producción.
     *
     * @return BelongsTo
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class, 'recipe_id');
    }

    /**
     * Relación: Obtiene los consumos de insumos asociados a la producción.
     *
     * @return HasMany
     */
    public function productionConsumptions(): HasMany
    {
        return $this->hasMany(ProductionConsumption::class, 'production_id');
    }
}
