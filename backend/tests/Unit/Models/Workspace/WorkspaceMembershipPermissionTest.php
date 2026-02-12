<?php

declare(strict_types=1);

use App\Enums\Workspace\Permission;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Models\Workspace\WorkspaceMembership;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->workspace = Workspace::factory()->create(['tenant_id' => $this->tenant->id]);
});

describe('hasPermission delegation', function () {
    it('delegates to role for OWNER', function () {
        $membership = WorkspaceMembership::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'role' => WorkspaceRole::OWNER,
            'joined_at' => now(),
        ]);

        expect($membership->hasPermission(Permission::WORKSPACE_DELETE))->toBeTrue()
            ->and($membership->hasPermission(Permission::BILLING_SUBSCRIPTION_MANAGE))->toBeTrue()
            ->and($membership->hasPermission(Permission::CONTENT_POSTS_APPROVE))->toBeTrue();
    });

    it('delegates to role for ADMIN', function () {
        $membership = WorkspaceMembership::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'role' => WorkspaceRole::ADMIN,
            'joined_at' => now(),
        ]);

        expect($membership->hasPermission(Permission::WORKSPACE_DELETE))->toBeFalse()
            ->and($membership->hasPermission(Permission::BILLING_SUBSCRIPTION_MANAGE))->toBeFalse()
            ->and($membership->hasPermission(Permission::CONTENT_POSTS_APPROVE))->toBeTrue()
            ->and($membership->hasPermission(Permission::WORKSPACE_MEMBERS_MANAGE))->toBeTrue();
    });

    it('delegates to role for EDITOR', function () {
        $membership = WorkspaceMembership::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'role' => WorkspaceRole::EDITOR,
            'joined_at' => now(),
        ]);

        expect($membership->hasPermission(Permission::CONTENT_POSTS_CREATE))->toBeTrue()
            ->and($membership->hasPermission(Permission::CONTENT_POSTS_APPROVE))->toBeFalse()
            ->and($membership->hasPermission(Permission::INBOX_ITEMS_REPLY))->toBeTrue()
            ->and($membership->hasPermission(Permission::WORKSPACE_MEMBERS_MANAGE))->toBeFalse();
    });

    it('delegates to role for VIEWER', function () {
        $membership = WorkspaceMembership::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'role' => WorkspaceRole::VIEWER,
            'joined_at' => now(),
        ]);

        expect($membership->hasPermission(Permission::CONTENT_POSTS_VIEW))->toBeTrue()
            ->and($membership->hasPermission(Permission::CONTENT_POSTS_CREATE))->toBeFalse()
            ->and($membership->hasPermission(Permission::INBOX_ITEMS_REPLY))->toBeFalse()
            ->and($membership->hasPermission(Permission::AI_ASSIST_USE))->toBeFalse();
    });

    it('accepts string input', function () {
        $membership = WorkspaceMembership::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'role' => WorkspaceRole::EDITOR,
            'joined_at' => now(),
        ]);

        expect($membership->hasPermission('content.posts.create'))->toBeTrue()
            ->and($membership->hasPermission('content.posts.approve'))->toBeFalse();
    });

    it('denies unknown permission strings', function () {
        $membership = WorkspaceMembership::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'role' => WorkspaceRole::OWNER,
            'joined_at' => now(),
        ]);

        expect($membership->hasPermission('nonexistent.permission'))->toBeFalse();
    });

    it('reflects role changes immediately', function () {
        $membership = WorkspaceMembership::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'role' => WorkspaceRole::VIEWER,
            'joined_at' => now(),
        ]);

        expect($membership->hasPermission(Permission::CONTENT_POSTS_CREATE))->toBeFalse();

        $membership->updateRole(WorkspaceRole::EDITOR);

        expect($membership->hasPermission(Permission::CONTENT_POSTS_CREATE))->toBeTrue();
    });
});
