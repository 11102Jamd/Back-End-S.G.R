<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Production;
use App\Models\Input;

class ProductionConsumptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'production_id' => Production::factory(),
            'input_id' => Input::factory(),
            'quantity_used' => $this->faker->randomFloat(3, 0.5, 10),
        ];
    }
}
