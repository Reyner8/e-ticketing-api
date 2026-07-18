<?php

namespace App\Http\Requests\DowntimeRecord;

use App\Enums\DowntimeImpact;
use App\Enums\DowntimeType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreDowntimeRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if ($this->filled('title')) {
            $this->merge([
                'title' => Str::title(trim($this->title)),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'type' => ['required', 'string', 'max:50', Rule::in(DowntimeType::values())],
            'reason' => ['required', 'string'],
            'start_time' => ['required', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'impact' => ['required', 'string', 'max:50', Rule::in(DowntimeImpact::values())],
            'location_ids' => ['required', 'array', 'min:1'],
            'location_ids.*' => ['integer', 'distinct', Rule::exists('downtime_locations', 'id')->where('is_active', true)],
            'description' => ['nullable', 'string'],
            'root_cause' => ['nullable', 'string'],
            'preventive_measures' => ['nullable', 'string'],
            'affected_users' => ['nullable', 'integer', 'min:0'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'source_component_ids' => ['required', 'array', 'min:1'],
            'source_component_ids.*' => ['integer', 'distinct', Rule::exists('downtime_components', 'id')->where('is_active', true)],
            'affected_component_ids' => ['nullable', 'array'],
            'affected_component_ids.*' => ['integer', 'distinct', Rule::exists('downtime_components', 'id')->where('is_active', true)],
        ];
    }
}
