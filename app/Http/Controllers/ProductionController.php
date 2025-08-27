<?php

namespace App\Http\Controllers;

use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\Production;
use App\Services\ProductionService;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    protected $productionService;

    public function __construct(ProductionService $productionService)
    {
        $this->productionService = $productionService;
    }

    public function index()
    {
        try {
            $production = Production::with('productionConsumptions')->orderBy('id', 'desc')->get();
            return response()->json($production);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Registro no encontrado',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    // ProductionController.php
    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipe_id' => 'required|exists:recipe,id',
            'quantity_to_produce' => 'required|numeric|min:0.001'
        ]);

        try {
            $production = $this->productionService->executeProduction(
                $validated['recipe_id'],
                $validated['quantity_to_produce']
            );

            return response()->json([
                'message' => 'Producción ejecutada exitosamente',
                'data' => $production,
                'total_cost' => round($production->total_cost, 3),
                'cost_per_unit' => round($production->total_cost / $production->quantity_to_produce, 3)
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Datos invalidados',
                'message' => $th->getMessage(),
            ], 422);
        }
    }
}
