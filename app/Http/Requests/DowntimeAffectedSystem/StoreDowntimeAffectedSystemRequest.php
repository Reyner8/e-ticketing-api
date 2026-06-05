<?php

namespace App\Http\Requests\DowntimeAffectedSystem;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDowntimeAffectedSystemRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'system_names' => ['required', 'array', 'min:1'],
            'system_names.*' => ['required', 'string', 'max:200'],
        ];
    }
}
