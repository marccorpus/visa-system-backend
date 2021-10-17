<?php

use App\Http\Controllers\ClientsController;

Route::get('/pagination', [ClientsController::class, 'indexPagination']);
Route::get('/statistics', [ClientsController::class, 'indexStatistics']);
Route::post('/', [ClientsController::class, 'store']);
Route::get('/{id}', [ClientsController::class, 'show']);
Route::get('/{id}/statistics', [ClientsController::class, 'statistics']);
Route::patch('/{id}/update-basic-information', [ClientsController::class, 'updateBasicInformation']);
Route::patch('/{id}/update-personal-information', [ClientsController::class, 'updatePersonalInformation']);
Route::patch('/{id}/update-contact-information', [ClientsController::class, 'updateContactInformation']);