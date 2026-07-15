<?php

namespace App\Http\Requests\FeatureRequest;

use App\Enums\AssignedTeam;
use App\Enums\FeatureRequestStatus;
use App\Enums\Priorities;
use App\Enums\RequestType;
use App\Enums\TargetApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class UpdateFeatureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        if ($this->filled('title')) {
            $this->merge([
                'title' => Str::title(trim($this->title))
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'request_type' => ['sometimes', 'string', Rule::in(RequestType::values())],
            'target_application' => ['nullable', 'string', Rule::in(TargetApplication::values())],
            'priority' => ['sometimes', 'string', Rule::in(Priorities::values())],
            'status' => ['sometimes', 'string', Rule::in(FeatureRequestStatus::values())],
            'progress' => 'sometimes|integer|min:0|max:100',
            'reporter_id' => 'sometimes|integer|exists:users,id',
            'assigned_to_id' => 'nullable|integer|exists:users,id',
            'assigned_team' => ['nullable', 'string', 'max:255', Rule::in(AssignedTeam::values())],
            'date_submitted' => 'sometimes|date',
            'approval_date' => 'nullable|date',
            'assignment_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'completion_date' => 'nullable|date',
            'review_date' => 'nullable|date',
            'estimated_effort' => 'nullable|numeric|decimal:0,2',
            'actual_effort' => 'nullable|numeric|decimal:0,2',
            'sla_time_elapsed' => 'nullable|numeric|decimal:0,2',
            'sla_time_remaining' => 'nullable|numeric|decimal:0,2',
            'sla_breached' => 'sometimes|boolean',
            'approved_by' => 'nullable|exists:users,id',
            'rejection_reason' => 'nullable|string|max:500',
            'roi_impact' => 'nullable|string',
            'quality_impact' => 'nullable|string',
            'post_implementation_notes' => 'nullable|string',
            'source_ticket_id' => 'nullable|integer|exists:feature_requests,id',
            'is_direct_input' => 'sometimes|boolean',
        ];
    }
}
