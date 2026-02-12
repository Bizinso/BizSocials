<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Enums\Platform\SuperAdminRole;
use App\Enums\Platform\SuperAdminStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * SuperAdminUser Model
 *
 * Represents platform administrators (Bizinso team members).
 * These users have access to the super admin panel for managing
 * all tenants, configurations, and platform settings.
 *
 * @property string $id UUID primary key
 * @property string $email Unique login email
 * @property string $password Hashed password
 * @property string $name Full name
 * @property SuperAdminRole $role Admin role level
 * @property SuperAdminStatus $status Account status
 * @property \Carbon\Carbon|null $last_login_at Last login timestamp
 * @property bool $mfa_enabled Whether MFA is enabled
 * @property string|null $mfa_secret MFA secret key (encrypted)
 * @property string|null $remember_token Remember me token
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PlatformConfig> $platformConfigs
 */
final class SuperAdminUser extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasUuids;
    use Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'super_admin_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'name',
        'role',
        'status',
        'last_login_at',
        'mfa_enabled',
        'mfa_secret',
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
            'role' => SuperAdminRole::class,
            'status' => SuperAdminStatus::class,
            'mfa_enabled' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the platform configs updated by this admin.
     *
     * @return HasMany<PlatformConfig>
     */
    public function platformConfigs(): HasMany
    {
        return $this->hasMany(PlatformConfig::class, 'updated_by');
    }

    /**
     * Check if the admin can log in.
     */
    public function canLogin(): bool
    {
        return $this->status->canLogin();
    }

    /**
     * Check if the admin has a specific role.
     */
    public function hasRole(SuperAdminRole $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if the admin has any of the specified roles.
     *
     * @param  array<SuperAdminRole>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Check if the admin can manage other admins.
     */
    public function canManageAdmins(): bool
    {
        return $this->role->canManageAdmins();
    }

    /**
     * Check if the admin has write access.
     */
    public function hasWriteAccess(): bool
    {
        return $this->role->hasWriteAccess();
    }

    /**
     * Update the last login timestamp.
     */
    public function recordLogin(): void
    {
        $this->last_login_at = now();
        $this->save();
    }

    /**
     * Check if the user is an admin.
     *
     * SuperAdminUser is always an admin.
     */
    public function isAdmin(): bool
    {
        return true;
    }
}
