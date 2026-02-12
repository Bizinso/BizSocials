<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\User\TenantRole;
use App\Enums\User\UserStatus;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Factory for User model.
 *
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<User>
     */
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'name' => fake()->name(),
            'avatar_url' => fake()->boolean(30) ? fake()->imageUrl(200, 200, 'people') : null,
            'phone' => fake()->boolean(50) ? fake()->phoneNumber() : null,
            'timezone' => fake()->boolean(30) ? fake()->timezone() : null,
            'language' => 'en',
            'status' => fake()->randomElement([
                UserStatus::ACTIVE,
                UserStatus::ACTIVE,
                UserStatus::ACTIVE, // Weight toward active
                UserStatus::PENDING,
            ]),
            'role_in_tenant' => fake()->randomElement([
                TenantRole::MEMBER,
                TenantRole::MEMBER,
                TenantRole::ADMIN,
                TenantRole::OWNER,
            ]),
            'email_verified_at' => fake()->boolean(80) ? fake()->dateTimeBetween('-1 year', 'now') : null,
            'last_login_at' => fake()->boolean(60) ? fake()->dateTimeBetween('-30 days', 'now') : null,
            'last_active_at' => fake()->boolean(70) ? fake()->dateTimeBetween('-7 days', 'now') : null,
            'mfa_enabled' => fake()->boolean(20),
            'mfa_secret' => null,
            'settings' => [
                'notifications' => [
                    'email_on_mention' => true,
                    'email_on_comment' => true,
                    'email_digest' => fake()->randomElement(['daily', 'weekly', 'none']),
                    'push_enabled' => fake()->boolean(50),
                ],
                'ui' => [
                    'theme' => fake()->randomElement(['light', 'dark', 'system']),
                    'compact_mode' => fake()->boolean(20),
                    'sidebar_collapsed' => fake()->boolean(10),
                ],
            ],
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Set user status to pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UserStatus::PENDING,
            'email_verified_at' => null,
        ]);
    }

    /**
     * Set user status to active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UserStatus::ACTIVE,
        ]);
    }

    /**
     * Set user status to suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UserStatus::SUSPENDED,
        ]);
    }

    /**
     * Set user status to deactivated.
     */
    public function deactivated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UserStatus::DEACTIVATED,
        ]);
    }

    /**
     * Set user role to owner.
     */
    public function owner(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role_in_tenant' => TenantRole::OWNER,
        ]);
    }

    /**
     * Set user role to admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role_in_tenant' => TenantRole::ADMIN,
        ]);
    }

    /**
     * Set user role to member.
     */
    public function member(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role_in_tenant' => TenantRole::MEMBER,
        ]);
    }

    /**
     * Set email as verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Set email as unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Enable MFA for the user.
     */
    public function withMfa(): static
    {
        return $this->state(fn (array $attributes): array => [
            'mfa_enabled' => true,
            'mfa_secret' => Str::random(32),
        ]);
    }

    /**
     * Associate with a specific tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes): array => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Set specific settings.
     *
     * @param  array<string, mixed>  $settings
     */
    public function withSettings(array $settings): static
    {
        return $this->state(fn (array $attributes): array => [
            'settings' => array_merge($attributes['settings'] ?? [], $settings),
        ]);
    }
}
