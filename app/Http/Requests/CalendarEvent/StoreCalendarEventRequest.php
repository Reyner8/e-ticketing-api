<?php

namespace App\Http\Requests\CalendarEvent;

use App\Enums\EventTypes;
use App\Enums\RecurringFreq;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class StoreCalendarEventRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:200'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
            'type' => ['required', 'string', 'max:50', Rule::in(EventTypes::values())],
            'description' => ['nullable', 'string'],
            'color' => ['required', 'string', 'max:50'],
            'all_day' => ['sometimes', 'boolean'],
            'recurring_frequency' => [
                'nullable',
                'string',
                Rule::in(RecurringFreq::values())
            ],
            'recurring_interval' => [
                'nullable',
                'integer',
                'min:1',
                Rule::requiredIf(fn() => ! is_null($this->recurring_frequency))
            ],
            'recurring_end_date' => [
                'nullable',
                'date',
                'after:start',
                Rule::requiredIf(fn() => ! is_null($this->recurring_frequency))
            ]
        ];
    }
}
