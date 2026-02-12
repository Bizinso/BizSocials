<?php

declare(strict_types=1);

namespace Database\Factories\Platform;

use App\Enums\Platform\SuperAdminRole;
use App\Enums\Platform\SuperAdminStatus;
use App\Models\Platform\SuperAdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Factory for SuperAdminUser model.
 *
 * @extends Factory<SuperAdminUser>
 */
final class SuperAdminUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<SuperAdminUser>
     */
    protected $model = SuperAdminUser::class;

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
            'email' => fake()->unique()->safeEmail(),
            'password' => self::$password ??= Hash::make('password'),
            'name' => fake()->name(),
            'role' => fake()->randomElement(SuperAdminRole::cases()),
            // 90% chance of ACTIVE status
            'status' => fake()->randomFloat(2, 0, 1) < 0.9
                ? SuperAdminStatus::ACTIVE
                : fake()->randomElement([SuperAdminStatus::INACTIVE, SuperAdminStatus::SUSPENDED]),
            'last_login_at' => fake()->optional(0.7)->dateTimeBetween('-30 days', 'now'),
            'mfa_enabled' => false,
            'mfa_secret' => null,
        ];
    }

    /**
     * Indicate the user is a super admin.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => SuperAdminRole::SUPER_ADMIN,
        ]);
    }

    /**
     * Indicate the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => SuperAdminRole::ADMIN,
        ]);
    }

    /**
     * Indicate the user is support staff.
     */
    public function support(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => SuperAdminRole::SUPPORT,
        ]);
    }

    /**
     * Indicate the user is a viewer.
     */
    public function viewer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => SuperAdminRole::VIEWER,
        ]);
    }

    /**
     * Indicate the user is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SuperAdminStatus::ACTIVE,
        ]);
    }

    /**
     * Indicate the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SuperAdminStatus::INACTIVE,
        ]);
    }

    /**
     * Indicate the user is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SuperAdminStatus::SUSPENDED,
        ]);
    }

    /**
     * Indicate MFA is enabled.
     */
    public function withMfa(): static
    {
        return $this->state(fn (array $attributes): array => [
            'mfa_enabled' => true,
            'mfa_secret' => Str::random(32),
        ]);
    }

    /**
     * Set a specific password.
     */
    public function withPassword(string $password): static
    {
        return $this->state(fn (array $attributes): array => [
            'password' => Hash::make($password),
        ]);
    }
}
