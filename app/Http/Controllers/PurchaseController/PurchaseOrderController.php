<?php

namespace App\Http\Controllers\PurchaseController;


use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\PurchaseOrders\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class PurchaseOrderController extends BaseCrudController
{
    protected $model = PurchaseOrder::class;
    protected $validationRules = [
        'ID_supplier' => 'nullable|exists:supplier,id',
        'PurchaseOrderDate' => 'required|date',
        'inputs' => 'required|array|min:1',
        'inputs.*.ID_input' => 'nullable|exists:inputs,id',
        'inputs.*.InitialQuantity' => 'required|numeric|min:0',
        'inputs.*.UnitMeasurement' => 'required|string|in:g,Kg,lb',
        'inputs.*.UnityPrice' => 'required|numeric|min:0'
    ];

    public function __construct(
        protected PurchaseOrder $purchaseOrder
    ) {}

    //sobre escribir el metodo store del crud, porque una orden de compra puede tener muchos insumos.
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validación de entrada
            $validationRules = $this->validateRequest($request);

            // Procesamiento de la orden
            $result = $this->purchaseOrder->orderWithInputs($validationRules);

            DB::commit();
            // Respuesta exitosa
            return response()->json([
                'Message' => "Orden de compra creada con exito",
                'OrdenCompra' => $result['order'],
                'Insumos' => $result['input_orders']
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Error en el controlador" . $th->getMessage());
            return response()->json([
                'error' => 'Datos inválidos',
                'message' => $th->getMessage(),
            ], 422);
        }
    }
}
