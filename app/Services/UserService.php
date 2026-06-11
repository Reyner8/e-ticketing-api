<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserService
{
    private const DEFAULT_PASSWORD = '12345678';

    public function store(array $data): User 
    {
        if (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
            $data['avatar'] = $this->uploadAvatar($data['avatar']);
        }

        if (empty($data['password'])) {
            $data['password'] = self::DEFAULT_PASSWORD;
        }

        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        if (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
            $this->deleteAvatar($user->avatar);
            $data['avatar'] = $this->uploadAvatar($data['avatar']);
        }

        $user->update($data);

        return $user->fresh();
    }

    public function updatePreferences(User $user, array $data): User
    {
        $mapped = [];

        if (isset($data['dark_mode'])) {
            $mapped['pref_dark_mode'] = $data['dark_mode'];
        }

        if (isset($data['email_notifications'])) {
            $mapped['pref_email_notifications'] = $data['email_notifications'];
        }

        if (isset($data['sla_alerts'])) {
            $mapped['pref_sla_alerts'] = $data['sla_alerts'];
        }

        if (isset($data['downtime_alerts'])) {
            $mapped['pref_downtime_alerts'] = $data['downtime_alerts'];
        }

        if (isset($data['digest_frequency'])) {
            $mapped['pref_digest_frequency'] = $data['digest_frequency'];
        }

        if (array_key_exists('quiet_hours', $data)) {
            $mapped['pref_quiet_hours'] = $data['quiet_hours']; 
        }

        $user->update($mapped);

        return $user->fresh();
    }

    public function toggleActive(User $user): User
    {
        if ($user->id === Auth::id()) {
            throw ValidationException::withMessages([
                'user' => ['You cannot deactivate your own account.']
            ]);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return $user->fresh();
    }

    public function delete(User $user): void
    {
        if ($user->id === Auth::id()) {
            throw ValidationException::withMessages([
                'user' => ['You cannot delete your own account.']
            ]);
        }

        $this->deleteAvatar($user->avatar);
        $user->delete();
    }

    //* Query
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
        ->when(
            isset($filters['role']),
            fn ($q) => $q->byRole($filters['role'])
        )
        ->when(
            isset($filters['team']),
            fn ($q) => $q->byTeam($filters['team'])
        )
        ->when(
            isset($filters['is_active']),
            fn($q) => $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN)) 
        )
        ->when(
            isset($filters['search']),
            fn ($q) => $q->where('name', 'like', '%' . $filters['search'] . '%')
            ->orWhere('email', 'like', '%' . $filters['search'] . '%')
        )
        ->orderBy('name')
        ->paginate(min($perPage, 50));
    }

    // Helpers
    private function uploadAvatar(UploadedFile $file): string
    {
        return $file->store('avatars', 'public');
    }

    private function deleteAvatar(?string $avatar): void
    {
        if ($avatar && ! str_starts_with($avatar, 'http')) {
            Storage::disk('public')->delete($avatar);
        }
    }
}