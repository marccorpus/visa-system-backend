<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\BaseController as BaseController;

use App\Models\Nationality;

class NationalitiesController extends BaseController
{
    
    public function index() {
        $response['nationalities'] = Nationality::orderBy('name')->get();

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

}
