<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\RecipeService;
use App\Models\Recipe;
use App\Models\Input;

class RecipeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RecipeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(RecipeService::class);
    }

    /** @test */
    public function it_can_create_a_recipe_with_ingredients()
    {
        $input1 = Input::factory()->create(['unit' => 'kg']);
        $input2 = Input::factory()->create(['unit' => 'kg']);

        $data = [
            'recipe_name' => 'Artisan Bread',
            'yield_quantity' => 20,
            'unit' => 'units',
            'ingredient' => [
                ['input_id' => $input1->id, 'quantity_required' => 2.5],
                ['input_id' => $input2->id, 'quantity_required' => 1.0],
            ]
        ];

        $recipe = $this->service->createRecipe($data);

        $this->assertDatabaseHas('recipe', [
            'id' => $recipe->id,
            'recipe_name' => 'Artisan Bread'
        ]);

        $this->assertDatabaseHas('recipe_ingredients', [
            'recipe_id' => $recipe->id,
            'input_id' => $input1->id,
            'quantity_required' => 2.5
        ]);
        $this->assertCount(2, $recipe->recipeIngredients);
    }

    /** @test */
    public function it_can_update_a_recipe_and_its_ingredients()
    {
        $input = Input::factory()->create(['unit' => 'kg']);
        $recipe = Recipe::factory()->create();
        $recipe->recipeIngredients()->create([
            'input_id' => $input->id,
            'quantity_required' => 2.0
        ]);

        $newInput = Input::factory()->create(['unit' => 'kg']);

        $updateData = [
            'recipe_name' => 'Whole Wheat Bread',
            'yield_quantity' => 15,
            'unit' => 'units',
            'ingredient' => [
                ['input_id' => $newInput->id, 'quantity_required' => 3.0]
            ]
        ];

        $this->service->updateRecipe($recipe->id, $updateData);

        $this->assertDatabaseHas('recipe', [
            'id' => $recipe->id,
            'recipe_name' => 'Whole Wheat Bread'
        ]);

        $this->assertDatabaseHas('recipe_ingredients', [
            'recipe_id' => $recipe->id,
            'input_id' => $newInput->id,
            'quantity_required' => 3.0
        ]);
    }

    /** @test */
    public function it_can_delete_a_recipe_with_its_ingredients()
    {
        $input = Input::factory()->create(['unit' => 'kg']);
        $recipe = Recipe::factory()->create();
        $recipe->recipeIngredients()->create([
            'input_id' => $input->id,
            'quantity_required' => 1.5
        ]);

        $this->service->deleteRecipe($recipe->id);

        $this->assertDatabaseMissing('recipe', ['id' => $recipe->id]);
        $this->assertDatabaseMissing('recipe_ingredients', ['recipe_id' => $recipe->id]);
    }
}
