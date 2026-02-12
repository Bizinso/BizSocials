<?php

declare(strict_types=1);

use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Team;
use App\Models\Workspace\TeamMember;
use App\Models\Workspace\Workspace;
use App\Services\Workspace\TeamService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = app(TeamService::class);
    $this->tenant = Tenant::factory()->active()->create();
    $this->owner = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->workspace->addMember($this->owner, WorkspaceRole::OWNER);
});

describe('list', function () {
    it('returns paginated teams', function () {
        Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Team A']);
        Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Team B']);

        $result = $this->service->list($this->workspace);

        expect($result->total())->toBe(2);
    });

    it('filters teams by search', function () {
        Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Marketing Team']);
        Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Sales Team']);

        $result = $this->service->list($this->workspace, ['search' => 'Marketing']);

        expect($result->total())->toBe(1);
        expect($result->items()[0]->name)->toBe('Marketing Team');
    });

    it('orders default teams first', function () {
        Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Zebra', 'is_default' => false]);
        Team::create(['workspace_id' => $this->workspace->id, 'name' => 'Alpha', 'is_default' => true]);

        $result = $this->service->list($this->workspace);

        expect($result->items()[0]->name)->toBe('Alpha');
        expect($result->items()[0]->is_default)->toBeTrue();
    });

    it('does not include teams from other workspaces', function () {
        $otherWorkspace = Workspace::factory()->create(['tenant_id' => $this->tenant->id]);
        Team::create(['workspace_id' => $this->workspace->id, 'name' => 'My Team']);
        Team::create(['workspace_id' => $otherWorkspace->id, 'name' => 'Other Team']);

        $result = $this->service->list($this->workspace);

        expect($result->total())->toBe(1);
    });
});

describe('create', function () {
    it('creates a team with given data', function () {
        $team = $this->service->create($this->workspace, $this->owner, [
            'name' => 'Content Team',
            'description' => 'Handles content creation',
            'is_default' => false,
        ]);

        expect($team->name)->toBe('Content Team');
        expect($team->description)->toBe('Handles content creation');
        expect($team->is_default)->toBeFalse();
        expect($team->workspace_id)->toBe($this->workspace->id);
    });

    it('creates a default team', function () {
        $team = $this->service->create($this->workspace, $this->owner, [
            'name' => 'Default Team',
            'is_default' => true,
        ]);

        expect($team->is_default)->toBeTrue();
    });

    it('unsets previous default when creating new default', function () {
        $first = $this->service->create($this->workspace, $this->owner, [
            'name' => 'First Default',
            'is_default' => true,
        ]);

        $second = $this->service->create($this->workspace, $this->owner, [
            'name' => 'Second Default',
            'is_default' => true,
        ]);

        expect($first->fresh()->is_default)->toBeFalse();
        expect($second->is_default)->toBeTrue();
    });

    it('writes audit log on create', function () {
        $team = $this->service->create($this->workspace, $this->owner, [
            'name' => 'Audit Team',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $team->id,
            'description' => 'team.created',
        ]);
    });
});

describe('update', function () {
    it('updates team name', function () {
        $team = Team::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Old Name',
        ]);

        $updated = $this->service->update($team, $this->owner, ['name' => 'New Name']);

        expect($updated->name)->toBe('New Name');
    });

    it('updates team description', function () {
        $team = Team::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Team',
            'description' => 'Old description',
        ]);

        $updated = $this->service->update($team, $this->owner, ['description' => 'New description']);

        expect($updated->description)->toBe('New description');
    });

    it('sets is_default and unsets previous default', function () {
        $first = Team::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'First',
            'is_default' => true,
        ]);
        $second = Team::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Second',
            'is_default' => false,
        ]);

        $this->service->update($second, $this->owner, ['is_default' => true]);

        expect($first->fresh()->is_default)->toBeFalse();
        expect($second->fresh()->is_default)->toBeTrue();
    });

    it('writes audit log on update', function () {
        $team = Team::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Team',
        ]);

        $this->service->update($team, $this->owner, ['name' => 'Updated']);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $team->id,
            'description' => 'team.updated',
        ]);
    });
});

describe('delete', function () {
    it('deletes the team', function () {
        $team = Team::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'To Delete',
        ]);
        $teamId = $team->id;

        $this->service->delete($team, $this->owner);

        expect(Team::find($teamId))->toBeNull();
    });

    it('writes audit log on delete', function () {
        $team = Team::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Audited Delete',
        ]);
        $teamId = $team->id;

        $this->service->delete($team, $this->owner);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $teamId,
            'description' => 'team.deleted',
        ]);
    });

    it('cascades member deletion', function () {
        $team = Team::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Cascade Team',
        ]);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $this->owner->id]);

        $this->service->delete($team, $this->owner);

        expect(TeamMember::where('team_id', $team->id)->count())->toBe(0);
    });
});

describe('addMember', function () {
    it('adds a workspace member to the team', function () {
        $team = Team::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Team',
        ]);

        $member = $this->service->addMember($team, $this->owner, $this->owner);

        expect($member->user_id)->toBe($this->owner->id);
        expect($team->hasMember($this->owner->id))->toBeTrue();
    });

    it('rejects non-workspace member', function () {
        $outsider = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $team = Team::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Team',
        ]);

        expect(fn () => $this->service->addMember($team, $outsider, $this->owner))
            ->toThrow(ValidationException::class);
    });

    it('rejects duplicate member', function () {
        $team = Team::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Team',
        ]);
        $this->service->addMember($team, $this->owner, $this->owner);

        expect(fn () => $this->service->addMember($team, $this->owner, $this->owner))
            ->toThrow(ValidationException::class);
    });

    it('writes audit log on add member', function () {
        $team = Team::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Team',
        ]);

        $this->service->addMember($team, $this->owner, $this->owner);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $team->id,
            'description' => 'team.member_added',
        ]);
    });
});

describe('removeMember', function () {
    it('removes a member from the team', function () {
        $team = Team::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Team',
        ]);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $this->owner->id]);

        $this->service->removeMember($team, $this->owner, $this->owner);

        expect($team->hasMember($this->owner->id))->toBeFalse();
    });

    it('rejects removing non-member', function () {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->workspace->addMember($user, WorkspaceRole::EDITOR);
        $team = Team::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Team',
        ]);

        expect(fn () => $this->service->removeMember($team, $user, $this->owner))
            ->toThrow(ValidationException::class);
    });

    it('writes audit log on remove member', function () {
        $team = Team::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Team',
        ]);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $this->owner->id]);

        $this->service->removeMember($team, $this->owner, $this->owner);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $team->id,
            'description' => 'team.member_removed',
        ]);
    });
});
