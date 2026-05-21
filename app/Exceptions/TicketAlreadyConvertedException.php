<?php

namespace App\Exceptions;

use App\Enums\ConversionTypes;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Support\Carbon;

class TicketAlreadyConvertedException extends Exception
{
    public function __construct(
        private readonly string $ticketId,
        private readonly ConversionTypes $convertedToType,
        private readonly string $convertedToId,
        private readonly Carbon $convertedAt,
    ) {
        parent::__construct(
            "Ticket {$ticketId} already converted to " .
            strtoupper($convertedToType->value) . " ({$convertedToId}) " .
            "on {$convertedAt}."
             
        );
    }
    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse 
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'data' => [
                'ticket_id' => $this->ticketId,
                'converted_to_type' => $this->convertedToType->value,
                'converted_to_id' => $this->convertedToId,
                'converted_at' => $this->convertedAt
            ]
        ], 409);
    }

    // getter
    public function getTicketId(): string { return $this->ticketId; }
    public function getConvertedToType(): ConversionTypes { return $this->convertedToType; }
    public function getConvertedToId(): string { return $this->convertedToId; }
    public function getConvertedAt(): Carbon { return $this->convertedAt; }
}
