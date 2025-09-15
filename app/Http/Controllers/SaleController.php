<?php

namespace App\Http\Controllers;

use App\Http\Controllers\globalCrud\BaseCrudController;
use Illuminate\Http\Request;
use App\Models\Sale;

//creo la clase SaleController que extiende de BaseCrudController
class SaleController extends BaseCrudController
{
    protected $model = Sale::class;
    protected $validationRules = [
        'user_id' => 'required|exists:users,id',
        'sale_date' => 'required|date',
        'sale_total' => 'nullable|numeric|min:0'
    ];

    //constructor que inyecta la dependencia SaleService
    public function __construct(private \App\Services\SaleService $saleService) {}

    public function index()
    {
        try {
            $sales = $this->model::with(['user', 'saleProducts.product'])
                ->orderBy('id', 'desc')
                ->get();

            return response()->json($sales);
        } catch (\Throwable $th) {
            return response()->json([
                "error" => "Error al obtener las ventas",
                "message" => $th->getMessage(),
            ], 500);
        }
    }

    //metodo store para registrar una venta
    public function store(Request $request)
    {
        try {
            // Validación específica para ventas con productos
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|exists:product,id',
                'products.*.quantity_requested' => 'required|numeric|min:0.01'
            ]);

            $result = $this->saleService->registerSale($validated);

            // Respuesta exitosa con detalles de la venta registrada
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['sale']
            ], 201);
        } catch (\Exception $th) {
            return response()->json([
                'success' => false,
                'error' => 'Error de validación',
                'messages' => $th->getMessage()
            ], 422);
        } catch (\Exception $th) {
            return response()->json([
                'success' => false,
                'error' => 'Error al registrar la venta',
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
