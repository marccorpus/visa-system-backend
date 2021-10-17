<?php

use App\Http\Controllers\GroupsController;

Route::get('/', [GroupsController::class, 'index']);
Route::get('/pagination', [GroupsController::class, 'indexPagination']);
Route::get('/statistics', [GroupsController::class, 'indexStatistics']);
Route::post('/', [GroupsController::class, 'store']);
Route::post('/add-members', [GroupsController::class, 'addMembers']);
Route::post('/remove-member', [GroupsController::class, 'removeMember']);
Route::get('/{id}', [GroupsController::class, 'show']);
Route::get('/{id}/statistics', [GroupsController::class, 'statistics']);
Route::get('/{id}/members', [GroupsController::class, 'members']);
Route::get('/{id}/non-members', [GroupsController::class, 'nonMembers']);
Route::get('/{id}/members-pagination', [GroupsController::class, 'membersPagination']);
Route::patch('/{id}/update-basic-information', [GroupsController::class, 'updateBasicInformation']);
Route::patch('/{id}/update-contact-information', [GroupsController::class, 'updateContactInformation']);