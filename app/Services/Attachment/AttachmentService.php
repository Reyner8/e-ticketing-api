<?php

namespace App\Services\Attachment;

use App\Models\Attachment;
use App\Services\Log\ActivityLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    private const max_file_size_bytes = 10 * 1024 * 1024;

    private const allow_mime_types = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'application/zip'
    ];

    protected ActivityLogService $logService;

    public function __construct(ActivityLogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Upload file to storage and save metadata to database
     * 
     * @param UploadedFile $file    File from request
     * @param Model $attachable     Resource owner
     * @param int $uploadedBy       Uploader user id
     * @return Attachment
     */

    public function upload(
        UploadedFile $file,
        Model $attachable,
        int $uploadedBy
    ): Attachment {
        $folder = $this->resolveFolder($attachable);
        $path = $file->store($folder, 'public');

        $attachment = $attachable->attachments()->create([
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'type' => $file->getType(),
            'url' => Storage::url($path),
            'uploaded_by' => $uploadedBy
        ]);

        $this->logService->logAttachmentAdded(
            loggable: $attachable,
            fileName: $file->getClientOriginalName()
        );

        return $attachment;
    }

    /**
     * Duplicate resource-level attachments from one model to another.
     * Used when a ticket is converted so the original evidence (e.g. public
     * submission screenshots) follows the new Error Report / Feature Request.
     * Comment-scoped attachments are skipped.
     *
     * @return int Number of attachments copied
     */
    public function copyToResource(Model $source, Model $target, int $copiedBy): int
    {
        $copied = 0;
        $folder = $this->resolveFolder($target);

        $attachments = $source->attachments()
            ->whereNull('comment_id')
            ->get();

        foreach ($attachments as $attachment) {
            $sourcePath = Str::after($attachment->url, '/storage/');

            if (! Storage::disk('public')->exists($sourcePath)) {
                continue;
            }

            $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
            $newPath = $folder . '/' . Str::uuid()->toString()
                . ($extension ? '.' . $extension : '');

            Storage::disk('public')->copy($sourcePath, $newPath);

            $target->attachments()->create([
                'name' => $attachment->name,
                'size' => $attachment->size,
                'type' => $attachment->type,
                'url' => Storage::url($newPath),
                'uploaded_by' => $copiedBy,
            ]);

            $copied++;
        }

        return $copied;
    }

    public function getByAttachable(Model $attachable, int $perPage = 15): LengthAwarePaginator
    {
        return $attachable->attachments()
            ->with('uploader:id,name,username')
            ->latest('uploaded_at')
            ->paginate(min($perPage, 50));
    }

    public function delete(Attachment $attachment, Model $attachable): void
    {
        if (
            (int) $attachment->attachable_id !== (int) $attachable->getKey()
            || $attachment->attachable_type !== $this->getMorphAlias($attachable)
        ) {
            abort(403, 'This attachment does not belong to that resource');
        }

        $this->deleteFileFromStorage($attachment->url);

        $attachment->delete();  
    }

    // Helper
    //* Define subfolder from model type
    private function resolveFolder(Model $attachable): string
    {
        $type = class_basename($attachable);

        return match ($type) {
            'Ticket' => 'attachments/tickets',
            'ErrorReport' => 'attachments/error-reports',
            'FeatureRequest' => 'attachments/feature-requests',
            'Comment' => 'attachments/comments',
            'Default' => 'attachments/misc',
        };
    }

    private function getMorphAlias(Model $attachable): string
    {
        return Relation::getMorphAlias(
            get_class($attachable)
        );
    }

    private function deleteFileFromStorage(string $url): void
    {
        $path = Str::after($url, '/storage/');

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
