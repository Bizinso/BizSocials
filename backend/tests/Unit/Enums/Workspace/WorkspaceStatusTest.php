<?php

declare(strict_types=1);

/**
 * WorkspaceStatus Enum Unit Tests
 *
 * Tests for the WorkspaceStatus enum which defines the lifecycle
 * status of workspaces in the platform.
 *
 * @see \App\Enums\Workspace\WorkspaceStatus
 */

use App\Enums\Workspace\WorkspaceStatus;

test('has all expected cases', function (): void {
    $cases = WorkspaceStatus::cases();

    expect($cases)->toHaveCount(3)
        ->and(WorkspaceStatus::ACTIVE->value)->toBe('active')
        ->and(WorkspaceStatus::SUSPENDED->value)->toBe('suspended')
        ->and(WorkspaceStatus::DELETED->value)->toBe('deleted');
});

test('label returns correct labels', function (): void {
    expect(WorkspaceStatus::ACTIVE->label())->toBe('Active')
        ->and(WorkspaceStatus::SUSPENDED->label())->toBe('Suspended')
        ->and(WorkspaceStatus::DELETED->label())->toBe('Deleted');
});

test('hasAccess returns true only for active status', function (): void {
    expect(WorkspaceStatus::ACTIVE->hasAccess())->toBeTrue()
        ->and(WorkspaceStatus::SUSPENDED->hasAccess())->toBeFalse()
        ->and(WorkspaceStatus::DELETED->hasAccess())->toBeFalse();
});

test('active can transition to suspended or deleted', function (): void {
    expect(WorkspaceStatus::ACTIVE->canTransitionTo(WorkspaceStatus::SUSPENDED))->toBeTrue()
        ->and(WorkspaceStatus::ACTIVE->canTransitionTo(WorkspaceStatus::DELETED))->toBeTrue()
        ->and(WorkspaceStatus::ACTIVE->canTransitionTo(WorkspaceStatus::ACTIVE))->toBeFalse();
});

test('suspended can transition to active or deleted', function (): void {
    expect(WorkspaceStatus::SUSPENDED->canTransitionTo(WorkspaceStatus::ACTIVE))->toBeTrue()
        ->and(WorkspaceStatus::SUSPENDED->canTransitionTo(WorkspaceStatus::DELETED))->toBeTrue()
        ->and(WorkspaceStatus::SUSPENDED->canTransitionTo(WorkspaceStatus::SUSPENDED))->toBeFalse();
});

test('deleted cannot transition to any status', function (): void {
    expect(WorkspaceStatus::DELETED->canTransitionTo(WorkspaceStatus::ACTIVE))->toBeFalse()
        ->and(WorkspaceStatus::DELETED->canTransitionTo(WorkspaceStatus::SUSPENDED))->toBeFalse()
        ->and(WorkspaceStatus::DELETED->canTransitionTo(WorkspaceStatus::DELETED))->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = WorkspaceStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(3)
        ->and($values)->toContain('active')
        ->and($values)->toContain('suspended')
        ->and($values)->toContain('deleted');
});

test('can create enum from string value', function (): void {
    $status = WorkspaceStatus::from('active');

    expect($status)->toBe(WorkspaceStatus::ACTIVE);
});

test('tryFrom returns null for invalid value', function (): void {
    $status = WorkspaceStatus::tryFrom('invalid');

    expect($status)->toBeNull();
});
