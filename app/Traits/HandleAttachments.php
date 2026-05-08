<?php

namespace App\Traits;

use App\Helpers\ApiResponse;
use App\Http\Requests\Attachment\StoreAttachmentRequest;
use App\Http\Resources\Attachment\AttachmentResource;
use App\Models\Attachment;
use App\Services\Attachment\AttachmentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Support\Facades\Gate;

trait HandleAttachments
{
    abstract protected function getAttachmentService(): AttachmentService;
    
    public function indexAttachments(Request $request, Model $attachable): JsonResponse
    {
        $attachments = $this->getAttachmentService()->getByAttachable(
            attachable: $attachable,
            perPage: $request->integer('per_page', 15)
        );

        return ApiResponse::paginated(
            $attachments,
            AttachmentResource::collection($attachments),
            'Attachment Retrieved Successfully.'
        );
    }

    public function storeAttachments(StoreAttachmentRequest $request, Model $attachable) : JsonResponse 
    {
        $attachment = $this->getAttachmentService()->upload(
            file: $request->file('file'),
            attachable: $attachable,
            uploadedBy: Auth::id()
        );
        
        return ApiResponse::success(
            new AttachmentResource($attachment->load('uploader')),
            'File Upload Successfully',
            201
        );
    }

    public function destroyAttachments(Model $attachable, Attachment $attachment) : JsonResponse 
    {
        Gate::authorize('delete', $attachment);

        $this->getAttachmentService()->delete(
            attachment: $attachment,
            attachable: $attachable
        );

        return ApiResponse::success(
            null,
            'Attachment Deleted Successfully'
        );
    }
}