<?php

namespace App\Traits;

use App\Models\StatusHistory;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasStatusHistory
{
    public function statusHistories(): MorphMany
    {
        return $this->morphMany(StatusHistory::class, 'statusable');
    }

    public function latestStatusHistories(): MorphMany
    {
        return $this->morphMany(StatusHistory::class, 'statusable')
        ->latest('changed_at')
        ->limit(1);
    }

    // Helpers
    public function hasStatusHistories(): bool
    {
        return $this->statusHistories()->exists();
    }

    public function statusHistoryCounts() : int 
    {
        return $this->statusHistories()->count();    
    }
}