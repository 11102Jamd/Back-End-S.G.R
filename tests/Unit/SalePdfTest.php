<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Sale;
use App\Models\SaleProduct;
use App\Models\Product;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;

class SalePdfTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Prueba que al enviar fechas válidas y existir ventas
     * en ese rango, el controlador devuelva un PDF exitoso.
     */
    #[Test]
    public function export_pdf_with_dates()
    {
        // Crear usuario
        $user = User::create([
            'name1' => 'Juan',
            'name2' => 'David',
            'surname1' => 'plazas',
            'surname2' => 'hernandez',
            'email' => 'testsale@example.com',
            'rol' => 'Cajero',
            'password' => bcrypt('password'),
        ]);

        // Crear producto
        $product = Product::create([
            'product_name' => 'Producto Test',
            'unit_price' => 25.50
        ]);

        // Crear venta
        $sale = Sale::create([
            'user_id' => $user->id,
            'sale_date' => now()->subDays(5)->format('Y-m-d'),
            'sale_total' => 51.00
        ]);

        // Crear producto de venta
        SaleProduct::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity_requested' => 2,
            'subtotal_price' => $product->unit_price * 2
        ]);

        // Hacer la petición al endpoint
        $requestData = [
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d')
        ];

        $response = $this->postJson('/api/sale/export-pdf', $requestData);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * Prueba que al no enviar parámetros de fechas
     * el controlador devuelva error de validación.
     */
    #[Test]
    public function if_params_not_exist_return_error()
    {
        $response = $this->postJson('/api/sale/export-pdf', []);

        $response->assertStatus(422);
    }

    /**
     * Prueba que al enviar fechas donde no existen ventas
     * el controlador devuelva error 404.
     */
    #[Test]
    public function sales_not_found_in_range_dates()
    {
        $requestData = [
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-31'
        ];

        $response = $this->postJson('/api/sale/export-pdf', $requestData);

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'No hay ventas en el rango de fechas establecido'
        ]);
    }

    /**
     * Prueba que al enviar una fecha de inicio mayor que la final
     * el controlador devuelva error de validación.
     */
    #[Test]
    public function export_pdf_with_mayor_start_date()
    {
        $requestData = [
            'start_date' => '2025-01-31',
            'end_date' => '2025-01-01'
        ];

        $response = $this->postJson('/api/sale/export-pdf', $requestData);

        $response->assertStatus(422);
    }


}
