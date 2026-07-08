<?php

namespace App\Http\Requests\PublicSubmission;

use App\Enums\Priorities;
use App\Enums\TicketCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StorePublicSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if ($this->filled('title')) {
            $this->merge(['title' => Str::title(trim($this->title))]);
        }
    }

    public function rules(): array
    {
        return [
            'submission_type' => ['required', 'string', Rule::in(['error_report', 'feature_request'])],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:5000'],
            'category' => ['required', 'string', Rule::in(TicketCategory::values())],
            'priority' => ['required', 'string', Rule::in(Priorities::values())],
            'submitter_name' => ['required', 'string', 'max:150'],
            'submitter_email' => ['required', 'email', 'max:150'],
            'submitter_phone' => ['nullable', 'string', 'max:50'],
            'submitter_unit' => ['nullable', 'string', 'max:100'],
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => ['file', 'max:10240', 'mimes:jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,zip'],
            'website' => ['prohibited'],
        ];
    }
}
