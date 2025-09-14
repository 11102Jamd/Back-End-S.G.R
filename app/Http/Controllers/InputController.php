<?php

namespace App\Http\Controllers;

use App\Http\Controllers\globalCrud\BaseCrudController;
use Illuminate\Http\Request;
use App\Models\Input;

/**
 * Controlador para la gestión de insumos.
 *
 * Este controlador extiende de BaseCrudController para aprovechar la lógica
 * CRUD genérica, y define reglas de validación y métodos específicos
 * relacionados con la entidad Input.
 *
 * @package App\Http\Controllers
 */
class InputController extends BaseCrudController
{
    /**
     * Modelo asociado al controlador.
     *
     * @var string
     */
    protected $model = Input::class;

    /**
     * Reglas de validación para los insumos.
     *
     * @var array<string, string>
     */
    protected $validationRules = [
        'name' => 'required|string|max:50|unique:input,name',
        'category' => 'required|string|max:20'
    ];

    /**
     * Lista todos los insumos registrados en la base de datos.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $inputs = $this->model::orderBy('id', 'desc')->get();

            return response()->json($inputs);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al obtener la lista de insumos',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Actualiza un insumo existente en la base de datos.
     *
     * Se modifica la regla de validación para permitir nombres únicos,
     * ignorando el insumo actual.
     *
     * @param  \Illuminate\Http\Request  $request  Datos de la petición.
     * @param  int  $id  Identificador del insumo a actualizar.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // Ajusta la regla de validación para permitir nombres únicos excepto el actual
            $this->validationRules['name'] = 'required|string|unique:input,name,' . $id;

            return parent::update($request, $id);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'No se pudo actualizar el insumo',
                'message' => $th->getMessage()
            ], 422);
        }
    }
}
