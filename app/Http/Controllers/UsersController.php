<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use App\Http\Controllers\BaseController as BaseController;

use App\Rules\MatchOldPassword;

use Validator;

use App\Models\User;

class UsersController extends BaseController
{
    
    const MALE = [
        'avatar_circle_color' => '#07BCD4',
        'avatar_top_type' => 'ShortHairShortFlat',
        'avatar_top_color' => 'Black',
        'avatar_hair_color' => 'Black',
        'avatar_accessories_type' => 'Blank',
        'avatar_eyebrow_type' => 'DefaultNatural',
        'avatar_eye_type' => 'Default',
        'avatar_facial_hair_type' => 'Blank',
        'avatar_facial_hair_color' => 'Auburn',
        'avatar_mouth_type' => 'Smile',
        'avatar_skin_color' => 'Light',
        'avatar_clothes_type' => 'BlazerSweater',
        'avatar_clothes_color' => 'Black',
        'avatar_graphic_type' => 'Bat'
    ];

    const FEMALE = [
        'avatar_circle_color' => '#E91E63',
        'avatar_top_type' => 'LongHairBob',
        'avatar_top_color' => 'Black',
        'avatar_hair_color' => 'Brown',
        'avatar_accessories_type' => 'Prescription02',
        'avatar_eyebrow_type' => 'DefaultNatural',
        'avatar_eye_type' => 'Default',
        'avatar_facial_hair_type' => 'Blank',
        'avatar_facial_hair_color' => 'Auburn',
        'avatar_mouth_type' => 'Smile',
        'avatar_skin_color' => 'Pale',
        'avatar_clothes_type' => 'BlazerSweater',
        'avatar_clothes_color' => 'Black',
        'avatar_graphic_type' => 'Bat'
    ];

    const DEFAULT_PASSWORD = 'secret123';

    public function index() {
        $withTrashed = request()->withTrashed;

        if($withTrashed == '1') {
            $response['users'] = User::withTrashed()->with('role')->orderBy('last_name')->orderBy('first_name')->get();
        } else {
            $response['users'] = User::with('role')->orderBy('last_name')->orderBy('first_name')->get();
        }

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'email' => 'required|email|unique:users,email',
            'use_default_password' => 'required',
            'password' => !$request->use_default_password ? 'required|min:6' : '',
            'password_confirmation' => 'same:password',
            'contact_number' => 'required|digits:10|unique:users,contact_number',
            'gender' => 'required|in:Male,Female'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $request['password'] = Hash::make($request->use_default_password ? self::DEFAULT_PASSWORD : $request->password);
        
        if($request->gender == 'Male') {
            $data = array_merge($request->all(), self::MALE);
        } elseif($request->gender == 'Female') {
            $data = array_merge($request->all(), self::FEMALE);
        }

        User::create($data);

        return $this->sendResponse(true, 'Account has been successfully added.', [], 200);
    }

    public function show($id) {
        $withTrashed = request()->withTrashed;

        if($withTrashed == '1') {
            $user = User::withTrashed()->with('role')->find($id);
        } else {
            $user = User::with('role')->find($id);
        }

        if(!$user) {
            return $this->sendResponse(false, 'Account not found.', [], 404);
        } else {
            $response['user'] = $user;

            return $this->sendResponse(true, 'Success.', $response, 200);
        }
    }

    public function update(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'email' => 'required|email|unique:users,email,'.$id,
            'contact_number' => 'required|digits:10|unique:users,contact_number,'. $id,
            'gender' => 'required|in:Male,Female'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $user = User::find($id);
        
        if(!$user) {
            return $this->sendResponse(false, 'Account not found.', [], 404);
        } else {
            $user->fill($request->all())->save();

            return $this->sendResponse(true, 'Account has been successfully updated.', [], 200);
        }
    }

    public function changePassword(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', new MatchOldPassword],
            'new_password' => 'required|min:6|different:current_password',
            'new_password_confirmation' => 'same:new_password'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $user = User::find($id);

        if(!$user) {
            return $this->sendResponse(false, 'User not found.', [], 404);
        } else {
            $user->update(['password'=> Hash::make($request->new_password)]);

            return $this->sendResponse(true, 'Password has been successfully updated.', [], 200);
        }
    }

    public function customizeAvatar(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'avatar_circle_color' => 'required',
            'avatar_top_type' => 'required',
            'avatar_top_color' => 'required',
            'avatar_hair_color' => 'required',
            'avatar_accessories_type' => 'required',
            'avatar_eyebrow_type' => 'required',
            'avatar_eye_type' => 'required',
            'avatar_facial_hair_type' => 'required',
            'avatar_facial_hair_color' => 'required',
            'avatar_mouth_type' => 'required',
            'avatar_skin_color' => 'required',
            'avatar_clothes_type' => 'required',
            'avatar_clothes_color' => 'required',
            'avatar_graphic_type' => 'required'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $user = User::find($id);
        
        if(!$user) {
            return $this->sendResponse(false, 'User not found.', [], 404);
        } else {
            $user->fill($request->all())->save();

            return $this->sendResponse(true, 'Avatar has been successfully customized.', [], 200);
        }
    }

    public function destroy($id) {
        $user = User::find($id);
        
        if(!$user) {
            return $this->sendResponse(false, 'User not found.', [], 404);
        } else {
            $user->delete();

            return $this->sendResponse(true, 'Account has been successfully deleted.', [], 200);
        }
    }

    public function restore($id) {
        $user = User::withTrashed()->find($id);

        if(!$user) {
            return $this->sendResponse(false, 'User not found.', [], 404);
        } else {
            $user->restore();

            return $this->sendResponse(true, 'Account has been successfully restored.', [], 200);
        }
    }

}
