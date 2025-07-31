<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    // Reglas de validación para creación/actualización
    protected function validationRules($id = null): array
    {
        return [
            'product_name' => [
                'required',
                'string',
                'max:50',
                'unique:products,product_name,'.$id.',id' 
            ],
            'initial_quantity' => [
                'required',
                'integer',
                'min:0' 
            ],
            'current_stock' => [
                'sometimes', 
                'integer',
                'min:0',
                'lte:initial_quantity' 
            ],
            'unit_price' => [
                'required',
                'numeric',
                'min:0.01', 
                'max:999999.99' 
            ]
        ];
    }

    // Listar todos los productos (con filtros opcionales)
    public function index(Request $request)
    {
        try {
            $query = Product::query()
                ->with(['manufacturing', 'orderDetails']) 
                ->latest(); 

            // Filtro por nombre
            if ($request->has('search')) {
                $query->where('product_name', 'like', '%'.$request->search.'%');
            }

            // Filtro por stock mínimo
            if ($request->has('min_stock')) {
                $query->where('current_stock', '>=', $request->min_stock);
            }

            // Paginación o todos los resultados
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

    // Mostrar un producto específico
    public function show($id)
    {
        try {
            $product = Product::with(['manufacturing', 'orderDetails'])
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

    // Crear un nuevo producto
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validar datos de entrada
            $validated = $request->validate($this->validationRules());
            
            // Crear producto
            $product = Product::create($validated);
            
            DB::commit(); 
            
            Log::info('Producto creado', ['id' => $product->id]);

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

    // Actualizar un producto existente
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $product = Product::findOrFail($id);
            
            // Validar datos de entrada (incluyendo el ID para la regla unique)
            $validated = $request->validate($this->validationRules($id));

            // Actualizar producto
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

    // Eliminar un producto
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $product = Product::withCount(['orderDetails', 'manufacturing'])
                ->findOrFail($id);

            // Verificar si tiene registros asociados
            if ($product->order_details_count > 0 || $product->manufacturing_count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar, el producto tiene registros asociados'
                ], 409);
            }

            // Eliminar producto
            $product->delete();
            
            DB::commit();
            
            Log::info('Producto eliminado', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado correctamente'
            ]);

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

    // Obtener información de stock específica
    public function stock($id)
    {
        try {
            $product = Product::with(['manufacturing', 'orderDetails'])
                ->findOrFail($id);

            $stockData = [
                'current' => $product->current_stock,
                'initial' => $product->initial_quantity,
                'reserved' => $product->orderDetails->sum('quantity'),
                'available' => $product->available_stock, // Usa el accesor del modelo
                'used_in_manufacturing' => $product->manufacturing->sum('quantity_used')
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