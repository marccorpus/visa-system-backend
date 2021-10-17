<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\BaseController as BaseController;

use Auth, Carbon\Carbon, DB, Validator;

use App\Models\Client;
use App\Models\Transaction;
use App\Models\ServiceTransaction;
use App\Models\Payment;
use App\Models\Service;

use Spatie\Activitylog\Models\Activity;

class TransactionsController extends BaseController
{
    
    public function indexPaginationOfClient($id) {
        $search = request()->search;

        $query = Transaction::whereHas('serviceTransactions', function($query) use($id) {
                $query->where('client_id', $id)->where('group_id', null)->withTrashed();
            })
            ->withCount('serviceTransactions')
            // nullable
            ->withCount(['serviceTransactions as total_service_cost' => function($query) {
                $query->select(DB::raw('sum(cost) + sum(under) + sum(charge)'));
            }])
            // nullable
            ->withCount(['serviceTransactions as total_completed_service_cost' => function($query) {
                $query->select(DB::raw('sum(cost) + sum(under) + sum(charge)'))
                    ->where('status', 'Completed')->orWhere('status', 'Released');
            }])
            // nullable
            ->withSum('payments', 'amount')
            ->with(['deletedBy' => function($query) {
                $query->withTrashed();
            }])
            ->orderBy('id', 'desc')
            ->withTrashed();

        if($search) {
            $query->where('tracking', 'like', '%'.$search.'%');
        }
        
        $response['transactions'] = $query->paginate(10);
        
        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function indexPaginationOfGroup($id) {
        $search = request()->search;

        $query = Transaction::whereHas('serviceTransactions', function($query) use($id) {
                $query->where('group_id', $id)->withTrashed();
            })
            ->withCount('serviceTransactions')
            // nullable
            ->withCount(['serviceTransactions as total_service_cost' => function($query) {
                $query->select(DB::raw('sum(cost) + sum(under) + sum(charge)'));
            }])
            // nullable
            ->withCount(['serviceTransactions as total_completed_service_cost' => function($query) {
                $query->select(DB::raw('sum(cost) + sum(under) + sum(charge)'))
                    ->where('status', 'Completed')->orWhere('status', 'Released');
            }])
            // nullable
            ->withSum('payments', 'amount')
            ->with(['deletedBy' => function($query) {
                $query->withTrashed();
            }])
            ->orderBy('id', 'desc')
            ->withTrashed();

        if($search) {
            $query->where('tracking', 'like', '%'.$search.'%');
        }
        
        $response['transactions'] = $query->paginate(10);
        
        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function dashboardStatistics() {
        $response['totalClients'] = Client::count();

        $response['totalServices'] = ServiceTransaction::count();

        $response['todaysTotalServiceCost'] = ServiceTransaction::whereDate('created_at', '=', Carbon::today())
            ->sum(DB::raw('cost + under + charge'));

        $response['yesterdaysTotalServiceCost'] = ServiceTransaction::whereDate('created_at', '=', Carbon::yesterday())
            ->sum(DB::raw('cost + under + charge'));
            
        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function dashboardServices() {
        $q = request()->q;
        
        $response['services'] = ServiceTransaction::where(function($query) use($q) {
                if($q == 'Pending/On Process') {
                    $query->where('status', 'Pending')->orWhere('status', 'On Process');
                } else {
                    $query->where('status', $q);
                }
            })
            ->with('transaction')
            ->with('client')
            ->with('group')
            ->with(['service' => function($query) {
                $query->withTrashed()->with(['parentService' => function($query2) {
                    $query2->withTrashed();
                }]);
            }])
            ->paginate(10);

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'discount' => 'required|numeric',
            'services' => 'required|array',
            'services.*.id' => 'required',
            'services.*.client_id' => 'required',
            'services.*.cost' => 'required|numeric',
            'services.*.under' => 'required|numeric',
            'services.*.charge' => 'required|numeric',
            'services.*.status' => 'required|in:Pending,On Process,Completed,Released',
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $createdBy = Auth::user();

        $transaction = Transaction::create([
            'discount' => $request->discount,
            'created_by' => $createdBy->id
        ]);
        $transaction->update(['tracking' => 'ABC-'.$transaction->id]);

        $logServices = [];
        foreach($request->services as $service) {
            $transaction->services()->attach($service['id'], [
                'client_id' => $service['client_id'],
                'group_id' => $service['group_id'],
                'cost' => $service['cost'],
                'under' => $service['under'],
                'charge' => $service['charge'],
                'status' => $service['status'],
                'created_by' => $createdBy->id
            ]);

            $logService = Service::withTrashed()->find($service['id']);
            $logServiceClient = Client::find($service['client_id']);
            array_push($logServices, [
                'service_name' => $logService->name,
                'parent_service_name' => $logService->parentService->name,
                'client_name' => $logServiceClient->first_name . ' ' . $logServiceClient->last_name,
                'total_service_cost' => $service['cost'] + $service['under'] + $service['charge'],
                'status' => $service['status']
            ]);
        }

        activity()
            ->tap(function(Activity $activity) {
                $activity->log_name = 'transaction';
            })
            ->causedBy($createdBy)
            ->performedOn($transaction)
            ->withProperties([
                'tracking' => $transaction->tracking,
                'discount' => $request->discount,
                'services' => $logServices
            ])
            ->log('Transaction was created.');

        return $this->sendResponse(true, 'Transaction has been successfully created.', [], 200);
    }

    public function additionalServices(Request $request) {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required',
            'services' => 'required|array',
            'services.*.id' => 'required',
            'services.*.client_id' => 'required',
            'services.*.cost' => 'required|numeric',
            'services.*.under' => 'required|numeric',
            'services.*.charge' => 'required|numeric',
            'services.*.status' => 'required|in:Pending,On Process,Completed,Released',
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $transaction = Transaction::find($request->transaction_id);
        
        if(!$transaction) {
            return $this->sendResponse(false, 'Transaction not found.', [], 404);
        } else {
            $createdBy = Auth::user();
            $logServices = [];

            foreach($request->services as $service) {
                $transaction->services()->attach($service['id'], [
                    'client_id' => $service['client_id'],
                    'group_id' => $service['group_id'],
                    'cost' => $service['cost'],
                    'under' => $service['under'],
                    'charge' => $service['charge'],
                    'status' => $service['status'],
                    'created_by' => $createdBy->id
                ]);

                $logService = Service::withTrashed()->find($service['id']);
                $logServiceClient = Client::find($service['client_id']);
                array_push($logServices, [
                    'service_name' => $logService->name,
                    'parent_service_name' => $logService->parentService->name,
                    'client_name' => $logServiceClient->first_name . ' ' . $logServiceClient->last_name,
                    'total_service_cost' => $service['cost'] + $service['under'] + $service['charge'],
                    'status' => $service['status']
                ]);
            }

            activity()
                ->tap(function(Activity $activity) {
                    $activity->log_name = 'transaction';
                })
                ->causedBy($createdBy)
                ->performedOn($transaction)
                ->withProperties([
                    'tracking' => $transaction->tracking,
                    'services' => $logServices
                ])
                ->log(count($request->services) > 1 ? 'Services were added.' : 'Service was added.');

            return $this->sendResponse(true, 'Transaction service has been successfully added.', [], 200);
        }
    }

    public function updateDiscount(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'discount' => 'required|numeric'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $transaction = Transaction::find($id);
        $oldDiscount = $transaction->discount;
        
        if(!$transaction) {
            return $this->sendResponse(false, 'Transaction not found.', [], 404);
        } else {
            $transaction->fill($request->all())->save();

            if($oldDiscount != $request->discount) {
                activity()
                    ->tap(function(Activity $activity) {
                        $activity->log_name = 'transaction';
                    })
                    ->causedBy(Auth::user())
                    ->performedOn($transaction)
                    ->withProperties([
                        'tracking' => $transaction->tracking,
                        'field' => 'discount',
                        'old' => $oldDiscount,
                        'new' => $request->discount
                    ])
                    ->log('Transaction was updated.');
            }

            return $this->sendResponse(true, 'Transaction discount has been successfully updated.', [], 200);
        }
    }

    public function destroy(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'deletion_reason' => 'required'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $transaction = Transaction::find($id);
        
        if(!$transaction) {
            return $this->sendResponse(false, 'Transaction not found.', [], 404);
        } else {
            $deletedBy = Auth::user();
            $deletionReason = $request->deletion_reason;

            $serviceTransactions = ServiceTransaction::where('transaction_id', $id);
            $serviceTransactions->update([
                'deleted_by' => $deletedBy->id,
                'deletion_reason' => $deletionReason
            ]);
            $serviceTransactions->delete();

            $payments = Payment::where('transaction_id', $id);
            $payments->update([
                'deleted_by' => $deletedBy->id,
                'deletion_reason' => $deletionReason
            ]);
            $payments->delete();
            
            $transaction->update([
                'deleted_by' => $deletedBy->id,
                'deletion_reason' => $deletionReason
            ]);
            $transaction->delete();

            activity()
                ->tap(function(Activity $activity) {
                    $activity->log_name = 'transaction';
                })
                ->causedBy($deletedBy)
                ->performedOn($transaction)
                ->withProperties([
                    'tracking' => $transaction->tracking,
                    'deletion_reason' => $deletionReason,
                ])
                ->log('Transaction was cancelled.');

            return $this->sendResponse(true, 'Transaction has been successfully cancelled.', [], 200);
        }
    }

    public function restore($id) {
        $transaction = Transaction::withTrashed()->find($id);

        if(!$transaction) {
            return $this->sendResponse(false, 'Transaction not found.', [], 404);
        } else {
            $transaction->update([
                'deleted_by' => null,
                'deletion_reason' => null
            ]);
            $transaction->restore();

            activity()
                ->tap(function(Activity $activity) {
                    $activity->log_name = 'transaction';
                })
                ->causedBy(Auth::user())
                ->performedOn($transaction)
                ->withProperties([
                    'tracking' => $transaction->tracking,
                    'discount' => $transaction->discount,
                ])
                ->log('Transaction was restored.');

            $serviceTransactions = ServiceTransaction::withTrashed()->where('transaction_id', $id);
            $serviceTransactions->update([
                'deleted_by' => null,
                'deletion_reason' => null
            ]);
            $serviceTransactions->restore();

            $payments = Payment::withTrashed()->where('transaction_id', $id);
            $payments->update([
                'deleted_by' => null,
                'deletion_reason' => null
            ]);
            $payments->restore();

            return $this->sendResponse(true, 'Transaction has been successfully restored.', [], 200);
        }
    }

}
