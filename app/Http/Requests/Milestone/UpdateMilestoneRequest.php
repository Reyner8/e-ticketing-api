<?php

namespace App\Http\Requests\Milestone;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateMilestoneRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "title" => ['sometimes', 'string', 'max:200'],
            "description" => ['nullable', 'string'],
            "target_date" => ['sometimes', 'date', 'after:today'],
            "progress" => ['sometimes', 'integer', 'min:0', 'max:100'],
        ];
    }
}
