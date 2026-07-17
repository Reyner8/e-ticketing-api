<?php

namespace App\Http\Requests\Ticket;

use App\Enums\AssignedTeam;
use App\Enums\Priorities;
use App\Enums\RequestType;
use App\Enums\TargetApplication;
use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConvertToFeatureRequestRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'request_type' => ['required', 'string', Rule::in(RequestType::values())],
            'target_application' => ['required', 'string', Rule::in(TargetApplication::values())],
            'priority' => ['nullable', 'string', Rule::in(Priorities::values())],
            'assigned_to_id' => 'nullable|integer|exists:users,id',
            'assigned_team' => ['nullable', 'string', 'max:255', Rule::in(AssignedTeam::values())],
            'due_date' => 'nullable|date',
            'conversion_reason' => 'required|string',
        ];
    }
}
