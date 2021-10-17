<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\BaseController as BaseController;

use App\Models\User;

use Auth, Validator;

class AuthController extends BaseController
{

    public function user() {
        $response['user'] = User::with('role')->find(Auth::user()->id);
        
        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function login(Request $request) {
        $validator = Validator::make($request->all(), [ 
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])) { 
            $user = Auth::user(); 
            $response['token'] =  $user->createToken('AnalytiqBusinessConsultancy')->accessToken; 
            $response['user'] =  $user;
   
            return $this->sendResponse(true, 'Success.', $response, 200);
        } else { 
            return $this->sendResponse(false, 'The email or password you entered is incorrect.', [], 422);
        } 
    }

    public function logout(Request $request) {
        $token = $request->user()->token();
        $token->revoke();

        return $this->sendResponse(true, 'Success.', [], 200);
    }

}
