<?php

declare(strict_types=1);

namespace Database\Factories\Workspace;

use App\Enums\Workspace\WorkspaceStatus;
use App\Models\Tenant\Tenant;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for Workspace model.
 *
 * @extends Factory<Workspace>
 */
final class WorkspaceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Workspace>
     */
    protected $model = Workspace::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company() . ' ' . fake()->randomElement(['Team', 'Workspace', 'Hub', 'Space']);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->boolean(70) ? fake()->sentence(10) : null,
            'status' => fake()->randomElement([
                WorkspaceStatus::ACTIVE,
                WorkspaceStatus::ACTIVE,
                WorkspaceStatus::ACTIVE, // Weight toward active
                WorkspaceStatus::SUSPENDED,
            ]),
            'settings' => [
                'timezone' => fake()->randomElement(['Asia/Kolkata', 'America/New_York', 'Europe/London', 'America/Los_Angeles']),
                'date_format' => fake()->randomElement(['DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD']),
                'approval_workflow' => [
                    'enabled' => fake()->boolean(60),
                    'required_for_roles' => ['editor'],
                ],
                'default_social_accounts' => [],
                'content_categories' => fake()->randomElements(['Marketing', 'Product', 'Support', 'Sales', 'HR'], rand(2, 4)),
                'hashtag_groups' => [
                    'brand' => ['#BizSocials', '#SocialMedia'],
                    'campaign' => [],
                ],
            ],
        ];
    }

    /**
     * Set workspace status to active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => WorkspaceStatus::ACTIVE,
        ]);
    }

    /**
     * Set workspace status to suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => WorkspaceStatus::SUSPENDED,
        ]);
    }

    /**
     * Set workspace status to deleted.
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => WorkspaceStatus::DELETED,
            'deleted_at' => now(),
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

    /**
     * Enable approval workflow.
     */
    public function withApprovalWorkflow(): static
    {
        return $this->state(function (array $attributes): array {
            $settings = $attributes['settings'] ?? [];
            $settings['approval_workflow'] = [
                'enabled' => true,
                'required_for_roles' => ['editor'],
            ];

            return ['settings' => $settings];
        });
    }

    /**
     * Disable approval workflow.
     */
    public function withoutApprovalWorkflow(): static
    {
        return $this->state(function (array $attributes): array {
            $settings = $attributes['settings'] ?? [];
            $settings['approval_workflow'] = [
                'enabled' => false,
                'required_for_roles' => [],
            ];

            return ['settings' => $settings];
        });
    }
}
