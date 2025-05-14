<?php

namespace App\Http\Controllers\PurchaseController;


use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\PurchaseOrders\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class PurchaseOrderController extends BaseCrudController
{
    protected $model = PurchaseOrder::class;
    protected $validationRules = [
        'ID_supplier' => 'required|exists:supplier,id',
        'PurchaseOrderDate' => 'required|date',
        'ID_input' => 'required|exists:inputs,id',
        'UnitMeasurement' => 'required|string',
        'InitialQuantity' => 'required|numeric|min:0',
        'UnityPrice' => 'required|numeric'
    ];

    public function __construct(
        protected PurchaseOrder $purchaseOrder){ 
    }

    //sobre escribir el metodo store del crud, porque una orden de compra puede tener muchos insumos.
    public function store(Request $request)
    {
        try {
            // Validación de entrada
            $validationRules = $this->validateRequest($request);

            // Procesamiento de la orden
            $result = $this->purchaseOrder->orderWithInputs($validationRules);

            // Respuesta exitosa
            return response()->json([
                'success' => true,
                'message' => 'Orden de compra generada con éxito',
                'data' => [
                    'order' => $result['order'],
                    'inputs' => $result['input_order']
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Manejo específico para errores de validación
            return response()->json([
                'success' => false,
                'error' => 'Error de validación',
                'messages' => $e->validator->errors()->all()
            ], 422);
        } catch (\Exception $e) {
            // Manejo de otros errores
            Log::error('Error al crear orden de compra: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al procesar la orden',
                'message' => 'Ocurrió un error inesperado. Por favor, intente nuevamente.'
            ], 500);
        }
    }
}
