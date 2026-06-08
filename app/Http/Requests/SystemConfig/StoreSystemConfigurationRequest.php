<?php

namespace App\Http\Requests\SystemConfig;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSystemConfigurationRequest extends FormRequest
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
            'config_key' => ['required', 'string', 'max:100', Rule::unique('system_configurations', 'config_key')],
            'config_value' => ['required'],
            'description' => ['nullable', 'string']
        ];
    }
}
