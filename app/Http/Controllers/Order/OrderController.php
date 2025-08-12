<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\CrudController;
use App\Models\Order\Order;
use App\Models\Order\OrderDetail;
use App\Models\Order\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends CrudController
{
    protected $model = Order::class;
    protected $modelName = 'Pedido';
    protected $relations = ['details.product', 'user'];

    // Validación de pedidos y detalles (tabla intermedia)
    protected function validateRequest(Request $request, $id = null)
    {
        return $request->validate([
            'ID_user' => 'required|exists:users,id',
            'orderDate' => 'required|date',
            'details' => 'required|array|min:1',
            'details.*.ID_product' => 'required|exists:products,id',
            'details.*.requestedQuantity' => 'required|numeric|min:1',
            'details.*.priceQuantity' => 'required|numeric|min:0'
        ]);
    }

    // Sobrescribe store de CrudController (firma compatible)
    public function store(Request $request, $tabla = null)
    {
        $validated = $this->validateRequest($request);

        DB::beginTransaction();
        try {
            // Crear pedido
            $order = $this->model::create([
                'ID_user' => $validated['ID_user'],
                'orderDate' => $validated['orderDate'],
                'orderTotal' => 0
            ]);

            $total = 0;

            foreach ($validated['details'] as $detail) {
                $product = Product::findOrFail($detail['ID_product']);

                if ($product->currentStock < $detail['requestedQuantity']) {
                    throw new \Exception("Stock insuficiente para {$product->name}");
                }

                OrderDetail::create([
                    'ID_order' => $order->id,
                    'ID_product' => $product->id,
                    'requestedQuantity' => $detail['requestedQuantity'],
                    'priceQuantity' => $detail['priceQuantity']
                ]);

                $product->decrement('currentStock', $detail['requestedQuantity']);
                $total += $detail['requestedQuantity'] * $detail['priceQuantity'];
            }

            $order->update(['orderTotal' => $total]);

            DB::commit();

            return response()->json([
                'message' => "{$this->modelName} creado correctamente",
                'data'    => $order->load($this->relations)
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en {$this->modelName}: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    // Sobrescribe destroy de CrudController (firma compatible)
    public function destroy($tabla = null, $id)
    {
        DB::beginTransaction();
        try {
            $order = $this->model::with('details.product')->findOrFail($id);

            foreach ($order->details as $detail) {
                $detail->product->increment('currentStock', $detail->requestedQuantity);
                $detail->delete();
            }

            $order->delete();

            DB::commit();

            return response()->json([
                'message' => "{$this->modelName} eliminado y stock revertido"
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
