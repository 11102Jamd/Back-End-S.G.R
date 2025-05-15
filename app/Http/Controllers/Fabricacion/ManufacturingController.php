<?php
namespace App\Http\Controllers\Fabricacion;
namespace App\Models\Fabricacion;

use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\Manufacturing;
use Illuminate\Http\Request;


class ManufacturingController extends BaseCrudController
{
    protected $model = Manufacturing::class;

    protected $validationRules = [
        'ID_product' => 'required|exists:product,id',
        'ManufacturingTime' => 'required',
        'Labour' => 'required|integer',
        'ManufactureProductG' => 'required|numeric',
        'TotalCostProduction' => 'required|numeric',
        'recipes' => 'required|array',
        'recipes.*.ID_inputs' => 'required|exists:inputs,id',
        'recipes.*.AmountSpent' => 'required|numeric',
        'recipes.*.UnitMeasurement' => 'required|string|max:10',
        'recipes.*.PriceQuantitySpent' => 'required|numeric',
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
    

}