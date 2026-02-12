<?php

declare(strict_types=1);

/**
 * DataExportRequest Model Unit Tests
 *
 * Tests for the DataExportRequest model.
 *
 * @see \App\Models\Audit\DataExportRequest
 */

use App\Enums\Audit\DataRequestStatus;
use App\Enums\Audit\DataRequestType;
use App\Models\Audit\DataExportRequest;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create data export request with factory', function (): void {
    $request = DataExportRequest::factory()->create();

    expect($request)->toBeInstanceOf(DataExportRequest::class)
        ->and($request->id)->not->toBeNull()
        ->and($request->status)->toBeInstanceOf(DataRequestStatus::class);
});

test('has correct table name', function (): void {
    $request = new DataExportRequest();

    expect($request->getTable())->toBe('data_export_requests');
});

test('casts attributes correctly', function (): void {
    $request = DataExportRequest::factory()->completed()->create();

    expect($request->request_type)->toBeInstanceOf(DataRequestType::class)
        ->and($request->status)->toBeInstanceOf(DataRequestStatus::class)
        ->and($request->data_categories)->toBeArray()
        ->and($request->expires_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('tenant relationship works', function (): void {
    $tenant = Tenant::factory()->create();
    $request = DataExportRequest::factory()->forTenant($tenant)->create();

    expect($request->tenant)->toBeInstanceOf(Tenant::class)
        ->and($request->tenant->id)->toBe($tenant->id);
});

test('user relationship works', function (): void {
    $user = User::factory()->create();
    $request = DataExportRequest::factory()->forUser($user)->create();

    expect($request->user)->toBeInstanceOf(User::class)
        ->and($request->user->id)->toBe($user->id);
});

test('requester relationship works', function (): void {
    $user = User::factory()->create();
    $request = DataExportRequest::factory()->requestedBy($user)->create();

    expect($request->requester)->toBeInstanceOf(User::class)
        ->and($request->requester->id)->toBe($user->id);
});

test('pending scope filters pending requests', function (): void {
    DataExportRequest::factory()->pending()->count(2)->create();
    DataExportRequest::factory()->completed()->count(1)->create();

    expect(DataExportRequest::pending()->count())->toBe(2);
});

test('completed scope filters completed requests', function (): void {
    DataExportRequest::factory()->completed()->count(2)->create();
    DataExportRequest::factory()->pending()->count(1)->create();

    expect(DataExportRequest::completed()->count())->toBe(2);
});

test('expired scope filters expired requests', function (): void {
    DataExportRequest::factory()->expired()->count(2)->create();
    DataExportRequest::factory()->completed()->count(1)->create();

    expect(DataExportRequest::expired()->count())->toBe(2);
});

test('isPending returns correct value', function (): void {
    $pending = DataExportRequest::factory()->pending()->create();
    $completed = DataExportRequest::factory()->completed()->create();

    expect($pending->isPending())->toBeTrue()
        ->and($completed->isPending())->toBeFalse();
});

test('isCompleted returns correct value', function (): void {
    $completed = DataExportRequest::factory()->completed()->create();
    $pending = DataExportRequest::factory()->pending()->create();

    expect($completed->isCompleted())->toBeTrue()
        ->and($pending->isCompleted())->toBeFalse();
});

test('isExpired returns correct value', function (): void {
    $expired = DataExportRequest::factory()->expired()->create();
    $completed = DataExportRequest::factory()->completed()->create();

    expect($expired->isExpired())->toBeTrue()
        ->and($completed->isExpired())->toBeFalse();
});

test('start changes status to processing', function (): void {
    $request = DataExportRequest::factory()->pending()->create();

    $request->start();

    expect($request->fresh()->status)->toBe(DataRequestStatus::PROCESSING);
});

test('complete sets file path and status', function (): void {
    $request = DataExportRequest::factory()->processing()->create();

    $request->complete('/path/to/file.json', 1000);

    $fresh = $request->fresh();
    expect($fresh->status)->toBe(DataRequestStatus::COMPLETED)
        ->and($fresh->file_path)->toBe('/path/to/file.json')
        ->and($fresh->file_size_bytes)->toBe(1000)
        ->and($fresh->completed_at)->not->toBeNull();
});

test('fail sets failure reason', function (): void {
    $request = DataExportRequest::factory()->processing()->create();

    $request->fail('Export failed');

    $fresh = $request->fresh();
    expect($fresh->status)->toBe(DataRequestStatus::FAILED)
        ->and($fresh->failure_reason)->toBe('Export failed');
});

test('incrementDownloadCount increments count', function (): void {
    $request = DataExportRequest::factory()->completed()->create(['download_count' => 0]);

    $request->incrementDownloadCount();

    expect($request->fresh()->download_count)->toBe(1);
});

test('getDownloadUrl returns null for expired or missing file', function (): void {
    $expired = DataExportRequest::factory()->expired()->create();
    $noFile = DataExportRequest::factory()->pending()->create();

    expect($expired->getDownloadUrl())->toBeNull()
        ->and($noFile->getDownloadUrl())->toBeNull();
});
