<?php

namespace App\Http\Controllers;

use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\Order;
use App\Models\Input;
use App\Services\OrderService;
use Illuminate\Http\Request;


class OrderController extends BaseCrudController
{
    protected $model = Order::class;

    protected $orderService;

    protected $validationRules  = [
        'supplier_name' => 'required|string|max:50',
        'order_date' => 'required|date',
        'items' => 'required|array|min:1',
        'items.*.input_id' => 'required|exists:input,id',
        'items.*.quantity_total' => 'required|numeric|min:0.01',
        'items.*.unit_price' => 'required|numeric|min:0.01'
    ];

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function index()
    {
        try {
            $orders = $this->model::with('batches')->orderBy('order_date', 'desc')->get();
            return response()->json($orders);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'error al obtener las ordenes',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $this->validationRequest($request);
            foreach ($validated['items'] as $item) {
                $input = Input::find($item['input_id']);

                if (!$input) {
                    throw new \Exception("El insumo con ID {$item['input_id']} no existe");
                }

                if (!in_array(strtolower($input->unit), ['kg', 'g', 'lb', 'l', 'oz'])) {
                    throw new \Exception("Unidad no válida para el insumo: {$input->unit}. Use: kg, g, l, lb, oz");
                }
            }

            $order = $this->orderService->createOrderWithBatches($validated);
            return response()->json([
                'message' => 'Orden creada exitosamente',
                'data' => $order,
                'order_total' => $order->order_total
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'error al crear la orden',
                'error' => $th->getMessage()
            ], 422);
        }
    }
}
