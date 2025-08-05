<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    // Método para reglas de validación reutilizable
    protected function getValidationRules($id = null): array
    {
        return [
            'ProductName' => [
                'required',
                'string',
                'max:50',
                Rule::unique('product', 'ProductName')->ignore($id)
            ],
            'InitialQuantity' => 'required|integer|min:0',
            'CurrentStock' => 'sometimes|integer|min:0|lte:InitialQuantity',
            'UnityPrice' => 'required|numeric|min:0.01|max:999999.99'
        ];
    }

    // Respuestas API estandarizadas
    protected function apiSuccess($data = null, string $message = '', int $code = Response::HTTP_OK)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message
        ], $code);
    }

    protected function apiError(string $message, $error = null, int $code = Response::HTTP_INTERNAL_SERVER_ERROR)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => app()->isLocal() ? $error : null
        ], $code);
    }

    // CRUD Methods
    public function index(Request $request)
    {
        try {
            $query = Product::with(['manufacturing', 'orderDetails'])
                ->latest();

            // Filtros
            $query->when($request->search, fn($q, $search) => 
                $q->where('ProductName', 'like', "%{$search}%"));
                
            $query->when($request->min_stock, fn($q, $stock) => 
                $q->where('CurrentStock', '>=', $stock));

            $products = $request->per_page ? $query->paginate($request->per_page) : $query->get();

            return $this->apiSuccess($products, 'Productos obtenidos exitosamente');

        } catch (\Exception $e) {
            Log::error("ProductController@index - Error: {$e->getMessage()}");
            return $this->apiError('Error al obtener productos', $e->getMessage());
        }
    }

    // Mostrar un producto específico
    public function show($id)
    {
        try {
            $product = Product::with(['manufacturing', 'orderDetails'])->findOrFail($id);
            return $this->apiSuccess($product, 'Producto encontrado');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->apiError('Producto no encontrado', null, Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            Log::error("ProductController@show - Error: {$e->getMessage()}");
            return $this->apiError('Error al buscar producto', $e->getMessage());
        }
    }

    // Crear un nuevo producto
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate($this->getValidationRules());
            
            // Verificar si el producto ya existe
            $product = Product::create($validated);
            
            DB::commit();
            Log::info("Producto creado - ID: {$product->id}");

            return $this->apiSuccess($product, 'Producto creado exitosamente', Response::HTTP_CREATED);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->apiError('Error de validación', $e->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("ProductController@store - Error: {$e->getMessage()}");
            return $this->apiError('Error al crear producto', $e->getMessage());
        }
    }


    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $product = Product::findOrFail($id);
            $validated = $request->validate($this->getValidationRules($id));

            $product->update($validated);
            
            DB::commit();
            Log::info("Producto actualizado - ID: {$id}");

            return $this->apiSuccess($product, 'Producto actualizado correctamente');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->apiError('Producto no encontrado', null, Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("ProductController@update - Error: {$e->getMessage()}");
            return $this->apiError('Error al actualizar producto', $e->getMessage());
        }
    }


    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $product = Product::withCount(['orderDetails', 'manufacturing'])->findOrFail($id);


            // Verificar si tiene detalles de pedido o fabricación
            if ($product->order_details_count > 0 || $product->manufacturing_count > 0) {
                return $this->apiError(
                    'No se puede eliminar, el producto tiene registros asociados',
                    null,
                    Response::HTTP_CONFLICT
                );
            }

            $product->delete();
            
            DB::commit();
            Log::info("Producto eliminado - ID: {$id}");

            return $this->apiSuccess(null, 'Producto eliminado correctamente');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->apiError('Producto no encontrado', null, Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("ProductController@destroy - Error: {$e->getMessage()}");
            return $this->apiError('Error al eliminar producto', $e->getMessage());
        }
    }

    // Método para obtener el estado del stock
    public function stock($id)
    {
        try {
            $product = Product::with(['manufacturing', 'orderDetails'])->findOrFail($id);

            $stockData = [
                'current' => $product->CurrentStock,
                'initial' => $product->InitialQuantity,
                'reserved' => $product->orderDetails->sum('quantity'),
                'available' => $product->available_stock,
                'used_in_manufacturing' => $product->manufacturing->sum('quantity_used')
            ];

            return $this->apiSuccess($stockData, 'Estado de stock obtenido');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->apiError('Producto no encontrado', null, Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            Log::error("ProductController@stock - Error: {$e->getMessage()}");
            return $this->apiError('Error al obtener stock', $e->getMessage());
        }
    }
}