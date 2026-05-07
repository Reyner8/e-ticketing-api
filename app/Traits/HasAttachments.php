<?php

namespace App\Traits;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAttachments
{
    public function attachments(): MorphMany
    {
        /** @var Model $this  */   
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function hasAttachments(): bool
    {
        return $this->attachments()->exists();
    }

    public function attachmentsCount(): int
    {
        return $this->attachments()->count();
    }
}