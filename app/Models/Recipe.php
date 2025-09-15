<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Recipe
 *
 * Representa una receta en el sistema.
 * Contiene información de nombre, cantidad de rendimiento y unidad,
 * y mantiene relaciones con ingredientes y producciones asociadas.
 */
class Recipe extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'recipe';

    protected $fillable = [
        'recipe_name',
        'yield_quantity',
    ];

    protected $dates = ['deleted_at'];

    /**
     * Relación: Obtiene los ingredientes asociados a la receta.
     *
     * @return HasMany
     */
    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class, 'recipe_id');
    }

    /**
     * Relación: Obtiene todas las producciones que se han hecho con esta receta.
     *
     * @return HasMany
     */
    public function productions(): HasMany
    {
        return $this->hasMany(Production::class, 'recipe_id');
    }
}
