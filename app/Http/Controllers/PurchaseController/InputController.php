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
            // tu controlador estaba casi bien, el probelmea era como llamabas la relaicon:
            /**
             * Cuando creas un relacion en laravel es el metodo
             * el problema fue que la colocaste como el nombre de la tabla y debe ser el nombre de la relacion
             * input_order = incorrecto --- inputOrders() = correcto
             */


            //latest ordena los input_order por los más recientes.
            $query->latest()->get()->take(1);
            //orderBy organiza por id y en forma descendiente los inputs
        }])->orderBy('id', 'desc')->get();

        //Devuelve por parametro los inputs en formato json
        return response()->json($inputs);
    }
}
