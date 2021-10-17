<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BaseController extends Controller
{
    
    public function sendResponse($success, $message, $data, $code) {
    	$response = [
            'success' => $success,
            'message' => $message,
            'data'    => $data
        ];

        return response()->json($response, $code);
    }

}
