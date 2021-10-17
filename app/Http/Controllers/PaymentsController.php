<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\BaseController as BaseController;

use Auth, Validator;

use App\Models\Payment;

class PaymentsController extends BaseController
{
    
    public function index($id) {
        $response['payments'] = Payment::withTrashed()->where('transaction_id', $id)
            ->with(['createdBy' => function($query) {
                $query->withTrashed();
            }])
            ->with(['deletedBy' => function($query) {
                $query->withTrashed();
            }])
            ->latest()->get();

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required',
            'amount' => 'required|numeric'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        Payment::create($request->all() + ['created_by' => Auth::id()]);

        return $this->sendResponse(true, 'Payment has been successfully created.', [], 200);
    }

    public function destroy(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'deletion_reason' => 'required'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $payment = Payment::find($id);
        
        if(!$payment) {
            return $this->sendResponse(false, 'Payment not found.', [], 404);
        } else {
            $payment->update([
                'deleted_by' => Auth::id(),
                'deletion_reason' => $request->deletion_reason
            ]);
            $payment->delete();

            return $this->sendResponse(true, 'Payment has been successfully cancelled.', [], 200);
        }
    }

    public function restore($id) {
        $payment = Payment::withTrashed()->find($id);

        if(!$payment) {
            return $this->sendResponse(false, 'Payment not found.', [], 404);
        } else {
            $payment->update([
                'deleted_by' => null,
                'deletion_reason' => null
            ]);
            $payment->restore();

            return $this->sendResponse(true, 'Payment has been successfully restored.', [], 200);
        }
    }

}
