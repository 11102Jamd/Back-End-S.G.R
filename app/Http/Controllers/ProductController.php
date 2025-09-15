<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Product;
use App\Http\Controllers\globalCrud\BaseCrudController;


class ProductController extends BaseCrudController
{
    protected $model = Product::class;
    protected $validationRules = [
        'product_name' => 'required|string|max:255|unique:product,product_name',
        'unit_price' => 'required|numeric|min:0',
    ];

    public function index()
    {
        try {
           $product = $this->model::with(['productProductions', 'productProductions.production'])
                ->orderBy('id', 'desc')
                ->get();

            return response()->json($product);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'No se pudo obtener la lista de Productos',
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
