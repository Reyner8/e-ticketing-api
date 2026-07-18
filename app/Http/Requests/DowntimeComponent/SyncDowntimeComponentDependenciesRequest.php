<?php

namespace App\Http\Requests\DowntimeComponent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncDowntimeComponentDependenciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'default_affected_component_ids' => ['present', 'array'],
            'default_affected_component_ids.*' => ['integer', 'distinct', Rule::exists('downtime_components', 'id')],
        ];
    }
}
