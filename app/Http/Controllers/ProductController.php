<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;

use App\Http\Controllers\globalCrud\BaseCrudController;


class Product extends BaseCrudController
{
    protected $model = Product::class;
    protected $validationRules = [
        'produc_name' => 'required|string|max:255|unique:product,name',
        'unit_price' => 'required|numeric|min:0',
    ];

    public function index()
    {
        try {
            $product = $this->model::with(['productProductions' => function ($query) {
                $query->orderBy('id', 'desc')->take(1);
            }, 'productProductions.production'])
                ->orderBy('id', 'desc')
                ->first();

            return response()->json($product);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'No se pudo obtener la lista de Productos',
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
