<?php

namespace App\Http\Controllers;

use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\Recipe;
use App\Services\RecipeService;
use Illuminate\Http\Request;
/**
 * Controlador encargado de gestionar las recetas.
 * 
 * Incluye métodos para mostrar, crear, actualizar y eliminar recetas.
 * Extiende de BaseCrudController para reutilizar lógica CRUD común.
 */
class RecipeController extends BaseCrudController
{

    protected $model = Recipe::class;
    /**
     * Servicio para la gestión de recetas.
     *
     * @var RecipeService
     */
    protected $recipeService;
    /**
     * Reglas de validación para crear/actualizar recetas.
     *
     * @var array
     */
    protected $validationRules = [
        'recipe_name' => 'required|string|max:50',
        'yield_quantity' => 'required|integer|max:100',
        'ingredient' => 'required|array|min:1',
        'ingredient.*.input_id' => 'required|exists:input,id',
        'ingredient.*.quantity_required' => 'required|numeric|min:0.001',
        'ingredient.*.unit_used' => 'required|string|max:8'
    ];
    /**
     * Inyecta la dependencia de RecipeService.
     *
     * @param RecipeService $recipeService
     */
    public function __construct(RecipeService $recipeService)
    {
        $this->recipeService = $recipeService;
    }

    /**
     * Muestra una receta específica con sus ingredientes.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Crea una nueva receta con sus ingredientes.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Actualiza una receta existente.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Elimina una receta existente por su ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $this->recipeService->deleteRecipe($id);
            return response()->json([
                'message' => 'Receta eliminada con éxito'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'No se pudo eliminar la receta',
                'message' => $th->getMessage()
            ], 422);
        }
    }
}
