<?php

declare(strict_types=1);

namespace Database\Factories\Audit;

use App\Enums\Audit\AuditAction;
use App\Enums\Audit\AuditableType;
use App\Models\Audit\AuditLog;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for AuditLog model.
 *
 * @extends Factory<AuditLog>
 */
final class AuditLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AuditLog>
     */
    protected $model = AuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'admin_id' => null,
            'action' => fake()->randomElement(AuditAction::cases()),
            'auditable_type' => fake()->randomElement(AuditableType::cases()),
            'auditable_id' => fake()->uuid(),
            'description' => fake()->sentence(),
            'old_values' => null,
            'new_values' => null,
            'metadata' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'request_id' => fake()->uuid(),
        ];
    }

    /**
     * Set the action to create.
     */
    public function createAction(): static
    {
        return $this->state(fn (array $attributes): array => [
            'action' => AuditAction::CREATE,
            'old_values' => null,
            'new_values' => ['name' => fake()->name(), 'email' => fake()->email()],
        ]);
    }

    /**
     * Set the action to update.
     */
    public function update(): static
    {
        return $this->state(fn (array $attributes): array => [
            'action' => AuditAction::UPDATE,
            'old_values' => ['name' => fake()->name()],
            'new_values' => ['name' => fake()->name()],
        ]);
    }

    /**
     * Set the action to delete.
     */
    public function delete(): static
    {
        return $this->state(fn (array $attributes): array => [
            'action' => AuditAction::DELETE,
            'old_values' => ['name' => fake()->name(), 'email' => fake()->email()],
            'new_values' => null,
        ]);
    }

    /**
     * Set the action to view.
     */
    public function view(): static
    {
        return $this->state(fn (array $attributes): array => [
            'action' => AuditAction::VIEW,
        ]);
    }

    /**
     * Set the action to login.
     */
    public function login(): static
    {
        return $this->state(fn (array $attributes): array => [
            'action' => AuditAction::LOGIN,
            'auditable_type' => AuditableType::USER,
        ]);
    }

    /**
     * Set the action to logout.
     */
    public function logout(): static
    {
        return $this->state(fn (array $attributes): array => [
            'action' => AuditAction::LOGOUT,
            'auditable_type' => AuditableType::USER,
        ]);
    }

    /**
     * Set the auditable type.
     */
    public function forType(AuditableType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'auditable_type' => $type,
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
     * Set for a specific admin.
     */
    public function byAdmin(SuperAdminUser $admin): static
    {
        return $this->state(fn (array $attributes): array => [
            'admin_id' => $admin->id,
            'user_id' => null,
        ]);
    }
}
