<?php

namespace App\Http\Controllers\Api\v1\StatusHistory;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatusHistory\UpdateFeatureRequestStatusRequest;
use App\Models\FeatureRequest;
use App\Services\StatusHistoryService;
use App\Traits\HandleStatusHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeatureRequestStatusHistoryController extends Controller
{
    use HandleStatusHistory;

    public function __construct(
        protected StatusHistoryService $statusHistoryService
    ) {}

    protected function getStatusHistoryService(): StatusHistoryService
    {
        return $this->statusHistoryService;
    }

    public function index(Request $request, FeatureRequest $feature) : JsonResponse 
    {
        return $this->indexStatusHistory($request, $feature);   
    }

    public function update(UpdateFeatureRequestStatusRequest $request, FeatureRequest $feature) : JsonResponse 
    {
        return $this->updateStatus($request, $feature);    
    }
}
