<?php

namespace App\Http\Controllers\Api\v1\StatusHistory;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatusHistory\UpdateStatusHistoryRequest;
use App\Models\ErrorReport;
use App\Services\StatusHistoryService;
use App\Traits\HandleStatusHistory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ErrorReportStatusHistoryController extends Controller
{
    use HandleStatusHistory;

    public function __construct(
        protected StatusHistoryService $statusHistoryService
    ) {}

    protected function getStatusHistoryService(): StatusHistoryService
    {
        return $this->statusHistoryService;
    }

    public function index(Request $request, ErrorReport $error) : JsonResponse 
    {
        return $this->indexStatusHistory($request, $error);    
    }

    public function update(UpdateStatusHistoryRequest $request, ErrorReport $error) : JsonResponse 
    {
        return $this->updateStatus($request, $error);    
    }
}
