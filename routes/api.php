<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\InputController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderPdfController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionPdfController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SalePdfController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Rutas de Acceso - login y reset-password
 */
Route::post('/login', [AuthController::class, 'login']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    /**
     * Rutas basicas para el Dashboard
     */
    // Route::prefix('dashboard')->group(function () {
    //     Route::get('/stats', [DashboardController::class, 'getDashboardStats']);
    //     Route::get('/sales-data', [DashboardController::class, 'getSalesData']);
    //     Route::get('/orders-data', [DashboardController::class, 'getOrdersData']);
    //     Route::get('/user-stats', [DashboardController::class, 'getUserStats']);
    //     Route::get('/top-products', [DashboardController::class, 'getTopProducts']);
    //     Route::get('/production-stats', [DashboardController::class, 'getProductionStats']);
    //     Route::get('/inventory-value', [DashboardController::class, 'getInventoryValue']);
    // });

    /**
     * Rutas especificas para el usuario Administrador
     */
    Route::middleware(['is_admin'])->group(function () {
        Route::apiResource('order', OrderController::class);
        Route::apiResource('input', InputController::class);
        Route::patch('input/{id}/disable', [InputController::class, 'disable']);
        Route::patch('input/{id}/enable', [InputController::class, 'enable']);
        Route::get('input/{input}/batches', [InputController::class, 'batches']);
        Route::apiResource('recipe', RecipeController::class);
        Route::patch('/recipe/{id}/disable', [RecipeController::class, 'disable']);
        Route::apiResource('product', ProductController::class);
        Route::patch('product/{id}/disable', [ProductController::class, 'disable']);
        Route::patch('product/{id}/enable', [ProductController::class, 'enable']);
        Route::post('/products/link-production', [ProductController::class, 'linkProductionToProduct']);
        Route::apiResource('user', UserController::class);
        Route::patch('user/{id}/disable', [UserController::class, 'disable']);
        Route::patch('user/{id}/enable', [UserController::class, 'enable']);
        Route::apiResource('sale', SaleController::class);

        Route::prefix('production')->group(function () {
            Route::get('/', [ProductionController::class, 'index']);

            // Pre-calcular requerimientos (POST) - No afecta la base de datos
            Route::post('/calculate-requirements', [ProductionController::class, 'calculateRequirements']);
            Route::get('/{id}', [ProductionController::class, 'show']);
            Route::post('/', [ProductionController::class, 'executeProduction']);
            Route::delete('/{id}', [ProductionController::class, 'destroy']);
        });
    });

    /**
     * Rutas especificas para el usuario Cajero
     */
    Route::middleware(['is_cashier'])->group(function () {
        Route::apiResource('order', OrderController::class)->only(['index', 'show', 'store']);
        //probar input con cashier
        Route::apiResource('product', ProductController::class)->only(['index', 'show']);
        Route::apiResource('sale', SaleController::class)->only(['index', 'show', 'store']);
    });

    /**
     * Rutas para el usuario panadero
     */
    Route::middleware(['is_baker'])->group(function () {
        Route::apiResource('product', ProductController::class)->only(['index', 'show', 'store']);
        Route::post('/products/link-production', [ProductController::class, 'linkProductionToProduct']);
        Route::apiResource('input', InputController::class)->only(['index', 'show', 'store', 'update']);
        Route::apiResource('recipe', RecipeController::class)->only(['index', 'show', 'store', 'update']);
        Route::prefix('production')->group(function () {
            Route::get('/', [ProductionController::class, 'index']);
            Route::get('/{id}', [ProductionController::class, 'show']);
            Route::post('/', [ProductionController::class, 'executeProduction']);
            Route::delete('/{id}', [ProductionController::class, 'destroy']);
        });
    });
    Route::apiResource('input', InputController::class)->only(['index', 'show']);
    Route::apiResource('product', ProductController::class)->only(['index', 'show']);
});

Route::post('/order/export-pdf', [OrderPdfController::class, 'exportPdf']);
Route::post('/production/export-pdf', [ProductionPdfController::class, 'exportPdf']);
Route::post('/sale/export-pdf', [SalePdfController::class, 'exportPdf']);


