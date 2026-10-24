<?php

namespace App\Http\Requests\SecurityAudit;

use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSecurityAuditFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string'],
            'severity' => ['required', 'string', Rule::in(FindingSeverity::values())],
            'recommendation' => ['nullable', 'string'],
            'corrective_action' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in(FindingStatus::values())],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
