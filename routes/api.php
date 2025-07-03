<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PurchaseController\InputController;
use App\Http\Controllers\Order\ProductController;
use App\Http\Controllers\Order\OrderController; // ← 👈 Aquí conectas el controlador de pedidos

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    //  Rutas de autenticación
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    //  CRUD para Insumos
    Route::apiResource('inputs', InputController::class);

    //  CRUD completo para Productos
    Route::apiResource('productos', OrderController::class);

    //  Filtro por fecha para productos (si tu controlador lo tiene)
    Route::post('productos/filtrar-fecha', [OrderController::class, 'filtrarPorFecha']);

    //  CRUD para Pedidos (el verdadero OrderController heredando BaseCrudController)
    Route::apiResource('pedidos', OrderController::class);

    //  Filtro por fecha para pedidos (si agregaste el método)
    Route::post('pedidos/filtrar-fecha', [OrderController::class, 'filtrarPorFecha']);
});
