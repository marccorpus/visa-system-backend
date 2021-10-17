<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ForgotPasswordController;

Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [ForgotPasswordController::class, 'forgotPassword']);
Route::post('reset-password', [ForgotPasswordController::class, 'resetPassword']);

Route::middleware('auth:api')->group(function() {

    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);
    
});