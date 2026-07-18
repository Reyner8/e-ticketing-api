<?php

namespace App\Http\Requests\DowntimeRecord;

use App\Models\DowntimeRecord;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ResolveDowntimeRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'end_time' => ['required', 'date'],
            'root_cause' => ['required', 'string'],
            'preventive_measures' => ['required', 'string'],
            'affected_users' => ['nullable', 'integer', 'min:0'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var DowntimeRecord|null $record */
            $record = $this->route('downtimeRecord');
            if (! $record || ! $this->filled('end_time')) {
                return;
            }

            $endTime = strtotime((string) $this->input('end_time'));
            $startTime = optional($record->start_time)->getTimestamp();

            if ($endTime === false || $startTime === null || $endTime <= $startTime) {
                $validator->errors()->add('end_time', 'End time must be after start time.');
            }
        });
    }
}
