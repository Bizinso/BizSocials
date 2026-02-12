<?php

declare(strict_types=1);

namespace App\Models\Audit;

use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LoginHistory Model
 *
 * Represents a login history entry for tracking user authentication.
 *
 * @property string $id UUID primary key
 * @property string $user_id User UUID
 * @property string|null $tenant_id Tenant UUID
 * @property bool $successful Whether login was successful
 * @property string|null $ip_address IP address
 * @property string|null $user_agent User agent string
 * @property string|null $device_type Device type (desktop, mobile, tablet)
 * @property string|null $browser Browser name
 * @property string|null $os Operating system
 * @property string|null $country_code Country code
 * @property string|null $city City name
 * @property string|null $failure_reason Reason for failure
 * @property \Carbon\Carbon|null $logged_out_at Logout timestamp
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User $user
 * @property-read Tenant|null $tenant
 *
 * @method static Builder<static> forUser(string $userId)
 * @method static Builder<static> successful()
 * @method static Builder<static> failed()
 * @method static Builder<static> fromIp(string $ip)
 * @method static Builder<static> recent(int $limit = 10)
 * @method static Builder<static> byDevice(string $deviceType)
 */
final class LoginHistory extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'login_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'tenant_id',
        'successful',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'country_code',
        'city',
        'failure_reason',
        'logged_out_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'successful' => 'boolean',
            'logged_out_at' => 'datetime',
        ];
    }

    /**
     * Get the user.
     *
     * @return BelongsTo<User, LoginHistory>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the tenant.
     *
     * @return BelongsTo<Tenant, LoginHistory>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Scope to filter by user.
     *
     * @param  Builder<LoginHistory>  $query
     * @return Builder<LoginHistory>
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get successful logins.
     *
     * @param  Builder<LoginHistory>  $query
     * @return Builder<LoginHistory>
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('successful', true);
    }

    /**
     * Scope to get failed logins.
     *
     * @param  Builder<LoginHistory>  $query
     * @return Builder<LoginHistory>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('successful', false);
    }

    /**
     * Scope to filter by IP address.
     *
     * @param  Builder<LoginHistory>  $query
     * @return Builder<LoginHistory>
     */
    public function scopeFromIp(Builder $query, string $ip): Builder
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope to get recent logins.
     *
     * @param  Builder<LoginHistory>  $query
     * @return Builder<LoginHistory>
     */
    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope to filter by device type.
     *
     * @param  Builder<LoginHistory>  $query
     * @return Builder<LoginHistory>
     */
    public function scopeByDevice(Builder $query, string $deviceType): Builder
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Check if the login was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * Check if the login failed.
     */
    public function isFailed(): bool
    {
        return !$this->successful;
    }

    /**
     * Get device info as an array.
     *
     * @return array<string, string|null>
     */
    public function getDeviceInfo(): array
    {
        return [
            'device_type' => $this->device_type,
            'browser' => $this->browser,
            'os' => $this->os,
        ];
    }

    /**
     * Get location info as an array.
     *
     * @return array<string, string|null>
     */
    public function getLocationInfo(): array
    {
        return [
            'country_code' => $this->country_code,
            'city' => $this->city,
            'ip_address' => $this->ip_address,
        ];
    }

    /**
     * Mark the session as logged out.
     */
    public function logout(): void
    {
        $this->logged_out_at = now();
        $this->save();
    }
}
