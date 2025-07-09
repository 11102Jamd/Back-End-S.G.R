<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Request;
use App\Http\Controllers\ManufacturingController\ManufacturingController;
use App\Http\Controllers\Order\ProductController;
use App\Http\Controllers\PurchaseController\InputController;
use App\Http\Controllers\PurchaseController\PurchaseOrderController;
use App\Http\Controllers\PurchaseController\SupplierController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    /**
     * Rutas especificas para el rol Administrador
     */
    Route::middleware(['is_admin'])->group(function () {
        Route::apiResource('suppliers', SupplierController::class);
        Route::apiResource('inputs', InputController::class);
        Route::apiResource('purchase', PurchaseOrderController::class)->except(['destroy']);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('manufacturing', ManufacturingController::class)->except(['destroy']);
    });

    /**
     * Rutas especificas para el panadero
     */
    Route::middleware(['is_baker'])->group(function () {
        Route::apiResource('manufacturing', ManufacturingController::class);
        Route::apiResource('inputs', InputController::class)->only(['index', 'show', 'store', 'update']);
    });

    /**
     * Rutas especificas para el Cajero
     */
    Route::middleware(['is_cashier'])->group(function () {
        Route::apiResource('products', ProductController::class)->only(['index', 'show']);
        Route::apiResource('purchase', PurchaseOrderController::class)->only(['index', 'store']);
    });
});
