<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\BaseController as BaseController;

use DB, Validator;

use App\Models\Client;
use App\Models\Transaction;
use App\Models\ServiceTransaction;
use App\Models\Payment;

class ClientsController extends BaseController
{
    
    public function indexPagination() {
        $response['clients'] = Client::withMax(['serviceTransactions' => function($query) {
                $query->where('group_id', null);
            }], 'created_at')
            ->withCount(['serviceTransactions as total_service_cost' => function($query) {
                $query->select(DB::raw('sum(cost) + sum(under) + sum(charge)'))
                    ->where('group_id', null);
            }])
            ->withCount(['serviceTransactions as total_completed_service_cost' => function($query) {
                $query->select(DB::raw('sum(cost) + sum(under) + sum(charge)'))
                    ->where(function($q) {
                        $q->where('status', 'Completed')->orWhere('status', 'Released');
                    })
                    ->where('group_id', null);
            }]);

        $search = request()->search;

        if($search) {
            $response['clients'] = $response['clients']
                ->where('id', 'like', '%'.$search.'%')
                ->orWhere(DB::raw('CONCAT_WS(" ", first_name, last_name)'), 'like', '%' . $search . '%')
                ->orWhere(DB::raw('CONCAT_WS(" ", last_name, first_name)'), 'like', '%' . $search . '%');
        }

        $response['clients'] = $response['clients']->orderBy('id', 'desc')->paginate(10);
        
        $updatedItems = $response['clients']->getCollection();
        $updatedItems->map(function($item) {
            $item['total_discount'] = $this->getTotalDiscount($item['id']);
            $item['total_amount_paid'] = $this->getTotalAmountPaid($item['id']);

            return $item;
        })->toArray();
        $response['clients']->setCollection($updatedItems);

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    private function getTotalDiscount($id) {
        return Transaction::whereHas('serviceTransactions', function($query) use($id) {
            $query->where('client_id', $id)->where('group_id', null);
        })
        ->sum('discount');
    }

    private function getTotalAmountPaid($id) {
        return Payment::whereHas('transaction.serviceTransactions', function($query) use($id) {
            $query->where('client_id', $id)->where('group_id', null);
        })
        ->sum('amount');
    }

    public function indexStatistics() {
        $totalServiceCost = ServiceTransaction::whereNotNull('client_id')
            ->where('group_id', null)
            ->sum(DB::raw('cost + under + charge'));

        $totalCompletedServiceCost = ServiceTransaction::whereNotNull('client_id')
            ->where('group_id', null)
            ->where(function($query) {
                $query->where('status', 'Completed')->orWhere('status', 'Released');
            })
            ->sum(DB::raw('cost + under + charge'));

        $totalDiscount = Transaction::whereHas('serviceTransactions', function($query) {
                $query->whereNotNull('client_id')->where('group_id', null);
            })
            ->sum('discount');

        $totalAmountPaid = Payment::whereHas('transaction.serviceTransactions', function($query) {
                $query->whereNotNull('client_id')->where('group_id', null);
            })
            ->sum('amount');

        $response['totalBalance'] = ($totalServiceCost - $totalDiscount) - $totalAmountPaid;

        $response['totalCollectable'] = ($totalCompletedServiceCost - $totalDiscount) - $totalAmountPaid;

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'passport_number' => 'required|max:100|unique:clients,passport_number'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $client = Client::create($request->all());
        $response['id'] = $client->id;

        return $this->sendResponse(true, 'Client has been successfully added.', $response, 200);
    }

    public function show($id) {
        $client = Client::with('group', 'nationality')->find($id);

        if(!$client) {
            return $this->sendResponse(false, 'Client not found.', [], 404);
        } else {
            $response['client'] = $client;

            return $this->sendResponse(true, 'Success.', $response, 200);
        }
    }

    public function statistics($id) {
        $response['totalServiceCost'] = ServiceTransaction::where('client_id', $id)
            ->where('group_id', null)
            ->sum(DB::raw('cost + under + charge'));

        $response['totalCompletedServiceCost'] = ServiceTransaction::where('client_id', $id)
            ->where('group_id', null)
            ->where(function($query) {
                $query->where('status', 'Completed')->orWhere('status', 'Released');
            })
            ->sum(DB::raw('cost + under + charge'));

        $response['totalDiscount'] = Transaction::whereHas('serviceTransactions', function($query) use($id) {
                $query->where('client_id', $id)->where('group_id', null);
            })
            ->sum('discount');

        $response['totalAmountPaid'] = Payment::whereHas('transaction.serviceTransactions', function($query) use($id) {
                $query->where('client_id', $id)->where('group_id', null);
            })
            ->sum('amount');

        $response['totalBalance'] = ($response['totalServiceCost'] - $response['totalDiscount']) - $response['totalAmountPaid'];

        $response['totalCollectable'] = ($response['totalCompletedServiceCost'] - $response['totalDiscount']) - $response['totalAmountPaid'];

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function updateBasicInformation(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $client = Client::find($id);
        
        if(!$client) {
            return $this->sendResponse(false, 'Client not found.', [], 404);
        } else {
            $client->fill($request->all())->save();

            return $this->sendResponse(true, 'Client has been successfully updated.', [], 200);
        }
    }

    public function updatePersonalInformation(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'passport_number' => 'required|max:100|unique:clients,passport_number,'.$id,
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:Male,Female',
            'civil_status' => 'nullable|in:Single,Married,Widowed,Divorced,Separated'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $client = Client::find($id);
        
        if(!$client) {
            return $this->sendResponse(false, 'Client not found.', [], 404);
        } else {
            $client->fill($request->all())->save();

            return $this->sendResponse(true, 'Client has been successfully updated.', [], 200);
        }
    }

    public function updateContactInformation(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'contact_number' => 'nullable|digits:10'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $client = Client::find($id);
        
        if(!$client) {
            return $this->sendResponse(false, 'Client not found.', [], 404);
        } else {
            $client->fill($request->all())->save();

            return $this->sendResponse(true, 'Client has been successfully updated.', [], 200);
        }
    }

}
