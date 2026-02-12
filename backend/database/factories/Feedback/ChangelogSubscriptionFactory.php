<?php

declare(strict_types=1);

namespace Database\Factories\Feedback;

use App\Models\Feedback\ChangelogSubscription;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for ChangelogSubscription model.
 *
 * @extends Factory<ChangelogSubscription>
 */
final class ChangelogSubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ChangelogSubscription>
     */
    protected $model = ChangelogSubscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'user_id' => User::factory(),
            'tenant_id' => Tenant::factory(),
            'notify_major' => true,
            'notify_minor' => true,
            'notify_patch' => false,
            'is_active' => true,
            'unsubscribed_at' => null,
        ];
    }

    /**
     * Set as active subscription.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
            'unsubscribed_at' => null,
        ]);
    }

    /**
     * Set as inactive subscription.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
            'unsubscribed_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Set to receive all notifications.
     */
    public function allNotifications(): static
    {
        return $this->state(fn (array $attributes): array => [
            'notify_major' => true,
            'notify_minor' => true,
            'notify_patch' => true,
        ]);
    }

    /**
     * Set to receive only major notifications.
     */
    public function majorOnly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'notify_major' => true,
            'notify_minor' => false,
            'notify_patch' => false,
        ]);
    }

    /**
     * Set for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    /**
     * Set as anonymous (no user).
     */
    public function anonymous(): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => null,
            'tenant_id' => null,
        ]);
    }
}
