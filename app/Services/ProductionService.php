<?php

namespace App\Services;

use App\Models\InputBatch;
use App\Models\Production;
use App\Models\ProductionConsumption;
use App\Models\Recipe;
use Exception;
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
     * Calcula el costo de un lote según la cantidad utilizada.
     *
     * @param float $gramsUsed Cantidad utilizada en gramos (estándar)
     * @param InputBatch $batch Lote del insumo
     * @return float Costo del lote según su unidad y precio
     */
    protected function calculateBatchCost(float $gramsUsed, InputBatch $batch) {
        $originalUnit = $batch->unit;
        $originalUnitUsed = $this->convetFromStandardUnit($gramsUsed, $originalUnit);
        return $originalUnitUsed * $batch->unit_price;
    }
    /**
     *  Convierte la cantidad estándar (gramos/mililitros/unidades) a la unidad original del lote.
     *
     * Soporta: kg, g, lb, oz, l, ml, un
     * Lanza excepción si la unidad no es válida.
     *
     * @param float $standarAmount Cantidad estándar (gramos/mililitros/unidades)
     * @param string $originalUnit Unidad original del lote
     * @return float Cantidad en la unidad original
     * @throws Exception
     */
    protected function convetFromStandardUnit(float $standarAmount, string $originalUnit)
    {
        $originalUnit = strtolower($originalUnit);
        if (in_array($originalUnit, ['kg', 'g', 'lb', 'oz'])) {
            return match ($originalUnit) {
                'kg' => $standarAmount / 1000,
                'g' => $standarAmount,
                'lb' => $standarAmount / 453.592,
                'oz' => $standarAmount / 28.349
            };
        } elseif (in_array($originalUnit, ['l', 'ml'])) {
            return match ($originalUnit) {
                'l' => $standarAmount / 1000,
                'ml' => $standarAmount
            };
        } elseif ($originalUnit=='un') {
            return $standarAmount;
        } else {
            throw new Exception("unidad original no valida");
        }
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
     *-Devuelve la producción con los consumos asociados.
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
