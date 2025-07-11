<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    
    public function index(Request $request)
    {
        try {
            $query = Product::with(['manufacturing', 'orderDetails'])
                ->latest();

            // Filtros dinámicos
            if ($request->has('name')) {
                $query->where('productName', 'like', '%'.$request->name.'%');
            }

            if ($request->has('min_stock')) {
                $query->where('currentStock', '>=', $request->min_stock);
            }

            if ($request->has('max_price')) {
                $query->where('unityPrice', '<=', $request->max_price);
            }

            $products = $request->has('per_page') 
                ? $query->paginate($request->per_page) 
                : $query->get();

            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Productos obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener productos: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al recuperar los productos',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    
    public function show($id)
    {
        try {
            $product = Product::with(['manufacturing', 'orderDetails.order'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Producto encontrado'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }
    }

    
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate(Product::validationRules());
            
            $product = Product::create($validated);
            
            DB::commit();
            
            Log::info('Producto creado', [
                'id' => $product->id,
                'name' => $product->productName
            ]);

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Producto creado exitosamente'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear producto: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el producto',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $product = Product::findOrFail($id);
            $validated = $request->validate(Product::validationRules($id));

            $product->update($validated);
            
            DB::commit();
            
            Log::info('Producto actualizado', ['id' => $id]);

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Producto actualizado correctamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar producto: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el producto',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $product = Product::withCount(['orderDetails', 'manufacturing'])
                ->findOrFail($id);

            // Verificar si tiene relaciones dependientes
            if ($product->order_details_count > 0 || $product->manufacturing_count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar, el producto tiene registros asociados'
                ], 409);
            }

            $product->delete();
            
            DB::commit();
            
            Log::info('Producto eliminado', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado correctamente'
            ], 204);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar producto: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el producto',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }


    public function stock($id)
    {
        try {
            $product = Product::with(['manufacturing', 'orderDetails'])
                ->findOrFail($id);

            $stockData = [
                'current' => $product->currentStock,
                'initial' => $product->initialQuantity,
                'reserved' => $product->orderDetails->sum('requestedQuantity'),
                'used_in_manufacturing' => $product->manufacturing->sum('ManufactureProductG')
            ];

            return response()->json([
                'success' => true,
                'data' => $stockData,
                'message' => 'Estado de stock obtenido'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener stock: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el stock',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
}