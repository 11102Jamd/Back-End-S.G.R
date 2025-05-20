<?php

namespace App\Http\Controllers\PurchaseController;

use App\Http\Controllers\Controller;
use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\PurchaseOrders\Inputs;
use Illuminate\Http\Request;

class InputController extends BaseCrudController
{
    //
    protected $model = Inputs::class;
    protected $validationRules = [
        'InputName' => 'required|string|max:50',
    ];

    //Metodo para obtener la lista de inputs
    public function index()
    {
        //With me permite hacer la relacion con los datos principales, al campo de la tabla lo relaciono con la funcion y le paso el parametro buscar.
        $inputs = Inputs::with(['inputOrders' => function ($query) {
            //latest ordena los input_order por los más recientes.
            $query->latest()->take(1);
            //orderBy organiza por id y en forma descendiente los inputs
        }])->orderBy('id', 'desc')->get();

        //Devuelve por parametro los inputs en formato json
        return response()->json($inputs);
    }
    
}
