<?php

use App\Http\Controllers\ServiceTransactionsController;

Route::get('transactions/{id}', [ServiceTransactionsController::class, 'transactionServices']);
Route::patch('/batch', [ServiceTransactionsController::class, 'batchUpdate']);
Route::patch('/{id}', [ServiceTransactionsController::class, 'update']);
Route::delete('/{id}', [ServiceTransactionsController::class, 'destroy']);
Route::patch('/{id}/restore', [ServiceTransactionsController::class, 'restore']);