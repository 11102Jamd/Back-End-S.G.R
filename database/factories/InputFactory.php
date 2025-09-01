<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Input;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Input>
 */
class InputFactory extends Factory
{
    protected $model = Input::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'unit' => $this->faker->randomElement(['kg', 'g', 'L', 'ml']),
        ];
    }
}
