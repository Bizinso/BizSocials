<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Enums\Platform\IntegrationStatus;
use App\Enums\Social\SocialPlatform;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * @property string $id
 * @property string $provider
 * @property string $display_name
 * @property array<string> $platforms
 * @property string $app_id_encrypted
 * @property string $app_secret_encrypted
 * @property array<string, string> $redirect_uris
 * @property string $api_version
 * @property array<string, array<string>> $scopes
 * @property bool $is_enabled
 * @property IntegrationStatus $status
 * @property string $environment
 * @property string|null $webhook_verify_token
 * @property string|null $webhook_secret_encrypted
 * @property array<string, mixed>|null $rate_limit_config
 * @property \Carbon\Carbon|null $last_verified_at
 * @property \Carbon\Carbon|null $last_rotated_at
 * @property array<string, mixed>|null $metadata
 * @property string|null $updated_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read SuperAdminUser|null $updatedByAdmin
 */
final class SocialPlatformIntegration extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'social_platform_integrations';

    protected $fillable = [
        'provider',
        'display_name',
        'platforms',
        'app_id_encrypted',
        'app_secret_encrypted',
        'redirect_uris',
        'api_version',
        'scopes',
        'is_enabled',
        'status',
        'environment',
        'webhook_verify_token',
        'webhook_secret_encrypted',
        'rate_limit_config',
        'last_verified_at',
        'last_rotated_at',
        'metadata',
        'updated_by',
    ];

    protected $hidden = [
        'app_id_encrypted',
        'app_secret_encrypted',
        'webhook_secret_encrypted',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platforms' => 'array',
            'redirect_uris' => 'array',
            'scopes' => 'array',
            'is_enabled' => 'boolean',
            'status' => IntegrationStatus::class,
            'rate_limit_config' => 'array',
            'last_verified_at' => 'datetime',
            'last_rotated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    /**
     * @return BelongsTo<SuperAdminUser, SocialPlatformIntegration>
     */
    public function updatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(SuperAdminUser::class, 'updated_by');
    }

    // ── Encrypted Accessors ────────────────────────────────────

    public function getAppId(): string
    {
        return Crypt::decryptString($this->app_id_encrypted);
    }

    public function setAppId(string $value): void
    {
        $this->app_id_encrypted = Crypt::encryptString($value);
    }

    public function getAppSecret(): string
    {
        return Crypt::decryptString($this->app_secret_encrypted);
    }

    public function setAppSecret(string $value): void
    {
        $this->app_secret_encrypted = Crypt::encryptString($value);
    }

    public function getWebhookSecret(): ?string
    {
        if ($this->webhook_secret_encrypted === null) {
            return null;
        }

        return Crypt::decryptString($this->webhook_secret_encrypted);
    }

    public function setWebhookSecret(?string $value): void
    {
        $this->webhook_secret_encrypted = $value !== null
            ? Crypt::encryptString($value)
            : null;
    }

    // ── Helpers ────────────────────────────────────────────────

    public function getMaskedAppId(): string
    {
        $appId = $this->getAppId();
        $len = strlen($appId);

        if ($len <= 9) {
            return str_repeat('*', $len);
        }

        return substr($appId, 0, 5) . '...' . substr($appId, -4);
    }

    /**
     * @return array<string>
     */
    public function getScopesFor(SocialPlatform $platform): array
    {
        return $this->scopes[$platform->value] ?? [];
    }

    public function getRedirectUri(SocialPlatform $platform): string
    {
        return $this->redirect_uris[$platform->value] ?? '';
    }

    public function isActive(): bool
    {
        return $this->is_enabled && $this->status === IntegrationStatus::ACTIVE;
    }

    public function coversPlatform(SocialPlatform $platform): bool
    {
        return in_array($platform->value, $this->platforms, true);
    }

    // ── Scopes ─────────────────────────────────────────────────

    /**
     * @param Builder<SocialPlatformIntegration> $query
     * @return Builder<SocialPlatformIntegration>
     */
    public function scopeForPlatform(Builder $query, SocialPlatform $platform): Builder
    {
        return $query->whereJsonContains('platforms', $platform->value);
    }

    /**
     * @param Builder<SocialPlatformIntegration> $query
     * @return Builder<SocialPlatformIntegration>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_enabled', true)->where('status', IntegrationStatus::ACTIVE);
    }
}
