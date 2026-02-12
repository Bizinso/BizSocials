<?php

declare(strict_types=1);

namespace Database\Factories\Audit;

use App\Enums\Audit\SessionStatus;
use App\Models\Audit\SessionHistory;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for SessionHistory model.
 *
 * @extends Factory<SessionHistory>
 */
final class SessionHistoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<SessionHistory>
     */
    protected $model = SessionHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tenant_id' => Tenant::factory(),
            'session_token' => Str::random(64),
            'status' => SessionStatus::ACTIVE,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'device_type' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
            'device_name' => fake()->randomElement(['MacBook Pro', 'iPhone 15', 'Windows PC', 'iPad']),
            'browser' => fake()->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'os' => fake()->randomElement(['Windows', 'macOS', 'Linux', 'iOS', 'Android']),
            'country_code' => fake()->countryCode(),
            'city' => fake()->city(),
            'is_current' => false,
            'last_activity_at' => now(),
            'expires_at' => now()->addDays(7),
            'revoked_at' => null,
            'revoked_by' => null,
            'revocation_reason' => null,
        ];
    }

    /**
     * Set as active session.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SessionStatus::ACTIVE,
            'last_activity_at' => now(),
            'expires_at' => now()->addDays(7),
            'revoked_at' => null,
            'revoked_by' => null,
        ]);
    }

    /**
     * Set as expired session.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SessionStatus::EXPIRED,
            'expires_at' => now()->subDays(1),
        ]);
    }

    /**
     * Set as revoked session.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SessionStatus::REVOKED,
            'revoked_at' => now(),
            'revoked_by' => User::factory(),
            'revocation_reason' => fake()->sentence(),
            'is_current' => false,
        ]);
    }

    /**
     * Set as logged out session.
     */
    public function loggedOut(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SessionStatus::LOGGED_OUT,
            'is_current' => false,
        ]);
    }

    /**
     * Set as current session.
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_current' => true,
            'status' => SessionStatus::ACTIVE,
        ]);
    }

    /**
     * Set device type to desktop.
     */
    public function desktop(): static
    {
        return $this->state(fn (array $attributes): array => [
            'device_type' => 'desktop',
            'device_name' => fake()->randomElement(['MacBook Pro', 'Windows PC', 'Linux Desktop']),
            'os' => fake()->randomElement(['Windows', 'macOS', 'Linux']),
        ]);
    }

    /**
     * Set device type to mobile.
     */
    public function mobile(): static
    {
        return $this->state(fn (array $attributes): array => [
            'device_type' => 'mobile',
            'device_name' => fake()->randomElement(['iPhone 15', 'Samsung Galaxy', 'Google Pixel']),
            'os' => fake()->randomElement(['iOS', 'Android']),
        ]);
    }

    /**
     * Set device type to tablet.
     */
    public function tablet(): static
    {
        return $this->state(fn (array $attributes): array => [
            'device_type' => 'tablet',
            'device_name' => fake()->randomElement(['iPad', 'Samsung Tab', 'Surface Pro']),
            'os' => fake()->randomElement(['iOS', 'Android', 'Windows']),
        ]);
    }

    /**
     * Set for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set for a specific tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes): array => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
