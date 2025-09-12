<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = Order::class;
    public function definition(): array
    {
        return [
            'supplier_name' => $this->faker->company(),
            'order_date' => $this->faker->date(),
            'order_total' => $this->faker->randomFloat(2, 50, 500),
        ];
    }
}
