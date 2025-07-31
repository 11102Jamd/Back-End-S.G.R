<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    // Reglas de validación centralizadas
    protected function validationRules($id = null): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'order_date' => 'sometimes|date|before_or_equal:now',
            'order_total' => 'sometimes|numeric|min:0.01|max:999999.99',
            'status' => 'sometimes|string|max:50'
        ];
    }

    // Listar pedidos
    public function index(Request $request)
    {
        try {
            $query = Order::with(['user', 'orderDetails.product'])
                ->latest();

            // Filtros dinámicos
            $query->when($request->user_id, fn($q, $id) => $q->where('user_id', $id))
                ->when($request->date_from, fn($q, $date) => $q->where('order_date', '>=', $date))
                ->when($request->date_to, fn($q, $date) => $q->where('order_date', '<=', $date))
                ->when($request->status, fn($q, $status) => $q->where('status', $status));

            // Paginación o todos
            $orders = $request->has('per_page') 
                ? $query->paginate($request->per_page) 
                : $query->get();

            return response()->json([
                'success' => true,
                'data' => $orders,
                'message' => 'Pedidos obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener pedidos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al recuperar pedidos',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    // Mostrar un pedido específico
    public function show($id)
    {
        try {
            $order = Order::with(['user', 'orderDetails.product'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Pedido encontrado'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        }
    }

    // Crear nuevo pedido
    public function store(Request $request)
    {
        
        DB::beginTransaction();
        try {
            $validated = $request->validate($this->validationRules());
            
            $order = Order::create($validated);
            
            DB::commit();
            
            Log::info('Pedido creado', [
                'id' => $order->id,
                'user_id' => $order->user_id,
                'status' => $order->status
            ]);

            return response()->json([
                'success' => true,
                'data' => $order->load('user', 'orderDetails.product'),
                'message' => 'Pedido creado exitosamente'
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
            Log::error('Error al crear pedido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear pedido',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    // Actualizar pedido
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $order = Order::findOrFail($id);
            $validated = $request->validate($this->validationRules($id));

            $order->update($validated);
            
            DB::commit();
            
            Log::info('Pedido actualizado', ['id' => $id, 'status' => $order->status]);

            return response()->json([
                'success' => true,
                'data' => $order->fresh(['user', 'orderDetails.product']),
                'message' => 'Pedido actualizado correctamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar pedido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar pedido',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    // Eliminar pedido
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $order = Order::findOrFail($id);

            $order->delete();
            
            DB::commit();
            
            Log::info('Pedido eliminado', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido eliminado correctamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar pedido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar pedido',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    // Pedidos recientes
    public function recent($days = 30)
    {
        try {
            $orders = Order::recent($days)
                ->with(['user', 'orderDetails.product'])
                ->get();
                
            return response()->json([
                'success' => true,
                'data' => $orders,
                'message' => 'Pedidos recientes obtenidos'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener pedidos recientes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedidos recientes',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
}