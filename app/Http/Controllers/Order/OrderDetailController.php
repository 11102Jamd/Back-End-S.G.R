<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\OrderDetail;
use App\Models\Order\Order;
use App\Models\Order\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderDetailController extends Controller
{
    // Reglas de validación centralizadas en el controlador
    protected function validationRules($id = null): array
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'product_id' => 'required|exists:products,id',
            'requested_quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0.01',
        ];
    }

    // Listar todos los detalles de pedido
    public function index()
    {
        try {
            $details = OrderDetail::with([
                    'order.customer', // Asumiendo relación con cliente
                    'product'
                ])
                ->latest()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $details,
                'message' => 'Detalles de pedido obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener detalles: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al recuperar detalles',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    // Mostrar un detalle específico
    public function show($id)
    {
        try {
            $detail = OrderDetail::with(['order.customer', 'product'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $detail,
                'message' => 'Detalle de pedido encontrado'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Detalle de pedido no encontrado'
            ], 404);
        }
    }

    // Crear un nuevo detalle de pedido
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate($this->validationRules());
            
            // Verificar existencia de pedido y producto
            $order = Order::findOrFail($validated['order_id']);
            $product = Product::findOrFail($validated['product_id']);

            // Validar stock disponible
            if ($product->current_stock < $validated['requested_quantity']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente',
                    'available_stock' => $product->current_stock
                ], 400);
            }

            // Crear el detalle
            $detail = OrderDetail::create($validated);
            
            // Actualizar stock del producto
            $product->decrement('current_stock', $validated['requested_quantity']);
            
            // Actualizar total del pedido
            $order->refreshTotal();

            DB::commit();

            Log::info('Detalle de pedido creado', [
                'id' => $detail->id,
                'order_id' => $order->id,
                'product_id' => $product->id
            ]);

            return response()->json([
                'success' => true,
                'data' => $detail,
                'message' => 'Detalle de pedido creado exitosamente'
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
            Log::error('Error al crear detalle: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear detalle de pedido',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    // Actualizar un detalle existente
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $detail = OrderDetail::findOrFail($id);
            $validated = $request->validate($this->validationRules($id));

            $product = Product::findOrFail($validated['product_id']);
            $order = Order::findOrFail($validated['order_id']);

            // Calcular diferencia de cantidad
            $quantityDifference = $validated['requested_quantity'] - $detail->requested_quantity;
            
            // Validar stock si se aumenta la cantidad
            if ($quantityDifference > 0 && $product->current_stock < $quantityDifference) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente',
                    'available_stock' => $product->current_stock,
                    'required_additional' => $quantityDifference
                ], 400);
            }

            // Actualizar detalle
            $detail->update($validated);
            
            // Ajustar stock del producto
            $product->decrement('current_stock', $quantityDifference);
            
            // Actualizar total del pedido
            $order->refreshTotal();

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $detail,
                'message' => 'Detalle de pedido actualizado correctamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar detalle: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar detalle de pedido',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    // Eliminar un detalle
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $detail = OrderDetail::findOrFail($id);
            $product = $detail->product;
            $order = $detail->order;
            
            // Eliminar el detalle
            $detail->delete();
            
            // Restaurar stock al producto
            $product->increment('current_stock', $detail->requested_quantity);
            
            // Actualizar total del pedido
            $order->refreshTotal();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Detalle de pedido eliminado correctamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar detalle: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar detalle de pedido',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
}