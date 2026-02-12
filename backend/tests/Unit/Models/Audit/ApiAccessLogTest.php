<?php

declare(strict_types=1);

/**
 * ApiAccessLog Model Unit Tests
 *
 * Tests for the ApiAccessLog model.
 *
 * @see \App\Models\Audit\ApiAccessLog
 */

use App\Models\Audit\ApiAccessLog;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create api access log with factory', function (): void {
    $log = ApiAccessLog::factory()->create();

    expect($log)->toBeInstanceOf(ApiAccessLog::class)
        ->and($log->id)->not->toBeNull()
        ->and($log->method)->toBeString();
});

test('has correct table name', function (): void {
    $log = new ApiAccessLog();

    expect($log->getTable())->toBe('api_access_logs');
});

test('casts attributes correctly', function (): void {
    $log = ApiAccessLog::factory()->create();

    expect($log->status_code)->toBeInt()
        ->and($log->response_time_ms)->toBeInt();
});

test('tenant relationship works', function (): void {
    $tenant = Tenant::factory()->create();
    $log = ApiAccessLog::factory()->forTenant($tenant)->create();

    expect($log->tenant)->toBeInstanceOf(Tenant::class)
        ->and($log->tenant->id)->toBe($tenant->id);
});

test('user relationship works', function (): void {
    $user = User::factory()->create();
    $log = ApiAccessLog::factory()->forUser($user)->create();

    expect($log->user)->toBeInstanceOf(User::class)
        ->and($log->user->id)->toBe($user->id);
});

test('forTenant scope filters by tenant', function (): void {
    $tenant = Tenant::factory()->create();
    ApiAccessLog::factory()->forTenant($tenant)->count(2)->create();
    ApiAccessLog::factory()->count(1)->create();

    expect(ApiAccessLog::forTenant($tenant->id)->count())->toBe(2);
});

test('byEndpoint scope filters by endpoint', function (): void {
    ApiAccessLog::factory()->forEndpoint('/api/v1/users')->count(2)->create();
    ApiAccessLog::factory()->forEndpoint('/api/v1/posts')->count(1)->create();

    expect(ApiAccessLog::byEndpoint('users')->count())->toBe(2);
});

test('byStatus scope filters by status code', function (): void {
    ApiAccessLog::factory()->success()->count(2)->create();
    ApiAccessLog::factory()->clientError()->count(1)->create();

    expect(ApiAccessLog::byStatus(200)->count())->toBeGreaterThanOrEqual(0);
});

test('errors scope filters error responses', function (): void {
    ApiAccessLog::factory()->success()->count(2)->create();
    ApiAccessLog::factory()->clientError()->count(1)->create();
    ApiAccessLog::factory()->serverError()->count(1)->create();

    expect(ApiAccessLog::errors()->count())->toBe(2);
});

test('slow scope filters slow requests', function (): void {
    ApiAccessLog::factory()->slow()->count(2)->create();
    ApiAccessLog::factory()->fast()->count(1)->create();

    expect(ApiAccessLog::slow(1000)->count())->toBe(2);
});

test('isSuccess returns true for 2xx responses', function (): void {
    $success = ApiAccessLog::factory()->success()->create();
    $error = ApiAccessLog::factory()->clientError()->create();

    expect($success->isSuccess())->toBeTrue()
        ->and($error->isSuccess())->toBeFalse();
});

test('isError returns true for 4xx and 5xx responses', function (): void {
    $clientError = ApiAccessLog::factory()->clientError()->create();
    $serverError = ApiAccessLog::factory()->serverError()->create();
    $success = ApiAccessLog::factory()->success()->create();

    expect($clientError->isError())->toBeTrue()
        ->and($serverError->isError())->toBeTrue()
        ->and($success->isError())->toBeFalse();
});

test('isSlow returns true for slow requests', function (): void {
    $slow = ApiAccessLog::factory()->slow()->create();
    $fast = ApiAccessLog::factory()->fast()->create();

    expect($slow->isSlow(1000))->toBeTrue()
        ->and($fast->isSlow(1000))->toBeFalse();
});

test('getFormattedResponseTime returns formatted time', function (): void {
    $fastLog = ApiAccessLog::factory()->create(['response_time_ms' => 150]);
    $slowLog = ApiAccessLog::factory()->create(['response_time_ms' => 1500]);
    $nullLog = ApiAccessLog::factory()->create(['response_time_ms' => null]);

    expect($fastLog->getFormattedResponseTime())->toBe('150ms')
        ->and($slowLog->getFormattedResponseTime())->toBe('1.50s')
        ->and($nullLog->getFormattedResponseTime())->toBe('N/A');
});
