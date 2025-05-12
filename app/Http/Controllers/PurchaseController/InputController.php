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

    public function index()
    {
        $inputs = Inputs::with(['input_order' => function ($search) {
            $search->latest()->take(1);
        }])->orderBy('id', 'desc')->get();

        return response()->json($inputs);
    }
}
