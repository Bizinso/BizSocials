<?php

declare(strict_types=1);

/**
 * DataDeletionRequest Model Unit Tests
 *
 * Tests for the DataDeletionRequest model.
 *
 * @see \App\Models\Audit\DataDeletionRequest
 */

use App\Enums\Audit\DataRequestStatus;
use App\Models\Audit\DataDeletionRequest;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create data deletion request with factory', function (): void {
    $request = DataDeletionRequest::factory()->create();

    expect($request)->toBeInstanceOf(DataDeletionRequest::class)
        ->and($request->id)->not->toBeNull()
        ->and($request->status)->toBeInstanceOf(DataRequestStatus::class);
});

test('has correct table name', function (): void {
    $request = new DataDeletionRequest();

    expect($request->getTable())->toBe('data_deletion_requests');
});

test('casts attributes correctly', function (): void {
    $request = DataDeletionRequest::factory()->approved()->create();

    expect($request->status)->toBeInstanceOf(DataRequestStatus::class)
        ->and($request->data_categories)->toBeArray()
        ->and($request->requires_approval)->toBeBool()
        ->and($request->approved_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('tenant relationship works', function (): void {
    $tenant = Tenant::factory()->create();
    $request = DataDeletionRequest::factory()->forTenant($tenant)->create();

    expect($request->tenant)->toBeInstanceOf(Tenant::class)
        ->and($request->tenant->id)->toBe($tenant->id);
});

test('user relationship works', function (): void {
    $user = User::factory()->create();
    $request = DataDeletionRequest::factory()->forUser($user)->create();

    expect($request->user)->toBeInstanceOf(User::class)
        ->and($request->user->id)->toBe($user->id);
});

test('requester relationship works', function (): void {
    $user = User::factory()->create();
    $request = DataDeletionRequest::factory()->requestedBy($user)->create();

    expect($request->requester)->toBeInstanceOf(User::class)
        ->and($request->requester->id)->toBe($user->id);
});

test('approver relationship works', function (): void {
    $request = DataDeletionRequest::factory()->approved()->create();

    expect($request->approver)->toBeInstanceOf(SuperAdminUser::class);
});

test('pending scope filters pending requests', function (): void {
    DataDeletionRequest::factory()->pending()->count(2)->create();
    DataDeletionRequest::factory()->completed()->count(1)->create();

    expect(DataDeletionRequest::pending()->count())->toBe(2);
});

test('approved scope filters approved requests', function (): void {
    DataDeletionRequest::factory()->approved()->count(2)->create();
    DataDeletionRequest::factory()->pending()->count(1)->create();

    expect(DataDeletionRequest::approved()->count())->toBe(2);
});

test('completed scope filters completed requests', function (): void {
    DataDeletionRequest::factory()->completed()->count(2)->create();
    DataDeletionRequest::factory()->pending()->count(1)->create();

    expect(DataDeletionRequest::completed()->count())->toBe(2);
});

test('isPending returns correct value', function (): void {
    $pending = DataDeletionRequest::factory()->pending()->create();
    $completed = DataDeletionRequest::factory()->completed()->create();

    expect($pending->isPending())->toBeTrue()
        ->and($completed->isPending())->toBeFalse();
});

test('isApproved returns correct value', function (): void {
    $approved = DataDeletionRequest::factory()->approved()->create();
    $pending = DataDeletionRequest::factory()->pending()->create();

    expect($approved->isApproved())->toBeTrue()
        ->and($pending->isApproved())->toBeFalse();
});

test('needsApproval returns correct value', function (): void {
    $needsApproval = DataDeletionRequest::factory()->requiresApproval()->create();
    $noApprovalNeeded = DataDeletionRequest::factory()->noApprovalRequired()->create();
    $alreadyApproved = DataDeletionRequest::factory()->approved()->create();

    expect($needsApproval->needsApproval())->toBeTrue()
        ->and($noApprovalNeeded->needsApproval())->toBeFalse()
        ->and($alreadyApproved->needsApproval())->toBeFalse();
});

test('approve sets approval fields', function (): void {
    $request = DataDeletionRequest::factory()->pending()->create();
    $admin = SuperAdminUser::factory()->create();

    $request->approve($admin);

    $fresh = $request->fresh();
    expect($fresh->approved_by)->toBe($admin->id)
        ->and($fresh->approved_at)->not->toBeNull()
        ->and($fresh->scheduled_for)->not->toBeNull();
});

test('complete sets completion fields', function (): void {
    $request = DataDeletionRequest::factory()->approved()->create();
    $summary = ['posts_deleted' => 10, 'comments_deleted' => 50];

    $request->complete($summary);

    $fresh = $request->fresh();
    expect($fresh->status)->toBe(DataRequestStatus::COMPLETED)
        ->and($fresh->completed_at)->not->toBeNull()
        ->and($fresh->deletion_summary)->toBe($summary);
});

test('fail sets failure reason', function (): void {
    $request = DataDeletionRequest::factory()->processing()->create();

    $request->fail('Deletion failed');

    $fresh = $request->fresh();
    expect($fresh->status)->toBe(DataRequestStatus::FAILED)
        ->and($fresh->failure_reason)->toBe('Deletion failed');
});

test('cancel sets status to cancelled', function (): void {
    $request = DataDeletionRequest::factory()->pending()->create();

    $request->cancel();

    expect($request->fresh()->status)->toBe(DataRequestStatus::CANCELLED);
});
