<?php

namespace App\Http\Controllers\Api\v1;

use App\Exceptions\TicketAlreadyConvertedException;
use App\Exceptions\TicketCannotBeConvertedException;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\ConvertToErrorReportRequest;
use App\Http\Requests\Ticket\ConvertToFeatureRequestRequest;
use App\Http\Resources\Ticket\ConversionResource;
use App\Models\Ticket;
use App\Services\Log\ActivityLogService;
use App\Services\Ticket\TicketConversionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class TicketConversionController extends Controller
{
    public function __construct(
        private readonly TicketConversionService $conversionService,
        private readonly ActivityLogService $logService
    ) {}    

    public function toErrorReport(ConvertToErrorReportRequest $request, string $ticketId, Ticket $ticket): JsonResponse
    {
        try {
            $errorReport = $this->conversionService->convertToErrorReport($ticketId, $request->validated());
            
            //* conversion source
            $this->logService->logConverted(
                loggable: $ticket,
                fromType: 'Ticket',
                toType: 'Error Report'
            );

            //* conversion results
            $this->logService->logCreated($errorReport, [
                'converted_from' => 'ticket',
                'ticket_id' => $ticket->id,
                'ticket_title' => $ticket->title
            ]);

            return ApiResponse::success(
                new ConversionResource($errorReport, $ticketId, 'error_report'),
                'Ticket successfully converted to Error Report',
                201
            );
        } catch (TicketAlreadyConvertedException | TicketCannotBeConvertedException $e) {
            return ApiResponse::error($e->getMessage(), 409);
        } catch (\Exception $e) {
            Log::error('Conversion to Error Report failed', [
                'ticket_id' => $ticketId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return ApiResponse::error('Something went wrong.', 500);
        }
    }

    public function toFeatureRequest(ConvertToFeatureRequestRequest $request, string $ticketId, Ticket $ticket): JsonResponse
    {
        try {
            $featureRequest = $this->conversionService->convertToFeatureRequest($ticketId, $request->validated());

            //* conversion source
            $this->logService->logConverted(
                loggable: $ticket,
                fromType: 'Ticket',
                toType: 'Feature Request'
            );

            //* conversion result
            $this->logService->logCreated($featureRequest, [
                'converted_from' => 'ticket',
                'ticket_id' => $ticket->id,
                'ticket_title' => $ticket->title
            ]);

            return ApiResponse::success(
                new ConversionResource($featureRequest, $ticketId, 'feature_request'),
                'Ticket successfully converted to Feature Request',
                201
            );
        } catch (TicketAlreadyConvertedException | TicketCannotBeConvertedException $e) {
            return ApiResponse::error($e->getMessage(), 409);
        } catch (\Exception $e) {
            Log::error('Conversion to Feature Request failed.', [
                'ticket_id' => $ticketId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return ApiResponse::error('Something went wrong.', 500);
        }
    }
}
