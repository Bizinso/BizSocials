<?php

declare(strict_types=1);

namespace Database\Factories\Audit;

use App\Enums\Audit\DataRequestStatus;
use App\Enums\Audit\DataRequestType;
use App\Models\Audit\DataExportRequest;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for DataExportRequest model.
 *
 * @extends Factory<DataExportRequest>
 */
final class DataExportRequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<DataExportRequest>
     */
    protected $model = DataExportRequest::class;

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
            'request_type' => DataRequestType::EXPORT,
            'status' => DataRequestStatus::PENDING,
            'data_categories' => ['profile', 'posts', 'comments'],
            'format' => fake()->randomElement(['json', 'csv', 'xml']),
            'file_path' => null,
            'file_size_bytes' => null,
            'expires_at' => null,
            'download_count' => 0,
            'completed_at' => null,
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
            'file_path' => null,
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
            'file_path' => 'exports/' . fake()->uuid() . '.json',
            'file_size_bytes' => fake()->numberBetween(1000, 1000000),
            'completed_at' => now(),
            'expires_at' => now()->addDays(7),
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
     * Set as expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DataRequestStatus::COMPLETED,
            'file_path' => 'exports/' . fake()->uuid() . '.json',
            'file_size_bytes' => fake()->numberBetween(1000, 1000000),
            'completed_at' => now()->subDays(10),
            'expires_at' => now()->subDays(3),
        ]);
    }

    /**
     * Set request type.
     */
    public function ofType(DataRequestType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'request_type' => $type,
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
