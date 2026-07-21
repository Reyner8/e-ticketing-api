<?php

namespace App\Http\Requests\BackupRestoreTest;

use App\Enums\RestoreTestResult;
use App\Enums\RestoreType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBackupRestoreTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'test_date' => ['required', 'date'],
            'performed_by' => ['nullable', 'integer', 'exists:users,id'],
            'application_system' => ['required', 'string', 'max:200'],
            'restore_type' => ['required', 'string', Rule::in(RestoreType::values())],
            'backup_datetime' => ['nullable', 'date'],
            'backup_source' => ['nullable', 'string', 'max:500'],
            'test_environment' => ['required', 'string', 'max:200'],
            'result' => ['required', 'string', Rule::in(RestoreTestResult::values())],
            'notes' => ['nullable', 'string'],
            'follow_up' => ['nullable', 'string'],
        ];
    }
}
