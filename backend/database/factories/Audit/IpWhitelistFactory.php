<?php

declare(strict_types=1);

namespace Database\Factories\Audit;

use App\Models\Audit\IpWhitelist;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for IpWhitelist model.
 *
 * @extends Factory<IpWhitelist>
 */
final class IpWhitelistFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<IpWhitelist>
     */
    protected $model = IpWhitelist::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'ip_address' => fake()->unique()->ipv4(),
            'cidr_range' => null,
            'label' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'is_active' => true,
            'created_by' => User::factory(),
            'expires_at' => null,
        ];
    }

    /**
     * Set as active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
            'expires_at' => null,
        ]);
    }

    /**
     * Set as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Set as expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subDays(1),
        ]);
    }

    /**
     * Set with expiration.
     */
    public function expiresAt(\Carbon\Carbon $date): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => $date,
        ]);
    }

    /**
     * Set with CIDR range.
     */
    public function withCidr(string $cidr): static
    {
        return $this->state(fn (array $attributes): array => [
            'cidr_range' => $cidr,
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
     * Set the creator.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_by' => $user->id,
        ]);
    }

    /**
     * Set specific IP address.
     */
    public function withIp(string $ip): static
    {
        return $this->state(fn (array $attributes): array => [
            'ip_address' => $ip,
        ]);
    }
}
