<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Input;

/**
 * Fábrica para generar instancias de la entidad Input.
 *
 * Esta clase permite crear registros ficticios de insumos
 * para pruebas automatizadas o seeders dentro de la base de datos.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Input>
 */
class InputFactory extends Factory
{
    /**
     * Modelo al que está vinculada la fábrica.
     *
     * @var class-string<\App\Models\Input>
     */
    protected $model = Input::class;

    /**
     * Define el estado predeterminado del modelo Input.
     *
     * Este método genera datos ficticios para los atributos:
     * - name: palabra aleatoria como nombre del insumo.
     * - unit: unidad de medida seleccionada entre kg, lb, l o un.
     *
     * @return array<string, mixed> Datos simulados para un Input.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'unit' => $this->faker->randomElement(['kg', 'lb', 'l', 'un']),
        ];
    }
}
