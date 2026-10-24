<?php

namespace App\Http\Requests\SecurityAudit;

use App\Enums\SecurityAuditStatus;
use App\Enums\SecurityAuditType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSecurityAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim((string) $this->input('title')),
            'period' => trim((string) $this->input('period')) ?: null,
            'external_auditor' => trim((string) $this->input('external_auditor')) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'type' => ['required', 'string', Rule::in(SecurityAuditType::values())],
            'audit_date' => ['required', 'date'],
            'period' => ['nullable', 'string', 'max:50'],
            'performed_by' => ['nullable', 'integer', 'exists:users,id'],
            'external_auditor' => ['nullable', 'string', 'max:200'],
            'scope' => ['nullable', 'string'],
            'summary' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in(SecurityAuditStatus::values())],
        ];
    }
}
