<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductProduction;
use App\Models\Sale;
use App\Models\SaleProduct;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase;

    private SaleService $saleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->saleService = new SaleService();
    }

    #[Test]
    public function it_registers_a_sale_and_updates_stock()
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['unit_price' => 100]);

        // Producción disponible: 10 unidades
        ProductProduction::factory()->create([
            'product_id' => $product->id,
            'quantity_produced' => 10
        ]);

        $saleData = [
            'user_id' => $user->id,
            'products' => [
                ['product_id' => $product->id, 'quantity_requested' => 5]
            ]
        ];

        // Act
        $result = $this->saleService->registerSale($saleData);

        // Assert
        $this->assertDatabaseHas('sale', [
            'id' => $result['sale']->id,
            'sale_total' => 500
        ]);

        $this->assertDatabaseHas('sale_product', [
            'sale_id' => $result['sale']->id,
            'product_id' => $product->id,
            'quantity_requested' => 5,
            'subtotal_price' => 500
        ]);

        $this->assertEquals(5, ProductProduction::first()->quantity_produced);
    }

    #[Test]
    public function it_fails_if_stock_is_not_enough()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['unit_price' => 50]);

        // Stock insuficiente (solo 2 unidades)
        ProductProduction::factory()->create([
            'product_id' => $product->id,
            'quantity_produced' => 2
        ]);

        $saleData = [
            'user_id' => $user->id,
            'products' => [
                ['product_id' => $product->id, 'quantity_requested' => 5]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Stock insuficiente");

        $this->saleService->registerSale($saleData);
    }

    #[Test]
    public function test_admin_can_create_sale()
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $token = $admin->createToken('auth_token')->plainTextToken;

        $product = Product::factory()->create(['unit_price' => 100]);
        ProductProduction::factory()->create([
            'product_id' => $product->id,
            'quantity_produced' => 10
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/sale', [
                'user_id' => $admin->id,
                'products' => [
                    ['product_id' => $product->id, 'quantity_requested' => 5]
                ]
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['sale_total' => 500]);
    }

    #[Test]
    public function test_cashier_can_create_sale()
    {
        $cashier = User::factory()->create(['rol' => 'Cajero']);
        $token = $cashier->createToken('auth_token')->plainTextToken;

        $product = Product::factory()->create(['unit_price' => 200]);
        ProductProduction::factory()->create([
            'product_id' => $product->id,
            'quantity_produced' => 8
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/sale', [
                'user_id' => $cashier->id,
                'products' => [
                    ['product_id' => $product->id, 'quantity_requested' => 2]
                ]
            ]);


        $response->assertStatus(201)
            ->assertJsonFragment(['sale_total' => 400]);
    }

    #[Test]
    public function test_baker_cannot_create_sale()
    {
        $baker = User::factory()->create(['rol' => 'Panadero']);
        $token = $baker->createToken('auth_token')->plainTextToken;

        $product = Product::factory()->create(['unit_price' => 150]);
        ProductProduction::factory()->create([
            'product_id' => $product->id,
            'quantity_produced' => 5
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/sale', [
                'user_id' => $baker->id,
                'products' => [
                    ['product_id' => $product->id, 'quantity_requested' => 2]
                ]
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function test_admin_can_delete_sale()
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);

        $token = $admin->createToken('auth_token')->plainTextToken;

        $product = Product::factory()->create([
            'product_name' => 'Pan de yuca',
            'unit_price' => 1200
        ]);

        // Creamos una venta asociada
        $sale = Sale::factory()->create([
            'user_id' => $admin->id,
            'sale_total' => 1200,
            'sale_date' => now(),
        ]);

        // Relacionamos producto con venta
        SaleProduct::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity_requested' => 1,
            'subtotal_price' => 1200,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/sale/{$sale->id}");

        $response->assertStatus(200);
    }

    #[Test]
    public function test_cashier_cannot_delete_sale()
    {
        $cashier = User::factory()->create(['rol' => 'Cajero']);

        $token = $cashier->createToken('auth_token')->plainTextToken;

        $product = Product::factory()->create([
            'product_name' => 'Pan de yuca',
            'unit_price' => 1200
        ]);

        // Creamos una venta asociada
        $sale = Sale::factory()->create([
            'user_id' => $cashier->id,
            'sale_total' => 1200,
            'sale_date' => now(),
        ]);

        // Relacionamos producto con venta
        SaleProduct::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity_requested' => 1,
            'subtotal_price' => 1200,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/sale/{$sale->id}");

        $response->assertStatus(403);
    }

    // #[Test]
    // public function test_baker_cannot_disable_product()
    // {
    //     $baker = User::factory()->create(['rol' => 'Panadero']);

    //     $token = $baker->createToken('auth_token')->plainTextToken;

    //     $product = Product::factory()->create([
    //         'product_name' => 'Pan de yuca',
    //         'unit_price' => 1200
    //     ]);

    //     $response = $this->withHeader('Authorization', "Bearer $token")
    //         ->patchJson("/api/product/{$product->id}/disable", [
    //             'product_name' => 'Pan francés con queso',
    //             'unit_price' => 1500
    //         ]);

    //     $response->assertStatus(403);
    // }
}
