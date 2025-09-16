<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleProductFactory extends Factory
{
    protected $model = SaleProduct::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(2, 100);
        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'quantity_requested' => $quantity,
            'subtotal_price' => $this->faker->randomFloat(2,1,100),
        ];
    }
}
