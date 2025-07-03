<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\Order\Order;

class OrderController extends BaseCrudController
{
    protected $model = Order::class;

    protected $validationRules = [
        'Id_usuario'   => 'required|exists:usuarios,id',
        'fechaPedido'  => 'required|date',
        'totalPagar'   => 'required|numeric|min:0',
    ];
}
