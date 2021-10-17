<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\BaseController as BaseController;

use Validator;

use App\Models\Document;

class DocumentsController extends BaseController
{
    
    public function index() {
        $withTrashed = request()->withTrashed;

        if($withTrashed == '1') {
            $response['documents'] = Document::withTrashed()->orderBy('name')->get();
        } else {
            $response['documents'] = Document::orderBy('name')->get();
        }

        return $this->sendResponse(true, 'Success.', $response, 200);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255|unique:documents,name'
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        Document::create($request->all());

        return $this->sendResponse(true, 'Document has been successfully added.', [], 200);
    }

    public function show($id) {
        $document = Document::find($id);

        if(!$document) {
            return $this->sendResponse(false, 'Document not found.', [], 404);
        } else {
            $response['document'] = $document;

            return $this->sendResponse(true, 'Success.', $response, 200);
        }
    }

    public function update(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255|unique:documents,name,' .$id
        ]);

        if($validator->fails()) {
            return $this->sendResponse(false, null, ['errors' => $validator->errors()], 422);
        }

        $document = Document::find($id);
        
        if(!$document) {
            return $this->sendResponse(false, 'Document not found.', [], 404);
        } else {
            $document->fill($request->all())->save();

            return $this->sendResponse(true, 'Document has been successfully updated.', [], 200);
        }
    }

    public function destroy($id) {
        $document = Document::find($id);
        
        if(!$document) {
            return $this->sendResponse(false, 'Document not found.', [], 404);
        } else {
            $document->delete();

            return $this->sendResponse(true, 'Document has been successfully deleted.', [], 200);
        }
    }

    public function restore($id) {
        $document = Document::withTrashed()->find($id);

        if(!$document) {
            return $this->sendResponse(false, 'Document not found.', [], 404);
        } else {
            $document->restore();

            return $this->sendResponse(true, 'Document has been successfully restored.', [], 200);
        }
    }

}
