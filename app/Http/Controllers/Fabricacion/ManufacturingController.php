<?php
namespace App\Http\Controllers\Fabricacion;

use App\Http\Controllers\Controller;
use App\Http\Controllers\globalCrud\BaseCrudController;
use Illuminate\Http\Request;
use App\Models\Fabricacion\Manufacturing;
use App\Models\Fabricacion\Recipes;

class ManufacturingController extends BaseCrudController
{
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

        // Guardar fabricación
        /*$manufacturing = Manufacturing::create([
            'ID_product' => $validated['ID_product'],
            'ManufacturingTime' => $validated['ManufacturingTime'],
            'Labour' => $validated['Labour'],
            'ManufactureProductG' => $validated['ManufactureProductG'],
            'TotalCostProduction' => $validated['TotalCostProduction'],
        ]);

        // Guardar recetas
        foreach ($validated['recipes'] as $recipe) {
            $manufacturing->recipes()->create($recipe);
        }

        return response()->json(['message' => 'Fabricación registrada con recetas'], 201);*/
    }
}

