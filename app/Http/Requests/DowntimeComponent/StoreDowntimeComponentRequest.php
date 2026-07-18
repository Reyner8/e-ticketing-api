<?php

namespace App\Http\Requests\DowntimeComponent;

use App\Enums\DowntimeComponentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDowntimeComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('downtime_components', 'code')],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', Rule::in(DowntimeComponentCategory::values())],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'default_affected_component_ids' => ['nullable', 'array'],
            'default_affected_component_ids.*' => ['integer', 'distinct', Rule::exists('downtime_components', 'id')],
        ];
    }
}
