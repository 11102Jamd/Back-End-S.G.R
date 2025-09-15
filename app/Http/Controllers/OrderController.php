<?php

/**
 * Archivo crado por Juan David Plazas Hernandez
 */

namespace App\Http\Controllers;

use App\Models\Input;
use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

/**
 * Declaramos la clase OrderController que herdea de su clase padre
 * BaseCrudController
 */
class OrderController extends BaseCrudController
{
    /**
     * Modelo asociado.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Definimos una propiedad que almacena el objeto orderService
     */
    protected $orderService;

    /**
     * Reglas de validación para la compra
     *
     * @var array<string, string>
     */
    protected $validationRules = [
        'supplier_name' => 'required|string|max:255',
        'order_date' => 'required|date',
        'items' => 'required|array|min:1',
        'items.*.input_id' => 'required|exists:input,id',
        'items.*.quantity_total' => 'required|integer',
        'items.*.unit' => 'required|string|max:10',
        'items.*.unit_price' => 'required|numeric|min:0.01'
    ];

    /**
     * Inyecta el objeto del servicio orderService
     *
     * Este método se encarga de recibir e inyectar el objeto de PdfService
     * dentro del controlador orderController, para poder realizar el registro adecuado
     * de las compras segun las reglas de validacion que regiqueran.
     *
     * @param \App\Services\PdfService $pdfService Instancia del servicio de PDF.
     * @return void
     */
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * este metodo index devuelve todos las compras realizadas
     *
     * @return \Illuminate\Http\JsonResponse Devuelve la lista de Usuarios
     */
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

    /**
     * este metodo index devuelve un rfegiostro en especifico por medio de su id
     *
     * @param int $id el id del registro a mostrar
     * @return \Illuminate\Http\JsonResponse Devuelve la lista de Usuarios
     */
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

    /**
     * Almacena una nueva orden de compra junto con sus lotes asociados.
     *
     * Este método realiza los siguientes pasos:
     * 1. Valida los datos de la solicitud mediante `validationRequest`.
     * 2. Verifica que cada insumo indicado exista en la base de datos.
     * 3. Crea la orden y los lotes correspondientes usando `orderService->createOrderWithBatches`.
     * 4. Retorna una respuesta JSON con la compra creada y el total de la compra.
     *
     * @param \Illuminate\Http\Request $request Solicitud HTTP con las reglas de validacion
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta en json con el cuerpo de la compra
     * @throws \Exception Si algún insumo no existe o falla la creación de la compra.
     */
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
