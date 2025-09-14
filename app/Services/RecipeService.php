<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Support\Facades\DB;
/**
 * Servicio encargado de manejar la lógica de negocio relacionada con recetas.
 *
 * Este servicio permite:
 * - Crear nuevas recetas con sus ingredientes asociados.
 * - Actualizar recetas existentes y sus ingredientes.
 * - Eliminar recetas y restaurar la consistencia de los datos eliminando sus ingredientes.
 *
 */
class RecipeService
{
    /**
     * Crea una nueva receta junto con sus ingredientes asociados.
     *
     *-Inicia una transacción de base de datos.
     *-Crea el registro de la receta principal.
     *-Itera sobre los ingredientes recibidos y los asocia a la receta creada.
     *-Devuelve la receta creada.
     *
     * @param array $data Datos de la receta y sus ingredientes
     * @return Recipe Receta creada con los ingredientes asociados
     */
    public function createRecipe(array $data)
    {
        return DB::transaction(function () use ($data) {
            $recipe = Recipe::create([
                'recipe_name' => $data['recipe_name'],
                'yield_quantity' => $data['yield_quantity'],
                'unit' => $data['unit']
            ]);
            foreach ($data['ingredient'] as $ingredient) {
                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'input_id' => $ingredient['input_id'],
                    'quantity_required' => $ingredient['quantity_required'],
                ]);
            }
            return $recipe;
        });
    }

    /**
     * Actualiza una receta existente junto con sus ingredientes.
     *
     *-Inicia una transacción de base de datos.
     *-Busca la receta por su ID; lanza error si no existe.
     *-Actualiza los campos principales de la receta.
     *-Elimina los ingredientes antiguos relacionados con la receta.
     *-Crea los nuevos ingredientes proporcionados en la solicitud.
     *-Devuelve la receta actualizada con los ingredientes cargados.
     *
     * @param int $id ID de la receta a actualizar
     * @param array $data Nuevos datos de la receta y sus ingredientes
     * @return Recipe Receta actualizada con sus ingredientes
     */
    public function updateRecipe(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $recipe = Recipe::findOrFail($id);

            $recipe->update([
                'recipe_name'    => $data['recipe_name'],
                'yield_quantity' => $data['yield_quantity'],
                'unit'           => $data['unit']
            ]);

            $recipe->recipeIngredients()->delete();

             // Crear nuevos ingredientes
            foreach ($data['ingredient'] as $ingredient) {
                RecipeIngredient::create([
                    'recipe_id'         => $recipe->id,
                    'input_id'          => $ingredient['input_id'],
                    'quantity_required' => $ingredient['quantity_required'],
                ]);
            }

            return $recipe->load('recipeIngredients');
        });
    }

    /**
     * Elimina una receta y todos sus ingredientes asociados.
     *
     *-Inicia una transacción de base de datos.
     *-Busca la receta por su ID; lanza error si no existe.
     *-Elimina todos los ingredientes asociados a la receta.
     *-Elimina la receta principal.
     *-Devuelve la receta eliminada.
     *
     * @param int $id ID de la receta a eliminar
     * @return Recipe Receta eliminada
     */
    public function deleteRecipe(int $id)
    {
        return DB::transaction(function () use ($id) {
            $recipe = Recipe::findOrFail($id);
            // Eliminar ingredientes relacionados
            $recipe->recipeIngredients()->delete();
            // Eliminar receta
            $recipe->delete();
            return $recipe;
        });
    }
}
