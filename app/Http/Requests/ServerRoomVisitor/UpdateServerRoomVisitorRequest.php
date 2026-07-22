<?php

namespace App\Http\Requests\ServerRoomVisitor;

use App\Enums\VisitorStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServerRoomVisitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entry_at' => ['sometimes', 'date'],
            'exit_at' => ['nullable', 'date', 'after_or_equal:entry_at'],
            'visitor_name' => ['sometimes', 'string', 'max:200'],
            'unit_or_vendor' => ['sometimes', 'string', 'max:200'],
            'purpose' => ['sometimes', 'string', 'max:500'],
            'escorted_by' => ['sometimes', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in(VisitorStatus::values())],
        ];
    }
}
