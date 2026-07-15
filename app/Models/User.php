<?php

namespace App\Models;

use App\Enums\AssignedTeam;
use App\Enums\DigestFreq;
use App\Enums\UserRole;
use App\Traits\HasAssignment;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'username',
    'name',
    'email',
    'password',
    'role',
    'team',
    'avatar',
    'is_active',
    'last_login',
    'pref_dark_mode',
    'pref_email_notifications',
    'pref_sla_alerts',
    'pref_downtime_alerts',
    'pref_digest_frequency',
    'pref_quiet_hours'
])]
#[WithoutTimestamps]

#[Hidden([
    'password',
    'remember_token',
])]

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasAssignment;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'team' => AssignedTeam::class,
            'pref_digest_frequency' => DigestFreq::class,
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'last_login' => 'datetime',
            'created_at' => 'datetime',
            'pref_dark_mode' => 'boolean',
            'pref_email_notifications' => 'boolean',
            'pref_sla_alerts' => 'boolean',
            'pref_downtime_alerts' => 'boolean',
            'password' => 'hashed',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }
    
    // Accessors
    public function getPreferencesAttribute(): array
    {
        return [
            'dark_mode' => $this->pref_dark_mode,
            'email_notifications' => $this->pref_email_notifications,
            'sla_alerts' => $this->pref_sla_alerts,
            'downtime_alerts' => $this->pref_downtime_alerts,
            'digest_frequency' => $this->pref_digest_frequency?->value,
            'quiet_hours' => $this->pref_quiet_hours,
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (is_null($this->avatar)) {
            return null;
        }

        if (str_starts_with($this->avatar, 'http')) {
            return $this->avatar;
        }

        return url('/storage/' . $this->avatar);
    }

    // Helpers
    public function isItStaff(): bool
    {
        return $this->role === UserRole::ItStaff;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isReporter(): bool
    {
        return $this->role === UserRole::Reporter;
    }

    public function isTeamLead(): bool
    {
        return $this->role === UserRole::TeamLead;
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    public function scopeByTeam(Builder $query, string $team): Builder
    {
        return $query->where('team', $team);
    }

    // Relations
    public function reporter(): HasMany
    {
        return $this->hasMany(Ticket::class, 'reporter_id');
    }

    public function assignee(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to_id');
    }

    public function convertBy(): HasMany
    {
        return $this->hasMany(Ticket::class, 'converted_by');
    }
}
