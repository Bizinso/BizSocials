<?php

declare(strict_types=1);

/**
 * InvitationStatus Enum Unit Tests
 *
 * Tests for the InvitationStatus enum which defines the status
 * of user invitations.
 *
 * @see \App\Enums\User\InvitationStatus
 */

use App\Enums\User\InvitationStatus;

test('has all expected cases', function (): void {
    $cases = InvitationStatus::cases();

    expect($cases)->toHaveCount(4)
        ->and(InvitationStatus::PENDING->value)->toBe('pending')
        ->and(InvitationStatus::ACCEPTED->value)->toBe('accepted')
        ->and(InvitationStatus::EXPIRED->value)->toBe('expired')
        ->and(InvitationStatus::REVOKED->value)->toBe('revoked');
});

test('label returns correct labels', function (): void {
    expect(InvitationStatus::PENDING->label())->toBe('Pending')
        ->and(InvitationStatus::ACCEPTED->label())->toBe('Accepted')
        ->and(InvitationStatus::EXPIRED->label())->toBe('Expired')
        ->and(InvitationStatus::REVOKED->label())->toBe('Revoked');
});

test('isFinal returns true for accepted expired and revoked', function (): void {
    expect(InvitationStatus::ACCEPTED->isFinal())->toBeTrue()
        ->and(InvitationStatus::EXPIRED->isFinal())->toBeTrue()
        ->and(InvitationStatus::REVOKED->isFinal())->toBeTrue()
        ->and(InvitationStatus::PENDING->isFinal())->toBeFalse();
});

test('pending can transition to accepted expired or revoked', function (): void {
    expect(InvitationStatus::PENDING->canTransitionTo(InvitationStatus::ACCEPTED))->toBeTrue()
        ->and(InvitationStatus::PENDING->canTransitionTo(InvitationStatus::EXPIRED))->toBeTrue()
        ->and(InvitationStatus::PENDING->canTransitionTo(InvitationStatus::REVOKED))->toBeTrue()
        ->and(InvitationStatus::PENDING->canTransitionTo(InvitationStatus::PENDING))->toBeFalse();
});

test('accepted cannot transition to any status', function (): void {
    expect(InvitationStatus::ACCEPTED->canTransitionTo(InvitationStatus::PENDING))->toBeFalse()
        ->and(InvitationStatus::ACCEPTED->canTransitionTo(InvitationStatus::ACCEPTED))->toBeFalse()
        ->and(InvitationStatus::ACCEPTED->canTransitionTo(InvitationStatus::EXPIRED))->toBeFalse()
        ->and(InvitationStatus::ACCEPTED->canTransitionTo(InvitationStatus::REVOKED))->toBeFalse();
});

test('expired cannot transition to any status', function (): void {
    expect(InvitationStatus::EXPIRED->canTransitionTo(InvitationStatus::PENDING))->toBeFalse()
        ->and(InvitationStatus::EXPIRED->canTransitionTo(InvitationStatus::ACCEPTED))->toBeFalse()
        ->and(InvitationStatus::EXPIRED->canTransitionTo(InvitationStatus::EXPIRED))->toBeFalse()
        ->and(InvitationStatus::EXPIRED->canTransitionTo(InvitationStatus::REVOKED))->toBeFalse();
});

test('revoked cannot transition to any status', function (): void {
    expect(InvitationStatus::REVOKED->canTransitionTo(InvitationStatus::PENDING))->toBeFalse()
        ->and(InvitationStatus::REVOKED->canTransitionTo(InvitationStatus::ACCEPTED))->toBeFalse()
        ->and(InvitationStatus::REVOKED->canTransitionTo(InvitationStatus::EXPIRED))->toBeFalse()
        ->and(InvitationStatus::REVOKED->canTransitionTo(InvitationStatus::REVOKED))->toBeFalse();
});

test('values returns all enum values', function (): void {
    $values = InvitationStatus::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(4)
        ->and($values)->toContain('pending')
        ->and($values)->toContain('accepted')
        ->and($values)->toContain('expired')
        ->and($values)->toContain('revoked');
});

test('can create enum from string value', function (): void {
    $status = InvitationStatus::from('accepted');

    expect($status)->toBe(InvitationStatus::ACCEPTED);
});

test('tryFrom returns null for invalid value', function (): void {
    $status = InvitationStatus::tryFrom('invalid');

    expect($status)->toBeNull();
});
