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
        'unit' => 'required|string|max:10'
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

    //Metodo que permite actualizar un insumo existente en la bd
    public function update(Request $request, $id)
    {
        try {
            $this->validationRules['name'] = 'required|string|unique:input,name,' . $id;
            //sobreescribir para generar la actualizacion en la bd
            parent::update($request, $id);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'No se pudo actualizar el insumo',
                'message' => $th->getMessage()
            ], 422);
        }
    }
}
