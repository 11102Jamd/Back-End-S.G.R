<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
/**
 * Modelo RecipeIngredient
 *
 * Representa la relación entre una receta y sus ingredientes.
 * Contiene información sobre el insumo utilizado y la cantidad requerida.
 */
class RecipeIngredient extends Model
{
    use HasFactory;

    protected $table = 'recipe_ingredients';

    protected $fillable = [
        'recipe_id',
        'input_id',
        'quantity_required',
        'unit_used'
    ];

    /**
     * Relación: Obtiene la receta a la que pertenece este ingrediente.
     *
     * @return BelongsTo
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class, 'recipe_id');
    }

    /**
     * Relación: Obtiene el insumo asociado a este ingrediente.
     *
     * @return BelongsTo
     */
    public function input(): BelongsTo
    {
        return $this->belongsTo(Input::class, 'input_id');
    }
}
