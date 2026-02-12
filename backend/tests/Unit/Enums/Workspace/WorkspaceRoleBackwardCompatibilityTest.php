<?php

declare(strict_types=1);

/**
 * Backward Compatibility Regression Tests
 *
 * Proves that the 8 @deprecated boolean methods on WorkspaceRole
 * return identical results to their hasPermission() equivalents
 * for every role.
 *
 * @see \App\Enums\Workspace\WorkspaceRole
 * @see \App\Enums\Workspace\Permission
 */

use App\Enums\Workspace\Permission;
use App\Enums\Workspace\WorkspaceRole;

dataset('all_roles', [
    'OWNER' => WorkspaceRole::OWNER,
    'ADMIN' => WorkspaceRole::ADMIN,
    'EDITOR' => WorkspaceRole::EDITOR,
    'VIEWER' => WorkspaceRole::VIEWER,
]);

describe('deprecated boolean methods match hasPermission()', function () {
    it('canManageWorkspace() === hasPermission(WORKSPACE_SETTINGS_UPDATE) for {role}', function (WorkspaceRole $role) {
        expect($role->canManageWorkspace())
            ->toBe($role->hasPermission(Permission::WORKSPACE_SETTINGS_UPDATE));
    })->with('all_roles');

    it('canManageBilling() === hasPermission(BILLING_SUBSCRIPTION_MANAGE) for {role}', function (WorkspaceRole $role) {
        expect($role->canManageBilling())
            ->toBe($role->hasPermission(Permission::BILLING_SUBSCRIPTION_MANAGE));
    })->with('all_roles');

    it('canManageMembers() === hasPermission(WORKSPACE_MEMBERS_MANAGE) for {role}', function (WorkspaceRole $role) {
        expect($role->canManageMembers())
            ->toBe($role->hasPermission(Permission::WORKSPACE_MEMBERS_MANAGE));
    })->with('all_roles');

    it('canManageSocialAccounts() === hasPermission(WORKSPACE_SOCIAL_ACCOUNTS_MANAGE) for {role}', function (WorkspaceRole $role) {
        expect($role->canManageSocialAccounts())
            ->toBe($role->hasPermission(Permission::WORKSPACE_SOCIAL_ACCOUNTS_MANAGE));
    })->with('all_roles');

    it('canCreateContent() === hasPermission(CONTENT_POSTS_CREATE) for {role}', function (WorkspaceRole $role) {
        expect($role->canCreateContent())
            ->toBe($role->hasPermission(Permission::CONTENT_POSTS_CREATE));
    })->with('all_roles');

    it('canApproveContent() === hasPermission(CONTENT_POSTS_APPROVE) for {role}', function (WorkspaceRole $role) {
        expect($role->canApproveContent())
            ->toBe($role->hasPermission(Permission::CONTENT_POSTS_APPROVE));
    })->with('all_roles');

    it('canPublishDirectly() === hasPermission(CONTENT_POSTS_PUBLISH) for {role}', function (WorkspaceRole $role) {
        expect($role->canPublishDirectly())
            ->toBe($role->hasPermission(Permission::CONTENT_POSTS_PUBLISH));
    })->with('all_roles');

    it('canDeleteWorkspace() === hasPermission(WORKSPACE_DELETE) for {role}', function (WorkspaceRole $role) {
        expect($role->canDeleteWorkspace())
            ->toBe($role->hasPermission(Permission::WORKSPACE_DELETE));
    })->with('all_roles');
});

describe('deprecated methods preserve exact role expectations', function () {
    it('canManageWorkspace: OWNER=true, ADMIN=true, EDITOR=false, VIEWER=false', function () {
        expect(WorkspaceRole::OWNER->canManageWorkspace())->toBeTrue()
            ->and(WorkspaceRole::ADMIN->canManageWorkspace())->toBeTrue()
            ->and(WorkspaceRole::EDITOR->canManageWorkspace())->toBeFalse()
            ->and(WorkspaceRole::VIEWER->canManageWorkspace())->toBeFalse();
    });

    it('canManageBilling: OWNER=true, ADMIN=false, EDITOR=false, VIEWER=false', function () {
        expect(WorkspaceRole::OWNER->canManageBilling())->toBeTrue()
            ->and(WorkspaceRole::ADMIN->canManageBilling())->toBeFalse()
            ->and(WorkspaceRole::EDITOR->canManageBilling())->toBeFalse()
            ->and(WorkspaceRole::VIEWER->canManageBilling())->toBeFalse();
    });

    it('canManageMembers: OWNER=true, ADMIN=true, EDITOR=false, VIEWER=false', function () {
        expect(WorkspaceRole::OWNER->canManageMembers())->toBeTrue()
            ->and(WorkspaceRole::ADMIN->canManageMembers())->toBeTrue()
            ->and(WorkspaceRole::EDITOR->canManageMembers())->toBeFalse()
            ->and(WorkspaceRole::VIEWER->canManageMembers())->toBeFalse();
    });

    it('canManageSocialAccounts: OWNER=true, ADMIN=true, EDITOR=false, VIEWER=false', function () {
        expect(WorkspaceRole::OWNER->canManageSocialAccounts())->toBeTrue()
            ->and(WorkspaceRole::ADMIN->canManageSocialAccounts())->toBeTrue()
            ->and(WorkspaceRole::EDITOR->canManageSocialAccounts())->toBeFalse()
            ->and(WorkspaceRole::VIEWER->canManageSocialAccounts())->toBeFalse();
    });

    it('canCreateContent: OWNER=true, ADMIN=true, EDITOR=true, VIEWER=false', function () {
        expect(WorkspaceRole::OWNER->canCreateContent())->toBeTrue()
            ->and(WorkspaceRole::ADMIN->canCreateContent())->toBeTrue()
            ->and(WorkspaceRole::EDITOR->canCreateContent())->toBeTrue()
            ->and(WorkspaceRole::VIEWER->canCreateContent())->toBeFalse();
    });

    it('canApproveContent: OWNER=true, ADMIN=true, EDITOR=false, VIEWER=false', function () {
        expect(WorkspaceRole::OWNER->canApproveContent())->toBeTrue()
            ->and(WorkspaceRole::ADMIN->canApproveContent())->toBeTrue()
            ->and(WorkspaceRole::EDITOR->canApproveContent())->toBeFalse()
            ->and(WorkspaceRole::VIEWER->canApproveContent())->toBeFalse();
    });

    it('canPublishDirectly: OWNER=true, ADMIN=true, EDITOR=false, VIEWER=false', function () {
        expect(WorkspaceRole::OWNER->canPublishDirectly())->toBeTrue()
            ->and(WorkspaceRole::ADMIN->canPublishDirectly())->toBeTrue()
            ->and(WorkspaceRole::EDITOR->canPublishDirectly())->toBeFalse()
            ->and(WorkspaceRole::VIEWER->canPublishDirectly())->toBeFalse();
    });

    it('canDeleteWorkspace: OWNER=true, ADMIN=false, EDITOR=false, VIEWER=false', function () {
        expect(WorkspaceRole::OWNER->canDeleteWorkspace())->toBeTrue()
            ->and(WorkspaceRole::ADMIN->canDeleteWorkspace())->toBeFalse()
            ->and(WorkspaceRole::EDITOR->canDeleteWorkspace())->toBeFalse()
            ->and(WorkspaceRole::VIEWER->canDeleteWorkspace())->toBeFalse();
    });
});
