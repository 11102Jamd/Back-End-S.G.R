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
    public function index()
    {
        try {
            $details = OrderDetail::with(['order.user', 'product'])
                ->latest()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $details
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching order details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al recuperar detalles'
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $detail = OrderDetail::with(['order.user', 'product'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $detail
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Detalle no encontrado'
            ], 404);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate(OrderDetail::validationRules());

        DB::beginTransaction();
        try {
            // Verificar existencia de pedido y producto
            $order = Order::find($validated['ID_order']);
            $product = Product::find($validated['ID_product']);

            if (!$order || !$product) {
                return response()->json([
                    'success' => false,
                    'message' => $order ? 'Producto no encontrado' : 'Pedido no encontrado'
                ], 404);
            }

            // Verificar stock
            if ($product->currentStock < $validated['requestedQuantity']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente'
                ], 400);
            }

            $detail = OrderDetail::create($validated);
            $order->update(['orderTotal' => $order->calculateTotal()]);

            DB::commit();

            Log::info('Detalle creado', [
                'id' => $detail->id,
                'order_id' => $order->id,
                'product_id' => $product->id
            ]);

            return response()->json([
                'success' => true,
                'data' => $detail,
                'message' => 'Detalle creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear detalle: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear detalle',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $detail = OrderDetail::findOrFail($id);
            $validated = $request->validate(OrderDetail::validationRules($id));

            $product = Product::find($validated['ID_product']);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            // Verificar stock (considerando la cantidad actual)
            $quantityDifference = $validated['requestedQuantity'] - $detail->requestedQuantity;
            if ($product->currentStock < $quantityDifference) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente'
                ], 400);
            }

            $detail->update($validated);
            
            if ($order = Order::find($detail->ID_order)) {
                $order->update(['orderTotal' => $order->calculateTotal()]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $detail,
                'message' => 'Detalle actualizado'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar detalle: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar detalle'
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $detail = OrderDetail::findOrFail($id);
            $orderId = $detail->ID_order;
            $detail->delete();

            if ($order = Order::find($orderId)) {
                $order->update(['orderTotal' => $order->calculateTotal()]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Detalle eliminado'
            ], 204);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar detalle: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar detalle'
            ], 500);
        }
    }
}