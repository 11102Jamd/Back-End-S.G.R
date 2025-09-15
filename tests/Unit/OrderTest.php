<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\Input;
use App\Models\InputBatch;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    //Método privado para crear un insumo en la base de datos
    private function createInput(string $name = 'Harina', string $unit = 'kg'): int
    {
        // Inserta un insumo directamente para evitar problemas con el atributo $fillable
        return DB::table('input')->insertGetId([
            'name' => $name,
            'unit' => $unit,
        ]);
    }

    //Test para verificar que se puedan listar las órdenes con los lotes
    public function can_list_orders_with_batches(): void
    {
        // Arrange: crear una orden y un lote correspondiente
        $orderId = DB::table('order')->insertGetId([
            'supplier_name' => 'Proveedor Test',
            'order_date'    => now(),
            'order_total'   => 100.50,
        ]);

        //Crear un insumo
        $inputId = $this->createInput('Harina', 'kg');

        //Insertar un lote correspondiente a la orden y el insumo creados
        DB::table('input_batch')->insert([
            'order_id'           => $orderId,
            'input_id'           => $inputId,
            'quantity_total'     => 10,
            'quantity_remaining' => 10000, // Medida en gramos
            'unit_price'         => 10.05,
            'subtotal_price'     => 100.50,
            'batch_number'       => 1,
            'received_date'      => now(),
        ]);

        // Act: Llamar a la API para obtener la lista de órdenes
        $response = $this->getJson('/order');


        // Assert: Verificar que la respuesta sea correcta
        $response->assertOk()
            ->assertJsonFragment([
                'supplier_name' => 'Proveedor Test', //Verificar el nombre del proveedor en la respuesta JSON
                'order_total'   => 100.50, //Verificar el total de la orden
            ]);
    }

    //Test que valida la creación de una nueva orden y el retorno de lotes y total
    public function can_create_order_and_returns_batches_and_total(): void
    {
        // Arrange: crear insumos válidos (unidades permitidas)
        $harinaId = $this->createInput('Harina', 'kg'); // se convertirá a gramos
        $levaduraId = $this->createInput('Levadura', 'g');

        // Definir la carga útil (payload) para la creación de la orden
        $payload = [
            'supplier_name' => 'Molinos S.A.',
            'order_date' => '2025-09-01',
            'items' => [
                [
                    'input_id' => $harinaId,
                    'quantity_total' => 2.0,     // 2 kg -> 2000 g
                    'unit_price' => 3000.111,    // subtotal 6000.222 -> redondea a 6000.222
                ],
                [
                    'input_id' => $levaduraId,
                    'quantity_total' => 50.0,    // 50 g -> 50 g
                    'unit_price' => 20.555,      // subtotal 1027.75 -> redondea a 1027.75
                ],
            ],
        ];

        $expectedTotal = 6002.222 + 1027.75; // Total esperado de la orden

        // Act: Enviar la carga útil a la API para crear la orden
        $response = $this->postJson('/order', $payload);

        // Assert: Verificar que la respuesta es correcta
        $response->assertCreated()
            ->assertJsonPath('message', 'Orden creada exitosamente') // Mensaje de éxito
            ->assertJsonPath('order_total', (float) number_format($expectedTotal, 3, '.', '')) // Confirmar el total
            ->assertJsonStructure([ // Verificar que la estructura JSON devuelta es la esperada
                'data' => [
                    'id',
                    'supplier_name',
                    'order_date',
                    'order_total',
                    'batches' => [
                        ['id', 'order_id', 'input_id', 'quantity_total', 'quantity_remaining', 'unit_price', 'subtotal_price', 'batch_number', 'received_date']
                    ]
                ]
            ]);

        // Verificar que la conversión a gramos se realizó correctamente para el primer lote
        $firstBatch = $response->json('data.batches.0');
        $this->assertEquals(2000, (int) round($firstBatch['quantity_remaining'])); // Asegurar la cantidad restante en gramos
    }

    // Test que valida que no se puede crear una orden sin campos requeridos
    public function cannot_create_order_without_required_fields(): void
    {
        // Falta supplier_name, items, etc.
        $payload = [
            'order_date' => '2025-09-01',
        ];

        $response = $this->postJson('/order', $payload);

        $response->assertStatus(422);
        // No asumimos el formato exacto del error del BaseCrudController:
        $this->assertStringContainsString('error', strtolower(json_encode($response->json())));
    }

    // Test que valida que no se puede crear una orden con un insumo de unidad inválida
    public function cannot_create_order_with_invalid_input_unit(): void
    {
        // Arrange: Crear un insumo con unidad NO permitida
        $inputId = $this->createInput('Huevos', 'un'); // 'un' no es un valor válido

        $payload = [
            'supplier_name' => 'Avícola S.A.',
            'order_date' => '2025-09-01',
            'items' => [
                [
                    'input_id' => $inputId,
                    'quantity_total' => 30,
                    'unit_price' => 500,
                ],
            ],
        ];

        // Act: Intentar crear la orden
        $response = $this->postJson('/order', $payload);

        // Assert: Verificar que se retorna un error de validación
        $response->assertStatus(422); // Código de estado HTTP 422
        $this->assertStringContainsString(
            'unidad no válida', // Mensaje de error específico
            strtolower(json_encode($response->json()))
        );
    }

    // Test que valida que el número de lote se incrementa al crear órdenes con el mismo insumo
    public function batch_number_increments_for_same_input_across_orders(): void
    {
        // Crear un insumo para pruebas
        $inputId = $this->createInput('Azúcar', 'kg');

        // Primera orden
        $payload1 = [
            'supplier_name' => 'Dulces S.A.',
            'order_date' => '2025-09-01',
            'items' => [
                ['input_id' => $inputId, 'quantity_total' => 1, 'unit_price' => 1000.0],
            ],
        ];

        // Realizar la creación de la primera orden
        $r1 = $this->postJson('/order', $payload1)->assertCreated();
        $batch1 = collect($r1->json('data.batches'))->first(); // Obtener el primer lote
        // Afirmar que el batch_number es 1
        $this->assertEquals(1, (int) $batch1['batch_number']);

        // Segunda orden con el mismo insumo
        $payload2 = [
            'supplier_name' => 'Dulces S.A.',
            'order_date' => '2025-09-02',
            'items' => [
                ['input_id' => $inputId, 'quantity_total' => 2, 'unit_price' => 900.0],
            ],
        ];

        // Realizar la creación de la segunda orden
        $r2 = $this->postJson('/order', $payload2)->assertCreated();
        $batch2 = collect($r2->json('data.batches'))->first(); // Obtener el segundo lote

        // Afirmar que el batch_number se ha incrementado a 2
        $this->assertEquals(
            2,
            (int) $batch2['batch_number'],
            'El batch_number debería incrementarse para el mismo input. Si falla, corrige getNextBatchNumber().'
        );
    }
}
