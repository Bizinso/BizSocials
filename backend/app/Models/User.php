<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\User\TenantRole;
use App\Enums\User\UserStatus;
use App\Models\Notification\Notification;
use App\Models\Notification\NotificationPreference;
use App\Models\Tenant\Tenant;
use App\Models\User\UserInvitation;
use App\Models\User\UserSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Laravel\Sanctum\HasApiTokens;

/**
 * User Model
 *
 * Represents a user within a tenant organization. Users are authenticated
 * entities that can log in and perform actions within their tenant's scope.
 *
 * @property string $id UUID primary key
 * @property string $tenant_id Tenant UUID
 * @property string $email User email address
 * @property string|null $password Hashed password (null for SSO)
 * @property string $name Display name
 * @property string|null $avatar_url Avatar image URL
 * @property string|null $phone Phone number
 * @property string|null $timezone User timezone (overrides tenant)
 * @property string $language Preferred language
 * @property UserStatus $status Account status
 * @property TenantRole $role_in_tenant Role within tenant
 * @property \Carbon\Carbon|null $email_verified_at Email verification timestamp
 * @property \Carbon\Carbon|null $last_login_at Last login timestamp
 * @property \Carbon\Carbon|null $last_active_at Last activity timestamp
 * @property bool $mfa_enabled MFA enabled flag
 * @property string|null $mfa_secret Encrypted MFA secret
 * @property array|null $settings User preferences
 * @property string|null $remember_token Remember token for persistent login
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read Tenant $tenant
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserSession> $sessions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserInvitation> $sentInvitations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Notification> $notifications
 * @property-read \Illuminate\Database\Eloquent\Collection<int, NotificationPreference> $notificationPreferences
 *
 * @method static Builder<static> active()
 * @method static Builder<static> forTenant(string $tenantId)
 * @method static Builder<static> withRole(TenantRole $role)
 */
final class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasUuids;
    use Notifiable;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'email',
        'password',
        'name',
        'avatar_url',
        'phone',
        'timezone',
        'language',
        'status',
        'role_in_tenant',
        'email_verified_at',
        'last_login_at',
        'last_active_at',
        'mfa_enabled',
        'mfa_secret',
        'settings',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'mfa_secret',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => UserStatus::class,
            'role_in_tenant' => TenantRole::class,
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_active_at' => 'datetime',
            'mfa_enabled' => 'boolean',
            'password' => 'hashed',
            'settings' => 'array',
        ];
    }

    /**
     * Get the tenant that this user belongs to.
     *
     * @return BelongsTo<Tenant, User>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get all sessions for this user.
     *
     * @return HasMany<UserSession>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    /**
     * Get all invitations sent by this user.
     *
     * @return HasMany<UserInvitation>
     */
    public function sentInvitations(): HasMany
    {
        return $this->hasMany(UserInvitation::class, 'invited_by');
    }

    /**
     * Get all notifications for this user.
     *
     * @return HasMany<Notification>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get all notification preferences for this user.
     *
     * @return HasMany<NotificationPreference>
     */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    /**
     * Scope to get only active users.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', UserStatus::ACTIVE);
    }

    /**
     * Scope to filter by tenant.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by role.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeWithRole(Builder $query, TenantRole $role): Builder
    {
        return $query->where('role_in_tenant', $role);
    }

    /**
     * Check if the user is active.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    /**
     * Check if the user can log in.
     */
    public function canLogin(): bool
    {
        return $this->status->canLogin();
    }

    /**
     * Check if the user is the owner of the tenant.
     */
    public function isOwner(): bool
    {
        return $this->role_in_tenant === TenantRole::OWNER;
    }

    /**
     * Check if the user is an admin (owner or admin role).
     */
    public function isAdmin(): bool
    {
        return in_array($this->role_in_tenant, [TenantRole::OWNER, TenantRole::ADMIN], true);
    }

    /**
     * Check if the user has verified their email.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Mark the user's email as verified.
     */
    public function markEmailAsVerified(): void
    {
        $this->email_verified_at = now();
        $this->save();
    }

    /**
     * Record a login event.
     */
    public function recordLogin(): void
    {
        $this->last_login_at = now();
        $this->last_active_at = now();
        $this->save();
    }

    /**
     * Update the last active timestamp.
     */
    public function updateLastActive(): void
    {
        $this->last_active_at = now();
        $this->save();
    }

    /**
     * Get the user's effective timezone.
     * Returns user's timezone if set, otherwise tenant's timezone.
     */
    public function getTimezone(): string
    {
        if ($this->timezone !== null) {
            return $this->timezone;
        }

        return $this->tenant?->getSetting('timezone', 'UTC') ?? 'UTC';
    }

    /**
     * Get a setting value using dot notation.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->settings ?? [], $key, $default);
    }

    /**
     * Set a setting value using dot notation.
     */
    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        Arr::set($settings, $key, $value);
        $this->settings = $settings;
        $this->save();
    }

    /**
     * Activate the user.
     */
    public function activate(): void
    {
        $this->status = UserStatus::ACTIVE;
        $this->save();
    }

    /**
     * Suspend the user.
     */
    public function suspend(): void
    {
        $this->status = UserStatus::SUSPENDED;
        $this->save();
    }

    /**
     * Deactivate the user.
     */
    public function deactivate(): void
    {
        $this->status = UserStatus::DEACTIVATED;
        $this->save();
    }
}
