<?php

use App\Http\Controllers\DocumentsController;

Route::get('/', [DocumentsController::class, 'index']);
Route::post('/', [DocumentsController::class, 'store']);
Route::get('/{id}', [DocumentsController::class, 'show']);
Route::patch('/{id}', [DocumentsController::class, 'update']);
Route::delete('/{id}', [DocumentsController::class, 'destroy']);
Route::patch('/{id}/restore', [DocumentsController::class, 'restore']);