<?php

declare(strict_types=1);

namespace App\Models\Social;

use App\Enums\Social\SocialAccountStatus;
use App\Enums\Social\SocialPlatform;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;

/**
 * SocialAccount Model
 *
 * Represents a connected social media account within a workspace.
 * Stores OAuth credentials securely with encryption.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property SocialPlatform $platform Social platform
 * @property string $platform_account_id Platform-specific account ID
 * @property string $account_name Display name
 * @property string|null $account_username Username/handle
 * @property string|null $profile_image_url Profile image URL
 * @property SocialAccountStatus $status Account status
 * @property string $access_token_encrypted Encrypted access token
 * @property string|null $refresh_token_encrypted Encrypted refresh token
 * @property \Carbon\Carbon|null $token_expires_at Token expiration
 * @property string $connected_by_user_id User who connected
 * @property \Carbon\Carbon $connected_at When connected
 * @property \Carbon\Carbon|null $last_refreshed_at When tokens refreshed
 * @property \Carbon\Carbon|null $disconnected_at When disconnected
 * @property array|null $metadata Platform-specific data
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 * @property-read User $connectedBy
 * @property-read string|null $accessToken Decrypted access token
 * @property-read string|null $refreshToken Decrypted refresh token
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> forPlatform(SocialPlatform $platform)
 * @method static Builder<static> connected()
 * @method static Builder<static> needsTokenRefresh(int $daysBeforeExpiry = 7)
 * @method static Builder<static> expired()
 */
final class SocialAccount extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'social_accounts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'platform',
        'platform_account_id',
        'account_name',
        'account_username',
        'profile_image_url',
        'status',
        'access_token_encrypted',
        'refresh_token_encrypted',
        'token_expires_at',
        'connected_by_user_id',
        'connected_at',
        'last_refreshed_at',
        'disconnected_at',
        'metadata',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'access_token_encrypted',
        'refresh_token_encrypted',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platform' => SocialPlatform::class,
            'status' => SocialAccountStatus::class,
            'token_expires_at' => 'datetime',
            'connected_at' => 'datetime',
            'last_refreshed_at' => 'datetime',
            'disconnected_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the decrypted access token.
     *
     * @return Attribute<string|null, string>
     */
    protected function accessToken(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->access_token_encrypted
                ? Crypt::decryptString($this->access_token_encrypted)
                : null,
            set: fn (string $value): array => [
                'access_token_encrypted' => Crypt::encryptString($value),
            ],
        );
    }

    /**
     * Get the decrypted refresh token.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function refreshToken(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->refresh_token_encrypted
                ? Crypt::decryptString($this->refresh_token_encrypted)
                : null,
            set: fn (?string $value): array => [
                'refresh_token_encrypted' => $value
                    ? Crypt::encryptString($value)
                    : null,
            ],
        );
    }

    /**
     * Get the workspace that this social account belongs to.
     *
     * @return BelongsTo<Workspace, SocialAccount>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the user who connected this social account.
     *
     * @return BelongsTo<User, SocialAccount>
     */
    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by_user_id');
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<SocialAccount>  $query
     * @return Builder<SocialAccount>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter by platform.
     *
     * @param  Builder<SocialAccount>  $query
     * @return Builder<SocialAccount>
     */
    public function scopeForPlatform(Builder $query, SocialPlatform $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to get only connected accounts.
     *
     * @param  Builder<SocialAccount>  $query
     * @return Builder<SocialAccount>
     */
    public function scopeConnected(Builder $query): Builder
    {
        return $query->where('status', SocialAccountStatus::CONNECTED);
    }

    /**
     * Scope to find accounts with tokens expiring soon.
     *
     * @param  Builder<SocialAccount>  $query
     * @return Builder<SocialAccount>
     */
    public function scopeNeedsTokenRefresh(Builder $query, int $daysBeforeExpiry = 7): Builder
    {
        return $query->where('status', SocialAccountStatus::CONNECTED)
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<=', now()->addDays($daysBeforeExpiry));
    }

    /**
     * Scope to find accounts with expired tokens.
     *
     * @param  Builder<SocialAccount>  $query
     * @return Builder<SocialAccount>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<', now());
    }

    /**
     * Check if the account is connected.
     */
    public function isConnected(): bool
    {
        return $this->status === SocialAccountStatus::CONNECTED;
    }

    /**
     * Check if the account is healthy (operational).
     */
    public function isHealthy(): bool
    {
        return $this->status->isHealthy();
    }

    /**
     * Check if the account can publish content.
     */
    public function canPublish(): bool
    {
        return $this->status->canPublish();
    }

    /**
     * Check if the token has expired.
     */
    public function isTokenExpired(): bool
    {
        if ($this->token_expires_at === null) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Check if the token is expiring soon.
     */
    public function isTokenExpiringSoon(int $days = 7): bool
    {
        if ($this->token_expires_at === null) {
            return false;
        }

        return $this->token_expires_at->lte(now()->addDays($days));
    }

    /**
     * Check if the account requires reconnection.
     */
    public function requiresReconnect(): bool
    {
        return $this->status->requiresReconnect();
    }

    /**
     * Get the display name with optional username.
     * Returns "Account Name (@username)" or just the name.
     */
    public function getDisplayName(): string
    {
        if ($this->account_username) {
            return "{$this->account_name} (@{$this->account_username})";
        }

        return $this->account_name;
    }

    /**
     * Disconnect the social account.
     */
    public function disconnect(): void
    {
        $this->status = SocialAccountStatus::DISCONNECTED;
        $this->disconnected_at = now();
        $this->save();
    }

    /**
     * Mark the token as expired.
     */
    public function markTokenExpired(): void
    {
        $this->status = SocialAccountStatus::TOKEN_EXPIRED;
        $this->save();
    }

    /**
     * Mark the account as revoked.
     */
    public function markRevoked(): void
    {
        $this->status = SocialAccountStatus::REVOKED;
        $this->save();
    }

    /**
     * Update the OAuth tokens.
     */
    public function updateTokens(
        string $accessToken,
        ?string $refreshToken,
        ?\DateTimeInterface $expiresAt
    ): void {
        $this->access_token = $accessToken;
        $this->refresh_token = $refreshToken;
        $this->token_expires_at = $expiresAt;
        $this->last_refreshed_at = now();
        $this->status = SocialAccountStatus::CONNECTED;
        $this->save();
    }

    /**
     * Get a metadata value using dot notation.
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->metadata ?? [], $key, $default);
    }

    /**
     * Set a metadata value using dot notation.
     */
    public function setMetadata(string $key, mixed $value): void
    {
        $metadata = $this->metadata ?? [];
        Arr::set($metadata, $key, $value);
        $this->metadata = $metadata;
        $this->save();
    }
}
