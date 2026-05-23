<?php

namespace App\Http\Requests\DowntimeRecord;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\DowntimeImpact;
use App\Enums\DowntimeType;
use App\Enums\DowntimeStatus;

class UpdateDowntimeRecordRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:200'],
            'type' => ['sometimes', 'string', 'max:50', Rule::in(DowntimeType::values())],
            'reason' => ['sometimes', 'string'],
            'start_time' => ['sometimes', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'duration' => ['nullable', 'integer'],
            'impact' => ['sometimes', 'string', 'max:50', Rule::in(DowntimeImpact::values())],
            'reported_by' => ['sometimes', 'integer', 'exists:users,id'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'max:50', Rule::in(DowntimeStatus::values())],
            'affected_users' => ['nullable', 'integer', 'min:0'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
