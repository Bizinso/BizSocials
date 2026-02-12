<?php

declare(strict_types=1);

/**
 * WorkspaceMembership Model Unit Tests
 *
 * Tests for the WorkspaceMembership model which represents
 * the membership of a user in a workspace with their assigned role.
 *
 * @see \App\Models\Workspace\WorkspaceMembership
 */

use App\Enums\Workspace\WorkspaceRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Models\Workspace\WorkspaceMembership;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;

test('has correct table name', function (): void {
    $membership = new WorkspaceMembership();

    expect($membership->getTable())->toBe('workspace_memberships');
});

test('uses uuid primary key', function (): void {
    $membership = WorkspaceMembership::factory()->create();

    expect($membership->id)->not->toBeNull()
        ->and(strlen($membership->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $membership = new WorkspaceMembership();
    $fillable = $membership->getFillable();

    expect($fillable)->toContain('workspace_id')
        ->and($fillable)->toContain('user_id')
        ->and($fillable)->toContain('role')
        ->and($fillable)->toContain('joined_at');
});

test('role casts to enum', function (): void {
    $membership = WorkspaceMembership::factory()->owner()->create();

    expect($membership->role)->toBeInstanceOf(WorkspaceRole::class)
        ->and($membership->role)->toBe(WorkspaceRole::OWNER);
});

test('joined_at casts to datetime', function (): void {
    $membership = WorkspaceMembership::factory()->create([
        'joined_at' => now(),
    ]);

    expect($membership->joined_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('workspace relationship returns belongs to', function (): void {
    $membership = new WorkspaceMembership();

    expect($membership->workspace())->toBeInstanceOf(BelongsTo::class);
});

test('workspace relationship works correctly', function (): void {
    $workspace = Workspace::factory()->create();
    $membership = WorkspaceMembership::factory()->forWorkspace($workspace)->create();

    expect($membership->workspace)->toBeInstanceOf(Workspace::class)
        ->and($membership->workspace->id)->toBe($workspace->id);
});

test('user relationship returns belongs to', function (): void {
    $membership = new WorkspaceMembership();

    expect($membership->user())->toBeInstanceOf(BelongsTo::class);
});

test('user relationship works correctly', function (): void {
    $user = User::factory()->create();
    $membership = WorkspaceMembership::factory()->forUser($user)->create();

    expect($membership->user)->toBeInstanceOf(User::class)
        ->and($membership->user->id)->toBe($user->id);
});

test('scope forWorkspace filters correctly', function (): void {
    $workspace1 = Workspace::factory()->create();
    $workspace2 = Workspace::factory()->create();
    WorkspaceMembership::factory()->count(3)->forWorkspace($workspace1)->create();
    WorkspaceMembership::factory()->count(2)->forWorkspace($workspace2)->create();

    $memberships = WorkspaceMembership::forWorkspace($workspace1->id)->get();

    expect($memberships)->toHaveCount(3)
        ->and($memberships->every(fn ($m) => $m->workspace_id === $workspace1->id))->toBeTrue();
});

test('scope forUser filters correctly', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    WorkspaceMembership::factory()->count(2)->forUser($user1)->create();
    WorkspaceMembership::factory()->count(3)->forUser($user2)->create();

    $memberships = WorkspaceMembership::forUser($user1->id)->get();

    expect($memberships)->toHaveCount(2)
        ->and($memberships->every(fn ($m) => $m->user_id === $user1->id))->toBeTrue();
});

test('scope withRole filters by role', function (): void {
    $workspace = Workspace::factory()->create();
    WorkspaceMembership::factory()->forWorkspace($workspace)->owner()->create();
    WorkspaceMembership::factory()->count(2)->forWorkspace($workspace)->admin()->create();
    WorkspaceMembership::factory()->count(3)->forWorkspace($workspace)->editor()->create();

    $admins = WorkspaceMembership::withRole(WorkspaceRole::ADMIN)->get();

    expect($admins)->toHaveCount(2)
        ->and($admins->every(fn ($m) => $m->role === WorkspaceRole::ADMIN))->toBeTrue();
});

test('scope owners filters owners', function (): void {
    $workspace = Workspace::factory()->create();
    WorkspaceMembership::factory()->forWorkspace($workspace)->owner()->create();
    WorkspaceMembership::factory()->count(2)->forWorkspace($workspace)->admin()->create();

    $owners = WorkspaceMembership::owners()->get();

    expect($owners)->toHaveCount(1)
        ->and($owners->first()->role)->toBe(WorkspaceRole::OWNER);
});

test('scope admins filters admins', function (): void {
    $workspace = Workspace::factory()->create();
    WorkspaceMembership::factory()->forWorkspace($workspace)->owner()->create();
    WorkspaceMembership::factory()->count(2)->forWorkspace($workspace)->admin()->create();
    WorkspaceMembership::factory()->count(3)->forWorkspace($workspace)->editor()->create();

    $admins = WorkspaceMembership::admins()->get();

    expect($admins)->toHaveCount(2)
        ->and($admins->every(fn ($m) => $m->role === WorkspaceRole::ADMIN))->toBeTrue();
});

test('isOwner checks role', function (): void {
    $owner = WorkspaceMembership::factory()->owner()->create();
    $admin = WorkspaceMembership::factory()->admin()->create();
    $editor = WorkspaceMembership::factory()->editor()->create();
    $viewer = WorkspaceMembership::factory()->viewer()->create();

    expect($owner->isOwner())->toBeTrue()
        ->and($admin->isOwner())->toBeFalse()
        ->and($editor->isOwner())->toBeFalse()
        ->and($viewer->isOwner())->toBeFalse();
});

test('isAdmin checks role', function (): void {
    $owner = WorkspaceMembership::factory()->owner()->create();
    $admin = WorkspaceMembership::factory()->admin()->create();
    $editor = WorkspaceMembership::factory()->editor()->create();
    $viewer = WorkspaceMembership::factory()->viewer()->create();

    expect($owner->isAdmin())->toBeFalse()
        ->and($admin->isAdmin())->toBeTrue()
        ->and($editor->isAdmin())->toBeFalse()
        ->and($viewer->isAdmin())->toBeFalse();
});

test('isEditor checks role', function (): void {
    $owner = WorkspaceMembership::factory()->owner()->create();
    $admin = WorkspaceMembership::factory()->admin()->create();
    $editor = WorkspaceMembership::factory()->editor()->create();
    $viewer = WorkspaceMembership::factory()->viewer()->create();

    expect($owner->isEditor())->toBeFalse()
        ->and($admin->isEditor())->toBeFalse()
        ->and($editor->isEditor())->toBeTrue()
        ->and($viewer->isEditor())->toBeFalse();
});

test('isViewer checks role', function (): void {
    $owner = WorkspaceMembership::factory()->owner()->create();
    $admin = WorkspaceMembership::factory()->admin()->create();
    $editor = WorkspaceMembership::factory()->editor()->create();
    $viewer = WorkspaceMembership::factory()->viewer()->create();

    expect($owner->isViewer())->toBeFalse()
        ->and($admin->isViewer())->toBeFalse()
        ->and($editor->isViewer())->toBeFalse()
        ->and($viewer->isViewer())->toBeTrue();
});

test('canManageWorkspace delegates to role', function (): void {
    $owner = WorkspaceMembership::factory()->owner()->create();
    $admin = WorkspaceMembership::factory()->admin()->create();
    $editor = WorkspaceMembership::factory()->editor()->create();
    $viewer = WorkspaceMembership::factory()->viewer()->create();

    expect($owner->canManageWorkspace())->toBeTrue()
        ->and($admin->canManageWorkspace())->toBeTrue()
        ->and($editor->canManageWorkspace())->toBeFalse()
        ->and($viewer->canManageWorkspace())->toBeFalse();
});

test('canManageMembers delegates to role', function (): void {
    $owner = WorkspaceMembership::factory()->owner()->create();
    $admin = WorkspaceMembership::factory()->admin()->create();
    $editor = WorkspaceMembership::factory()->editor()->create();
    $viewer = WorkspaceMembership::factory()->viewer()->create();

    expect($owner->canManageMembers())->toBeTrue()
        ->and($admin->canManageMembers())->toBeTrue()
        ->and($editor->canManageMembers())->toBeFalse()
        ->and($viewer->canManageMembers())->toBeFalse();
});

test('canCreateContent delegates to role', function (): void {
    $owner = WorkspaceMembership::factory()->owner()->create();
    $admin = WorkspaceMembership::factory()->admin()->create();
    $editor = WorkspaceMembership::factory()->editor()->create();
    $viewer = WorkspaceMembership::factory()->viewer()->create();

    expect($owner->canCreateContent())->toBeTrue()
        ->and($admin->canCreateContent())->toBeTrue()
        ->and($editor->canCreateContent())->toBeTrue()
        ->and($viewer->canCreateContent())->toBeFalse();
});

test('canApproveContent delegates to role', function (): void {
    $owner = WorkspaceMembership::factory()->owner()->create();
    $admin = WorkspaceMembership::factory()->admin()->create();
    $editor = WorkspaceMembership::factory()->editor()->create();
    $viewer = WorkspaceMembership::factory()->viewer()->create();

    expect($owner->canApproveContent())->toBeTrue()
        ->and($admin->canApproveContent())->toBeTrue()
        ->and($editor->canApproveContent())->toBeFalse()
        ->and($viewer->canApproveContent())->toBeFalse();
});

test('canPublishDirectly delegates to role', function (): void {
    $owner = WorkspaceMembership::factory()->owner()->create();
    $admin = WorkspaceMembership::factory()->admin()->create();
    $editor = WorkspaceMembership::factory()->editor()->create();
    $viewer = WorkspaceMembership::factory()->viewer()->create();

    expect($owner->canPublishDirectly())->toBeTrue()
        ->and($admin->canPublishDirectly())->toBeTrue()
        ->and($editor->canPublishDirectly())->toBeFalse()
        ->and($viewer->canPublishDirectly())->toBeFalse();
});

test('updateRole changes role', function (): void {
    $membership = WorkspaceMembership::factory()->editor()->create();

    expect($membership->role)->toBe(WorkspaceRole::EDITOR);

    $membership->updateRole(WorkspaceRole::ADMIN);

    expect($membership->role)->toBe(WorkspaceRole::ADMIN);
});

test('factory creates valid model', function (): void {
    $membership = WorkspaceMembership::factory()->create();

    expect($membership)->toBeInstanceOf(WorkspaceMembership::class)
        ->and($membership->id)->not->toBeNull()
        ->and($membership->workspace_id)->not->toBeNull()
        ->and($membership->user_id)->not->toBeNull()
        ->and($membership->role)->toBeInstanceOf(WorkspaceRole::class)
        ->and($membership->joined_at)->not->toBeNull();
});

test('unique constraint on workspace_id and user_id', function (): void {
    $tenant = Tenant::factory()->create();
    $workspace = Workspace::factory()->forTenant($tenant)->create();
    $user = User::factory()->forTenant($tenant)->create();

    WorkspaceMembership::factory()->forWorkspace($workspace)->forUser($user)->create();

    expect(fn () => WorkspaceMembership::factory()->forWorkspace($workspace)->forUser($user)->create())
        ->toThrow(QueryException::class);
});

test('same user can be member of different workspaces', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();
    $workspace1 = Workspace::factory()->forTenant($tenant)->create();
    $workspace2 = Workspace::factory()->forTenant($tenant)->create();

    $membership1 = WorkspaceMembership::factory()->forWorkspace($workspace1)->forUser($user)->create();
    $membership2 = WorkspaceMembership::factory()->forWorkspace($workspace2)->forUser($user)->create();

    expect($membership1->id)->not->toBe($membership2->id)
        ->and($membership1->user_id)->toBe($membership2->user_id);
});
