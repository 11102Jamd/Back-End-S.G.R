<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Production;
use App\Models\ProductProduction;
use App\Models\User;
use App\Services\ProductProductionService;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use DatabaseTransactions;
    /**
     * Creacion de un producto
     * Vlidamos que un producto pueda ser creado verficando el rol del usuario que lo crea
     */
    #[Test]
    public function test_admin_can_create_product()
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);

        $token = $admin->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/product', [
                'product_name' => 'Pan francés',
                'unit_price' => 1200
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['product_name' => 'Pan francés']);
    }

    #[Test]
    public function test_baker_can_create_product()
    {
        $baker = User::factory()->create(['rol' => 'Panadero']);

        $token = $baker->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/product', [
                'product_name' => 'Pan de Coco',
                'unit_price' => 600
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['product_name' => 'Pan de Coco']);
    }

    #[Test]
    public function test_chashier_cannot_create_product()
    {
        $cashier = User::factory()->create(['rol' => 'Cajero']);

        $token = $cashier->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/product', [
                'product_name' => 'product',
                'unit_price' => 600
            ]);
        $response->assertStatus(403);
    }


    /**
     * Pruebas unitarias para abastecer producto
     */
    protected ProductProductionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductProductionService();
    }

    public function test_it_links_production_to_product_and_calculates_margin()
    {
        $product = Product::factory()->create(['unit_price' => 2000]);
        $production = Production::factory()->create([
            'price_for_product' => 1000,
            'quantity_to_produce' => 50
        ]);

        $result = $this->service->linkProductionToProduct($production->id, $product->id);

        $this->assertDatabaseHas('product_production', [
            'production_id' => $production->id,
            'product_id' => $product->id,
            'quantity_produced' => 50
        ]);

        $this->assertEquals(50.0, $result['profit_margin_percentage']); // (2000-1000)/2000 * 100 = 50%
        $this->assertInstanceOf(ProductProduction::class, $result['product_production']);
    }

    public function test_calculate_profit_margin_percentage_handles_zero_cost()
    {
        $margin = $this->service->calculateProfitMarginPercentage(0, 2000);
        $this->assertEquals(0, $margin);
    }

    public function test_calculate_profit_margin_percentage_correct_calculation()
    {
        $margin = $this->service->calculateProfitMarginPercentage(1200, 2000);
        $this->assertEquals(40.0, $margin); // (2000-1200)/2000*100 = 40%
    }

    /**
     * Validamos que nuestros roles administrador y panadero son los unico que permiten realizar
     * el abastecimiento de un produco
     */

    #[Test]
    public function test_admin_can_link_production_to_product()
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $token = $admin->createToken('auth_token')->plainTextToken;

        $product = Product::factory()->create(['unit_price' => 2000]);
        $production = Production::factory()->create([
            'price_for_product' => 1000,
            'quantity_to_produce' => 50
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/products/link-production', [
                'production_id' => $production->id,
                'product_id' => $product->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'message' => 'Producción vinculada al producto exitosamente',
            ]);
    }

    #[Test]
    public function test_baker_can_link_production_to_product()
    {
        $baker = User::factory()->create(['rol' => 'Panadero']);
        $token = $baker->createToken('auth_token')->plainTextToken;

        $product = Product::factory()->create(['unit_price' => 2000]);
        $production = Production::factory()->create([
            'price_for_product' => 1000,
            'quantity_to_produce' => 50
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/products/link-production', [
                'production_id' => $production->id,
                'product_id' => $product->id,
            ]);

        $response->assertStatus(201);
    }

    #[Test]
    public function test_cashier_cannot_link_production_to_product()
    {
        $cashier = User::factory()->create(['rol' => 'Cajero']);
        $token = $cashier->createToken('auth_token')->plainTextToken;

        $product = Product::factory()->create(['unit_price' => 2000]);
        $production = Production::factory()->create([
            'price_for_product' => 1000,
            'quantity_to_produce' => 50
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/products/link-production', [
                'production_id' => $production->id,
                'product_id' => $product->id,
            ]);

        $response->assertStatus(403); // Prohibido
    }

    /**
     * Solos el rol administrador puede actualizar un producto
     */
    #[Test]
    public function test_admin_can_update_product()
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);

        $token = $admin->createToken('auth_token')->plainTextToken;

        $product = Product::factory()->create([
            'product_name' => 'Pan francés',
            'unit_price' => 1200
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/product/{$product->id}", [
                'product_name' => 'Pan francés con queso',
                'unit_price' => 1500
            ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function test_baker_cannot_update_product()
    {
        $baker = User::factory()->create(['rol' => 'Panadero']);

        $token = $baker->createToken('auth_token')->plainTextToken;

        $product = Product::factory()->create([
            'product_name' => 'Pan francés',
            'unit_price' => 1200
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/product/{$product->id}", [
                'product_name' => 'Pan francés con queso',
                'unit_price' => 1500
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function test_admin_can_disable_product()
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);

        $token = $admin->createToken('auth_token')->plainTextToken;

        $product = Product::factory()->create([
            'product_name' => 'Pan de yuca',
            'unit_price' => 1200
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->patchJson("/api/product/{$product->id}/disable", [
                'product_name' => 'Pan francés con queso',
                'unit_price' => 1500
            ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function test_baker_cannot_disable_product()
    {
        $baker = User::factory()->create(['rol' => 'Panadero']);

        $token = $baker->createToken('auth_token')->plainTextToken;

        $product = Product::factory()->create([
            'product_name' => 'Pan de yuca',
            'unit_price' => 1200
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->patchJson("/api/product/{$product->id}/disable", [
                'product_name' => 'Pan francés con queso',
                'unit_price' => 1500
            ]);

        $response->assertStatus(403);
    }
}
