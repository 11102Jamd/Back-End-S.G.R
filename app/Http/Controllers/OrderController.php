<?php

namespace App\Http\Controllers;

use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\Order;
use App\Models\Input;
use App\Services\OrderService;
use Illuminate\Http\Request;

/**
 * Controlador para la gestión de órdenes de compra.
 *
 * Este controlador maneja las operaciones principales del módulo de compras,
 * incluyendo la creación, validación y listado de órdenes con sus lotes asociados.
 *
 * @package App\Http\Controllers
 */
class OrderController extends BaseCrudController
{
    /**
     * Modelo que maneja el CRUD principal del módulo.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Servicio que contiene la lógica de negocio para órdenes.
     *
     * @var \App\Services\OrderService
     */
    protected $orderService;

    /**
     * Reglas de validación para garantizar la integridad de los datos.
     *
     * @var array<string, string>
     */
    protected $validationRules = [
        'supplier_name' => 'required|string|max:50',
        'order_date' => 'required|date',
        'items' => 'required|array|min:1',
        'items.*.input_id' => 'required|exists:input,id',
        'items.*.quantity_total' => 'required|numeric|min:0.01',
        'items.*.unit_price' => 'required|numeric|min:0.01'
    ];

    /**
     * Inyección de dependencias para el servicio de órdenes.
     *
     * @param  \App\Services\OrderService  $orderService
     */
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Lista todas las órdenes con sus lotes asociados.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $orders = $this->model::with('batches')
                ->orderBy('order_date', 'desc')
                ->get();

            return response()->json($orders);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al obtener las órdenes',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Crea una nueva orden de compra con validación de insumos y lotes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validated = $this->validationRequest($request);

            // Validación de insumos y sus unidades de medida
            foreach ($validated['items'] as $item) {
                $input = Input::find($item['input_id']);

                if (!$input) {
                    throw new \Exception("El insumo con ID {$item['input_id']} no existe");
                }

                if (!in_array(strtolower($input->unit), ['kg', 'g', 'lb', 'l', 'oz'])) {
                    throw new \Exception(
                        "Unidad no válida para el insumo: {$input->unit}. Use: kg, g, l, lb, oz"
                    );
                }
            }

            // Delegación de la creación de la orden al servicio
            $order = $this->orderService->createOrderWithBatches($validated);

            return response()->json([
                'message' => 'Orden creada exitosamente',
                'data' => $order,
                'order_total' => $order->order_total
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al crear la orden',
                'error' => $th->getMessage()
            ], 422);
        }
    }

    /**
     * Muestra los detalles de una orden de compra por ID.
     *
     * Incluye la carga anticipada de las relaciones `batches` e `input`.
     *
     * @param  int  $id  ID de la orden de compra.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $order = Order::with('batches.input')->findOrFail($id);

            return response()->json($order);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al obtener la orden',
                'error' => $th->getMessage()
            ]);
        }
    }
}
