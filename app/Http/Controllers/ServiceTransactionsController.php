<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\BaseController as BaseController;

use Auth, Validator;

use App\Models\ServiceTransaction;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\Client;

use Spatie\Activitylog\Models\Activity;

class ServiceTransactionsController extends BaseController
{
    
    public function transactionServices($id) {
        $response['transaction_services'] = ServiceTransaction::where('transaction_id', $id)
            ->with(['service' => function($query) {
                $query->withTrashed()->with(['parentService' => function($query2) {
                    $query2->withTrashed();
                }]);
            }])
            ->with('client')
            ->with(['deletedBy' => function($query) {
                $query->withTrashed();
            }])
            ->withTrashed()
            ->orderBy('id', 'desc')->get();

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function update(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Pending,On Process,Completed,Released'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $transactionService = ServiceTransaction::find($id);
        $oldStatus = $transactionService->status;
        
        if(!$transactionService) {
            return $this->sendResponse(false, 'Transaction service not found.', [], 404);
        } else {
            $transactionService->fill($request->all())->save();

            if($oldStatus != $request->status) {
                $performedOn = Transaction::find($transactionService->transaction_id);
                $logTracking = $performedOn->tracking;
                $service = Service::withTrashed()->find($transactionService->service_id);
                $client = Client::find($transactionService->client_id);
                $logServices = [];
                $logService = [
                    'service_name' => $service->name,
                    'parent_service_name' => $service->parentService->name,
                    'client_name' => $client->first_name . ' ' . $client->last_name,
                    'total_service_cost' => $transactionService->cost + $transactionService->under + $transactionService->charge,
                    'status' => $oldStatus,
                    'new_status' => $request->status
                ];
                array_push($logServices, $logService);

                activity()
                    ->tap(function(Activity $activity) {
                        $activity->log_name = 'transaction';
                    })
                    ->causedBy(Auth::user())
                    ->performedOn($performedOn)
                    ->withProperties([
                        'tracking' => $logTracking,
                        'services' => $logServices
                    ])
                    ->log('Service status was updated.');
            }

            return $this->sendResponse(true, 'Transaction service has been successfully updated.', [], 200);
        }
    }

    public function batchUpdate(Request $request) {
        $validator = Validator::make($request->all(), [
            'transaction_services' => 'required|array',
            'transaction_services.*.id' => 'required',
            'transaction_services.*.status' => 'required|in:Pending,On Process,Completed,Released'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $performedOn = null;
        $logTracking = null;
        $logServices = [];
        $changes = false;

        foreach($request->transaction_services as $transaction_service) {
            $temp = ServiceTransaction::find($transaction_service['id']);
            $oldStatus = $temp->status;
            $temp->update(['status' => $transaction_service['status']]);

            if($oldStatus != $transaction_service['status']) {
                $changes = true;

                if(!$performedOn && !$logTracking) {
                    $performedOn = Transaction::find($temp->transaction_id);
                    $logTracking = $performedOn->tracking;
                }
    
                $logService = Service::withTrashed()->find($temp->service_id);
                $logServiceClient = Client::find($temp->client_id);
                array_push($logServices, [
                    'service_name' => $logService->name,
                    'parent_service_name' => $logService->parentService->name,
                    'client_name' => $logServiceClient->first_name . ' ' . $logServiceClient->last_name,
                    'total_service_cost' => $temp->cost + $temp->under + $temp->charge,
                    'status' => $oldStatus,
                    'new_status' => $transaction_service['status']
                ]);
            }
        }

        if($changes) {
            activity()
                ->tap(function(Activity $activity) {
                    $activity->log_name = 'transaction';
                })
                ->causedBy(Auth::user())
                ->performedOn($performedOn)
                ->withProperties([
                    'tracking' => $logTracking,
                    'services' => $logServices
                ])
                ->log(count($request->transaction_services) > 1 ? 'Service status were updated.' : 'Service status was updated.');
        }

        return $this->sendResponse(true, 'Transaction service has been successfully updated.', [], 200);
    }

    public function destroy(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'deletion_reason' => 'required'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $transactionService = ServiceTransaction::find($id);
        
        if(!$transactionService) {
            return $this->sendResponse(false, 'Transaction service not found.', [], 404);
        } else {
            $deletedBy = Auth::user();
            $deletionReason = $request->deletion_reason;

            $performedOn = Transaction::find($transactionService->transaction_id);
            $logTracking = $performedOn->tracking;
            $service = Service::withTrashed()->find($transactionService->service_id);
            $client = Client::find($transactionService->client_id);
            $logServices = [];
            $logService = [
                'service_name' => $service->name,
                'parent_service_name' => $service->parentService->name,
                'client_name' => $client->first_name . ' ' . $client->last_name,
                'total_service_cost' => $transactionService->cost + $transactionService->under + $transactionService->charge,
                'status' => $transactionService->status
            ];
            array_push($logServices, $logService);

            $transactionService->update([
                'deleted_by' => $deletedBy->id,
                'deletion_reason' => $deletionReason
            ]);
            $transactionService->delete();

            activity()
                ->tap(function(Activity $activity) {
                    $activity->log_name = 'transaction';
                })
                ->causedBy($deletedBy)
                ->performedOn($performedOn)
                ->withProperties([
                    'tracking' => $logTracking,
                    'services' => $logServices,
                    'deletion_reason' => $deletionReason,
                ])
                ->log('Service was cancelled.');

            return $this->sendResponse(true, 'Transaction service has been successfully cancelled.', [], 200);
        }
    }

    public function restore($id) {
        $transactionService = ServiceTransaction::withTrashed()->find($id);

        if(!$transactionService) {
            return $this->sendResponse(false, 'Transaction service not found.', [], 404);
        } else {
            $transactionService->restore();

            $performedOn = Transaction::find($transactionService->transaction_id);
            $logTracking = $performedOn->tracking;
            $service = Service::withTrashed()->find($transactionService->service_id);
            $client = Client::find($transactionService->client_id);
            $logServices = [];
            $logService = [
                'service_name' => $service->name,
                'parent_service_name' => $service->parentService->name,
                'client_name' => $client->first_name . ' ' . $client->last_name,
                'total_service_cost' => $transactionService->cost + $transactionService->under + $transactionService->charge,
                'status' => $transactionService->status
            ];
            array_push($logServices, $logService);

            activity()
                ->tap(function(Activity $activity) {
                    $activity->log_name = 'transaction';
                })
                ->causedBy(Auth::user())
                ->performedOn($performedOn)
                ->withProperties([
                    'tracking' => $logTracking,
                    'services' => $logServices
                ])
                ->log('Service was restored.');

            return $this->sendResponse(true, 'Transaction service has been successfully restored.', [], 200);
        }
    }

}
