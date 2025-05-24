<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Order\ProductController;
use App\Http\Controllers\PurchaseController\InputController;
use Illuminate\Support\Facades\Request;
use App\Http\Controllers\Fabricacion\ManufacturingController;
use App\Http\Controllers\PurchaseController\InputController;
use App\Http\Controllers\PurchaseController\PurchaseOrderController;
use App\Http\Controllers\PurchaseController\SupplierController;
use App\Models\PurchaseOrders\Inputs;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::middleware(['is_baker'])->group(function () {
        Route::apiResource('inputs', InputController::class);
    });
    Route::middleware(['is_admin'])->group(function () {
        Route::apiResource('inputs', InputController::class);
    });

});

Route::apiResource('manufacturing', ManufacturingController::class);
Route::apiResource('inputs', InputController::class);
Route::apiResource('purchaseorder', PurchaseOrderController::class);
Route::apiResource('supplier', SupplierController::class);
