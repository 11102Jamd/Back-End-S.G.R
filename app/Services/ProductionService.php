<?php

namespace App\Services;

use App\Models\InputBatch;
use App\Models\Production;
use App\Models\ProductionConsumption;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;

class ProductionService
{
    protected function calculateBatchCost(float $gramsUsed, InputBatch $batch): float
    {
        $unit = strtolower($batch->input->unit);
        $conversionRates = ['kg' => 1000, 'lb' => 453.592, 'g' => 1, 'l' => 1000, 'un' => 1];
        $gramsPerUnit = $conversionRates[$unit] ?? 1;
        $unitsUsed = $gramsUsed / $gramsPerUnit;

        return $unitsUsed * $batch->unit_price;
    }

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

            ProductionConsumption::create([
                'production_id' => $productionId,
                'input_id' => $inputId,
                'input_batches_id' => $batch->id,
                'quantity_used' => round($consumedGram, 3),
                'unit_price' => round($batch->unit_price, 3),
                'total_cost' => round($batchCost, 3)
            ]);

            $batch->decrement('quantity_remaining', $consumedGram);
            $remaining -= $consumedGram;
            $totalCost += $batchCost;
            $batchUsed[] = [
                'batch_id' => $batch->id,
                'grams_used' => round($consumedGram, 3),
                'cost' => round($batchCost, 3)
            ];
        }

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
