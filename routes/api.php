<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CrudController;

Route::prefix('crud')->group(function () {
    Route::get('{tabla}', [CrudController::class, 'index']);
    Route::get('{tabla}/{id}', [CrudController::class, 'show']);
    Route::post('{tabla}', [CrudController::class, 'store']);
    Route::put('{tabla}/{id}', [CrudController::class, 'update']);
    Route::delete('{tabla}/{id}', [CrudController::class, 'destroy']);
});
