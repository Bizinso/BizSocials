<?php

declare(strict_types=1);

namespace App\Models\Audit;

use App\Enums\Audit\SessionStatus;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SessionHistory Model
 *
 * Represents a user session history entry.
 *
 * @property string $id UUID primary key
 * @property string $user_id User UUID
 * @property string|null $tenant_id Tenant UUID
 * @property string $session_token Session token
 * @property SessionStatus $status Session status
 * @property string|null $ip_address IP address
 * @property string|null $user_agent User agent string
 * @property string|null $device_type Device type
 * @property string|null $device_name Device name
 * @property string|null $browser Browser name
 * @property string|null $os Operating system
 * @property string|null $country_code Country code
 * @property string|null $city City name
 * @property bool $is_current Whether this is the current session
 * @property \Carbon\Carbon|null $last_activity_at Last activity timestamp
 * @property \Carbon\Carbon|null $expires_at Expiration timestamp
 * @property \Carbon\Carbon|null $revoked_at Revocation timestamp
 * @property string|null $revoked_by Revoker user UUID
 * @property string|null $revocation_reason Revocation reason
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User $user
 * @property-read Tenant|null $tenant
 * @property-read User|null $revoker
 *
 * @method static Builder<static> forUser(string $userId)
 * @method static Builder<static> active()
 * @method static Builder<static> expired()
 * @method static Builder<static> revoked()
 * @method static Builder<static> current()
 * @method static Builder<static> byDevice(string $deviceType)
 * @method static Builder<static> ordered()
 */
final class SessionHistory extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'session_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'tenant_id',
        'session_token',
        'status',
        'ip_address',
        'user_agent',
        'device_type',
        'device_name',
        'browser',
        'os',
        'country_code',
        'city',
        'is_current',
        'last_activity_at',
        'expires_at',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SessionStatus::class,
            'is_current' => 'boolean',
            'last_activity_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * Get the user.
     *
     * @return BelongsTo<User, SessionHistory>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the tenant.
     *
     * @return BelongsTo<Tenant, SessionHistory>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the user who revoked this session.
     *
     * @return BelongsTo<User, SessionHistory>
     */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Scope to filter by user.
     *
     * @param  Builder<SessionHistory>  $query
     * @return Builder<SessionHistory>
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get active sessions.
     *
     * @param  Builder<SessionHistory>  $query
     * @return Builder<SessionHistory>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SessionStatus::ACTIVE);
    }

    /**
     * Scope to get expired sessions.
     *
     * @param  Builder<SessionHistory>  $query
     * @return Builder<SessionHistory>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', SessionStatus::EXPIRED);
    }

    /**
     * Scope to get revoked sessions.
     *
     * @param  Builder<SessionHistory>  $query
     * @return Builder<SessionHistory>
     */
    public function scopeRevoked(Builder $query): Builder
    {
        return $query->where('status', SessionStatus::REVOKED);
    }

    /**
     * Scope to get current sessions.
     *
     * @param  Builder<SessionHistory>  $query
     * @return Builder<SessionHistory>
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope to filter by device type.
     *
     * @param  Builder<SessionHistory>  $query
     * @return Builder<SessionHistory>
     */
    public function scopeByDevice(Builder $query, string $deviceType): Builder
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Scope to order by last activity.
     *
     * @param  Builder<SessionHistory>  $query
     * @return Builder<SessionHistory>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('last_activity_at', 'desc');
    }

    /**
     * Check if the session is active.
     */
    public function isActive(): bool
    {
        return $this->status === SessionStatus::ACTIVE && !$this->isExpired();
    }

    /**
     * Check if the session has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if this is the current session.
     */
    public function isCurrent(): bool
    {
        return $this->is_current;
    }

    /**
     * Revoke this session.
     */
    public function revoke(User $revoker, ?string $reason = null): void
    {
        $this->status = SessionStatus::REVOKED;
        $this->revoked_at = now();
        $this->revoked_by = $revoker->id;
        $this->revocation_reason = $reason;
        $this->is_current = false;
        $this->save();
    }

    /**
     * Update last activity timestamp.
     */
    public function updateLastActivity(): bool
    {
        $this->last_activity_at = now();

        return $this->save();
    }

    /**
     * Mark this session as current.
     */
    public function markAsCurrent(): void
    {
        // Remove current flag from other sessions
        self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_current' => false]);

        $this->is_current = true;
        $this->save();
    }
}
