<?php

declare(strict_types=1);

namespace Database\Factories\Audit;

use App\Models\Audit\LoginHistory;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for LoginHistory model.
 *
 * @extends Factory<LoginHistory>
 */
final class LoginHistoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<LoginHistory>
     */
    protected $model = LoginHistory::class;

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
            'successful' => true,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'device_type' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
            'browser' => fake()->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'os' => fake()->randomElement(['Windows', 'macOS', 'Linux', 'iOS', 'Android']),
            'country_code' => fake()->countryCode(),
            'city' => fake()->city(),
            'failure_reason' => null,
            'logged_out_at' => null,
        ];
    }

    /**
     * Set as successful login.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes): array => [
            'successful' => true,
            'failure_reason' => null,
        ]);
    }

    /**
     * Set as failed login.
     */
    public function failed(?string $reason = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'successful' => false,
            'failure_reason' => $reason ?? fake()->randomElement([
                'invalid_credentials',
                'account_locked',
                'mfa_failed',
                'session_expired',
            ]),
        ]);
    }

    /**
     * Set device type to desktop.
     */
    public function desktop(): static
    {
        return $this->state(fn (array $attributes): array => [
            'device_type' => 'desktop',
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
            'os' => fake()->randomElement(['iOS', 'Android']),
        ]);
    }

    /**
     * Set as logged out.
     */
    public function loggedOut(): static
    {
        return $this->state(fn (array $attributes): array => [
            'logged_out_at' => now(),
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

    /**
     * Set for a specific IP address.
     */
    public function fromIp(string $ip): static
    {
        return $this->state(fn (array $attributes): array => [
            'ip_address' => $ip,
        ]);
    }
}
