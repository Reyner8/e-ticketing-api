<?php

namespace App\Http\Requests\StatusHistory;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusHistoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in($this->getAllowedStatus()),

                function ($attribute, $value, $fail) {
                    $resource = $this->route('ticket') ??
                    $this->route('errorReport') ?? 
                    $this->route('featureRequest');

                    if ($resource && $resource->status === $value) {
                        $fail("Status is already '{$value}'.");
                    }
                }
                
            ],
            'reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    // Helper
    private function getAllowedStatus() : array 
    {
        return [
            'draft',
            'pending_approval',
            'assigned',
            'in_progress',
            'waiting_for_user',
            'approved',
            'resolved',
            'closed',
            'converted',
            'completed',
            'overdue',
            'submission',
            'accepted',
            'rejected',
            'development',
            'testing',
            'validation',
            'post_implementation_review'
        ];    
    }
}
