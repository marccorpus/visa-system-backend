<?php

use App\Http\Controllers\ServicesController;

Route::get('/', [ServicesController::class, 'index']);
Route::get('/index-with-rates', [ServicesController::class, 'indexWithRates']);
Route::post('/', [ServicesController::class, 'store']);
Route::get('/{id}', [ServicesController::class, 'show']);
Route::patch('/{id}', [ServicesController::class, 'update']);
Route::delete('/{id}', [ServicesController::class, 'destroy']);
Route::patch('/{id}/restore', [ServicesController::class, 'restore']);