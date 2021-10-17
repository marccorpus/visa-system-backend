<?php

use App\Http\Controllers\PaymentsController;

Route::get('/{id}', [PaymentsController::class, 'index']);
Route::post('/', [PaymentsController::class, 'store']);
Route::delete('/{id}', [PaymentsController::class, 'destroy']);
Route::patch('/{id}/restore', [PaymentsController::class, 'restore']);