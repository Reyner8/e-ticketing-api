<?php

namespace App\Http\Requests\FeatureRequest;

use App\Enums\AssignedTeam;
use App\Enums\Priorities;
use App\Enums\RequestType;
use App\Services\ApplicationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreFeatureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'title' => Str::title(trim($this->title)),
            'is_direct_input' => !$this->has('source_ticket_id') || is_null($this->source_ticket_id)
        ]);  
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'request_type' => ['required', 'string', Rule::in(RequestType::values())],
            'target_application' => ['nullable', 'string', 'max:50', ApplicationService::activeCodeExistsRule()],
            'priority' => ['required', 'string', Rule::in(Priorities::values())],
            'assigned_to_id' => 'nullable|integer|exists:users,id',
            'assigned_team' => ['nullable', 'string', 'max:255', Rule::in(AssignedTeam::values())],
            'due_date' => 'nullable|date',
            'source_ticket_id' => 'nullable|integer|exists:feature_requests,id',
            'is_direct_input' => 'required|boolean',
        ];
    }
}
