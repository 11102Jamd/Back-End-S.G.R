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
        'InitialQuantity' => 'required|integer|max:10|min:1',
        'UnitMeasurement' => 'required|string|max:2|min:1',
        'CurrentStock' => 'required|integer|min:1',
        'UnitMeasurementGrams' => 'required|string|max:1',
        'UnityPrice' => 'required|numeric|min:0'
    ];


}
