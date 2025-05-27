<?php

namespace App\Http\Controllers\ManufacturingController;

use App\Http\Controllers\Controller;
use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\Manufacturing\Manufacturing;
use Illuminate\Http\Request;

class ManufacturingController extends BaseCrudController
{
    protected $model = Manufacturing::class;

    protected $validationRules = [
        'ID_product' => 'required|exists:product,id',
        'ManufacturingTime' => 'required|integer|min:1',
        'recipes' => 'required|array|min:1',
        'recipes.*.ID_inputs' => 'required|exists:inputs,id',
        'recipes.*.AmountSpent' => 'required|numeric|min:0.01',
        'recipes.*.UnitMeasurement' => 'required|string|in:g,kg,lb'
    ];

    public function store(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);

            $recipes = $validated['recipes'];
            unset($validated['recipes']);


            $manufacturing = $this->model::create($validated);


            foreach ($recipes as $recipe) {
                $manufacturing->recipes()->create($recipe);
            }

            return response()->json([
                'message' => 'Fabricación registrada con recetas',
                'data' => $manufacturing->load('recipes')
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Error al registrar fabricación',
                'message' => $th->getMessage()
            ], 422);
        }
    }

    public function destroy($id)
    {
        try {
            $manufacturing = Manufacturing::with('recipes')->findOrFail($id);

            foreach ($manufacturing->recipes as $recipe) {
                $recipe->restoredStockInpunts();
                $recipe->delete();
            }
            $manufacturing->delete();

            return response()->json([
                'message' => 'Fabricacion eleminidad y stock restaurado correctamente'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Error al eliminar fabricacion',
                'message' => $th->getMessage()
            ], 422);
        }
    }
}
