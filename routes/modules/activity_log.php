<?php

use App\Http\Controllers\ActivityLogsController;

Route::get('/{id}/client-profile', [ActivityLogsController::class, 'clientProfile']);
Route::get('/{id}/client-payment', [ActivityLogsController::class, 'clientPayment']);
Route::get('/{id}/client-transaction', [ActivityLogsController::class, 'clientTransaction']);

Route::get('/{id}/group-profile', [ActivityLogsController::class, 'groupProfile']);
Route::get('/{id}/group-payment', [ActivityLogsController::class, 'groupPayment']);
Route::get('/{id}/group-transaction', [ActivityLogsController::class, 'groupTransaction']);