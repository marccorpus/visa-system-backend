<?php

use App\Http\Controllers\TransactionsController;

Route::get('/{id}/index-pagination-of-client', [TransactionsController::class, 'indexPaginationOfClient']);
Route::get('/{id}/index-pagination-of-group', [TransactionsController::class, 'indexPaginationOfGroup']);
Route::get('/dashboard-statistics', [TransactionsController::class, 'dashboardStatistics']);
Route::get('/dashboard-services', [TransactionsController::class, 'dashboardServices']);
Route::post('/', [TransactionsController::class, 'store']);
Route::post('/additional-services', [TransactionsController::class, 'additionalServices']);
Route::patch('/{id}/update-discount', [TransactionsController::class, 'updateDiscount']);
Route::delete('/{id}', [TransactionsController::class, 'destroy']);
Route::patch('/{id}/restore', [TransactionsController::class, 'restore']);