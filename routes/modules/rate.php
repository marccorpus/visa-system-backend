<?php

use App\Http\Controllers\RatesController;

Route::get('/', [RatesController::class, 'index']);
Route::post('/', [RatesController::class, 'store']);
Route::get('/{id}', [RatesController::class, 'show']);
Route::patch('/{id}', [RatesController::class, 'update']);
Route::delete('/{id}', [RatesController::class, 'destroy']);
Route::patch('/{id}/restore', [RatesController::class, 'restore']);