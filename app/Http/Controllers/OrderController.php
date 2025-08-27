<?php

namespace App\Http\Controllers;

use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\Order;
use App\Models\Input;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends BaseCrudController
{

    //Modelo que maneja el crud principal del modulo
    protected $model = Order::class;

    //Servicio generado para manejar la logica del negocio
    protected $orderService;

    //Reglas de validacion para que todos los datos sean correctos
    protected $validationRules  = [
        'supplier_name' => 'required|string|max:50',
        'order_date' => 'required|date',
        'items' => 'required|array|min:1',
        'items.*.input_id' => 'required|exists:input,id',
        'items.*.quantity_total' => 'required|numeric|min:0.01',
        'items.*.unit_price' => 'required|numeric|min:0.01'
    ];

    //Es la inyeccion de dependencias para pasar le una solicitud al servicio
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    //Metodo que lista los lotes asociados a las ordenes generadas, Get Orders
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

    //Metodo que valida y crea una orden de compra, Post Orders
    public function store(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);
            
            //Ciclo para recorrer los itmes de la compra para validar existencia y unidades de medida
            foreach ($validated['items'] as $item) {
                $input = Input::find($item['input_id']);

                if (!$input) {
                    throw new \Exception("El insumo con ID {$item['input_id']} no existe");
                }

                //Valida las unidades de medida dentro del array
                if (!in_array(strtolower($input->unit), ['kg', 'g', 'lb', 'l', 'oz'])) {
                    throw new \Exception("Unidad no válida para el insumo: {$input->unit}. Use: kg, g, l, lb, oz");
                }
            }

            //Se le asigna al servicio la orden de crear la compra y sus respectivos lotes
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
