<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\{Order, OrderDetail, Product};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{DB, Log};


class OrderController extends Controller
{
    // Configuración centralizada
    protected $relations = ['user', 'details.product'];
    protected $detailRelations = ['order', 'product'];
    
    // Reglas de validación dinámicas
    protected function rules($type, $id = null): array 
    {
        return [
            'order' => [
                'user_id' => 'required|exists:users,id',
                'order_date' => 'sometimes|date|before_or_equal:now',
                'order_total' => 'sometimes|numeric|min:0.01|max:999999.99',
                'status' => 'sometimes|string|max:50'
            ],
            'detail' => [
                'ID_order' => 'required|exists:orders,id',
                'ID_product' => 'required|exists:products,id',
                'requestedQuantity' => 'required|numeric|min:0.01',
                'princeQuantity' => 'required|numeric|min:0.01',
            ]
        ][$type];
    }

    // Respuesta API estandarizada
    protected function response($success, $data = null, $message = '', $code = 200, $error = null): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'data' => $data,
            'message' => $message,
            'error' => app()->isLocal() ? $error : null
        ], $code);
    }

    // Procesamiento de transacciones
    protected function transaction(callable $callback, $errorMessage, $logMessage)
    {

        DB::beginTransaction();
        try {
            $result = $callback();
            DB::commit();
            return $result;
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->response(false, null, 'Error de validación', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("$logMessage: {$e->getMessage()}");
            return $this->response(false, null, $errorMessage, 500, $e->getMessage());
        }
    }

    /**
     * MÉTODOS PARA ORDERS 
     **/
    
    public function index(Request $request)
    {
        return $this->transaction(
            fn() => $this->response(
                true,
                Order::with($this->relations)
                    ->when($request->user_id, fn($q, $id) => $q->where('user_id', $id))
                    ->when($request->date_from, fn($q, $date) => $q->where('order_date', '>=', $date))
                    ->when($request->date_to, fn($q, $date) => $q->where('order_date', '<=', $date))
                    ->when($request->status, fn($q, $status) => $q->where('status', $status))
                    ->latest()
                    ->paginate($request->per_page ?? 15),
                'Pedidos obtenidos exitosamente'
            ),
            'Error al obtener pedidos',
            'OrderController@index'
        );
    }

    public function showOrder($id)
    {
        return $this->transaction(
            fn() => $this->response(
                true,
                Order::with($this->relations)->findOrFail($id),
                'Pedido encontrado'
            ),
            'Error al buscar pedido',
            'OrderController@showOrder'
        );
    }

    public function storeOrder(Request $request)
    {
        return $this->transaction(
            fn() => tap(
                Order::create($request->validate($this->rules('order'))),
                fn($order) => Log::info("Pedido creado - ID: {$order->id}")
            ),
            'Error al crear pedido',
            'OrderController@storeOrder'
        );
    }

    public function updateOrder(Request $request, $id)
    {
        return $this->transaction(
            fn() => tap(
                Order::findOrFail($id)->update($request->validate($this->rules('order', $id))),
                fn() => Log::info("Pedido actualizado - ID: $id")
            ),
            'Error al actualizar pedido',
            'OrderController@updateOrder'
        );
    }

    public function destroyOrder($id)
    {
        return $this->transaction(
            fn() => tap(
                Order::findOrFail($id)->delete(),
                fn() => Log::info("Pedido eliminado - ID: $id")
            ),
            'Error al eliminar pedido',
            'OrderController@destroyOrder'
        );
    }

    /***
      MÉTODOS PARA ORDERDETAILS 
     ***/

    public function indexDetails($orderId)
    {
        return $this->response(
            true,
            OrderDetail::with($this->detailRelations)
                ->where('ID_order', $orderId)
                ->latest()
                ->get(),
            'Detalles obtenidos exitosamente'
        );
    }

    public function showDetail($orderId, $detailId)
    {
        return $this->response(
            true,
            OrderDetail::with($this->detailRelations)
                ->where('ID_order', $orderId)
                ->findOrFail($detailId),
            'Detalle encontrado'
        );
    }

    public function storeDetail(Request $request, $orderId)
    {
        return $this->transaction(function() use ($request, $orderId) {
            $data = $request->validate($this->rules('detail'));
            $data['ID_order'] = $orderId;
            $product = Product::findOrFail($data['ID_product']);

            if ($product->CurrentStock < $data['requestedQuantity']) {
                return $this->response(
                    false, 
                    ['available_stock' => $product->CurrentStock], 
                    'Stock insuficiente', 
                    400
                );
            }

            $detail = OrderDetail::create($data);
            $product->decrement('CurrentStock', $data['requestedQuantity']);
            Order::find($orderId)->refreshTotal();
            Log::info("Detalle creado - ID: {$detail->id}");

            return $this->response(true, $detail, 'Detalle creado exitosamente', 201);
        }, 'Error al crear detalle', 'OrderController@storeDetail');
    }

    public function updateDetail(Request $request, $orderId, $detailId)
    {
        return $this->transaction(function() use ($request, $orderId, $detailId) {
            $detail = OrderDetail::where('ID_order', $orderId)->findOrFail($detailId);
            $data = $request->validate($this->rules('detail', $detailId));
            $product = Product::findOrFail($data['ID_product']);
            $quantityDiff = $data['requestedQuantity'] - $detail->requestedQuantity;

            if ($quantityDiff > 0 && $product->CurrentStock < $quantityDiff) {
                return $this->response(
                    false,
                    ['available_stock' => $product->CurrentStock],
                    'Stock insuficiente para la cantidad adicional',
                    400
                );
            }

            $detail->update($data);
            $product->decrement('CurrentStock', $quantityDiff);
            Order::find($orderId)->refreshTotal();
            
            return $this->response(true, $detail, 'Detalle actualizado correctamente');
        }, 'Error al actualizar detalle', 'OrderController@updateDetail');
    }

    public function destroyDetail($orderId, $detailId)
    {
        return $this->transaction(function() use ($orderId, $detailId) {
            $detail = OrderDetail::where('ID_order', $orderId)->findOrFail($detailId);
            $detail->product->increment('CurrentStock', $detail->requestedQuantity);
            $detail->delete();
            Order::find($orderId)->refreshTotal();
            Log::info("Detalle eliminado - ID: $detailId");
            
            return $this->response(true, null, 'Detalle eliminado correctamente');
        }, 'Error al eliminar detalle', 'OrderController@destroyDetail');
    }
}