<?php

namespace App\Http\Controllers\Api\v1\Attachment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attachment\StoreAttachmentRequest;
use App\Models\Attachment;
use App\Models\BackupRestoreTest;
use App\Services\Attachment\AttachmentService;
use App\Traits\HandleAttachments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackupRestoreTestAttachmentController extends Controller
{
    use HandleAttachments;

    public function __construct(
        protected AttachmentService $attachmentService
    ) {}

    protected function getAttachmentService(): AttachmentService
    {
        return $this->attachmentService;
    }

    public function index(Request $request, BackupRestoreTest $backup_restore_test): JsonResponse
    {
        return $this->indexAttachments($request, $backup_restore_test);
    }

    public function store(StoreAttachmentRequest $request, BackupRestoreTest $backup_restore_test): JsonResponse
    {
        return $this->storeAttachments($request, $backup_restore_test);
    }

    public function destroy(BackupRestoreTest $backup_restore_test, Attachment $attachment): JsonResponse
    {
        return $this->destroyAttachments($backup_restore_test, $attachment);
    }
}
