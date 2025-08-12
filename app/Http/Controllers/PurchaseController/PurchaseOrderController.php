<?php

namespace App\Http\Controllers\PurchaseController;


use App\Http\Controllers\globalCrud\BaseCrudController;
use App\Models\PurchaseOrders\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\PurchaseOrders\Inputs;


class PurchaseOrderController extends BaseCrudController
{
    protected $model = PurchaseOrder::class;
    protected $validationRules = [
        'ID_supplier' => 'required|exists:supplier,id',
        'PurchaseOrderDate' => 'required|date',
        'inputs' => 'required|array|min:1',
        'inputs.*.ID_input' => 'required|exists:inputs,id',
        'inputs.*.InitialQuantity' => 'required|numeric|min:0',
        'inputs.*.UnitMeasurement' => 'required|string|in:g,kg,lb',
        'inputs.*.UnityPrice' => 'required|numeric|min:0'
    ];

    //sobre escribir el metodo store del crud, porque una orden de compra puede tener muchos insumos.
    public function store(Request $request)
    {
        //dd($request->all());
        DB::beginTransaction();
        try {
            // Validación de entrada
            $validationData = $this->validateRequest($request);

            $purchaseOrder = $this->model::create([
                'ID_supplier' => $validationData['ID_supplier'],
                'PurchaseOrderDate' => $validationData['PurchaseOrderDate'],
            ]);

            $result = $purchaseOrder->addInputs($validationData['inputs']);

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

    public function index(){
        $orders = PurchaseOrder::with(['supplier:id,name','inputOrders.input'])->get();
        return response()->json($orders);
    }
}
