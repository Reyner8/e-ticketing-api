<?php

namespace App\Http\Requests\User;

use App\Enums\AssignedTeam;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;

class StoreUserRequest extends FormRequest
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
        if ($this->filled('name')) {
            $this->merge([
                'name' => Str::title(trim($this->name))
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
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', Rule::unique('users', 'email')],
            'password' => ['sometimes', Password::min(8)],
            'role' => ['required', 'string', Rule::in(UserRole::values())],
            'team' => [
                'nullable',
                'string',
                Rule::in(AssignedTeam::values()),
                Rule::requiredIf(fn() => $this->role === UserRole::ItStaff->value)
            ],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
