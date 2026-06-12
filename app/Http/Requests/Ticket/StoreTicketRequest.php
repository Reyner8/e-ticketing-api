<?php

namespace App\Http\Requests\Ticket;

use App\Enums\AssignedTeam;
use App\Enums\Priorities;
use App\Enums\TicketCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', Rule::in(TicketCategory::values())],  
            'priority' => ['required', 'string', Rule::in(Priorities::values())], 
            'due_date' => ['nullable', 'date', 'after:now'],
            'estimated_effort' => ['nullable', 'numeric', 'min:0'],
            'parent_ticket_id' => ['nullable', 'string', Rule::exists('tickets', 'id')],
        ];
    }
}
