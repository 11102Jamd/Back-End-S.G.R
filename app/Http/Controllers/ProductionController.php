<?php

namespace App\Http\Controllers;

use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\Production;
use App\Services\ProductionService;
use Illuminate\Http\Request;

/**
 * Controlador de Producciones
 *
 * Este controlador se encarga de manejar todas las operaciones relacionadas con la producción:
 * - Listar todas las producciones
 * - Mostrar detalles de una producción específica
 * - Ejecutar una nueva producción (consumiendo insumos)
 * - Eliminar una producción y revertir los consumos
 *
 * Todas las respuestas se devuelven en formato JSON, indicando éxito o errores.
 */

class ProductionController extends Controller
{
    protected $productionService;
    /**
     * Inyecta la dependencia de ProductionService.
     *
     * @param ProductionService $productionService
     */
    public function __construct(ProductionService $productionService)
    {
        $this->productionService = $productionService;
    }

    /**
     * Obtiene todas las producciones registradas.
     *
     * - Trae la información de la receta asociada y los consumos de insumos.
     * - Ordena los resultados de forma descendente por ID.
     *
     * @return \Illuminate\Http\JsonResponse Lista de producciones o mensaje de error
     */
    public function index()
    {
        try {
            $production = Production::with('recipe','productionConsumptions')->orderBy('id', 'desc')->get();
            return response()->json($production);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Registro no encontrado',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Muestra los detalles de una producción específica.
     *
     * Incluye:
     * - La receta asociada
     * - Los insumos consumidos
     * - Los lotes utilizados de cada insumo
     *
     * @param int $id ID de la producción
     * @return \Illuminate\Http\JsonResponse Producción con detalles o mensaje de error
     */
    public function show($id)
    {
        try {
            $production = Production::with([
                'recipe',
                'productionConsumptions.input',
                'productionConsumptions.batch',
            ])->findOrFail($id);

            return response()->json($production);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'No se pudo obtener la producción',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Ejecuta una nueva producción.
     *
     * Pasos importantes:
     * 1. Valida los datos de la solicitud (ID de receta y cantidad a producir).
     * 2. Llama al servicio de producción que calcula consumos y costos.
     * 3. Devuelve los datos de la producción, costo total y costo unitario.
     *
     * @param \Illuminate\Http\Request $request Datos de la producción
     * @return \Illuminate\Http\JsonResponse Datos de la producción o mensaje de error
     */
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

    /**
     * Elimina una producción y revierte los consumos de insumos.
     *
     * - Valida que la producción exista
     * - Restaura la cantidad utilizada de cada insumo en sus respectivos lotes
     * - Elimina la producción y los registros de consumos asociados
     *
     * @param int $id ID de la producción a eliminar
     * @return \Illuminate\Http\JsonResponse Mensaje de éxito o error
     */
    public function destroy($id)
    {
        try {
            $this->productionService->destroyProduction($id);
            return response()->json(['message' => 'Producción eliminada y consumos revertidos correctamente']);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'No se pudo eliminar la producción',
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
