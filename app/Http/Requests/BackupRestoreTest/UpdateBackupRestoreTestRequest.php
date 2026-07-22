<?php

namespace App\Http\Requests\BackupRestoreTest;

use App\Enums\BackupSource;
use App\Enums\RestoreTestResult;
use App\Enums\RestoreType;
use App\Enums\TestEnvironment;
use App\Services\ApplicationService;
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
            'application_system' => ['sometimes', 'string', 'max:50', ApplicationService::activeCodeExistsRule()],
            'restore_type' => ['sometimes', 'string', Rule::in(RestoreType::values())],
            'backup_datetime' => ['nullable', 'date'],
            'backup_source' => ['nullable', 'string', Rule::in(BackupSource::values())],
            'test_environment' => ['sometimes', 'string', Rule::in(TestEnvironment::values())],
            'result' => ['sometimes', 'string', Rule::in(RestoreTestResult::values())],
            'notes' => ['nullable', 'string'],
            'follow_up' => ['nullable', 'string'],
        ];
    }
}
