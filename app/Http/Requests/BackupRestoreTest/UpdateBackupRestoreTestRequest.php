<?php

namespace App\Http\Requests\BackupRestoreTest;

use App\Enums\RestoreTestResult;
use App\Enums\RestoreType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBackupRestoreTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'test_date' => ['sometimes', 'date'],
            'performed_by' => ['sometimes', 'integer', 'exists:users,id'],
            'application_system' => ['sometimes', 'string', 'max:200'],
            'restore_type' => ['sometimes', 'string', Rule::in(RestoreType::values())],
            'backup_datetime' => ['nullable', 'date'],
            'backup_source' => ['nullable', 'string', 'max:500'],
            'test_environment' => ['sometimes', 'string', 'max:200'],
            'result' => ['sometimes', 'string', Rule::in(RestoreTestResult::values())],
            'notes' => ['nullable', 'string'],
            'follow_up' => ['nullable', 'string'],
        ];
    }
}
