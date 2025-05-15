<?php

use App\Http\Controllers\Auth\AuthController;

use App\Http\Controllers\PurchaseController\InputController;
use App\Models\Fabricacion\ManufacturingController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});
Route::apiResource('manufacturing', ManufacturingController::class);
Route::apiResource('/inputs', InputController::class);

