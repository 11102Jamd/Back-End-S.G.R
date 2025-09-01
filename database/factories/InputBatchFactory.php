<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;
use App\Models\Input;

class InputBatchFactory extends Factory
{
    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(3, 10, 100);

        return [
            'order_id' => Order::factory(),
            'input_id' => Input::factory(),
            'quantity_total' => $quantity,
            'quantity_remaining' => $quantity,
            'unit_price' => $this->faker->randomFloat(3, 1, 50),
            'subtotal_price' => fn (array $attrs) => $attrs['quantity_total'] * $attrs['unit_price'],
            'batch_number' => $this->faker->unique()->numberBetween(1000, 9999),
            'received_date' => $this->faker->date(),
        ];
    }
}
