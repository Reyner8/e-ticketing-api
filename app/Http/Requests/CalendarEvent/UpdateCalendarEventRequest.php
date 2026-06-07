<?php

namespace App\Http\Requests\CalendarEvent;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\EventTypes;
use App\Enums\RecurringFreq;
use Illuminate\Support\Str;

class UpdateCalendarEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'title' => Str::title(trim($this->title)),
            'is_direct_input' => !$this->has('source_ticket_id') || is_null($this->source_ticket_id)
        ]);  
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
            'start' => ['sometimes', 'date'],
            'end' => ['sometimes', 'date', 'after_or_equal:start'],
            'type' => ['sometimes', 'string', 'max:50', Rule::in(EventTypes::values())],
            'description' => ['nullable', 'string'],
            'color' => ['sometimes', 'string', 'max:50'],
            'all_day' => ['sometimes', 'boolean'],
            'recurring_frequency' => [
                'nullable',
                'string',
                Rule::in(RecurringFreq::values())
            ],
            'recurring_interval' => ['nullable', 'integer', 'min:1',],
            'recurring_end_date' => ['nullable', 'date', 'after:start',
            ],
        ];
    }
}
