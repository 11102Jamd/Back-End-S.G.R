<?php

namespace App\Http\Controllers;

use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends BaseCrudController
{
    protected $model = User::class;

    protected $validationRules = [
        'name1' => 'required|string|max:50',
        'name2' => 'required|string|max:50',
        'surname1' => 'required|string|max:50',
        'surname2' => 'required|string|max:50',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:8',
        'rol' => 'required|string|max:20'
    ];



    public function update(Request $request, $id)
    {
        try {
            $this->validationRules['email'] = 'required|email|unique:users,email,' . $id;
            return parent::update($request, $id);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'No se pudo actualizar el usuario',
                'message' => $th->getMessage()
            ], 422);
        }
    }
}
