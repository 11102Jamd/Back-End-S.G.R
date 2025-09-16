<?php

namespace Database\Factories;

use App\Models\Production;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Recipe;

class ProductionFactory extends Factory
{
    protected $model = Production::class;

    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'production_date' => $this->faker->date(),
            'quantity_to_produce' => $this->faker->randomFloat(2,1,100),
            'total_cost' => $this->faker->randomFloat(2,1,100),
            'price_for_product' => $this->faker->randomFloat(2, 500, 2000),
        ];
    }
}
