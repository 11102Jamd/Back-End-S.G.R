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

    private function createInput(string $name = 'Harina', string $unit = 'kg'): int
    {
        // Insert directo para evitar problemas de $fillable
        return DB::table('input')->insertGetId([
            'name' => $name,
            'unit' => $unit,
        ]);
    }

    public function can_list_orders_with_batches(): void
    {
        // Arrange: crear orden + lote (si tienes factories, puedes usarlas)
        $orderId = DB::table('order')->insertGetId([
            'supplier_name' => 'Proveedor Test',
            'order_date'    => now(),
            'order_total'   => 100.50,
        ]);

        $inputId = $this->createInput('Harina', 'kg');

        DB::table('input_batch')->insert([
            'order_id'           => $orderId,
            'input_id'           => $inputId,
            'quantity_total'     => 10,
            'quantity_remaining' => 10000, // gramos
            'unit_price'         => 10.05,
            'subtotal_price'     => 100.50,
            'batch_number'       => 1,
            'received_date'      => now(),
        ]);

        // Act
        $response = $this->getJson('/order');

        // Assert
        $response->assertOk()
            ->assertJsonFragment([
                'supplier_name' => 'Proveedor Test',
                'order_total'   => 100.50,
            ]);
    }

    public function can_create_order_and_returns_batches_and_total(): void
    {
        // Arrange: insumos válidos (unidad permitida)
        $harinaId = $this->createInput('Harina', 'kg'); // se convertirá a gramos
        $levaduraId = $this->createInput('Levadura', 'g');

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

        $expectedTotal = 6002.222 + 1027.75; // 7029.972

        // Act
        $response = $this->postJson('/order', $payload);

        // Assert
        $response->assertCreated()
            ->assertJsonPath('message', 'Orden creada exitosamente')
            ->assertJsonPath('order_total', (float) number_format($expectedTotal, 3, '.', ''))
            ->assertJsonStructure([
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

        // Verifica conversión a gramos en el primer batch (2 kg -> 2000 g)
        $firstBatch = $response->json('data.batches.0');
        $this->assertEquals(2000, (int) round($firstBatch['quantity_remaining']));
    }

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

    public function cannot_create_order_with_invalid_input_unit(): void
    {
        // Arrange: insumo con unidad NO permitida por el controlador
        // Ojo: el controlador valida unidad contra ['kg','g','lb','l','oz']
        $inputId = $this->createInput('Huevos', 'un');

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

        $response = $this->postJson('/order', $payload);

        $response->assertStatus(422);
        $this->assertStringContainsString(
            'unidad no válida',
            strtolower(json_encode($response->json()))
        );
    }

    public function batch_number_increments_for_same_input_across_orders(): void
    {
        $inputId = $this->createInput('Azúcar', 'kg');

        // Primera orden
        $payload1 = [
            'supplier_name' => 'Dulces S.A.',
            'order_date' => '2025-09-01',
            'items' => [
                ['input_id' => $inputId, 'quantity_total' => 1, 'unit_price' => 1000.0],
            ],
        ];
        $r1 = $this->postJson('/order', $payload1)->assertCreated();
        $batch1 = collect($r1->json('data.batches'))->first();
        $this->assertEquals(1, (int) $batch1['batch_number']);

        // Segunda orden con el mismo insumo
        $payload2 = [
            'supplier_name' => 'Dulces S.A.',
            'order_date' => '2025-09-02',
            'items' => [
                ['input_id' => $inputId, 'quantity_total' => 2, 'unit_price' => 900.0],
            ],
        ];
        $r2 = $this->postJson('/order', $payload2)->assertCreated();
        $batch2 = collect($r2->json('data.batches'))->first();

        // Esperamos que sea 2 para el mismo input
        $this->assertEquals(
            2,
            (int) $batch2['batch_number'],
            'El batch_number debería incrementarse para el mismo input. Si falla, corrige getNextBatchNumber().'
        );
    }
}
