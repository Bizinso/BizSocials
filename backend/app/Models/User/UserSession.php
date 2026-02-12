<?php

declare(strict_types=1);

namespace App\Models\User;

use App\Enums\User\DeviceType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * UserSession Model
 *
 * Represents an active session for a user. Tracks device information,
 * location, and activity timestamps.
 *
 * @property string $id UUID primary key
 * @property string $user_id User UUID
 * @property string $token_hash Hashed session token
 * @property string|null $ip_address Client IP address
 * @property string|null $user_agent Client user agent
 * @property DeviceType|null $device_type Device type
 * @property array|null $location Geolocation data
 * @property \Carbon\Carbon $last_active_at Last activity timestamp
 * @property \Carbon\Carbon $expires_at Session expiration timestamp
 * @property \Carbon\Carbon $created_at
 *
 * @property-read User $user
 *
 * @method static Builder<static> forUser(string $userId)
 * @method static Builder<static> active()
 * @method static Builder<static> expired()
 */
final class UserSession extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_sessions';

    /**
     * Indicates if the model should be timestamped.
     * Sessions only have created_at, no updated_at.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'token_hash',
        'ip_address',
        'user_agent',
        'device_type',
        'location',
        'last_active_at',
        'expires_at',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'device_type' => DeviceType::class,
            'location' => 'array',
            'last_active_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns this session.
     *
     * @return BelongsTo<User, UserSession>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter sessions by user.
     *
     * @param  Builder<UserSession>  $query
     * @return Builder<UserSession>
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get only active (non-expired) sessions.
     *
     * @param  Builder<UserSession>  $query
     * @return Builder<UserSession>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope to get only expired sessions.
     *
     * @param  Builder<UserSession>  $query
     * @return Builder<UserSession>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Create a new session for a user.
     */
    public static function createForUser(
        string $userId,
        string $token,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        int $expiresInDays = 7
    ): self {
        $deviceType = $userAgent !== null
            ? DeviceType::fromUserAgent($userAgent)
            : DeviceType::API;

        return self::create([
            'user_id' => $userId,
            'token_hash' => self::hashToken($token),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_type' => $deviceType,
            'location' => null,
            'last_active_at' => now(),
            'expires_at' => now()->addDays($expiresInDays),
            'created_at' => now(),
        ]);
    }

    /**
     * Check if the session is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the session is active (not expired).
     */
    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Update the last active timestamp.
     */
    public function refreshActivity(): bool
    {
        $this->last_active_at = now();

        return $this->save();
    }

    /**
     * Invalidate the session (delete it).
     */
    public function invalidate(): void
    {
        $this->delete();
    }

    /**
     * Hash a session token.
     */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Generate a unique session token.
     */
    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * Clean up expired sessions.
     *
     * @return int Number of deleted sessions
     */
    public static function cleanExpired(): int
    {
        return self::expired()->delete();
    }
}
