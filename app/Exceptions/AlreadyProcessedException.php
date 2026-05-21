<?php

namespace App\Exceptions;

use BackedEnum;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

class AlreadyProcessedException extends Exception
{
    public function __construct(
        private readonly string $id,
        private readonly BackedEnum $currentStatus
    ) { 
        parent::__construct(
            "{$id} already processed with {$currentStatus->value} status."
        );  
    }

    public function render(): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'data' => [
                'id' => $this->id,
                'current_status' => $this->currentStatus->value
            ]
        ], 409);
    }
}
