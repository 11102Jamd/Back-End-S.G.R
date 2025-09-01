<?php

namespace App\Http\Controllers;

use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\Recipe;
use App\Services\RecipeService;
use Illuminate\Http\Request;

class RecipeController extends BaseCrudController
{
    protected $model = Recipe::class;

    protected $recipeService;

    protected $validationRules = [
        'recipe_name' => 'required|string|max:50',
        'yield_quantity' => 'required|integer|max:100',
        'unit' => 'required|string|max:10',
        'ingredient' => 'required|array|min:1',
        'ingredient.*.input_id' => 'required|exists:input,id',
        'ingredient.*.quantity_required' => 'required|numeric|min:0.001'
    ];

    public function __construct(RecipeService $recipeService)
    {
        $this->recipeService = $recipeService;
    }

    public function show($id)
    {
        try {
            //devolver un registro por id
            $record = $this->model::with(['recipeIngredients'])->findOrFail($id);
            return response()->json($record);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'error' => 'Error resgistro no encontrado',
                'message' => $th->getMessage(),
            ], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $this->validationRequest($request);
            $recipe = $this->recipeService->createRecipe($validated);
            return response()->json($recipe);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'error' => 'Datos invalidados',
                'message' => $th->getMessage(),
            ], 422);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $validated = $this->validationRequest($request);
            $recipe = $this->recipeService->updateRecipe($id, $validated);
            return response()->json($recipe);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'No se pudo actualizar la receta',
                'message' => $th->getMessage(),
            ], 422);
        }
    }
}
