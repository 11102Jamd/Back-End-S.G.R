<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Support\Facades\DB;

class RecipeService
{
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
                    'input_id'=>$ingredient['input_id'],
                    'quantity_required'=>$ingredient['quantity_required'],
                ]);
            }
            return $recipe;
        });
    }
}
