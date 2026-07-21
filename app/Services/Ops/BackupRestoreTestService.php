<?php

namespace App\Services\Ops;

use App\Enums\RestoreTestResult;
use App\Enums\RestoreType;
use App\Models\BackupRestoreTest;
use App\Support\SequentialIdGenerator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class BackupRestoreTestService
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return BackupRestoreTest::query()
            ->with(['performer:id,name,username', 'creator:id,name,username'])
            ->when(
                isset($filters['result']),
                fn ($q) => $q->where('result', $filters['result'])
            )
            ->when(
                isset($filters['restore_type']),
                fn ($q) => $q->where('restore_type', $filters['restore_type'])
            )
            ->when(
                isset($filters['from']),
                fn ($q) => $q->whereDate('test_date', '>=', $filters['from'])
            )
            ->when(
                isset($filters['to']),
                fn ($q) => $q->whereDate('test_date', '<=', $filters['to'])
            )
            ->when(
                isset($filters['search']),
                function ($q) use ($filters) {
                    $term = '%'.$filters['search'].'%';

                    return $q->where(function ($inner) use ($term) {
                        $inner->where('id', 'like', $term)
                            ->orWhere('application_system', 'like', $term)
                            ->orWhere('test_environment', 'like', $term)
                            ->orWhere('backup_source', 'like', $term);
                    });
                }
            )
            ->latest('test_date')
            ->latest('id')
            ->paginate(min($perPage, 50));
    }

    public function store(array $data): BackupRestoreTest
    {
        $data['id'] = SequentialIdGenerator::next(BackupRestoreTest::class, 'RST');
        $data['performed_by'] = $data['performed_by'] ?? Auth::id();
        $data['created_by'] = Auth::id();
        $data['restore_type'] = RestoreType::from($data['restore_type'])->value;
        $data['result'] = RestoreTestResult::from($data['result'])->value;

        return BackupRestoreTest::create($data)->load(['performer', 'creator']);
    }

    public function update(BackupRestoreTest $test, array $data): BackupRestoreTest
    {
        if (isset($data['restore_type'])) {
            $data['restore_type'] = RestoreType::from($data['restore_type'])->value;
        }
        if (isset($data['result'])) {
            $data['result'] = RestoreTestResult::from($data['result'])->value;
        }

        $test->update($data);

        return $test->fresh()->load(['performer', 'creator']);
    }

    public function delete(BackupRestoreTest $test): void
    {
        $test->attachments()->each(fn ($a) => $a->delete());
        $test->delete();
    }
}
