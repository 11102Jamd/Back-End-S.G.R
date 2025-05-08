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
}
