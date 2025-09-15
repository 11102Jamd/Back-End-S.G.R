<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Production;
use App\Models\ProductProduction;
use Illuminate\Support\Facades\DB;

class ProductProductionService
{
    /**
     * Relaciona una producción con un producto y calcula el porcentaje de ganancia
     */
    public function linkProductionToProduct(int $productionId, int $productId)
    {
        return DB::transaction(function () use ($productionId, $productId) {
            $production = Production::findOrFail($productionId);
            $product = Product::findOrFail($productId);

            // Calcular el porcentaje de ganancia
            $profitMarginPercentage = $this->calculateProfitMarginPercentage(
                $production->price_for_product, // Costo de producción por unidad
                $product->unit_price            // Precio de venta del producto
            );

            // Crear la relación en product_production
            $productProduction = ProductProduction::create([
                'production_id' => $production->id,
                'product_id' => $product->id,
                'quantity_produced' => $production->quantity_to_produce, // Tomar de la producción
                'profit_margin_porcentage' => $profitMarginPercentage
            ]);

            return [
                'product_production' => $productProduction->load(['product', 'production']),
                'profit_margin_percentage' => $profitMarginPercentage
            ];
        });
    }

    /**
     * Calcula el porcentaje de margen de ganancia
     */
    public function calculateProfitMarginPercentage(float $productionCost, float $sellingPrice): float
    {
        if ($productionCost <= 0) {
            return 0;
        }

        $profit = $sellingPrice - $productionCost;
        $marginPercentage = ($profit / $sellingPrice) * 100;

        return round($marginPercentage, 1);
    }
}

