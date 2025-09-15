<?php

/**
 * Prueba ProductionPdfController realizada por el desarrollador
 * Juan Alejandro Muñoz Devia
 */

namespace Tests\Unit;

use App\Models\Input;
use App\Models\InputBatch;
use App\Models\Order;
use App\Models\Production;
use App\Models\ProductionConsumption;
use App\Models\Recipe;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ProductionPdfTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Prueba que al enviar fechas válidas y existir producciones
     * en ese rango, el controlador devuelva un PDF exitoso.
     *
     * Se valida:
     * - Código de respuesta 200
     * - Cabecera con tipo "application/pdf"
     */
    #[Test]
    public function export_pdf_with_dates()
    {
        $order = Order::create([
            'supplier_name' => 'Proveedor Test',
            'order_date' => now()->subDays(15)->format('Y-m-d'),
            'order_total' => 1000.50
        ]);

        $recipe = Recipe::create([
            'recipe_name' => 'receta test',
            'yield_quantity' => 20
        ]);

        // Crear insumo y lote
        $input = Input::create([
            'name' => 'Insumo Test',
            'category' => 'solido_con'
        ]);

        $batch = InputBatch::create([
            'input_id' => $input->id,
            'order_id' => $order->id,
            'quantity_total' => 50,
            'unit' => 'kg',
            'quantity_remaining' => 50,
            'unit_converted' => 50,
            'unit_price' => 5,
            'subtotal_price' => 250,
            'batch_number' => 2001,
            'received_date' => now()->format('Y-m-d')
        ]);

        $recipe->recipeIngredients()->create([
            'input_id' => $input->id,
            'quantity_required' => 5,
            'unit_used' => 'g'
        ]);

        // Crear producción
        $production = Production::create([
            'recipe_id' => $recipe->id,
            'quantity_to_produce' => 10,
            'price_for_product' => 20,
            'total_cost' => 200,
            'production_date' => now()->subDays(5)->format('Y-m-d')
        ]);

        // Registrar consumo - Asegurar que la relación se llame 'batch'
        ProductionConsumption::create([
            'production_id' => $production->id,
            'input_id' => $input->id,
            'input_batches_id' => $batch->id, // Cambiado a input_batch_id
            'quantity_used' => 10,
            'unit_price' => $batch->unit_price,
            'total_cost' => 50
        ]);

        // Hacer la petición al endpoint
        $requestData = [
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d')
        ];

        $response = $this->postJson('/api/production/export-pdf', $requestData);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }


    /**
     * Prueba que al no enviar parámetros de fechas
     * el controlador devuelva error de validación.
     *
     * Se valida:
     * - Código de respuesta 422
     */
    #[Test]
    public function if_params_not_exist_return_error()
    {
        $response = $this->postJson('/api/production/export-pdf', []);

        $response->assertStatus(422);
    }

    /**
     * Prueba que al enviar fechas donde no existen producciones
     * el controlador devuelva error 404.
     *
     * Se valida:
     * - Código de respuesta 404
     * - Mensaje JSON indicando que no hay producciones
     */
    #[Test]
    public function production_not_found_in_range_dates()
    {
        $requestData = [
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-31'
        ];

        $response = $this->postJson('/api/production/export-pdf', $requestData);

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'No hay producciones en el rango de fechas establecido'
        ]);
    }

    /**
     * Prueba que al enviar una fecha de inicio mayor que la final
     * el controlador devuelva error de validación.
     *
     * Se valida:
     * - Código de respuesta 422
     */

    #[Test]
    public function export_pdf_with_mayor_start_date()
    {
        $requestData = [
            'start_date' => '2025-01-31',
            'end_date' => '2025-01-01'
        ];

        $response = $this->postJson('/api/production/export-pdf', $requestData);

        $response->assertStatus(422);
    }
}
