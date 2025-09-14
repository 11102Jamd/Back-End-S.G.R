<?php

namespace App\Http\Controllers;

use App\Http\Controllers\globalCrud\BaseCrudController;
use Illuminate\Http\Request;
use App\Models\Input;

class InputController extends BaseCrudController
{
    //Para trabajar con la tabla de la base de datos
    protected $model = Input::class;

    //Las reglas de validacion para los insumos
    protected $validationRules = [
        'name' => 'required|string|max:50|unique:input,name',
        'category' => 'required|string|max:20'
    ];

    //Metodo para listar los insumos, permite mostrar los registro de la bd
    public function index()
    {
        try {
            $inputs = $this->model::orderBy('id', 'desc')->get();
            return response()->json($inputs);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'error al obtener la lista de insumos',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Actualiza un insumo existente.
     *
     * Modifica la validación del campo `name` para permitir
     * que el mismo insumo mantenga su nombre sin conflicto de unicidad.
     *
     * @param \Illuminate\Http\Request $request Datos de la solicitud.
     * @param int $id ID del insumo a actualizar.
     * @return \Illuminate\Http\JsonResponse insumo actualizado o error de validación.
     */
    public function update(Request $request, $id)
    {
        try {
            $this->validationRules['name'] = 'required|string|unique:input,name,' . $id;
            return parent::update($request, $id);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'No se pudo actualizar el insumo',
                'message' => $th->getMessage()
            ], 422);
        }
    }

    /**
     * desabilita un insumo en la base de datos pero no lo elimina
     * con el fin dee que si no lo elimina no se eliminen sus registros en cascada
     *
     * @param int $id ID del insumo a inhabilitar.
     * @return \Illuminate\Http\JsonResponse insumo inhabilitado o error de validación.
     */
    public function disable($id)
    {
        try {
            $input = $this->model::findOrFail($id);
            $input->delete();
            return response()->json([
                'message' => 'insumo inhabilitado correctamente',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'error no sep udo encontrar el insumo con ese registro',
                'message' => $th->getMessage()
            ], 404);
        }
    }


    public function enable($id)
    {
        try {
            $input = $this->model::withTrashed()->findOrFail($id);

            if ($input->trashed()) {
                $input->restore();
                return response()->json([
                    'message' => 'insumo reactivado correctamente',
                    'input_id' => $id
                ], 200);
            }

            return response()->json([
                'message' => 'El insumo ya estaba activo',
                'input_id' => $id
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'No se pudo reactivar el insumo',
                'message' => $th->getMessage()
            ], 404);
        }
    }
}
