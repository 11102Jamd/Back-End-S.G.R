<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Order\OrderDetailController;  
use App\Http\Controllers\Order\ProductController;
use App\Http\Controllers\Order\OrderController; 

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/recent/{days?}', [OrderController::class, 'recent']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::put('/{id}', [OrderController::class, 'update']);
        Route::delete('/{id}', [OrderController::class, 'destroy']);
        
        // Detalles de pedido
        Route::prefix('{orderId}/details')->group(function () {
            Route::get('/', [OrderDetailController::class, 'index']);
            Route::post('/', [OrderDetailController::class, 'store']);
            Route::get('/{id}', [OrderDetailController::class, 'show']);
            Route::put('/{id}', [OrderDetailController::class, 'update']);
            Route::delete('/{id}', [OrderDetailController::class, 'destroy']);
        });
    });

    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::get('/{id}/stock', [ProductController::class, 'stock']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });

});