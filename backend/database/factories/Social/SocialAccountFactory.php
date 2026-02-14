<?php

declare(strict_types=1);

namespace Database\Factories\Social;

use App\Enums\Social\SocialAccountStatus;
use App\Enums\Social\SocialPlatform;
use App\Models\Social\SocialAccount;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

/**
 * Factory for SocialAccount model.
 *
 * @extends Factory<SocialAccount>
 */
final class SocialAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<SocialAccount>
     */
    protected $model = SocialAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $platform = fake()->randomElement(SocialPlatform::cases());
        $accountName = fake()->company();

        return [
            'workspace_id' => Workspace::factory(),
            'platform' => $platform,
            'platform_account_id' => (string) fake()->unique()->numberBetween(100000000, 999999999),
            'account_name' => $accountName,
            'account_username' => fake()->boolean(70) ? fake()->userName() : null,
            'profile_image_url' => fake()->boolean(80) ? fake()->imageUrl(200, 200, 'business') : null,
            'status' => SocialAccountStatus::CONNECTED,
            'access_token_encrypted' => Crypt::encryptString(fake()->sha256()),
            'refresh_token_encrypted' => fake()->boolean(80)
                ? Crypt::encryptString(fake()->sha256())
                : null,
            'token_expires_at' => fake()->boolean(70)
                ? fake()->dateTimeBetween('+1 week', '+60 days')
                : null,
            'connected_by_user_id' => User::factory(),
            'connected_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'last_refreshed_at' => fake()->boolean(50)
                ? fake()->dateTimeBetween('-1 week', 'now')
                : null,
            'disconnected_at' => null,
            'metadata' => $this->generateMetadata($platform),
        ];
    }

    /**
     * Set the status to connected.
     */
    public function connected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SocialAccountStatus::CONNECTED,
            'disconnected_at' => null,
        ]);
    }

    /**
     * Set the status to token expired.
     */
    public function tokenExpired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SocialAccountStatus::TOKEN_EXPIRED,
            'token_expires_at' => fake()->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }

    /**
     * Set the status to revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SocialAccountStatus::REVOKED,
        ]);
    }

    /**
     * Set the status to disconnected.
     */
    public function disconnected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SocialAccountStatus::DISCONNECTED,
            'disconnected_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Set the platform to LinkedIn.
     */
    public function linkedin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'platform' => SocialPlatform::LINKEDIN,
            'metadata' => [
                'organization_id' => 'urn:li:organization:' . fake()->numberBetween(10000, 99999),
                'vanity_name' => fake()->slug(2),
                'page_type' => 'company',
            ],
        ]);
    }

    /**
     * Set the platform to Facebook.
     */
    public function facebook(): static
    {
        return $this->state(fn (array $attributes): array => [
            'platform' => SocialPlatform::FACEBOOK,
            'metadata' => [
                'page_id' => (string) fake()->numberBetween(100000000, 999999999),
                'page_access_token_encrypted' => Crypt::encryptString(fake()->sha256()),
                'category' => fake()->randomElement(['Business', 'Brand', 'Product/Service']),
            ],
        ]);
    }

    /**
     * Set the platform to Instagram.
     */
    public function instagram(): static
    {
        return $this->state(fn (array $attributes): array => [
            'platform' => SocialPlatform::INSTAGRAM,
            'metadata' => [
                'facebook_page_id' => (string) fake()->numberBetween(100000000, 999999999),
                'account_type' => 'BUSINESS',
                'followers_count' => fake()->numberBetween(100, 100000),
            ],
        ]);
    }

    /**
     * Set the platform to Twitter.
     */
    public function twitter(): static
    {
        return $this->state(fn (array $attributes): array => [
            'platform' => SocialPlatform::TWITTER,
            'metadata' => [
                'user_id' => (string) fake()->numberBetween(100000000, 999999999),
                'verified' => fake()->boolean(10),
            ],
        ]);
    }

    /**
     * Associate with a specific workspace.
     */
    public function forWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes): array => [
            'workspace_id' => $workspace->id,
        ]);
    }

    /**
     * Set the user who connected this account.
     */
    public function connectedBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'connected_by_user_id' => $user->id,
        ]);
    }

    /**
     * Set the token to expire in a given number of days.
     */
    public function expiringIn(int $days): static
    {
        return $this->state(fn (array $attributes): array => [
            'token_expires_at' => now()->addDays($days),
            'status' => SocialAccountStatus::CONNECTED,
        ]);
    }

    /**
     * Set the token as already expired.
     */
    public function expiredToken(): static
    {
        return $this->state(fn (array $attributes): array => [
            'token_expires_at' => fake()->dateTimeBetween('-1 month', '-1 day'),
            'status' => SocialAccountStatus::CONNECTED,
        ]);
    }

    /**
     * Set specific metadata.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes): array => [
            'metadata' => array_merge($attributes['metadata'] ?? [], $metadata),
        ]);
    }

    /**
     * Generate platform-specific metadata.
     *
     * @return array<string, mixed>
     */
    private function generateMetadata(SocialPlatform $platform): array
    {
        return match ($platform) {
            SocialPlatform::LINKEDIN => [
                'organization_id' => 'urn:li:organization:' . fake()->numberBetween(10000, 99999),
                'vanity_name' => fake()->slug(2),
                'page_type' => 'company',
            ],
            SocialPlatform::FACEBOOK => [
                'page_id' => (string) fake()->numberBetween(100000000, 999999999),
                'page_access_token_encrypted' => Crypt::encryptString(fake()->sha256()),
                'category' => fake()->randomElement(['Business', 'Brand', 'Product/Service']),
            ],
            SocialPlatform::INSTAGRAM => [
                'facebook_page_id' => (string) fake()->numberBetween(100000000, 999999999),
                'account_type' => 'BUSINESS',
                'followers_count' => fake()->numberBetween(100, 100000),
            ],
            SocialPlatform::TWITTER => [
                'user_id' => (string) fake()->numberBetween(100000000, 999999999),
                'verified' => fake()->boolean(10),
            ],
            SocialPlatform::YOUTUBE => [
                'channel_id' => 'UC' . fake()->regexify('[A-Za-z0-9_-]{22}'),
                'channel_title' => fake()->company() . ' Channel',
                'subscriber_count' => fake()->numberBetween(100, 100000),
            ],
            SocialPlatform::WHATSAPP => [
                'phone_number_id' => (string) fake()->numberBetween(100000000, 999999999),
                'business_account_id' => (string) fake()->numberBetween(100000000, 999999999),
            ],
        };
    }
}
