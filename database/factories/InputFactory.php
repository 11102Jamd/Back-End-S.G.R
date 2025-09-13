<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Input;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Input>
 */
class InputFactory extends Factory
{
    //Especifica que esta que esta fabrica esta vinculada al modelo Input
    protected $model = Input::class;

    //Define el estado predeterminado del modelo
    public function definition(): array
    {
        return [
            //Genera un nombre aleatorio para el insumo
            'name' => $this->faker->word(), //Nombre del insumo como palabra aleatoria

            //Selecciona aleatoriamente una unidad de medida entre las opciones especificadas
            'unit' => $this->faker->randomElement(['kg', 'lb', 'l', 'un']) //Unidades de medida
        ];
    }
}
