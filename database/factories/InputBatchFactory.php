<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\InputBatch;
use App\Models\Order;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InputBatch>
 */
class InputBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */


    //Especifica que esta fabrica esta vinculada al modelo InputBatch
    protected $model = InputBatch::class;

    //Define el estado predeterminado del modelo
    public function definition(): array
    {
        //Crea un nuevo pedido asociado a este lote utilizando la fàbrica de Order
        return [
            'order_id' => Order::factory(),
            'input_id' => 1,
            'quantity_total' => $this->faker->randomFloat(2, 1, 10),
            'quantity_remaining' => $this->faker->randomFloat(2, 1, 10),
            'unit_price' => $this->faker->randomFloat(2, 1, 100),
            'subtotal_price' => $this->faker->randomFloat(2, 10, 1000),
            'batch_number' => $this->faker->numberBetween(1, 100),
            'received_date' => now()
        ];
    }
}
