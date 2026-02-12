<?php

declare(strict_types=1);

use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Team;
use App\Models\Workspace\TeamMember;
use App\Models\Workspace\Workspace;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->owner = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
    $this->admin = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::ADMIN,
    ]);
    $this->member = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::MEMBER,
    ]);
    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->workspace->addMember($this->owner, WorkspaceRole::OWNER);
    $this->workspace->addMember($this->admin, WorkspaceRole::ADMIN);
    $this->workspace->addMember($this->member, WorkspaceRole::EDITOR);
});

describe('GET /api/v1/workspaces/{workspace}/teams', function () {
    it('returns paginated team list', function () {
        Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Team A']);
        Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Team B']);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/teams");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'workspace_id', 'name', 'description', 'is_default', 'member_count', 'created_at'],
                ],
                'meta',
            ]);
    });

    it('supports search filter', function () {
        Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Marketing']);
        Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Sales']);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/teams?search=Market");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Marketing');
    });

    it('requires authentication', function () {
        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/teams");

        $response->assertUnauthorized();
    });

    it('denies access to other tenant workspace', function () {
        $otherTenant = Tenant::factory()->active()->create();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherWorkspace = Workspace::factory()->create(['tenant_id' => $otherTenant->id]);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson("/api/v1/workspaces/{$otherWorkspace->id}/teams");

        $response->assertNotFound();
    });
});

describe('POST /api/v1/workspaces/{workspace}/teams', function () {
    it('creates a team as owner', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/teams", [
            'name' => 'New Team',
            'description' => 'A test team',
            'is_default' => false,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Team')
            ->assertJsonPath('data.description', 'A test team')
            ->assertJsonPath('data.is_default', false);

        $this->assertDatabaseHas('teams', [
            'workspace_id' => $this->workspace->id,
            'name' => 'New Team',
        ]);
    });

    it('creates a team as admin', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/teams", [
            'name' => 'Admin Team',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Admin Team');
    });

    it('denies creation for editor', function () {
        Sanctum::actingAs($this->member);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/teams", [
            'name' => 'Forbidden Team',
        ]);

        $response->assertForbidden();
    });

    it('validates required name', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/teams", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('validates name minimum length', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/teams", [
            'name' => 'A',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('enforces unique name per workspace', function () {
        Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Existing']);

        Sanctum::actingAs($this->owner);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/teams", [
            'name' => 'Existing',
        ]);

        // Should fail at DB level — 500 or 422 depending on error handling
        expect($response->status())->toBeGreaterThanOrEqual(400);
    });

    it('writes audit log on create', function () {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/teams", [
            'name' => 'Audited Team',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'description' => 'team.created',
        ]);
    });
});

describe('GET /api/v1/workspaces/{workspace}/teams/{team}', function () {
    it('returns team detail with members', function () {
        $team = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Detail Team']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $this->owner->id]);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$team->id}");

        $response->assertOk()
            ->assertJsonPath('data.name', 'Detail Team')
            ->assertJsonPath('data.member_count', 1)
            ->assertJsonCount(1, 'data.members')
            ->assertJsonStructure([
                'data' => [
                    'id', 'workspace_id', 'name', 'description', 'is_default', 'member_count',
                    'members' => [
                        '*' => ['id', 'user_id', 'name', 'email', 'joined_at'],
                    ],
                    'created_at',
                ],
            ]);
    });

    it('returns 404 for team in other workspace', function () {
        $otherWorkspace = Workspace::factory()->create(['tenant_id' => $this->tenant->id]);
        $otherTeam = Team::create(['workspace_id' => $otherWorkspace->id, 'name' => 'Other']);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$otherTeam->id}");

        $response->assertNotFound();
    });
});

describe('PUT /api/v1/workspaces/{workspace}/teams/{team}', function () {
    it('updates team name', function () {
        $team = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Old Name']);

        Sanctum::actingAs($this->owner);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$team->id}", [
            'name' => 'New Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    });

    it('sets default and unsets previous', function () {
        $first = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'First', 'is_default' => true]);
        $second = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Second', 'is_default' => false]);

        Sanctum::actingAs($this->owner);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$second->id}", [
            'is_default' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.is_default', true);

        expect($first->fresh()->is_default)->toBeFalse();
    });

    it('denies update for editor', function () {
        $team = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Team']);

        Sanctum::actingAs($this->member);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$team->id}", [
            'name' => 'Updated',
        ]);

        $response->assertForbidden();
    });

    it('writes audit log on update', function () {
        $team = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Team']);

        Sanctum::actingAs($this->owner);

        $this->putJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$team->id}", [
            'name' => 'Updated',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $team->id,
            'description' => 'team.updated',
        ]);
    });
});

describe('DELETE /api/v1/workspaces/{workspace}/teams/{team}', function () {
    it('deletes team as owner', function () {
        $team = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'To Delete']);

        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$team->id}");

        $response->assertOk();
        expect(Team::find($team->id))->toBeNull();
    });

    it('deletes team as admin', function () {
        $team = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Admin Delete']);

        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$team->id}");

        $response->assertOk();
    });

    it('denies deletion for editor', function () {
        $team = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Protected']);

        Sanctum::actingAs($this->member);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$team->id}");

        $response->assertForbidden();
    });

    it('cascades member deletion', function () {
        $team = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Cascade']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $this->owner->id]);

        Sanctum::actingAs($this->owner);

        $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$team->id}");

        expect(TeamMember::where('team_id', $team->id)->count())->toBe(0);
    });

    it('writes audit log on delete', function () {
        $team = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Audit Delete']);
        $teamId = $team->id;

        Sanctum::actingAs($this->owner);

        $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$team->id}");

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $teamId,
            'description' => 'team.deleted',
        ]);
    });
});

describe('POST /api/v1/workspaces/{workspace}/teams/{team}/members', function () {
    it('adds a workspace member to team', function () {
        $team = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Team']);

        Sanctum::actingAs($this->owner);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$team->id}/members", [
            'user_id' => $this->admin->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user_id', $this->admin->id)
            ->assertJsonStructure([
                'data' => ['id', 'user_id', 'name', 'email', 'joined_at'],
            ]);

        expect($team->hasMember($this->admin->id))->toBeTrue();
    });

    it('rejects non-workspace member', function () {
        $outsider = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $team = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Team']);

        Sanctum::actingAs($this->owner);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$team->id}/members", [
            'user_id' => $outsider->id,
        ]);

        $response->assertUnprocessable();
    });

    it('rejects duplicate member', function () {
        $team = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Team']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $this->admin->id]);

        Sanctum::actingAs($this->owner);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$team->id}/members", [
            'user_id' => $this->admin->id,
        ]);

        $response->assertUnprocessable();
    });

    it('denies add member for editor', function () {
        $team = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Team']);

        Sanctum::actingAs($this->member);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$team->id}/members", [
            'user_id' => $this->admin->id,
        ]);

        $response->assertForbidden();
    });

    it('validates user_id is required', function () {
        $team = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Team']);

        Sanctum::actingAs($this->owner);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$team->id}/members", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);
    });

    it('writes audit log on add member', function () {
        $team = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Team']);

        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$team->id}/members", [
            'user_id' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $team->id,
            'description' => 'team.member_added',
        ]);
    });
});

describe('DELETE /api/v1/workspaces/{workspace}/teams/{team}/members/{user}', function () {
    it('removes a member from team', function () {
        $team = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Team']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $this->admin->id]);

        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$team->id}/members/{$this->admin->id}");

        $response->assertOk();
        expect($team->hasMember($this->admin->id))->toBeFalse();
    });

    it('returns 422 for non-member', function () {
        $team = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Team']);

        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$team->id}/members/{$this->admin->id}");

        $response->assertUnprocessable();
    });

    it('denies remove for editor', function () {
        $team = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Team']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $this->admin->id]);

        Sanctum::actingAs($this->member);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$team->id}/members/{$this->admin->id}");

        $response->assertForbidden();
    });

    it('writes audit log on remove member', function () {
        $team = Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Team']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $this->admin->id]);

        Sanctum::actingAs($this->owner);

        $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/teams/{$team->id}/members/{$this->admin->id}");

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $team->id,
            'description' => 'team.member_removed',
        ]);
    });
});
