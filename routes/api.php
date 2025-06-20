<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PurchaseController\InputController;
use App\Http\Controllers\Order\ProductController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    // Rutas de autenticación
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // CRUD para Insumos (Inputs)
    Route::apiResource('inputs', InputController::class);

    // CRUD completo para Productos (antes Pedidos)
    Route::apiResource('productos', ProductController::class);

    // Ruta adicional para filtrar productos por fecha (ajusta el método si es necesario)
    Route::post('productos/filtrar-fecha', [ProductController::class, 'filtrarPorFecha']);
});