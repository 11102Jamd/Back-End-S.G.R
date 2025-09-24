<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;

use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\Product;
use App\Services\ProductProductionService;

class ProductController extends BaseCrudController
{
    protected $model = Product::class;

    protected $productProductionService;

    protected $validationRules = [
        'product_name' => 'required|string|max:60|unique:product,product_name',
        'unit_price' => 'required|numeric|min:0',
    ];

    public function __construct(ProductProductionService $productProductionService)
    {
        $this->productProductionService = $productProductionService;
    }

    public function index()
    {
        try {
            $product = $this->model::with(['productProductions', 'productProductions.production'])
                ->OrderBy('id', 'desc')
                ->get();

            return response()->json($product);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'No se pudo obtener la lista de Productos',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Actualiza un producto existente.
     *
     * Modifica la validación del campo `product_name` para permitir
     * que el mismo producto mantenga su nombre sin conflicto de unicidad.
     *
     * @param \Illuminate\Http\Request $request Datos de la solicitud.
     * @param int $id ID del producto a actualizar.
     * @return \Illuminate\Http\JsonResponse producto actualizado o error de validación.
     */
    public function update(Request $request, $id)
    {
        try {
            $this->validationRules['product_name'] = 'required|string|unique:product,product_name,' . $id;
            parent::update($request, $id);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'No se pudo actualizar el insumo',
                'message' => $th->getMessage()
            ], 422);
        }
    }

    public function linkProductionToProduct(Request $request)
    {
        $validated = $request->validate([
            'production_id' => 'required|exists:production,id',
            'product_id' => 'required|exists:product,id'
        ]);

        try {
            $result = $this->productProductionService->linkProductionToProduct(
                $validated['production_id'],
                $validated['product_id']
            );

            return response()->json([
                'message' => 'Producción vinculada al producto exitosamente',
                'data' => $result
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Error al vincular producción con producto',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * desabilita un insumo en la base de datos pero no lo elimina
     * con el fin dee que si no lo elimina no se eliminen sus registros en cascada
     *
     * @param int $id ID del insumo a inhabilitar.
     * @return \Illuminate\Http\JsonResponse insumo inhabilitado o error de validación.
     */
    public function disable($id)
    {
        try {
            $product = $this->model::findOrFail($id);
            $product->delete();
            return response()->json([
                'message' => 'producto inhabilitado exitosamente',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'error no se pudo encontrar el producto',
                'message' => $th->getMessage()
            ], 404);
        }
    }


    public function enable($id)
    {
        try {
            $product = $this->model::withTrashed()->findOrFail($id);

            if ($product->trashed()) {
                $product->restore();
                return response()->json([
                    'message' => 'producto reactivado correctamente',
                    'producto_id' => $id
                ], 200);
            }

            return response()->json([
                'message' => 'El producto ya estaba activo',
                'producto_id' => $id
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'No se pudo reactivar el insumo',
                'message' => $th->getMessage()
            ], 404);
        }
    }
}
