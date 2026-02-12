<?php

declare(strict_types=1);

namespace Database\Factories\Platform;

use App\Enums\Platform\IntegrationStatus;
use App\Models\Platform\SocialPlatformIntegration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

/**
 * @extends Factory<SocialPlatformIntegration>
 */
final class SocialPlatformIntegrationFactory extends Factory
{
    protected $model = SocialPlatformIntegration::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => 'meta',
            'display_name' => 'Meta (Facebook & Instagram)',
            'platforms' => ['facebook', 'instagram'],
            'app_id_encrypted' => Crypt::encryptString(fake()->numerify('##############')),
            'app_secret_encrypted' => Crypt::encryptString(fake()->sha256()),
            'redirect_uris' => [
                'facebook' => config('app.url') . '/api/v1/oauth/facebook/callback',
                'instagram' => config('app.url') . '/api/v1/oauth/instagram/callback',
            ],
            'api_version' => 'v24.0',
            'scopes' => [
                'facebook' => ['pages_show_list', 'pages_read_engagement', 'pages_manage_posts'],
                'instagram' => ['instagram_basic', 'instagram_content_publish', 'instagram_manage_comments'],
            ],
            'is_enabled' => true,
            'status' => IntegrationStatus::ACTIVE,
            'environment' => 'production',
            'webhook_verify_token' => null,
            'webhook_secret_encrypted' => null,
            'rate_limit_config' => null,
            'last_verified_at' => null,
            'last_rotated_at' => null,
            'metadata' => null,
            'updated_by' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_enabled' => true,
            'status' => IntegrationStatus::ACTIVE,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_enabled' => false,
            'status' => IntegrationStatus::DISABLED,
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_enabled' => true,
            'status' => IntegrationStatus::MAINTENANCE,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'last_verified_at' => now(),
        ]);
    }

    public function withAppId(string $appId): static
    {
        return $this->state(fn (array $attributes): array => [
            'app_id_encrypted' => Crypt::encryptString($appId),
        ]);
    }

    public function withAppSecret(string $appSecret): static
    {
        return $this->state(fn (array $attributes): array => [
            'app_secret_encrypted' => Crypt::encryptString($appSecret),
        ]);
    }
}
