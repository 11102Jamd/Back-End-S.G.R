<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'supplier_name' => $this->faker->company(),
            'order_date' => $this->faker->date(),
            'order_total' => $this->faker->randomFloat(3, 100, 5000),
        ];
    }
}
