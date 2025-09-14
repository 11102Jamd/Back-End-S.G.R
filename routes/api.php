<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InputController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\RecipeController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
//   Route::post('/logout', [AuthController::class, 'logout']);
//     Route::get('/user', function (Request $request) {
//         return $request->user();
//     });

//     /**
//      * Rutas especificas para el rol Administrador
//      */
//     Route::middleware(['is_admin'])->group(function () {
//         // Route::apiResource('suppliers', SupplierController::class);
//         // Route::apiResource('inputs', InputController::class);
//         // Route::apiResource('purchase', PurchaseOrderController::class)->except(['destroy']);
//         // Route::apiResource('products', ProductController::class);
//         // Route::apiResource('manufacturing', ManufacturingController::class)->except(['destroy']);
//         Route::apiResource('user', UserController::class);
//     });
    /**
     * Rutas especificas para el rol Administrador
     */
    Route::middleware(['is_admin'])->group(function () {
        Route::apiResource('input', InputController::class);
        Route::apiResource('order', OrderController::class);

        // Route::apiResource('suppliers', SupplierController::class);
        // Route::apiResource('inputs', InputController::class);
        // Route::apiResource('purchase', PurchaseOrderController::class)->except(['destroy']);
        // Route::apiResource('products', ProductController::class);
        // Route::apiResource('manufacturing', ManufacturingController::class)->except(['destroy']);
        // Route::apiResource('users', UserController::class);
    });

//     /**
//      * Rutas especificas para el panadero
//      */
//     Route::middleware(['is_baker'])->group(function () {
//         // Route::apiResource('manufacturing', ManufacturingController::class);
//         // Route::apiResource('inputs', InputController::class)->only(['index', 'show', 'store', 'update']);
//     });

//     /**
//      * Rutas especificas para el Cajero
//      */
//     Route::middleware(['is_cashier'])->group(function () {
//         // Route::apiResource('products', ProductController::class)->only(['index', 'show']);
//         // Route::apiResource('purchase', PurchaseOrderController::class)->only(['index', 'store']);
//     });
});

Route::apiResource('input', InputController::class);
Route::apiResource('order', OrderController::class);
Route::apiResource('recipe',RecipeController::class);
Route::apiResource('production',ProductionController::class);
