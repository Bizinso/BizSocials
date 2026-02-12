<?php

declare(strict_types=1);

namespace App\Services\Workspace;

use App\Enums\Audit\AuditAction;
use App\Models\User;
use App\Models\Workspace\Team;
use App\Models\Workspace\TeamMember;
use App\Models\Workspace\Workspace;
use App\Services\Audit\AuditLogService;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

final class TeamService extends BaseService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * List teams for a workspace.
     *
     * @param array<string, mixed> $filters
     */
    public function list(Workspace $workspace, array $filters = []): LengthAwarePaginator
    {
        $query = Team::forWorkspace($workspace->id)
            ->withCount('teamMembers');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Create a team in a workspace.
     *
     * @param array{name: string, description?: string|null, is_default?: bool} $data
     */
    public function create(Workspace $workspace, User $actor, array $data): Team
    {
        return $this->transaction(function () use ($workspace, $actor, $data) {
            $isDefault = $data['is_default'] ?? false;

            // If setting as default, unset previous default
            if ($isDefault) {
                Team::forWorkspace($workspace->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $team = Team::create([
                'workspace_id' => $workspace->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_default' => $isDefault,
            ]);

            $this->auditLogService->record(
                action: AuditAction::CREATE,
                auditable: $team,
                user: $actor,
                newValues: [
                    'name' => $team->name,
                    'description' => $team->description,
                    'is_default' => $team->is_default,
                ],
                description: 'team.created',
            );

            $this->log('Team created', [
                'team_id' => $team->id,
                'workspace_id' => $workspace->id,
            ]);

            return $team;
        });
    }

    /**
     * Update a team.
     *
     * @param array{name?: string, description?: string|null, is_default?: bool} $data
     */
    public function update(Team $team, User $actor, array $data): Team
    {
        return $this->transaction(function () use ($team, $actor, $data) {
            $oldValues = [
                'name' => $team->name,
                'description' => $team->description,
                'is_default' => $team->is_default,
            ];

            // If setting as default, unset previous default
            if (isset($data['is_default']) && $data['is_default'] && ! $team->is_default) {
                Team::forWorkspace($team->workspace_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $team->update($data);

            $this->auditLogService->record(
                action: AuditAction::UPDATE,
                auditable: $team,
                user: $actor,
                oldValues: $oldValues,
                newValues: [
                    'name' => $team->name,
                    'description' => $team->description,
                    'is_default' => $team->is_default,
                ],
                description: 'team.updated',
            );

            $this->log('Team updated', ['team_id' => $team->id]);

            return $team->fresh();
        });
    }

    /**
     * Delete a team.
     */
    public function delete(Team $team, User $actor): void
    {
        $this->transaction(function () use ($team, $actor) {
            $this->auditLogService->record(
                action: AuditAction::DELETE,
                auditable: $team,
                user: $actor,
                oldValues: [
                    'name' => $team->name,
                    'workspace_id' => $team->workspace_id,
                ],
                description: 'team.deleted',
            );

            $this->log('Team deleted', ['team_id' => $team->id]);

            $team->delete();
        });
    }

    /**
     * Add a member to a team.
     */
    public function addMember(Team $team, User $user, User $actor): TeamMember
    {
        // Verify user is a workspace member
        $workspace = $team->workspace;
        if (! $workspace->hasMember($user->id)) {
            throw ValidationException::withMessages([
                'user_id' => ['User must be a member of the workspace to join a team.'],
            ]);
        }

        // Check for duplicate
        if ($team->hasMember($user->id)) {
            throw ValidationException::withMessages([
                'user_id' => ['User is already a member of this team.'],
            ]);
        }

        return $this->transaction(function () use ($team, $user, $actor) {
            $member = TeamMember::create([
                'team_id' => $team->id,
                'user_id' => $user->id,
            ]);

            $this->auditLogService->record(
                action: AuditAction::CREATE,
                auditable: $team,
                user: $actor,
                newValues: [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                ],
                description: 'team.member_added',
            );

            $this->log('Team member added', [
                'team_id' => $team->id,
                'user_id' => $user->id,
            ]);

            return $member->load('user');
        });
    }

    /**
     * Remove a member from a team.
     */
    public function removeMember(Team $team, User $user, User $actor): void
    {
        $member = TeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $member) {
            throw ValidationException::withMessages([
                'user_id' => ['User is not a member of this team.'],
            ]);
        }

        $this->transaction(function () use ($team, $user, $actor, $member) {
            $this->auditLogService->record(
                action: AuditAction::DELETE,
                auditable: $team,
                user: $actor,
                oldValues: [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                ],
                description: 'team.member_removed',
            );

            $this->log('Team member removed', [
                'team_id' => $team->id,
                'user_id' => $user->id,
            ]);

            $member->delete();
        });
    }
}
