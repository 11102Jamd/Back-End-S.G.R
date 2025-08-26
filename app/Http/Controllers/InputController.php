<?php

namespace App\Http\Controllers;

use App\Http\Controllers\globalCrud\BaseCrudController;
use Illuminate\Http\Request;
use App\Models\Input;

class InputController extends BaseCrudController
{
    //
    protected $model = Input::class;
    protected $validationRules = [
        'name' => 'required|string|max:50|unique:input,name',
        'unit' => 'required|string|max:10'
    ];

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

    public function update(Request $request, $id)
    {
        try {
            $this->validationRules['name'] = 'required|string|unique:input,name,' . $id;
            parent::update($request, $id);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'No se pudo actualizar el insumo',
                'message' => $th->getMessage()
            ], 422);
        }
    }
}
