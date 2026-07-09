<?php

namespace App\Http\Requests\PublicSubmission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

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
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:5000'],
            'submitter_name' => ['required', 'string', 'max:150'],
            'submitter_unit' => ['required', 'string', 'max:100'],
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => ['file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp'],
            'website' => ['prohibited'],
        ];
    }
}
