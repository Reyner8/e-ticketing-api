<?php

namespace App\Http\Controllers\Api\v1\StatusHistory;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatusHistory\UpdateStatusHistoryRequest;
use App\Models\Ticket;
use App\Services\StatusHistoryService;
use App\Traits\HandleStatusHistory;
use Illuminate\Http\Request;

class TicketStatusHistoryController extends Controller
{
    use HandleStatusHistory;

    public function __construct(
        protected StatusHistoryService $statusHistoryService
    ) {}

    protected function getStatusHistoryService(): StatusHistoryService
    {
        return $this->statusHistoryService;
    }

    public function index(Request $request, Ticket $ticket)
    {
        return $this->indexStatusHistory($request, $ticket);
    }

    public function update(UpdateStatusHistoryRequest $request, Ticket $ticket)
    {
        return $this->updateStatus($request, $ticket);
    }
}
