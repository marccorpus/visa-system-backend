<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;

use App\Http\Controllers\BaseController as BaseController;

use Validator;

class ForgotPasswordController extends BaseController
{
    
    public function forgotPassword(Request $request) {
        $validator = Validator::make($request->all(), [ 
            'email' => 'required|email|exists:users,email'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        Password::sendResetLink($request->all());

        return $this->sendResponse(true, 'Password reset link has been sent on your email.', [], 200);
    }

    public function resetPassword(Request $request) {
        $validator = Validator::make($request->all(), [ 
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => 'required|min:6',
            'password_confirmation' => 'same:password'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $reset_password_status = Password::reset($request->all(), function ($user, $password) {
            $user->password = Hash::make($password);
            $user->save();
        });

        if ($reset_password_status == Password::INVALID_TOKEN) {
            return $this->sendResponse(false, 'Invalid token provided.', [], 400);
        }

        return $this->sendResponse(true, 'Password has been successfully changed.', [], 200);
    }

}
