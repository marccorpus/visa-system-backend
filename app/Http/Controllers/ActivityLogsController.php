<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\BaseController as BaseController;

use Spatie\Activitylog\Models\Activity;
use App\Models\User;
use App\Models\Payment;
use App\Models\Transaction;

class ActivityLogsController extends BaseController
{
    
    public function clientProfile($id) {
        $response['logs'] = Activity::where('log_name', 'profile')
            ->where('subject_type', 'App\Models\Client')
            ->where('subject_id', $id)
            ->orderBy('id', 'desc')->paginate(10);

        $updatedItems = $response['logs']->getCollection();
        $updatedItems->map(function($item) {
            $item['causer'] = User::withTrashed()->find($item['causer_id']);

            return $item;
        })->toArray();
        $response['logs']->setCollection($updatedItems);

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function clientPayment($id) {
        $paymentIds = Payment::whereHas('transaction', function($query) use($id) {
            $query->withTrashed()->whereHas('serviceTransactions', function($query2) use($id) {
                $query2->where('client_id', $id)->where('group_id', null)->withTrashed();
            });
        })->withTrashed()->pluck('id');

        $response['logs'] = Activity::where('log_name', 'payment')
            ->where('subject_type', 'App\Models\Payment')
            ->whereIn('subject_id', $paymentIds)
            ->orderBy('id', 'desc')->paginate(10);

        $updatedItems = $response['logs']->getCollection();
        $updatedItems->map(function($item) {
            $item['causer'] = User::withTrashed()->find($item['causer_id']);
    
            return $item;
        })->toArray();
        $response['logs']->setCollection($updatedItems);
    
        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function clientTransaction($id) {
        $transactionIds = Transaction::whereHas('serviceTransactions', function($query) use($id) {
                $query->where('client_id', $id)->where('group_id', null)->withTrashed();
            })
            ->withTrashed()->pluck('id');

        $response['logs'] = Activity::where('log_name', 'transaction')
            ->where('subject_type', 'App\Models\Transaction')
            ->whereIn('subject_id', $transactionIds)
            ->orderBy('id', 'desc')->paginate(10);

        $updatedItems = $response['logs']->getCollection();
        $updatedItems->map(function($item) {
            $item['causer'] = User::withTrashed()->find($item['causer_id']);
    
            return $item;
        })->toArray();
        $response['logs']->setCollection($updatedItems);
    
        return $this->sendResponse(true, 'Success.', $response, 200);

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function groupProfile($id) {
        $response['logs'] = Activity::where('log_name', 'profile')
            ->where('subject_type', 'App\Models\Group')
            ->where('subject_id', $id)
            ->orderBy('id', 'desc')->paginate(10);

        $updatedItems = $response['logs']->getCollection();
        $updatedItems->map(function($item) {
            $item['causer'] = User::withTrashed()->find($item['causer_id']);

            return $item;
        })->toArray();
        $response['logs']->setCollection($updatedItems);

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function groupPayment($id) {
        $paymentIds = Payment::whereHas('transaction', function($query) use($id) {
            $query->withTrashed()->whereHas('serviceTransactions', function($query2) use($id) {
                $query2->where('group_id', $id)->withTrashed();
            });
        })->withTrashed()->pluck('id');

        $response['logs'] = Activity::where('log_name', 'payment')
            ->where('subject_type', 'App\Models\Payment')
            ->whereIn('subject_id', $paymentIds)
            ->orderBy('id', 'desc')->paginate(10);

        $updatedItems = $response['logs']->getCollection();
        $updatedItems->map(function($item) {
            $item['causer'] = User::withTrashed()->find($item['causer_id']);
    
            return $item;
        })->toArray();
        $response['logs']->setCollection($updatedItems);
    
        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function groupTransaction($id) {
        $transactionIds = Transaction::whereHas('serviceTransactions', function($query) use($id) {
            $query->where('group_id', $id)->withTrashed();
        })
        ->withTrashed()->pluck('id');

        $response['logs'] = Activity::where('log_name', 'transaction')
            ->where('subject_type', 'App\Models\Transaction')
            ->whereIn('subject_id', $transactionIds)
            ->orderBy('id', 'desc')->paginate(10);

        $updatedItems = $response['logs']->getCollection();
        $updatedItems->map(function($item) {
            $item['causer'] = User::withTrashed()->find($item['causer_id']);

            return $item;
        })->toArray();
        $response['logs']->setCollection($updatedItems);

        return $this->sendResponse(true, 'Success.', $response, 200);

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

}
