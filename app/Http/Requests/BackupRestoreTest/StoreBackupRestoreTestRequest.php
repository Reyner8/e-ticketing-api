<?php

namespace App\Http\Requests\BackupRestoreTest;

use App\Enums\BackupSource;
use App\Enums\RestoreTestResult;
use App\Enums\RestoreType;
use App\Enums\TestEnvironment;
use App\Services\ApplicationService;
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
            'application_system' => ['required', 'string', 'max:50', ApplicationService::activeCodeExistsRule()],
            'restore_type' => ['required', 'string', Rule::in(RestoreType::values())],
            'backup_datetime' => ['nullable', 'date'],
            'backup_source' => ['nullable', 'string', Rule::in(BackupSource::values())],
            'test_environment' => ['required', 'string', Rule::in(TestEnvironment::values())],
            'result' => ['required', 'string', Rule::in(RestoreTestResult::values())],
            'notes' => ['nullable', 'string'],
            'follow_up' => ['nullable', 'string'],
        ];
    }
}
