<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Fabricacion\ManufacturingController;
use App\Http\Controllers\PurchaseController\InputController;
use App\Http\Controllers\PurchaseController\PurchaseOrderController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::apiResource('inputs', InputController::class);
    
    
});
Route::apiResource('manufacturing', ManufacturingController::class);


//añadir aqui ruta
//añadir una ruta purchaseOrder
Route::get('purchaseorders', PurchaseOrderController::class);