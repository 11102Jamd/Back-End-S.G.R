<?php

namespace App\Http\Controllers\PurchaseController;

use App\Http\Controllers\Controller;
use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\PurchaseOrders\Supplier;
use Illuminate\Http\Request;

class SupplierController extends BaseCrudController
{
    //logica de validacion
    protected $model = Supplier::class;
    protected $validationRules =[
        'name'=>'required|string|max:50',
        'email'=>'required|email|unique:supplier,email',
        'Addres'=>'required|string|max:255',
        'Phone'=>'required|string|max:10'
    ];

    public function update(Request $request, $id)
    {
        $this->validationRules['email'] = 'required|email|unique:users,email,'.$id;
        return parent::update($request, $id);
    }
}
