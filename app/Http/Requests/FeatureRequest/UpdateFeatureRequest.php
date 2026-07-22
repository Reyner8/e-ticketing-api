<?php

namespace App\Http\Requests\FeatureRequest;

use App\Enums\AssignedTeam;
use App\Enums\FeatureRequestStatus;
use App\Enums\Priorities;
use App\Enums\RequestType;
use App\Services\ApplicationService;
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
            'target_application' => ['nullable', 'string', 'max:50', ApplicationService::activeCodeExistsRule()],
            'priority' => ['sometimes', 'string', Rule::in(Priorities::values())],
            'status' => ['sometimes', 'string', Rule::in(FeatureRequestStatus::values())],
            'progress' => 'sometimes|integer|min:0|max:100',
            'reporter_id' => 'sometimes|integer|exists:users,id',
            'assigned_to_id' => 'nullable|integer|exists:users,id',
            'assigned_team' => ['nullable', 'string', 'max:255', Rule::in(AssignedTeam::values())],
            'due_date' => 'nullable|date',
            'sla_breached' => 'sometimes|boolean',
            'approved_by' => 'nullable|exists:users,id',
            'rejection_reason' => 'nullable|string|max:500',
            'post_implementation_notes' => 'nullable|string',
            'source_ticket_id' => 'nullable|integer|exists:feature_requests,id',
            'is_direct_input' => 'sometimes|boolean',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->has('due_date') || $this->input('due_date') === null) {
                return;
            }

            $feature = $this->route('feature');
            if (!$feature) {
                return;
            }

            $status = $feature->status instanceof FeatureRequestStatus
                ? $feature->status->value
                : (string) $feature->status;

            $allowed = [
                FeatureRequestStatus::Development->value,
                FeatureRequestStatus::Testing->value,
                FeatureRequestStatus::Validation->value,
                FeatureRequestStatus::Completed->value,
                FeatureRequestStatus::PostImplementationReview->value,
            ];

            if (!in_array($status, $allowed, true)) {
                $validator->errors()->add(
                    'due_date',
                    'Due date hanya dapat diatur setelah status Development.'
                );
            }
        });
    }
}
