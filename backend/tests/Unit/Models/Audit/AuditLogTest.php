<?php

declare(strict_types=1);

/**
 * AuditLog Model Unit Tests
 *
 * Tests for the AuditLog model.
 *
 * @see \App\Models\Audit\AuditLog
 */

use App\Enums\Audit\AuditAction;
use App\Enums\Audit\AuditableType;
use App\Models\Audit\AuditLog;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create audit log with factory', function (): void {
    $log = AuditLog::factory()->create();

    expect($log)->toBeInstanceOf(AuditLog::class)
        ->and($log->id)->not->toBeNull()
        ->and($log->action)->toBeInstanceOf(AuditAction::class);
});

test('has correct table name', function (): void {
    $log = new AuditLog();

    expect($log->getTable())->toBe('audit_logs');
});

test('casts attributes correctly', function (): void {
    $log = AuditLog::factory()->update()->create();

    expect($log->action)->toBeInstanceOf(AuditAction::class)
        ->and($log->auditable_type)->toBeInstanceOf(AuditableType::class)
        ->and($log->old_values)->toBeArray()
        ->and($log->new_values)->toBeArray();
});

test('tenant relationship works', function (): void {
    $tenant = Tenant::factory()->create();
    $log = AuditLog::factory()->forTenant($tenant)->create();

    expect($log->tenant)->toBeInstanceOf(Tenant::class)
        ->and($log->tenant->id)->toBe($tenant->id);
});

test('user relationship works', function (): void {
    $user = User::factory()->create();
    $log = AuditLog::factory()->forUser($user)->create();

    expect($log->user)->toBeInstanceOf(User::class)
        ->and($log->user->id)->toBe($user->id);
});

test('admin relationship works', function (): void {
    $admin = SuperAdminUser::factory()->create();
    $log = AuditLog::factory()->byAdmin($admin)->create();

    expect($log->admin)->toBeInstanceOf(SuperAdminUser::class)
        ->and($log->admin->id)->toBe($admin->id);
});

test('forTenant scope filters by tenant', function (): void {
    $tenant = Tenant::factory()->create();
    AuditLog::factory()->forTenant($tenant)->count(2)->create();
    AuditLog::factory()->count(1)->create();

    expect(AuditLog::forTenant($tenant->id)->count())->toBe(2);
});

test('byAction scope filters by action', function (): void {
    AuditLog::factory()->createAction()->count(2)->create();
    AuditLog::factory()->login()->count(3)->create();

    expect(AuditLog::byAction(AuditAction::LOGIN)->count())->toBe(3);
});

test('byType scope filters by auditable type', function (): void {
    AuditLog::factory()->forType(AuditableType::USER)->count(2)->create();
    AuditLog::factory()->forType(AuditableType::POST)->count(1)->create();

    expect(AuditLog::byType(AuditableType::USER)->count())->toBe(2);
});

test('recent scope orders by created_at desc', function (): void {
    AuditLog::factory()->count(5)->create();

    $logs = AuditLog::recent(3)->get();

    expect($logs)->toHaveCount(3);
});

test('isCreate returns correct value', function (): void {
    $createLog = AuditLog::factory()->createAction()->create();
    $updateLog = AuditLog::factory()->update()->create();

    expect($createLog->isCreate())->toBeTrue()
        ->and($updateLog->isCreate())->toBeFalse();
});

test('isUpdate returns correct value', function (): void {
    $updateLog = AuditLog::factory()->update()->create();
    $createLog = AuditLog::factory()->createAction()->create();

    expect($updateLog->isUpdate())->toBeTrue()
        ->and($createLog->isUpdate())->toBeFalse();
});

test('isDelete returns correct value', function (): void {
    $deleteLog = AuditLog::factory()->delete()->create();
    $createLog = AuditLog::factory()->createAction()->create();

    expect($deleteLog->isDelete())->toBeTrue()
        ->and($createLog->isDelete())->toBeFalse();
});

test('getChangedFields returns fields that changed', function (): void {
    $log = AuditLog::factory()->create([
        'old_values' => ['name' => 'Old Name', 'email' => 'old@example.com'],
        'new_values' => ['name' => 'New Name', 'email' => 'old@example.com'],
    ]);

    expect($log->getChangedFields())->toContain('name')
        ->and($log->getChangedFields())->not->toContain('email');
});

test('getOldValue returns old value for field', function (): void {
    $log = AuditLog::factory()->create([
        'old_values' => ['name' => 'Old Name'],
        'new_values' => ['name' => 'New Name'],
    ]);

    expect($log->getOldValue('name'))->toBe('Old Name')
        ->and($log->getOldValue('nonexistent'))->toBeNull();
});

test('getNewValue returns new value for field', function (): void {
    $log = AuditLog::factory()->create([
        'old_values' => ['name' => 'Old Name'],
        'new_values' => ['name' => 'New Name'],
    ]);

    expect($log->getNewValue('name'))->toBe('New Name')
        ->and($log->getNewValue('nonexistent'))->toBeNull();
});
