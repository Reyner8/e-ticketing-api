<?php

namespace App\Http\Requests\TimelineEntries;

use App\Enums\TimelinePhase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class StoreTimelineEntryRequest extends FormRequest
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
            'phase' => ['required', 'string', 'max:50', Rule::in(TimelinePhase::values())],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'start_date' => ['nullable', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:today'],
            'progress' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'assigned_to' => [
                'nullable',
                'string',
                Rule::exists('users', 'id')->where('is_active', true)
            ],
            'notes' => ['nullable', 'string']
        ];
    }
}
