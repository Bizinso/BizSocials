<?php

declare(strict_types=1);

namespace Database\Factories\User;

use App\Enums\User\InvitationStatus;
use App\Enums\User\TenantRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\User\UserInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for UserInvitation model.
 *
 * @extends Factory<UserInvitation>
 */
final class UserInvitationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<UserInvitation>
     */
    protected $model = UserInvitation::class;

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
            'role_in_tenant' => fake()->randomElement([
                TenantRole::MEMBER,
                TenantRole::MEMBER,
                TenantRole::ADMIN,
            ]),
            'workspace_memberships' => fake()->boolean(40) ? [
                [
                    'workspace_id' => fake()->uuid(),
                    'role' => fake()->randomElement(['ADMIN', 'EDITOR', 'VIEWER']),
                ],
            ] : null,
            'invited_by' => User::factory(),
            'token' => UserInvitation::generateToken(),
            'status' => InvitationStatus::PENDING,
            'expires_at' => now()->addDays(UserInvitation::EXPIRES_IN_DAYS),
            'accepted_at' => null,
        ];
    }

    /**
     * Set invitation status to pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvitationStatus::PENDING,
            'accepted_at' => null,
            'expires_at' => now()->addDays(UserInvitation::EXPIRES_IN_DAYS),
        ]);
    }

    /**
     * Set invitation status to accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvitationStatus::ACCEPTED,
            'accepted_at' => now()->subDays(fake()->numberBetween(1, 7)),
        ]);
    }

    /**
     * Set invitation status to expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvitationStatus::EXPIRED,
            'expires_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'accepted_at' => null,
        ]);
    }

    /**
     * Set invitation status to revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvitationStatus::REVOKED,
            'accepted_at' => null,
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
     * Set the inviter user.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'invited_by' => $user->id,
            'tenant_id' => $user->tenant_id,
        ]);
    }

    /**
     * Set role to admin.
     */
    public function asAdmin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role_in_tenant' => TenantRole::ADMIN,
        ]);
    }

    /**
     * Set role to member.
     */
    public function asMember(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role_in_tenant' => TenantRole::MEMBER,
        ]);
    }

    /**
     * Set specific workspace memberships.
     *
     * @param  array<array{workspace_id: string, role: string}>  $memberships
     */
    public function withWorkspaceMemberships(array $memberships): static
    {
        return $this->state(fn (array $attributes): array => [
            'workspace_memberships' => $memberships,
        ]);
    }
}
