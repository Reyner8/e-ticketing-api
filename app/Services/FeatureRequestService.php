<?php

namespace App\Services;

use function Symfony\Component\Clock\now;

use App\Enums\ConversionTypes;
use App\Models\ConversionHistory;
use App\Models\FeatureRequest;
use Illuminate\Support\Facades\DB;

class FeatureRequestService
{
    public function generateFeatureRequestId(): string
    {
        return DB::transaction(function () {
            $prefix = 'FR';
            $year = now()->format('Y');
            $pattern = "{$prefix}-{$year}-%";

            $lastFeatureRequestId = FeatureRequest::where('id', 'like', $pattern)
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->value('id');

            $lastHistoricalId = ConversionHistory::where(
                'target_type',
                ConversionTypes::FeatureRequest->value
            )
                ->where('target_id', 'like', $pattern)
                ->lockForUpdate()
                ->orderBy('target_id', 'desc')
                ->value('target_id');

            $lastNumber = max(
                $lastFeatureRequestId ? (int) substr($lastFeatureRequestId, -3) : 0,
                $lastHistoricalId ? (int) substr($lastHistoricalId, -3) : 0,
            );
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

            return "{$prefix}-{$year}-{$newNumber}";
        });
    }
}