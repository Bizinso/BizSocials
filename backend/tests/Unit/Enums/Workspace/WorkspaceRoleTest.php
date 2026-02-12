<?php

declare(strict_types=1);

/**
 * WorkspaceRole Enum Unit Tests
 *
 * Tests for the WorkspaceRole enum which defines the role
 * of a user within a workspace.
 *
 * @see \App\Enums\Workspace\WorkspaceRole
 */

use App\Enums\Workspace\WorkspaceRole;

test('has all expected cases', function (): void {
    $cases = WorkspaceRole::cases();

    expect($cases)->toHaveCount(4)
        ->and(WorkspaceRole::OWNER->value)->toBe('owner')
        ->and(WorkspaceRole::ADMIN->value)->toBe('admin')
        ->and(WorkspaceRole::EDITOR->value)->toBe('editor')
        ->and(WorkspaceRole::VIEWER->value)->toBe('viewer');
});

test('label returns correct labels', function (): void {
    expect(WorkspaceRole::OWNER->label())->toBe('Owner')
        ->and(WorkspaceRole::ADMIN->label())->toBe('Admin')
        ->and(WorkspaceRole::EDITOR->label())->toBe('Editor')
        ->and(WorkspaceRole::VIEWER->label())->toBe('Viewer');
});

test('canManageWorkspace returns true for owner and admin', function (): void {
    expect(WorkspaceRole::OWNER->canManageWorkspace())->toBeTrue()
        ->and(WorkspaceRole::ADMIN->canManageWorkspace())->toBeTrue()
        ->and(WorkspaceRole::EDITOR->canManageWorkspace())->toBeFalse()
        ->and(WorkspaceRole::VIEWER->canManageWorkspace())->toBeFalse();
});

test('canManageBilling returns true only for owner', function (): void {
    expect(WorkspaceRole::OWNER->canManageBilling())->toBeTrue()
        ->and(WorkspaceRole::ADMIN->canManageBilling())->toBeFalse()
        ->and(WorkspaceRole::EDITOR->canManageBilling())->toBeFalse()
        ->and(WorkspaceRole::VIEWER->canManageBilling())->toBeFalse();
});

test('canManageMembers returns true for owner and admin', function (): void {
    expect(WorkspaceRole::OWNER->canManageMembers())->toBeTrue()
        ->and(WorkspaceRole::ADMIN->canManageMembers())->toBeTrue()
        ->and(WorkspaceRole::EDITOR->canManageMembers())->toBeFalse()
        ->and(WorkspaceRole::VIEWER->canManageMembers())->toBeFalse();
});

test('canManageSocialAccounts returns true for owner and admin', function (): void {
    expect(WorkspaceRole::OWNER->canManageSocialAccounts())->toBeTrue()
        ->and(WorkspaceRole::ADMIN->canManageSocialAccounts())->toBeTrue()
        ->and(WorkspaceRole::EDITOR->canManageSocialAccounts())->toBeFalse()
        ->and(WorkspaceRole::VIEWER->canManageSocialAccounts())->toBeFalse();
});

test('canCreateContent returns true for owner admin and editor', function (): void {
    expect(WorkspaceRole::OWNER->canCreateContent())->toBeTrue()
        ->and(WorkspaceRole::ADMIN->canCreateContent())->toBeTrue()
        ->and(WorkspaceRole::EDITOR->canCreateContent())->toBeTrue()
        ->and(WorkspaceRole::VIEWER->canCreateContent())->toBeFalse();
});

test('canApproveContent returns true for owner and admin', function (): void {
    expect(WorkspaceRole::OWNER->canApproveContent())->toBeTrue()
        ->and(WorkspaceRole::ADMIN->canApproveContent())->toBeTrue()
        ->and(WorkspaceRole::EDITOR->canApproveContent())->toBeFalse()
        ->and(WorkspaceRole::VIEWER->canApproveContent())->toBeFalse();
});

test('canPublishDirectly returns true for owner and admin', function (): void {
    expect(WorkspaceRole::OWNER->canPublishDirectly())->toBeTrue()
        ->and(WorkspaceRole::ADMIN->canPublishDirectly())->toBeTrue()
        ->and(WorkspaceRole::EDITOR->canPublishDirectly())->toBeFalse()
        ->and(WorkspaceRole::VIEWER->canPublishDirectly())->toBeFalse();
});

test('canDeleteWorkspace returns true only for owner', function (): void {
    expect(WorkspaceRole::OWNER->canDeleteWorkspace())->toBeTrue()
        ->and(WorkspaceRole::ADMIN->canDeleteWorkspace())->toBeFalse()
        ->and(WorkspaceRole::EDITOR->canDeleteWorkspace())->toBeFalse()
        ->and(WorkspaceRole::VIEWER->canDeleteWorkspace())->toBeFalse();
});

test('hierarchy returns correct values', function (): void {
    expect(WorkspaceRole::OWNER->hierarchy())->toBe(4)
        ->and(WorkspaceRole::ADMIN->hierarchy())->toBe(3)
        ->and(WorkspaceRole::EDITOR->hierarchy())->toBe(2)
        ->and(WorkspaceRole::VIEWER->hierarchy())->toBe(1);
});

test('isAtLeast checks role hierarchy correctly', function (): void {
    // Owner is at least all roles
    expect(WorkspaceRole::OWNER->isAtLeast(WorkspaceRole::OWNER))->toBeTrue()
        ->and(WorkspaceRole::OWNER->isAtLeast(WorkspaceRole::ADMIN))->toBeTrue()
        ->and(WorkspaceRole::OWNER->isAtLeast(WorkspaceRole::EDITOR))->toBeTrue()
        ->and(WorkspaceRole::OWNER->isAtLeast(WorkspaceRole::VIEWER))->toBeTrue();

    // Admin is at least admin, editor, viewer but not owner
    expect(WorkspaceRole::ADMIN->isAtLeast(WorkspaceRole::OWNER))->toBeFalse()
        ->and(WorkspaceRole::ADMIN->isAtLeast(WorkspaceRole::ADMIN))->toBeTrue()
        ->and(WorkspaceRole::ADMIN->isAtLeast(WorkspaceRole::EDITOR))->toBeTrue()
        ->and(WorkspaceRole::ADMIN->isAtLeast(WorkspaceRole::VIEWER))->toBeTrue();

    // Editor is at least editor, viewer but not owner or admin
    expect(WorkspaceRole::EDITOR->isAtLeast(WorkspaceRole::OWNER))->toBeFalse()
        ->and(WorkspaceRole::EDITOR->isAtLeast(WorkspaceRole::ADMIN))->toBeFalse()
        ->and(WorkspaceRole::EDITOR->isAtLeast(WorkspaceRole::EDITOR))->toBeTrue()
        ->and(WorkspaceRole::EDITOR->isAtLeast(WorkspaceRole::VIEWER))->toBeTrue();

    // Viewer is only at least viewer
    expect(WorkspaceRole::VIEWER->isAtLeast(WorkspaceRole::OWNER))->toBeFalse()
        ->and(WorkspaceRole::VIEWER->isAtLeast(WorkspaceRole::ADMIN))->toBeFalse()
        ->and(WorkspaceRole::VIEWER->isAtLeast(WorkspaceRole::EDITOR))->toBeFalse()
        ->and(WorkspaceRole::VIEWER->isAtLeast(WorkspaceRole::VIEWER))->toBeTrue();
});

test('values returns all enum values', function (): void {
    $values = WorkspaceRole::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(4)
        ->and($values)->toContain('owner')
        ->and($values)->toContain('admin')
        ->and($values)->toContain('editor')
        ->and($values)->toContain('viewer');
});

test('can create enum from string value', function (): void {
    $role = WorkspaceRole::from('owner');

    expect($role)->toBe(WorkspaceRole::OWNER);
});

test('tryFrom returns null for invalid value', function (): void {
    $role = WorkspaceRole::tryFrom('invalid');

    expect($role)->toBeNull();
});
