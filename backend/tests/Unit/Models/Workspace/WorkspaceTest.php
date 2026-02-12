<?php

declare(strict_types=1);

/**
 * Workspace Model Unit Tests
 *
 * Tests for the Workspace model which represents isolated
 * organizational containers within a tenant.
 *
 * @see \App\Models\Workspace\Workspace
 */

use App\Enums\Workspace\WorkspaceRole;
use App\Enums\Workspace\WorkspaceStatus;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Models\Workspace\WorkspaceMembership;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;

test('has correct table name', function (): void {
    $workspace = new Workspace();

    expect($workspace->getTable())->toBe('workspaces');
});

test('uses uuid primary key', function (): void {
    $workspace = Workspace::factory()->create();

    expect($workspace->id)->not->toBeNull()
        ->and(strlen($workspace->id))->toBe(36);
});

test('uses soft deletes', function (): void {
    $workspace = new Workspace();

    expect(in_array(SoftDeletes::class, class_uses_recursive($workspace), true))->toBeTrue();
});

test('has correct fillable attributes', function (): void {
    $workspace = new Workspace();
    $fillable = $workspace->getFillable();

    expect($fillable)->toContain('tenant_id')
        ->and($fillable)->toContain('name')
        ->and($fillable)->toContain('slug')
        ->and($fillable)->toContain('description')
        ->and($fillable)->toContain('status')
        ->and($fillable)->toContain('settings');
});

test('status casts to enum', function (): void {
    $workspace = Workspace::factory()->active()->create();

    expect($workspace->status)->toBeInstanceOf(WorkspaceStatus::class)
        ->and($workspace->status)->toBe(WorkspaceStatus::ACTIVE);
});

test('settings casts to array', function (): void {
    $settings = [
        'timezone' => 'America/New_York',
        'approval_workflow' => ['enabled' => true],
    ];

    $workspace = Workspace::factory()->withSettings($settings)->create();

    expect($workspace->settings)->toBeArray()
        ->and($workspace->settings['timezone'])->toBe('America/New_York');
});

test('tenant relationship returns belongs to', function (): void {
    $workspace = new Workspace();

    expect($workspace->tenant())->toBeInstanceOf(BelongsTo::class);
});

test('tenant relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $workspace = Workspace::factory()->forTenant($tenant)->create();

    expect($workspace->tenant)->toBeInstanceOf(Tenant::class)
        ->and($workspace->tenant->id)->toBe($tenant->id);
});

test('memberships relationship returns has many', function (): void {
    $workspace = new Workspace();

    expect($workspace->memberships())->toBeInstanceOf(HasMany::class);
});

test('memberships relationship works correctly', function (): void {
    $workspace = Workspace::factory()->create();
    WorkspaceMembership::factory()->count(3)->forWorkspace($workspace)->create();

    expect($workspace->memberships)->toHaveCount(3)
        ->and($workspace->memberships->first())->toBeInstanceOf(WorkspaceMembership::class);
});

test('members relationship returns belongs to many', function (): void {
    $workspace = new Workspace();

    expect($workspace->members())->toBeInstanceOf(BelongsToMany::class);
});

test('members relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $workspace = Workspace::factory()->forTenant($tenant)->create();
    $users = User::factory()->count(3)->forTenant($tenant)->create();

    foreach ($users as $user) {
        WorkspaceMembership::factory()->forWorkspace($workspace)->forUser($user)->create();
    }

    expect($workspace->members)->toHaveCount(3)
        ->and($workspace->members->first())->toBeInstanceOf(User::class);
});

test('scope active filters correctly', function (): void {
    $tenant = Tenant::factory()->create();
    Workspace::factory()->count(3)->forTenant($tenant)->active()->create();
    Workspace::factory()->count(2)->forTenant($tenant)->suspended()->create();

    $activeWorkspaces = Workspace::active()->get();

    expect($activeWorkspaces)->toHaveCount(3)
        ->and($activeWorkspaces->every(fn ($w) => $w->status === WorkspaceStatus::ACTIVE))->toBeTrue();
});

test('scope forTenant filters by tenant_id', function (): void {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    Workspace::factory()->count(2)->forTenant($tenant1)->create();
    Workspace::factory()->count(3)->forTenant($tenant2)->create();

    $tenant1Workspaces = Workspace::forTenant($tenant1->id)->get();

    expect($tenant1Workspaces)->toHaveCount(2)
        ->and($tenant1Workspaces->every(fn ($w) => $w->tenant_id === $tenant1->id))->toBeTrue();
});

test('scope forUser filters by membership', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();
    $workspace1 = Workspace::factory()->forTenant($tenant)->create();
    $workspace2 = Workspace::factory()->forTenant($tenant)->create();
    Workspace::factory()->forTenant($tenant)->create(); // Not a member

    WorkspaceMembership::factory()->forWorkspace($workspace1)->forUser($user)->create();
    WorkspaceMembership::factory()->forWorkspace($workspace2)->forUser($user)->create();

    $userWorkspaces = Workspace::forUser($user->id)->get();

    expect($userWorkspaces)->toHaveCount(2);
});

test('slug is auto-generated from name', function (): void {
    $tenant = Tenant::factory()->create();
    $workspace = Workspace::factory()->forTenant($tenant)->create([
        'name' => 'Marketing Team',
        'slug' => null,
    ]);

    expect($workspace->slug)->toBe('marketing-team');
});

test('generateSlug creates URL-safe slugs', function (): void {
    $tenant = Tenant::factory()->create();
    $slug = Workspace::generateSlug('Marketing Team', $tenant->id);

    expect($slug)->toBe('marketing-team');
});

test('generateSlug handles duplicate names', function (): void {
    $tenant = Tenant::factory()->create();

    // Create first workspace
    Workspace::factory()->forTenant($tenant)->create([
        'name' => 'Marketing Team',
        'slug' => 'marketing-team',
    ]);

    // Generate slug for duplicate name
    $slug = Workspace::generateSlug('Marketing Team', $tenant->id);

    expect($slug)->toBe('marketing-team-2');
});

test('isActive returns true only for active status', function (): void {
    $active = Workspace::factory()->active()->create();
    $suspended = Workspace::factory()->suspended()->create();

    expect($active->isActive())->toBeTrue()
        ->and($suspended->isActive())->toBeFalse();
});

test('hasAccess returns true only for active status', function (): void {
    $active = Workspace::factory()->active()->create();
    $suspended = Workspace::factory()->suspended()->create();

    expect($active->hasAccess())->toBeTrue()
        ->and($suspended->hasAccess())->toBeFalse();
});

test('getOwner returns the owner user', function (): void {
    $tenant = Tenant::factory()->create();
    $workspace = Workspace::factory()->forTenant($tenant)->create();
    $owner = User::factory()->forTenant($tenant)->create();

    WorkspaceMembership::factory()
        ->forWorkspace($workspace)
        ->forUser($owner)
        ->owner()
        ->create();

    expect($workspace->getOwner())->toBeInstanceOf(User::class)
        ->and($workspace->getOwner()->id)->toBe($owner->id);
});

test('getOwner returns null when no owner exists', function (): void {
    $workspace = Workspace::factory()->create();

    expect($workspace->getOwner())->toBeNull();
});

test('getMemberCount returns correct count', function (): void {
    $workspace = Workspace::factory()->create();
    WorkspaceMembership::factory()->count(5)->forWorkspace($workspace)->create();

    expect($workspace->getMemberCount())->toBe(5);
});

test('hasMember checks membership', function (): void {
    $tenant = Tenant::factory()->create();
    $workspace = Workspace::factory()->forTenant($tenant)->create();
    $member = User::factory()->forTenant($tenant)->create();
    $nonMember = User::factory()->forTenant($tenant)->create();

    WorkspaceMembership::factory()->forWorkspace($workspace)->forUser($member)->create();

    expect($workspace->hasMember($member->id))->toBeTrue()
        ->and($workspace->hasMember($nonMember->id))->toBeFalse();
});

test('getMemberRole returns correct role', function (): void {
    $tenant = Tenant::factory()->create();
    $workspace = Workspace::factory()->forTenant($tenant)->create();
    $user = User::factory()->forTenant($tenant)->create();

    WorkspaceMembership::factory()
        ->forWorkspace($workspace)
        ->forUser($user)
        ->admin()
        ->create();

    expect($workspace->getMemberRole($user->id))->toBe(WorkspaceRole::ADMIN);
});

test('getMemberRole returns null for non-member', function (): void {
    $tenant = Tenant::factory()->create();
    $workspace = Workspace::factory()->forTenant($tenant)->create();
    $user = User::factory()->forTenant($tenant)->create();

    expect($workspace->getMemberRole($user->id))->toBeNull();
});

test('addMember creates membership', function (): void {
    $tenant = Tenant::factory()->create();
    $workspace = Workspace::factory()->forTenant($tenant)->create();
    $user = User::factory()->forTenant($tenant)->create();

    $membership = $workspace->addMember($user, WorkspaceRole::EDITOR);

    expect($membership)->toBeInstanceOf(WorkspaceMembership::class)
        ->and($membership->user_id)->toBe($user->id)
        ->and($membership->workspace_id)->toBe($workspace->id)
        ->and($membership->role)->toBe(WorkspaceRole::EDITOR)
        ->and($membership->joined_at)->not->toBeNull();
});

test('removeMember deletes membership', function (): void {
    $tenant = Tenant::factory()->create();
    $workspace = Workspace::factory()->forTenant($tenant)->create();
    $user = User::factory()->forTenant($tenant)->create();

    WorkspaceMembership::factory()->forWorkspace($workspace)->forUser($user)->create();

    expect($workspace->hasMember($user->id))->toBeTrue();

    $result = $workspace->removeMember($user->id);

    expect($result)->toBeTrue()
        ->and($workspace->hasMember($user->id))->toBeFalse();
});

test('removeMember returns false for non-member', function (): void {
    $tenant = Tenant::factory()->create();
    $workspace = Workspace::factory()->forTenant($tenant)->create();
    $user = User::factory()->forTenant($tenant)->create();

    $result = $workspace->removeMember($user->id);

    expect($result)->toBeFalse();
});

test('getSetting retrieves from settings JSON', function (): void {
    $workspace = Workspace::factory()->create([
        'settings' => [
            'timezone' => 'Asia/Kolkata',
            'approval_workflow' => [
                'enabled' => true,
                'required_for_roles' => ['editor'],
            ],
        ],
    ]);

    expect($workspace->getSetting('timezone'))->toBe('Asia/Kolkata')
        ->and($workspace->getSetting('approval_workflow.enabled'))->toBeTrue()
        ->and($workspace->getSetting('nonexistent', 'default'))->toBe('default');
});

test('setSetting updates settings JSON', function (): void {
    $workspace = Workspace::factory()->create([
        'settings' => ['timezone' => 'UTC'],
    ]);

    $workspace->setSetting('timezone', 'America/New_York');
    $workspace->setSetting('new_key', 'new_value');

    $workspace->refresh();

    expect($workspace->settings['timezone'])->toBe('America/New_York')
        ->and($workspace->settings['new_key'])->toBe('new_value');
});

test('suspend changes status to suspended', function (): void {
    $workspace = Workspace::factory()->active()->create();

    $workspace->suspend();

    expect($workspace->status)->toBe(WorkspaceStatus::SUSPENDED);
});

test('activate changes status to active', function (): void {
    $workspace = Workspace::factory()->suspended()->create();

    $workspace->activate();

    expect($workspace->status)->toBe(WorkspaceStatus::ACTIVE);
});

test('isApprovalRequired checks workflow settings', function (): void {
    $workspaceWithApproval = Workspace::factory()->withApprovalWorkflow()->create();
    $workspaceWithoutApproval = Workspace::factory()->withoutApprovalWorkflow()->create();

    expect($workspaceWithApproval->isApprovalRequired(WorkspaceRole::EDITOR))->toBeTrue()
        ->and($workspaceWithApproval->isApprovalRequired(WorkspaceRole::OWNER))->toBeFalse()
        ->and($workspaceWithoutApproval->isApprovalRequired(WorkspaceRole::EDITOR))->toBeFalse();
});

test('factory creates valid model', function (): void {
    $workspace = Workspace::factory()->create();

    expect($workspace)->toBeInstanceOf(Workspace::class)
        ->and($workspace->id)->not->toBeNull()
        ->and($workspace->name)->toBeString()
        ->and($workspace->slug)->toBeString()
        ->and($workspace->status)->toBeInstanceOf(WorkspaceStatus::class)
        ->and($workspace->settings)->toBeArray();
});

test('slug unique within tenant constraint', function (): void {
    $tenant = Tenant::factory()->create();
    Workspace::factory()->forTenant($tenant)->create(['slug' => 'test-workspace']);

    expect(fn () => Workspace::factory()->forTenant($tenant)->create(['slug' => 'test-workspace']))
        ->toThrow(QueryException::class);
});

test('same slug allowed in different tenants', function (): void {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    $workspace1 = Workspace::factory()->forTenant($tenant1)->create(['slug' => 'same-slug']);
    $workspace2 = Workspace::factory()->forTenant($tenant2)->create(['slug' => 'same-slug']);

    expect($workspace1->id)->not->toBe($workspace2->id)
        ->and($workspace1->slug)->toBe($workspace2->slug);
});

test('soft delete works correctly', function (): void {
    $workspace = Workspace::factory()->create();
    $workspaceId = $workspace->id;

    $workspace->delete();

    expect(Workspace::find($workspaceId))->toBeNull()
        ->and(Workspace::withTrashed()->find($workspaceId))->not->toBeNull()
        ->and(Workspace::withTrashed()->find($workspaceId)->deleted_at)->not->toBeNull();
});

test('default settings are initialized on creation', function (): void {
    $tenant = Tenant::factory()->create();
    $workspace = Workspace::factory()->forTenant($tenant)->create([
        'settings' => null,
    ]);

    expect($workspace->settings)->toBeArray()
        ->and($workspace->getSetting('timezone'))->toBe('Asia/Kolkata')
        ->and($workspace->getSetting('approval_workflow.enabled'))->toBeTrue();
});
