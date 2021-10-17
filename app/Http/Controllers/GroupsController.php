<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\BaseController as BaseController;

use Auth, DB, Validator;

use App\Models\Group;
use App\Models\Client;
use App\Models\Transaction;
use App\Models\ServiceTransaction;
use App\Models\Payment;

use Spatie\Activitylog\Models\Activity;

class GroupsController extends BaseController
{
    
    public function index() {
        $search = request()->search;

        $response['groups'] = Group::orderBy('name')->get();

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function indexPagination() {
        $response['groups'] = Group::withMax('serviceTransactions', 'created_at')
            ->withCount(['serviceTransactions as total_service_cost' => function($query) {
                $query->select(DB::raw('sum(cost) + sum(under) + sum(charge)'));
            }])
            ->withCount(['serviceTransactions as total_completed_service_cost' => function($query) {
                $query->select(DB::raw('sum(cost) + sum(under) + sum(charge)'))
                    ->where('status', 'Completed')->orWhere('status', 'Released');
            }]);

        $search = request()->search;

        if($search) {
            $response['groups'] = $response['groups']
                ->where('id', 'like', '%'.$search.'%')
                ->orWhere('name', 'like', '%'.$search.'%');
        }

        $response['groups'] = $response['groups']->orderBy('id', 'desc')->paginate(10);

        $updatedItems = $response['groups']->getCollection();
        $updatedItems->map(function($item) {
            $item['total_discount'] = $this->getTotalDiscount($item['id']);
            $item['total_amount_paid'] = $this->getTotalAmountPaid($item['id']);

            return $item;
        })->toArray();
        $response['groups']->setCollection($updatedItems);

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    private function getTotalDiscount($id) {
        return Transaction::whereHas('serviceTransactions', function($query) use($id) {
            $query->where('group_id', $id);
        })
        ->sum('discount');
    }

    private function getTotalAmountPaid($id) {
        return Payment::whereHas('transaction.serviceTransactions', function($query) use($id) {
            $query->where('group_id', $id);
        })
        ->sum('amount');
    }

    public function indexStatistics() {
        $totalServiceCost = ServiceTransaction::whereNotNull('group_id')
            ->sum(DB::raw('cost + under + charge'));

        $totalCompletedServiceCost = ServiceTransaction::whereNotNull('group_id')
            ->where(function($query) {
                $query->where('status', 'Completed')->orWhere('status', 'Released');
            })
            ->sum(DB::raw('cost + under + charge'));

        $totalDiscount = Transaction::whereHas('serviceTransactions', function($query) {
                $query->whereNotNull('group_id');
            })
            ->sum('discount');

        $totalAmountPaid = Payment::whereHas('transaction.serviceTransactions', function($query) {
                $query->whereNotNull('group_id');
            })
            ->sum('amount');

        $response['totalBalance'] = ($totalServiceCost - $totalDiscount) - $totalAmountPaid;

        $response['totalCollectable'] = ($totalCompletedServiceCost - $totalDiscount) - $totalAmountPaid;

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255|unique:groups,name'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $group = Group::create($request->all());
        $response['id'] = $group->id;

        return $this->sendResponse(true, 'Group has been successfully added.', $response, 200);
    }

    public function addMembers(Request $request) {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required',
            'clients' => 'required|array'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        foreach($request->clients as $client) {
            Client::find($client)->update(['group_id' => $request->group_id]);
        }

        activity()
            ->tap(function(Activity $activity) {
                $activity->log_name = 'profile';
            })
            ->causedBy(Auth::user())
            ->performedOn(Group::find($request->group_id))
            ->withProperties([
                'clients' => Client::whereIn('id', $request->clients)->get()
            ])
            ->log('New member'. (count($request->clients) > 1 ? 's' : '') .' was added.');

        return $this->sendResponse(true, 'New member'. (count($request->clients) > 1 ? 's have' : ' has') .' been successfully added.', [], 200);
    }

    public function removeMember(Request $request) {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required',
            'client_id' => 'required'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        Client::find($request->client_id)->update(['group_id' => null]);

        activity()
            ->tap(function(Activity $activity) {
                $activity->log_name = 'profile';
            })
            ->causedBy(Auth::user())
            ->performedOn(Group::find($request->group_id))
            ->withProperties([
                'clients' => Client::whereIn('id', [$request->client_id])->get()
            ])
            ->log('Member was removed.');

        return $this->sendResponse(true, 'Member has been successfully removed.', [], 200);
    }

    public function show($id) {
        $group = Group::find($id);

        if(!$group) {
            return $this->sendResponse(false, 'Group not found.', [], 404);
        } else {
            $response['group'] = $group;

            return $this->sendResponse(true, 'Success.', $response, 200);
        }
    }

    public function statistics($id) {
        $response['totalServiceCost'] = ServiceTransaction::where('group_id', $id)
            ->sum(DB::raw('cost + under + charge'));

        $response['totalCompletedServiceCost'] = ServiceTransaction::where('group_id', $id)
            ->where(function($query) {
                $query->where('status', 'Completed')->orWhere('status', 'Released');
            })
            ->sum(DB::raw('cost + under + charge'));

        $response['totalDiscount'] = Transaction::whereHas('serviceTransactions', function($query) use($id) {
                $query->where('group_id', $id);
            })
            ->sum('discount');

        $response['totalAmountPaid'] = Payment::whereHas('transaction.serviceTransactions', function($query) use($id) {
                $query->where('group_id', $id);
            })
            ->sum('amount');

        $response['totalBalance'] = ($response['totalServiceCost'] - $response['totalDiscount']) - $response['totalAmountPaid'];

        $response['totalCollectable'] = ($response['totalCompletedServiceCost'] - $response['totalDiscount']) - $response['totalAmountPaid'];

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function members($id) {
        $response['members'] = Client::where('group_id', $id)->orderBy('first_name')->orderBy('last_name')->get();

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function nonMembers($id) {
        $response['non_members'] = Client::where('group_id', '<>', $id)
            ->orWhereNull('group_id')
            ->orderBy('first_name')->orderBy('last_name')->get();

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function membersPagination($id) {
        $response['members'] = Client::where('group_id', $id);

        $search = request()->search;

        if($search) {
            $response['members'] = $response['members']
                ->where(function($query) use($search) {
                    $query->where('id', 'like', '%'.$search.'%')
                        ->orWhere(DB::raw('CONCAT_WS(" ", first_name, last_name)'), 'like', '%' . $search . '%')
                        ->orWhere(DB::raw('CONCAT_WS(" ", last_name, first_name)'), 'like', '%' . $search . '%');
                });
        }

        $response['members'] = $response['members']->orderBy('id', 'desc')->paginate(10);
        
        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function updateBasicInformation(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255|unique:groups,name,'.$id
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $group = Group::find($id);
        
        if(!$group) {
            return $this->sendResponse(false, 'Group not found.', [], 404);
        } else {
            $group->fill($request->all())->save();

            return $this->sendResponse(true, 'Group has been successfully updated.', [], 200);
        }
    }

    public function updateContactInformation(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'contact_number' => 'nullable|digits:10'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $group = Group::find($id);
        
        if(!$group) {
            return $this->sendResponse(false, 'Group not found.', [], 404);
        } else {
            $group->fill($request->all())->save();

            return $this->sendResponse(true, 'Group has been successfully updated.', [], 200);
        }
    }

}
