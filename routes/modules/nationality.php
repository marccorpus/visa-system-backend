<?php

use App\Http\Controllers\NationalitiesController;

Route::get('/', [NationalitiesController::class, 'index']);