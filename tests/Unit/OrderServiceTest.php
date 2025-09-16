<?php

namespace Tests\Unit;

use App\Models\Input;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = new OrderService();
    }

    #[Test]
    public function test_it_creates_order_with_batches_and_calculates_total()
    {
        $input = Input::factory()->create([
            'name' => 'aceite',
            'category' => 'liquido'
        ]);

        $orderData = [
            'supplier_name' => 'Proveedor ABC',
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'input_id' => $input->id,
                    'quantity_total' => 10,
                    'unit' => 'l',
                    'unit_price' => 50
                ]
            ]
        ];

        $order = $this->orderService->createOrderWithBatches($orderData);

        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'supplier_name' => 'Proveedor ABC'
        ]);

        $this->assertEquals(500, $order->order_total); // 10 * 50
        $this->assertCount(1, $order->batches);

        $batch = $order->batches->first();
        $this->assertEquals($input->id, $batch->input_id);
        $this->assertEquals(10000, $batch->quantity_remaining); // 10 l -> 10000 ml
        $this->assertEquals('ml', $batch->unit_converted);
        $this->assertEquals(1, $batch->batch_number);
    }

    #[Test]
    public function test_it_throws_exception_for_invalid_unit()
    {
        $input = Input::factory()->create([
            'name' => 'harina',
            'category' => 'solido_no_con'
        ]);

        $orderData = [
            'supplier_name' => 'Proveedor XYZ',
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'input_id' => $input->id,
                    'quantity_total' => 5,
                    'unit' => 'l',
                    'unit_price' => 100
                ]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("La unidad 'l' no es válida para la categoría 'solido_no_con'");

        $this->orderService->createOrderWithBatches($orderData);
    }

    #[Test]
    public function test_it_increments_batch_number_correctly()
    {
        $input = Input::factory()->create([
            'name' => 'papa',
            'category' => 'solido_no_con'
        ]);

        // Primer batch
        $this->orderService->createOrderWithBatches([
            'supplier_name' => 'Proveedor 1',
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'input_id' => $input->id,
                    'quantity_total' => 2,
                    'unit' => 'kg',
                    'unit_price' => 10
                ]
            ]
        ]);

        // Segundo batch
        $order2 = $this->orderService->createOrderWithBatches([
            'supplier_name' => 'Proveedor 2',
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'input_id' => $input->id,
                    'quantity_total' => 3,
                    'unit' => 'kg',
                    'unit_price' => 15
                ]
            ]
        ]);

        $batch = $order2->batches->first();
        $this->assertEquals(2, $batch->batch_number);
    }
}
