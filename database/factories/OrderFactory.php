<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

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

    //
    protected $model = Order::class;

    //Define el estado predeterminado del modelo
    public function definition(): array
    {
        return [
            //Genera un nombre de proveedor aleatorio
            'supplier_name' => $this->faker->company(), //Nombre de la empresa proveedora

            //Genera una fecha aleatoria para el pedido
            'order_date' => $this->faker->date(),//Fecha del pedido

            //Genera un total de pedido aleatorio entre 50 y 500
            'order_total' => $this->faker->randomFloat(2, 50, 500),//total del pedido, con 2 decimales
        ];
    }
}
