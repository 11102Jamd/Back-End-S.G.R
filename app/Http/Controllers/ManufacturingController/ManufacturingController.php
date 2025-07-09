<?php

namespace App\Http\Controllers\ManufacturingController;

use App\Http\Controllers\Controller;
use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\Manufacturing\Manufacturing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        DB::beginTransaction();
        try {
            $validated = $this->validateRequest($request);

            // Crear fabricación
            $manufacturing = $this->model::create([
                'ID_product'=> $validated["ID_product"],
                'ManufacturingTime'=>$validated["ManufacturingTime"]
            ]);

            // Calcular mano de obra
            $manufacturing->calculateLabour();

            // Calcular insumos, stock y costo total
            $manufacturing->addIngredients($validated[
                "recipes"
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Fabricación registrada con recetas y cálculos',
                'data' => $manufacturing->load('recipes')
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error en el controlador de fabricacion..".$th->getMessage());
            return response()->json([
                'error' => 'Error al registrar fabricación',
                'message' => $th->getMessage()
            ], 422);
        }
    }


    public function index()
    {
        try {
            $manufacturings = Manufacturing::with(['product', 'recipes.input'])->get(); 
            return response()->json($manufacturings);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Error al obtener las fabricaciones',
                'message' => $th->getMessage()
            ], 500);
        }
    }


    public function destroy($id)
    {
        try {
            $manufacturing = Manufacturing::with('recipes')->findOrFail($id);

            foreach ($manufacturing->recipes as $recipe) {
                $recipe->restoreStockInputs();
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
