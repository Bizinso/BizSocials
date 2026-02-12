<?php

declare(strict_types=1);

namespace Database\Factories\Workspace;

use App\Enums\Workspace\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Models\Workspace\WorkspaceMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for WorkspaceMembership model.
 *
 * @extends Factory<WorkspaceMembership>
 */
final class WorkspaceMembershipFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<WorkspaceMembership>
     */
    protected $model = WorkspaceMembership::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'user_id' => User::factory(),
            'role' => fake()->randomElement([
                WorkspaceRole::EDITOR,
                WorkspaceRole::EDITOR,
                WorkspaceRole::EDITOR,
                WorkspaceRole::ADMIN,
                WorkspaceRole::VIEWER,
            ]),
            'joined_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Set role to owner.
     */
    public function owner(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => WorkspaceRole::OWNER,
        ]);
    }

    /**
     * Set role to admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => WorkspaceRole::ADMIN,
        ]);
    }

    /**
     * Set role to editor.
     */
    public function editor(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => WorkspaceRole::EDITOR,
        ]);
    }

    /**
     * Set role to viewer.
     */
    public function viewer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => WorkspaceRole::VIEWER,
        ]);
    }

    /**
     * Associate with a specific workspace.
     */
    public function forWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes): array => [
            'workspace_id' => $workspace->id,
        ]);
    }

    /**
     * Associate with a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }
}
