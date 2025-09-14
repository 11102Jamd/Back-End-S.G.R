<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Fábrica para generar instancias del modelo Order.
 *
 * Esta clase permite crear registros ficticios de órdenes de compra
 * que incluyen proveedor, fecha y total del pedido. Es útil para
 * pruebas automatizadas o para cargar datos de ejemplo en seeders.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Modelo al que está vinculada la fábrica.
     *
     * @var class-string<\App\Models\Order>
     */
    protected $model = Order::class;

    /**
     * Define el estado predeterminado del modelo Order.
     *
     * Este método genera datos ficticios para los atributos:
     * - supplier_name: nombre de empresa proveedora aleatorio.
     * - order_date: fecha del pedido en formato YYYY-MM-DD.
     * - order_total: monto total de la orden con dos decimales.
     *
     * @return array<string, mixed> Datos simulados para un Order.
     */
    public function definition(): array
    {
        return [
            'supplier_name' => $this->faker->company(),
            'order_date'    => $this->faker->date(),
            'order_total'   => $this->faker->randomFloat(2, 50, 500),
        ];
    }
}
