<?php

declare(strict_types=1);

namespace Database\Factories\Audit;

use App\Enums\Audit\SecurityEventType;
use App\Enums\Audit\SecuritySeverity;
use App\Models\Audit\SecurityEvent;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for SecurityEvent model.
 *
 * @extends Factory<SecurityEvent>
 */
final class SecurityEventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<SecurityEvent>
     */
    protected $model = SecurityEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $eventType = fake()->randomElement(SecurityEventType::cases());

        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'event_type' => $eventType,
            'severity' => SecuritySeverity::from($eventType->severity()),
            'description' => fake()->sentence(),
            'metadata' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'country_code' => fake()->countryCode(),
            'city' => fake()->city(),
            'is_resolved' => false,
            'resolved_by' => null,
            'resolved_at' => null,
            'resolution_notes' => null,
        ];
    }

    /**
     * Set event type to login success.
     */
    public function loginSuccess(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_type' => SecurityEventType::LOGIN_SUCCESS,
            'severity' => SecuritySeverity::INFO,
        ]);
    }

    /**
     * Set event type to login failure.
     */
    public function loginFailure(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_type' => SecurityEventType::LOGIN_FAILURE,
            'severity' => SecuritySeverity::MEDIUM,
        ]);
    }

    /**
     * Set event type to suspicious activity.
     */
    public function suspiciousActivity(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_type' => SecurityEventType::SUSPICIOUS_ACTIVITY,
            'severity' => SecuritySeverity::HIGH,
        ]);
    }

    /**
     * Set event type to account locked.
     */
    public function accountLocked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_type' => SecurityEventType::ACCOUNT_LOCKED,
            'severity' => SecuritySeverity::CRITICAL,
        ]);
    }

    /**
     * Set severity level.
     */
    public function withSeverity(SecuritySeverity $severity): static
    {
        return $this->state(fn (array $attributes): array => [
            'severity' => $severity,
        ]);
    }

    /**
     * Mark as resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_resolved' => true,
            'resolved_by' => SuperAdminUser::factory(),
            'resolved_at' => now(),
            'resolution_notes' => fake()->sentence(),
        ]);
    }

    /**
     * Mark as unresolved.
     */
    public function unresolved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_resolved' => false,
            'resolved_by' => null,
            'resolved_at' => null,
            'resolution_notes' => null,
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
     * Set for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
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
