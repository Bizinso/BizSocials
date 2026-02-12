<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Workspace;

use App\Enums\Workspace\Permission;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Team\AddTeamMemberRequest;
use App\Http\Requests\Team\CreateTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Models\User;
use App\Models\Workspace\Team;
use App\Models\Workspace\Workspace;
use App\Services\Workspace\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TeamController extends Controller
{
    public function __construct(
        private readonly TeamService $teamService,
    ) {}

    /**
     * List teams in a workspace.
     */
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        $filters = [
            'search' => $request->query('search'),
            'per_page' => $request->query('per_page', 15),
        ];

        $teams = $this->teamService->list($workspace, $filters);

        $data = collect($teams->items())->map(fn (Team $team) => [
            'id' => $team->id,
            'workspace_id' => $team->workspace_id,
            'name' => $team->name,
            'description' => $team->description,
            'is_default' => $team->is_default,
            'member_count' => $team->team_members_count ?? 0,
            'created_at' => $team->created_at->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $data->values(),
            'meta' => [
                'current_page' => $teams->currentPage(),
                'last_page' => $teams->lastPage(),
                'per_page' => $teams->perPage(),
                'total' => $teams->total(),
                'from' => $teams->firstItem(),
                'to' => $teams->lastItem(),
            ],
        ]);
    }

    /**
     * Create a team.
     */
    public function store(CreateTeamRequest $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        $team = $this->teamService->create($workspace, $user, $request->validated());

        return $this->created([
            'id' => $team->id,
            'workspace_id' => $team->workspace_id,
            'name' => $team->name,
            'description' => $team->description,
            'is_default' => $team->is_default,
            'member_count' => 0,
            'created_at' => $team->created_at->toIso8601String(),
        ], 'Team created successfully');
    }

    /**
     * Get team details.
     */
    public function show(Request $request, Workspace $workspace, Team $team): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id || $team->workspace_id !== $workspace->id) {
            return $this->notFound('Team not found');
        }

        $team->loadCount('teamMembers');
        $team->load('teamMembers.user');

        $members = $team->teamMembers->map(fn ($tm) => [
            'id' => $tm->id,
            'user_id' => $tm->user_id,
            'name' => $tm->user->name,
            'email' => $tm->user->email,
            'avatar_url' => $tm->user->avatar_url ?? null,
            'joined_at' => $tm->created_at->toIso8601String(),
        ]);

        return $this->success([
            'id' => $team->id,
            'workspace_id' => $team->workspace_id,
            'name' => $team->name,
            'description' => $team->description,
            'is_default' => $team->is_default,
            'member_count' => $team->team_members_count ?? 0,
            'members' => $members,
            'created_at' => $team->created_at->toIso8601String(),
        ]);
    }

    /**
     * Update a team.
     */
    public function update(UpdateTeamRequest $request, Workspace $workspace, Team $team): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id || $team->workspace_id !== $workspace->id) {
            return $this->notFound('Team not found');
        }

        $team = $this->teamService->update($team, $user, $request->validated());
        $team->loadCount('teamMembers');

        return $this->success([
            'id' => $team->id,
            'workspace_id' => $team->workspace_id,
            'name' => $team->name,
            'description' => $team->description,
            'is_default' => $team->is_default,
            'member_count' => $team->team_members_count ?? 0,
            'created_at' => $team->created_at->toIso8601String(),
        ], 'Team updated successfully');
    }

    /**
     * Delete a team.
     */
    public function destroy(Request $request, Workspace $workspace, Team $team): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id || $team->workspace_id !== $workspace->id) {
            return $this->notFound('Team not found');
        }

        $membership = $workspace->memberships()
            ->where('user_id', $user->id)
            ->first();

        if (! $membership || ! $membership->hasPermission(Permission::WORKSPACE_TEAMS_MANAGE)) {
            return $this->forbidden('You do not have permission to delete teams');
        }

        $this->teamService->delete($team, $user);

        return $this->success(null, 'Team deleted successfully');
    }

    /**
     * Add a member to a team.
     */
    public function addMember(AddTeamMemberRequest $request, Workspace $workspace, Team $team): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id || $team->workspace_id !== $workspace->id) {
            return $this->notFound('Team not found');
        }

        $targetUser = User::find($request->validated()['user_id']);

        if (! $targetUser) {
            return $this->notFound('User not found');
        }

        $member = $this->teamService->addMember($team, $targetUser, $user);

        return $this->created([
            'id' => $member->id,
            'user_id' => $member->user_id,
            'name' => $member->user->name,
            'email' => $member->user->email,
            'joined_at' => $member->created_at->toIso8601String(),
        ], 'Member added to team');
    }

    /**
     * Remove a member from a team.
     */
    public function removeMember(Request $request, Workspace $workspace, Team $team, string $userId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id || $team->workspace_id !== $workspace->id) {
            return $this->notFound('Team not found');
        }

        $membership = $workspace->memberships()
            ->where('user_id', $user->id)
            ->first();

        if (! $membership || ! $membership->hasPermission(Permission::WORKSPACE_TEAMS_MANAGE)) {
            return $this->forbidden('You do not have permission to manage team members');
        }

        $targetUser = User::where('id', $userId)
            ->where('tenant_id', $user->tenant_id)
            ->first();

        if (! $targetUser) {
            return $this->notFound('User not found');
        }

        $this->teamService->removeMember($team, $targetUser, $user);

        return $this->success(null, 'Member removed from team');
    }
}
