<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitIdentityVerificationRequest;
use App\Http\Requests\UpdateIdentityVerificationRequest;
use App\Http\Resources\SubmitIdentityVerificationResource;
use App\Models\IdentityDocument;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;

class SubmitIdentityVerificationController extends Controller
{
    use AuthorizesRequests, ValidatesRequests; 

    public function store(SubmitIdentityVerificationRequest $request) {
        $this->authorize('create', IdentityDocument::class); 
        $path = $request->file('file')->store('identity-documents/' . $request->user()->id, 'local'); 
        $document = $request->user()->identityDocument()->updateOrCreate([], [
            'document_type' => $request->document_type,
            'file_path' => $path,
            'status_verification' => 'pending',
        ]);

        return response()->json(new SubmitIdentityVerificationResource($document)); 
    }

    public function update(UpdateIdentityVerificationRequest $request, IdentityDocument $document) {
        $this->authorize('update', $document); 
        $document->update([
            'status_verification' => $request->validated()->status_verification,
        ]); 

        if ($request->status_verification === 'approved') {
            $document->user()->update(['is_identity_verified' => true]); 
        }

        return response()->json(new SubmitIdentityVerificationResource($document)); 
    }
}
