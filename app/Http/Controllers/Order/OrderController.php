<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    
    public function index(Request $request)
    {
        try {
            $query = Order::with(['user', 'orderDetail.product'])
                ->latest();

            // Filtros dinámicos
            if ($request->has('user_id')) {
                $query->where('ID_user', $request->user_id);
            }

            if ($request->has('date_from')) {
                $query->where('orderDate', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('orderDate', '<=', $request->date_to);
            }

            $orders = $request->has('per_page') 
                ? $query->paginate($request->per_page) 
                : $query->get();

            return response()->json([
                'success' => true,
                'data' => $orders
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching orders: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al recuperar los pedidos'
            ], 500);
        }
    }

    
    public function show($id)
    {
        try {
            $order = Order::with(['user', 'orderDetail.product'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $order
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        }
    }

    
    public function store(Request $request)
    {
        $validated = $request->validate(Order::validationRules());

        DB::beginTransaction();
        try {
            $order = Order::create($validated);
            
            DB::commit();
            
            Log::info("Pedido creado exitosamente", [
                'order_id' => $order->id,
                'user_id' => $validated['ID_user'] ?? null
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Pedido creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Error al crear pedido: " . $e->getMessage(), [
                'error' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $order = Order::findOrFail($id);
            $validated = $request->validate(Order::validationRules($id));

            $order->update($validated);
            
            DB::commit();
            
            Log::info("Pedido actualizado", ['order_id' => $id]);
            
            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Pedido actualizado correctamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Error al actualizar pedido: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el pedido',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $order = Order::findOrFail($id);
            $order->delete();
            
            DB::commit();
            
            Log::info("Pedido eliminado", ['order_id' => $id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Pedido eliminado correctamente'
            ], 204);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Error al eliminar pedido: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el pedido',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }


    public function recent($days = 30)
    {
        try {
            $orders = Order::recent($days)
                ->with(['user', 'orderDetail.product'])
                ->get();
                
            return response()->json([
                'success' => true,
                'data' => $orders,
                'message' => 'Pedidos recientes obtenidos'
            ]);

        } catch (\Exception $e) {
            Log::error("Error al obtener pedidos recientes: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedidos recientes'
            ], 500);
        }
    }
}