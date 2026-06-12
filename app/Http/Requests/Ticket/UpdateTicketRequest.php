<?php

namespace App\Http\Requests\Ticket;

use App\Enums\AssignedTeam;
use App\Enums\ConversionTypes;
use App\Enums\Priorities;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use Illuminate\Support\Str;

class UpdateTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        if ($this->filled('title')) {
            $this->merge([
                'title' => Str::title(trim($this->title))
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'category' => ['sometimes', 'string', Rule::in(TicketCategory::values())],  
            'priority' => ['sometimes', 'string', Rule::in(Priorities::values())],  
            'due_date' => ['nullable', 'date'],
            'estimated_effort' => ['nullable', 'numeric', 'decimal:0,2'],
            'actual_effort' => ['nullable', 'numeric', 'decimal:0,2'],
            'parent_ticket_id' => ['nullable', 'string', Rule::exists('tickets', 'id')],
        ];
    }
}
