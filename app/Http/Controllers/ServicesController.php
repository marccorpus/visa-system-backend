<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\BaseController as BaseController;

use Validator;

use App\Models\ParentService;
use App\Models\Service;
use App\Models\RateService;
use App\Models\DocumentService;

class ServicesController extends BaseController
{
    
    public function index() {
        $withTrashed = request()->withTrashed;

        if($withTrashed == '1') {
            $response['parent_services'] = ParentService::withTrashed()->with(['services' => function($query) {
                $query->withTrashed()->orderBy('name');
            }])->orderBy('name')->get();
        } else {
            $response['parent_services'] = ParentService::with(['services' => function($query) {
                $query->orderBy('name');
            }])->orderBy('name')->get();
        }
        
        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function indexWithRates() {
        $response['parent_services'] = ParentService::with(['services' => function($query) {
            $query->orderBy('name');
        }])->with(['services.rates' => function ($query) {
            $query->where('rates.id', 1);
        }])->orderBy('name')->get();

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:parent,child',
            'parent_service_id' => 'nullable|required_if:type,child|integer',
            'name' => 'required|max:255|unique:'.($request->type == 'parent' ? 'parent_services' : 'services').',name',
            'processing_days' => 'nullable|required_if:processing_type,Fix|sometimes|integer',
            'processing_minimum_days' => 'nullable|required_if:processing_type,Range|integer',
            'processing_maximum_days' => 'nullable|required_if:processing_type,Range|integer',
            'rates' => 'required_if:type,child|array',
            'rates.*.id' => 'required_if:type,child|integer',
            'rates.*.cost' => 'required_if:type,child|numeric',
            'rates.*.under' => 'required_if:type,child|numeric',
            'rates.*.charge' => 'required_if:type,child|numeric',
            'documents_to_be_receive' => 'sometimes|array',
            'documents_to_be_release' => 'sometimes|array'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        if($request->type == 'parent') {
            ParentService::create($request->all());
        } elseif($request->type == 'child') {
            $service = Service::create([
                'parent_service_id' => $request->parent_service_id,
                'name' => $request->name,
                'processing_type' => $request->processing_type,
                'processing_days' => ($request->processing_type == 'fix') ? $request->processing_days : null,
                'processing_minimum_days' => ($request->processing_type == 'range') ? $request->processing_minimum_days : null,
                'processing_maximum_days' => ($request->processing_type == 'range') ? $request->processing_maximum_days : null
            ]);

            foreach($request->rates as $rate) {
                $service->rates()->attach($rate['id'], [
                    'cost' => $rate['cost'],
                    'under' => $rate['under'],
                    'charge' => $rate['charge']
                ]);
            }

            foreach($request->documents_to_be_receive as $document) {
                $service->documents()->attach($document, [
                    'type' => 'to be receive'
                ]);
            }

            foreach($request->documents_to_be_release as $document) {
                $service->documents()->attach($document, [
                    'type' => 'to be release'
                ]);
            }
        }

        return $this->sendResponse(true, 'Service has been successfully added.', [], 200);
    }

    public function show($id) {
        $type = request()->type;
        $withTrashed = request()->withTrashed;

        if($type == 'parent') {
            if($withTrashed == '1') {
                $service = ParentService::withTrashed()->find($id);
            } else {
                $service = ParentService::find($id);
            }
        } elseif($type == 'child') {
            if($withTrashed == '1') {
                $service = Service::withTrashed()->with(['rates' => function($query) {
                    $query->withTrashed()->orderBy('name');
                }])->with(['documents' => function($query) {
                    $query->withTrashed()->orderBy('name');
                }])->find($id);
            } else {
                $service = Service::with(['rates' => function($query) {
                    $query->orderBy('name');
                }])->with(['documents' => function($query) {
                    $query->orderBy('name');
                }])->find($id);
            }   
        }

        if(!$service) {
            return $this->sendResponse(false, 'Service not found.', [], 404);
        } else {
            $response['service'] = $service;

            return $this->sendResponse(true, 'Success.', $response, 200);
        }
    }

    public function update(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:parent,child',
            'parent_service_id' => 'nullable|required_if:type,child|integer',
            'name' => 'required|max:255|unique:'.($request->type == 'parent' ? 'parent_services' : 'services').',name,'.$id,
            'processing_days' => 'nullable|required_if:processing_type,Fix|sometimes|integer',
            'processing_minimum_days' => 'nullable|required_if:processing_type,Range|integer',
            'processing_maximum_days' => 'nullable|required_if:processing_type,Range|integer',
            'rates' => 'required_if:type,child|array',
            'rates.*.id' => 'required_if:type,child|integer',
            'rates.*.cost' => 'required_if:type,child|numeric',
            'rates.*.under' => 'required_if:type,child|numeric',
            'rates.*.charge' => 'required_if:type,child|numeric',
            'documents_to_be_receive' => 'sometimes|array',
            'documents_to_be_release' => 'sometimes|array'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        if($request->type == 'parent') {
            $service = ParentService::find($id);
        } elseif($request->type == 'child') {
            $service = Service::find($id);
        }
        
        if(!$service) {
            return $this->sendResponse(false, 'Service not found.', [], 404);
        } else {
            if($request->type == 'parent') {
                $service->fill($request->all())->save();
            } elseif($request->type == 'child') {
                $service->update([
                    'parent_service_id' => $request->parent_service_id,
                    'name' => $request->name,
                    'processing_type' => $request->processing_type,
                    'processing_days' => ($request->processing_type == 'fix') ? $request->processing_days : null,
                    'processing_minimum_days' => ($request->processing_type == 'range') ? $request->processing_minimum_days : null,
                    'processing_maximum_days' => ($request->processing_type == 'range') ? $request->processing_maximum_days : null
                ]);

                foreach($request->rates as $rate) {
                    RateService::updateOrCreate(
                        ['rate_id' => $rate['id'], 'service_id' => $service->id],
                        ['cost' => $rate['cost'], 'under' => $rate['under'], 'charge' => $rate['charge']]
                    );
                }

                foreach($request->documents_to_be_receive as $document) {
                    DocumentService::updateOrCreate(
                        ['document_id' => $document, 'service_id' => $service->id],
                        ['type' => 'to be receive']
                    );
                }
                foreach($request->documents_to_be_release as $document) {
                    DocumentService::updateOrCreate(
                        ['document_id' => $document, 'service_id' => $service->id],
                        ['type' => 'to be release']
                    );
                }
            }

            return $this->sendResponse(true, 'Service has been successfully updated.', [], 200);
        }
    }

    public function destroy($id) {
        $type = request()->type;

        if($type == 'parent') {
            $service = ParentService::find($id);
            
        } elseif($type == 'child') {
            $service = Service::find($id);
        }
        
        if(!$service) {
            return $this->sendResponse(false, 'Service not found.', [], 404);
        } else {
            if($type == 'parent') {
                Service::where('parent_service_id', $id)->delete();
            }
            $service->delete();

            return $this->sendResponse(true, 'Service has been successfully deleted.', [], 200);
        }
    }

    public function restore($id) {
        $type = request()->type;

        if($type == 'parent') {
            $service = ParentService::withTrashed()->find($id);
        } elseif($type == 'child') {
            $service = Service::withTrashed()->find($id);
        }

        if(!$service) {
            return $this->sendResponse(false, 'Service not found.', [], 404);
        } else {
            if($type == 'parent') {
                Service::withTrashed()->where('parent_service_id', $id)->restore();
            }
            $service->restore();

            return $this->sendResponse(true, 'Service has been successfully restored.', [], 200);
        }
    }

}
