<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Production;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getDashboardStats(Request $request)
    {
        try {
            $stats = [
                'total_orders' => Order::count(), // cuenta la cantidad de compras existentes
                'total_sales' => Sale::count(),
                'total_users' => User::count(),
                'completed_productions' => Production::count(),
                'sum_sales' => Sale::sum('sale_total'),
                'sum_orders' => Order::sum('order_total'),
                'sum_production' => Production::sum('total_cost'),
            ];

            $stones = ($stats['sum_production'] + $stats['sum_orders']) - $stats['sum_sales'];

            return response()->json([
                'generales' => $stats,
                'ganancias' => $stones
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Error al obtener los datos generales de la panaderia',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function getSalesData(Request $request)
    {
        try {
            $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));

            $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

            $salesData = Sale::whereBetween('sale_date', [$startDate, $endDate])
                ->get()
                ->groupBy(function ($sale) {
                    return Carbon::parse($sale->sale_date)->format('Y-m-d');
                })
                ->map(function ($dailySales) {
                    return [
                        'date' => $dailySales->first()->sale_date,
                        'sales' => $dailySales->count(),
                        'total_sales' => $dailySales->sum('sale_total')
                    ];
                })
                ->values()->sortBy('date');

            return response()->json($salesData);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'error al obtener los datos',
                'message' => $th->getMessage()
            ], 404);
        }
    }

    public function getOrdersData(Request $request)
    {
        try {
            $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

            $ordersData = Order::whereBetween('order_date', [$startDate, $endDate])
                ->get()
                ->groupBy(function ($order) {
                    return Carbon::parse($order->order_date)->format('Y-m-d');
                })
                ->map(function ($dailyOrders) {
                    return [
                        'date' => $dailyOrders->first()->order_date,
                        'orders' => $dailyOrders->count(),
                        'total_orders' => $dailyOrders->sum('order_total')
                    ];
                })
                ->values()
                ->sortBy('date');

            return response()->json($ordersData);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'error al obtener los datos',
                'message' => $th->getMessage()
            ], 404);
        }
    }

    public function getUserStats()
    {
        try {
            $userStats = User::all()->groupBy('rol')
                ->map(function ($users, $rol) {
                    return [
                        'rol' => $rol,
                        'total' => $users->count()
                    ];
                })->values();

            return response()->json($userStats);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'error al obtener los datos',
                'message' => $th->getMessage()
            ], 404);
        }
    }

    public function getTopProducts()
    {
        try {
            $topProducts = Product::withSum('saleProducts', 'quantity_requested')
                ->orderByDesc('sale_products_sum_quantity_requested')
                ->limit(5)
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'product_name' => $product->product_name,
                        'total_sold' => $product->sale_products_sum_quantity_requested ?? 0
                    ];
                });

            return response()->json($topProducts);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'error al obtener los datos de los productos mas vendidos',
                'message' => $th->getMessage()
            ], 404);
        }
    }


    public function getProductionStats(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        // Obtener estadísticas de producción usando Eloquent
        $productions = Production::whereBetween('production_date', [$startDate, $endDate])->get();

        $productionStats = [
            'total_productions' => $productions->count(),
            'total_cost' => $productions->sum('total_cost'),
            'total_quantity' => $productions->sum('quantity_to_produce')
        ];

        return response()->json($productionStats);
    }
}
