<?php

namespace Tests\Feature;

use App\Models\Input;
use App\Models\InputBatch;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Services\ProductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

class ProductionServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    protected ProductionService $productionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->productionService = new ProductionService();
    }

    /** @test */
    public function it_can_execute_a_production_successfully()
    {
        $input = Input::factory()->create(['unit' => 'g']);
        //Crea un lote de insumo asociado al insumo creado.
        $batch = InputBatch::factory()->create([
            'input_id' => $input->id,
            'quantity_remaining' => 1000,
            'unit_price' => 2.5,
        ]);

        $recipe = Recipe::factory()->create(['yield_quantity' => 1, 'unit' => 'un']);
        RecipeIngredient::factory()->create([
            'recipe_id' => $recipe->id,
            'input_id' => $input->id,
            'quantity_required' => 200,
        ]);

        $production = $this->productionService->executeProduction($recipe->id, 1);
        //Comprueba que en la tabla production exista un registro con
        $this->assertDatabaseHas('production', [
            'id' => $production->id,
            'total_cost' => 500,
        ]);
        //Verifica que el stock del lote se haya reducido correctamente
        $this->assertEquals(800, $batch->fresh()->quantity_remaining);
    }

    /** @test */
    //verifica que la producción falle si no hay stock suficiente
    public function it_fails_if_not_enough_stock()
    {
        $this->expectException(\Exception::class);
        //Crea un insumo y un lote con menos stock del necesario.
        $input = Input::factory()->create(['unit' => 'g']);
        InputBatch::factory()->create([
            'input_id' => $input->id,
            'quantity_remaining' => 50,
            'unit_price' => 2.5,
        ]);
        //Crea la receta y asocia el insumo necesario
        $recipe = Recipe::factory()->create(['yield_quantity' => 1, 'unit' => 'un']);
        RecipeIngredient::factory()->create([
            'recipe_id' => $recipe->id,
            'input_id' => $input->id,
            'quantity_required' => 200,
        ]);

        $this->productionService->executeProduction($recipe->id, 1);
    }

    /** @test */
    //verifica si al eliminar una producción, el stock se restaura correctamente
    public function it_can_destroy_a_production_and_restore_stock()
    {
        $input = Input::factory()->create(['unit' => 'g']);
        $batch = InputBatch::factory()->create([
            'input_id' => $input->id,
            'quantity_remaining' => 1000,
            'unit_price' => 2.5,
        ]);

        $recipe = Recipe::factory()->create(['yield_quantity' => 1, 'unit' => 'un']);
        RecipeIngredient::factory()->create([
            'recipe_id' => $recipe->id,
            'input_id' => $input->id,
            'quantity_required' => 200,
        ]);
        //Ejecuta la producción y verifica que el stock disminuyó correctamente.
        $production = $this->productionService->executeProduction($recipe->id, 1);
        $this->assertEquals(800, $batch->fresh()->quantity_remaining);

        $this->productionService->destroyProduction($production->id);

        $this->assertEquals(1000, $batch->fresh()->quantity_remaining);
        $this->assertDatabaseMissing('production', ['id' => $production->id]);
    }
}
