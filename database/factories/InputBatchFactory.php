<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\InputBatch;
use App\Models\Order;

/**
 * Fábrica para generar instancias de la entidad InputBatch.
 *
 * Esta clase permite crear registros ficticios de lotes de insumos
 * para pruebas automatizadas o seeders dentro de la base de datos.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InputBatch>
 */
class InputBatchFactory extends Factory
{
    /**
     * Modelo al que está vinculada la fábrica.
     *
     * @var class-string<\App\Models\InputBatch>
     */
    protected $model = InputBatch::class;

    /**
     * Define el estado predeterminado del modelo InputBatch.
     *
     * Este método genera datos ficticios para los atributos:
     * - order_id: se asocia con un pedido creado por OrderFactory.
     * - input_id: se asigna un valor por defecto (ej. 1).
     * - quantity_total: cantidad total del lote.
     * - quantity_remaining: cantidad disponible restante.
     * - unit_price: precio unitario del insumo.
     * - subtotal_price: costo total del lote.
     * - batch_number: número de lote generado aleatoriamente.
     * - received_date: fecha de recepción del lote.
     *
     * @return array<string, mixed> Datos simulados para un InputBatch.
     */
    public function definition(): array
    {
        return [
            'order_id'          => Order::factory(),
            'input_id'          => 1,
            'quantity_total'    => $this->faker->randomFloat(2, 1, 10),
            'quantity_remaining' => $this->faker->randomFloat(2, 1, 10),
            'unit_price'        => $this->faker->randomFloat(2, 1, 100),
            'subtotal_price'    => $this->faker->randomFloat(2, 10, 1000),
            'batch_number'      => $this->faker->numberBetween(1, 100),
            'received_date'     => now(),
        ];
    }
}
