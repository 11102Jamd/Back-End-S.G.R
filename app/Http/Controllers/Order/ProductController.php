<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\CrudController;
use App\Models\order\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

    class ProductController extends CrudController
{
    //Modelo principal que usará el CrudController//
     
    protected $model = Product::class;

    // Nombre legible del recurso para respuestas genéricas//
    protected $modelName = 'Producto';

    // Relaciones Eloquent que se cargarán automáticamente//
    protected $relations = ['orderDetails', 'manufacturings'];

    // Validación personalizada al crear o actualizar un producto//
    protected function validateRequest(Request $request, $id = null)
    {
        return $request->validate([
            'name'           => 'required|string|max:255',
            'initialQuantity'=> 'required|numeric|min:0',
            'unitPrice'      => 'required|numeric|min:0',
            'currentStock'   => 'required|numeric|min:0',
        ]);
    }

    // Sobrescribimos store de CrudController con firma compatible//
    public function store(Request $request, $tabla = null)
    {
        $validated = $this->validateRequest($request);

        try {
            $product = $this->model::create($validated);

            Log::info("Producto creado: {$product->id} - {$product->name}");

            return response()->json([
                'message' => "{$this->modelName} creado correctamente",
                'data'    => $product
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            Log::error("Error al crear {$this->modelName}: {$e->getMessage()}");
            return response()->json(['error' => 'Error interno'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
