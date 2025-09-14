<?php

namespace App\Http\Controllers;

use App\Models\Input;
use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends BaseCrudController
{
    protected $model = Order::class;

    protected $orderService;

    protected $validationRules = [
        'supplier_name' => 'required|string|max:255',
        'order_date' => 'required|date',
        'items' => 'required|array|min:1',
        'items.*.input_id' => 'required|exists:input,id',
        'items.*.quantity_total' => 'required|integer',
        'items.*.unit' => 'required|string|max:10',
        'items.*.unit_price' => 'required|numeric|min:0.01'
    ];

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function index()
    {
        try {
            $orders = $this->model::with('batches.input')
                ->orderBy('id', 'desc')
                ->get();

            return response()->json($orders);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'error al obtener las ordenes',
                'error' => $th->getMessage()
            ], 500);
        }
    }


    public function show($id)
    {
        try {
            $order = $this->model::with('batches.input')->findOrFail($id);
            return response()->json($order);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'orden de compra no encontrada',
                'message' => $th->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $this->validationRequest($request);

            // Validación adicional de unidades
            foreach ($validated['items'] as $item) {
                $input = Input::find($item['input_id']);
                if (!$input) {
                    throw new \Exception("El insumo con ID {$item['input_id']} no existe");
                }
            }

            $order = $this->orderService->createOrderWithBatches($validated);

            return response()->json([
                'message' => 'Orden creada exitosamente',
                'data' => $order,
                'order_total' => round($order->order_total, 3)
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'error al crear la orden de compra',
                'error' => $th->getMessage()
            ], 422);
        }
    }
}
