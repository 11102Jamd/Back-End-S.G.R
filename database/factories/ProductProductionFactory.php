<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Production;
use App\Models\ProductProduction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductProduction>
 */
class ProductProductionFactory extends Factory
{
    protected $model = ProductProduction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $product = Product::factory()->create();
        $production = Production::factory()->create();

        $productionCost = $production->price_for_product;
        $sellingPrice = $product->unit_price;

        $profitMarginPercentage = $sellingPrice > 0
            ? round((($sellingPrice - $productionCost) / $sellingPrice) * 100, 1)
            : 0;

        return [
            'production_id' => $production->id,
            'product_id' => $product->id,
            'quantity_produced' => $production->quantity_to_produce ?? $this->faker->numberBetween(10, 100),
            'profit_margin_porcentage' => $profitMarginPercentage,
        ];
    }
}
