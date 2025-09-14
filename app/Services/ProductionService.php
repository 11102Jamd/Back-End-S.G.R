<?php

namespace App\Services;

use App\Models\InputBatch;
use App\Models\Production;
use App\Models\ProductionConsumption;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;
/**
 * Servicio ProductionService
 *
 * Clase ProductionService
 * Servicio que maneja toda la lógica relacionada con la producción de recetas.
 * Se encarga de:
 * - Ejecutar una producción según la receta y la cantidad solicitada.
 * - Registrar el consumo de insumos de los lotes disponibles.
 * - Calcular costos totales y por unidad.
 * - Eliminar producciones y devolver los insumos consumidos a sus lotes.
 */
class ProductionService
{
    /**
     * Calcula el costo de un lote según la cantidad de insumo que se va a usar.
     *
     * Convierte la cantidad utilizada a la unidad correspondiente del lote
     * (kg, lb, g, l, un) y multiplica por el precio unitario del lote.
     *
     * @param float $gramsUsed Cantidad de insumo utilizada
     * @param InputBatch $batch Lote del insumo
     * @return float Costo del lote
     */
    protected function calculateBatchCost(float $gramsUsed, InputBatch $batch): float
    {
        $unit = strtolower($batch->input->unit);
        $conversionRates = ['kg' => 1000, 'lb' => 453.592, 'g' => 1, 'l' => 1000, 'un' => 1];
        $gramsPerUnit = $conversionRates[$unit] ?? 1;
        $unitsUsed = $gramsUsed / $gramsPerUnit;

        return $unitsUsed * $batch->unit_price;
    }

    /**
     * Consume un insumo de los lotes disponibles y registra cada consumo.
     *
     * 
     * -Obtiene los lotes disponibles del insumo, ordenados por fecha de recepción.
     * -Bloquea los registros para evitar que otro proceso los use al mismo tiempo.
     * -Itera sobre los lotes hasta cubrir la cantidad requerida.
     * -Crea un registro de consumo (ProductionConsumption) por cada lote utilizado.
     * -Reduce la cantidad disponible del lote según lo consumido.
     * -Lanza un error si no alcanza el stock necesario.
     *
     * @param int $productionId ID de la producción
     * @param int $inputId ID del insumo
     * @param float $requiredGrams Cantidad de insumo requerida
     * @return array Información del consumo: total usado, costo y lotes empleados
     * @throws \Exception Si no hay suficiente stock
     */
    protected function consumeIngredient(int $productionId, int $inputId, float $requiredGrams): array
    {
        $remaining = $requiredGrams;
        $totalCost = 0;
        $batchUsed = [];

        $batches = InputBatch::with('input')
            ->where('input_id', $inputId)
            ->where('quantity_remaining', '>', 0)
            ->orderBY('received_date', 'asc')
            ->lockForUpdate()->get();
        foreach ($batches as $batch) {
            if ($remaining <= 0) break;
            $consumedGram = min($batch->quantity_remaining, $remaining);
            $batchCost = $this->calculateBatchCost($consumedGram, $batch);

             // Registrar el consumo en la base de datos
            ProductionConsumption::create([
                'production_id' => $productionId,
                'input_id' => $inputId,
                'input_batches_id' => $batch->id,
                'quantity_used' => round($consumedGram, 3),
                'unit_price' => round($batch->unit_price, 3),
                'total_cost' => round($batchCost, 3)
            ]);

            // Actualizar la cantidad restante del lote
            $batch->decrement('quantity_remaining', $consumedGram);
            $remaining -= $consumedGram;
            $totalCost += $batchCost;
            $batchUsed[] = [
                'batch_id' => $batch->id,
                'grams_used' => round($consumedGram, 3),
                'cost' => round($batchCost, 3)
            ];
        }

        // Validación de stock insuficiente
        if ($remaining > 0) {
            $inputName = $batches->first()?->input?->input_name ?? "ID {$inputId}";
            throw new \Exception("Stock insuficiente para el insumo: {$inputName}. Faltan {$remaining} gramos.");
        }

        return [
            'total_grams_used' => $requiredGrams - $remaining,
            'total_cost' => round($totalCost, 3),
            'batches' => $batchUsed
        ];
    }

/**
     * Ejecuta la producción de una receta según la cantidad solicitada.
     *
     *
     *-Obtiene la receta con todos sus ingredientes.
     *-Calcula el factor de escala según la cantidad a producir.
     *-Crea un registro inicial de producción con costos en cero.
     *-Itera sobre cada ingrediente y registra el consumo de los lotes disponibles.
     *-Calcula el costo total y el precio por unidad.
     *-Devuelve la producción con los consumos asociados cargados.
     *
     * @param int $recipeId ID de la receta
     * @param float $quantityToProduce Cantidad de producto a producir
     * @return Production Producción creada con consumos y costos
     */
    public function executeProduction(int $recipeId, float $quantityToProduce)
    {
        return DB::transaction(function () use ($recipeId, $quantityToProduce) {
            $recipe = Recipe::with('recipeIngredients.input')->findOrFail($recipeId);
            $scaleFactor = $quantityToProduce / $recipe->yield_quantity;
            $production = Production::create([
                'recipe_id' => $recipeId,
                'quantity_to_produce' => $quantityToProduce,
                'production_date' => now(),
                'price_for_product' => 0,
                'total_cost' => 0
            ]);
            $totalCost = 0;
            foreach ($recipe->recipeIngredients as $ingredient) {
                $requiredGrams = $ingredient->quantity_required * $scaleFactor;
                $consumptionResult = $this->consumeIngredient(
                    $production->id,
                    $ingredient->input_id,
                    $requiredGrams
                );
                $totalCost += $consumptionResult['total_cost'];
            }

            $production->update([
                'total_cost' => round($totalCost, 3),
                'price_for_product' => round($totalCost / $quantityToProduce, 3)
            ]);

            return $production->load('productionConsumptions.batch.input');
        });
    }

    /**
     * Elimina una producción y revierte los consumos de los lotes asociados.
     *
     *-Obtiene la producción y todos los consumos relacionados.
     *-Para cada consumo, incrementa la cantidad restante en el lote correspondiente.
     *-Elimina los registros de consumo.
     *-Elimina el registro de producción.
     *-Devuelve true si se eliminó correctamente.
     * 
     * @param int $id ID de la producción a eliminar
     * @return bool Retorna true si la operación fue exitosa
     */
    public function destroyProduction(int $id)
    {
        return DB::transaction(function () use ($id) {
            $production = Production::with('productionConsumptions.batch')->findOrFail($id);

            foreach ($production->productionConsumptions as $consumption) {
                $batch = $consumption->batch;
                if ($batch) {
                    $batch->increment('quantity_remaining', $consumption->quantity_used);
                }
                $consumption->delete();
            }
            $production->delete();
            return true;
        });
    }
}
