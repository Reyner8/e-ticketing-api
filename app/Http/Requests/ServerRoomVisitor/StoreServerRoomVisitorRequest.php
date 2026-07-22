<?php

namespace App\Http\Requests\ServerRoomVisitor;

use Illuminate\Foundation\Http\FormRequest;

class StoreServerRoomVisitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entry_at' => ['required', 'date'],
            'visitor_name' => ['required', 'string', 'max:200'],
            'unit_or_vendor' => ['required', 'string', 'max:200'],
            'purpose' => ['required', 'string', 'max:500'],
            'escorted_by' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
