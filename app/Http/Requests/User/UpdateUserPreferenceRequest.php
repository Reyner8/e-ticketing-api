<?php

namespace App\Http\Requests\User;

use App\Enums\DigestFreq;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserPreferenceRequest extends FormRequest
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
            'dark_mode' => ['sometimes', 'boolean'],
            'email_notifications' => ['sometimes', 'boolean'],
            'sla_alerts' => ['sometimes', 'boolean'],
            'downtime_alerts' => ['sometimes', 'boolean'],
            'digest_frequency' => [
                'sometimes',
                'string',
                Rule::in(DigestFreq::values())
            ],
            'quiet_hours' => [
                'nullable',
                'string',
                'regex:/^\d{2}:\d{2}-\d{2}:\d{2}$/',
            ],
        ];
    }
}
