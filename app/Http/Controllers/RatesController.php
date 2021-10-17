<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\BaseController as BaseController;

use Validator;

use App\Models\Rate;

class RatesController extends BaseController
{
    
    public function index() {
        $withTrashed = request()->withTrashed;

        if($withTrashed == '1') {
            $response['rates'] = Rate::withTrashed()->orderBy('name')->get();
        } else {
            $response['rates'] = Rate::orderBy('name')->get();
        }

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255|unique:rates,name'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        Rate::create($request->all());

        return $this->sendResponse(true, 'Rate has been successfully added.', [], 200);
    }

    public function show($id) {
        $rate = Rate::find($id);

        if(!$rate) {
            return $this->sendResponse(false, 'Rate not found.', [], 404);
        } else {
            $response['rate'] = $rate;

            return $this->sendResponse(true, 'Success.', $response, 200);
        }
    }

    public function update(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255|unique:rates,name,' .$id
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $rate = Rate::find($id);
        
        if(!$rate) {
            return $this->sendResponse(false, 'Rate not found.', [], 404);
        } else {
            $rate->fill($request->all())->save();

            return $this->sendResponse(true, 'Rate has been successfully updated.', [], 200);
        }
    }

    public function destroy($id) {
        $rate = Rate::find($id);
        
        if(!$rate) {
            return $this->sendResponse(false, 'Rate not found.', [], 404);
        } else {
            $rate->delete();

            return $this->sendResponse(true, 'Rate has been successfully deleted.', [], 200);
        }
    }

    public function restore($id) {
        $rate = Rate::withTrashed()->find($id);

        if(!$rate) {
            return $this->sendResponse(false, 'Rate not found.', [], 404);
        } else {
            $rate->restore();

            return $this->sendResponse(true, 'Rate has been successfully restored.', [], 200);
        }
    }

}
