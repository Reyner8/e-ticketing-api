<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\UserRole;
use App\Enums\AssignedTeam;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'username' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user'))
            ],
            'password' => ['sometimes', Password::min(8)->mixedCase()->numbers()],
            'role' => ['sometimes', 'string', Rule::in(UserRole::values())],
            'team' => [
                'nullable',
                'string',
                'max:255',
                Rule::in(AssignedTeam::values()),
                Rule::requiredIf(fn() => $this->role === UserRole::ItStaff->value)
            ],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
