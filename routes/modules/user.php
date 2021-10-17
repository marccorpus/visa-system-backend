<?php

use App\Http\Controllers\UsersController;

Route::get('/', [UsersController::class, 'index']);
Route::post('/', [UsersController::class, 'store']);
Route::get('/{id}', [UsersController::class, 'show']);
Route::patch('/{id}', [UsersController::class, 'update']);
Route::patch('/{id}/change-password', [UsersController::class, 'changePassword']);
Route::patch('/{id}/customize-avatar', [UsersController::class, 'customizeAvatar']);
Route::delete('/{id}', [UsersController::class, 'destroy']);
Route::patch('/{id}/restore', [UsersController::class, 'restore']);