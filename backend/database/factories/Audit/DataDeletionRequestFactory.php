<?php

declare(strict_types=1);

namespace Database\Factories\Audit;

use App\Enums\Audit\DataRequestStatus;
use App\Models\Audit\DataDeletionRequest;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for DataDeletionRequest model.
 *
 * @extends Factory<DataDeletionRequest>
 */
final class DataDeletionRequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<DataDeletionRequest>
     */
    protected $model = DataDeletionRequest::class;

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
            'requested_by' => User::factory(),
            'status' => DataRequestStatus::PENDING,
            'data_categories' => ['profile', 'posts', 'comments'],
            'reason' => fake()->sentence(),
            'requires_approval' => true,
            'approved_by' => null,
            'approved_at' => null,
            'scheduled_for' => null,
            'completed_at' => null,
            'deletion_summary' => null,
            'failure_reason' => null,
        ];
    }

    /**
     * Set status to pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DataRequestStatus::PENDING,
            'approved_by' => null,
            'approved_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Set status to processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DataRequestStatus::PROCESSING,
        ]);
    }

    /**
     * Set status to completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DataRequestStatus::COMPLETED,
            'completed_at' => now(),
            'deletion_summary' => [
                'posts_deleted' => fake()->numberBetween(0, 100),
                'comments_deleted' => fake()->numberBetween(0, 500),
                'files_deleted' => fake()->numberBetween(0, 50),
            ],
        ]);
    }

    /**
     * Set status to failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DataRequestStatus::FAILED,
            'failure_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Set status to cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DataRequestStatus::CANCELLED,
        ]);
    }

    /**
     * Set as approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'approved_by' => SuperAdminUser::factory(),
            'approved_at' => now(),
            'scheduled_for' => now()->addDays(30),
        ]);
    }

    /**
     * Set as requiring approval.
     */
    public function requiresApproval(): static
    {
        return $this->state(fn (array $attributes): array => [
            'requires_approval' => true,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    /**
     * Set as not requiring approval.
     */
    public function noApprovalRequired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'requires_approval' => false,
        ]);
    }

    /**
     * Set as scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'approved_by' => SuperAdminUser::factory(),
            'approved_at' => now(),
            'scheduled_for' => now()->addDays(7),
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
     * Set the requester.
     */
    public function requestedBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'requested_by' => $user->id,
        ]);
    }
}
