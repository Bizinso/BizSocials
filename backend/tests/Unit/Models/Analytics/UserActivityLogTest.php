<?php

declare(strict_types=1);

/**
 * UserActivityLog Model Unit Tests
 *
 * Tests for the UserActivityLog model which tracks user activity.
 *
 * @see \App\Models\Analytics\UserActivityLog
 */

use App\Enums\Analytics\ActivityCategory;
use App\Enums\Analytics\ActivityType;
use App\Models\Analytics\UserActivityLog;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

test('has correct table name', function (): void {
    $log = new UserActivityLog();

    expect($log->getTable())->toBe('user_activity_logs');
});

test('uses uuid primary key', function (): void {
    $log = UserActivityLog::factory()->create();

    expect($log->id)->not->toBeNull()
        ->and(strlen($log->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $log = new UserActivityLog();
    $fillable = $log->getFillable();

    expect($fillable)->toContain('tenant_id')
        ->and($fillable)->toContain('user_id')
        ->and($fillable)->toContain('workspace_id')
        ->and($fillable)->toContain('activity_type')
        ->and($fillable)->toContain('activity_category')
        ->and($fillable)->toContain('resource_type')
        ->and($fillable)->toContain('resource_id')
        ->and($fillable)->toContain('page_url')
        ->and($fillable)->toContain('session_id')
        ->and($fillable)->toContain('device_type')
        ->and($fillable)->toContain('browser')
        ->and($fillable)->toContain('os')
        ->and($fillable)->toContain('metadata');
});

test('does not use timestamps', function (): void {
    $log = new UserActivityLog();

    expect($log->timestamps)->toBeFalse();
});

test('activity_type casts to ActivityType enum', function (): void {
    $log = UserActivityLog::factory()
        ->ofType(ActivityType::POST_CREATED)
        ->create();

    expect($log->activity_type)->toBeInstanceOf(ActivityType::class)
        ->and($log->activity_type)->toBe(ActivityType::POST_CREATED);
});

test('activity_category casts to ActivityCategory enum', function (): void {
    $log = UserActivityLog::factory()
        ->ofCategory(ActivityCategory::CONTENT_CREATION)
        ->create();

    expect($log->activity_category)->toBeInstanceOf(ActivityCategory::class)
        ->and($log->activity_category)->toBe(ActivityCategory::CONTENT_CREATION);
});

test('metadata casts to array', function (): void {
    $metadata = ['post_id' => 'post-123', 'platform' => 'twitter'];
    $log = UserActivityLog::factory()->withMetadata($metadata)->create();

    expect($log->metadata)->toBeArray()
        ->and($log->metadata['post_id'])->toBe('post-123')
        ->and($log->metadata['platform'])->toBe('twitter');
});

test('created_at casts to datetime', function (): void {
    $log = UserActivityLog::factory()->create();

    expect($log->created_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('tenant relationship returns belongs to', function (): void {
    $log = new UserActivityLog();

    expect($log->tenant())->toBeInstanceOf(BelongsTo::class);
});

test('tenant relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();
    $log = UserActivityLog::factory()->forUser($user)->create();

    expect($log->tenant)->toBeInstanceOf(Tenant::class)
        ->and($log->tenant->id)->toBe($tenant->id);
});

test('user relationship returns belongs to', function (): void {
    $log = new UserActivityLog();

    expect($log->user())->toBeInstanceOf(BelongsTo::class);
});

test('user relationship works correctly', function (): void {
    $user = User::factory()->create();
    $log = UserActivityLog::factory()->forUser($user)->create();

    expect($log->user)->toBeInstanceOf(User::class)
        ->and($log->user->id)->toBe($user->id);
});

test('workspace relationship returns belongs to', function (): void {
    $log = new UserActivityLog();

    expect($log->workspace())->toBeInstanceOf(BelongsTo::class);
});

test('workspace relationship works correctly', function (): void {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->forTenant($user->tenant)->create();
    $log = UserActivityLog::factory()->forUser($user)->forWorkspace($workspace)->create();

    expect($log->workspace)->toBeInstanceOf(Workspace::class)
        ->and($log->workspace->id)->toBe($workspace->id);
});

test('workspace can be null', function (): void {
    $log = UserActivityLog::factory()->create(['workspace_id' => null]);

    expect($log->workspace_id)->toBeNull()
        ->and($log->workspace)->toBeNull();
});

describe('scopes', function () {
    test('forUser scope filters by user id', function (): void {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        UserActivityLog::factory()->count(3)->forUser($user1)->create();
        UserActivityLog::factory()->count(2)->forUser($user2)->create();

        $results = UserActivityLog::forUser($user1->id)->get();

        expect($results)->toHaveCount(3)
            ->and($results->every(fn ($l) => $l->user_id === $user1->id))->toBeTrue();
    });

    test('forTenant scope filters by tenant id', function (): void {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        $user1 = User::factory()->forTenant($tenant1)->create();
        $user2 = User::factory()->forTenant($tenant2)->create();

        UserActivityLog::factory()->count(3)->forUser($user1)->create();
        UserActivityLog::factory()->count(2)->forUser($user2)->create();

        $results = UserActivityLog::forTenant($tenant1->id)->get();

        expect($results)->toHaveCount(3)
            ->and($results->every(fn ($l) => $l->tenant_id === $tenant1->id))->toBeTrue();
    });

    test('forWorkspace scope filters by workspace id', function (): void {
        $user = User::factory()->create();
        $workspace1 = Workspace::factory()->forTenant($user->tenant)->create();
        $workspace2 = Workspace::factory()->forTenant($user->tenant)->create();

        UserActivityLog::factory()->count(3)->forUser($user)->forWorkspace($workspace1)->create();
        UserActivityLog::factory()->count(2)->forUser($user)->forWorkspace($workspace2)->create();

        $results = UserActivityLog::forWorkspace($workspace1->id)->get();

        expect($results)->toHaveCount(3)
            ->and($results->every(fn ($l) => $l->workspace_id === $workspace1->id))->toBeTrue();
    });

    test('ofType scope filters by activity type', function (): void {
        $user = User::factory()->create();

        UserActivityLog::factory()->count(3)->forUser($user)->ofType(ActivityType::POST_CREATED)->create();
        UserActivityLog::factory()->count(2)->forUser($user)->ofType(ActivityType::DASHBOARD_VIEWED)->create();

        $results = UserActivityLog::ofType(ActivityType::POST_CREATED)->get();

        expect($results)->toHaveCount(3)
            ->and($results->every(fn ($l) => $l->activity_type === ActivityType::POST_CREATED))->toBeTrue();
    });

    test('ofCategory scope filters by activity category', function (): void {
        $user = User::factory()->create();

        UserActivityLog::factory()->count(3)->forUser($user)->ofCategory(ActivityCategory::CONTENT_CREATION)->create();
        UserActivityLog::factory()->count(2)->forUser($user)->ofCategory(ActivityCategory::ANALYTICS)->create();

        $results = UserActivityLog::ofCategory(ActivityCategory::CONTENT_CREATION)->get();

        expect($results)->toHaveCount(3)
            ->and($results->every(fn ($l) => $l->activity_category === ActivityCategory::CONTENT_CREATION))->toBeTrue();
    });

    test('forSession scope filters by session id', function (): void {
        $user = User::factory()->create();
        $sessionId = 'session-123';

        UserActivityLog::factory()->count(3)->forUser($user)->forSession($sessionId)->create();
        UserActivityLog::factory()->count(2)->forUser($user)->forSession('other-session')->create();

        $results = UserActivityLog::forSession($sessionId)->get();

        expect($results)->toHaveCount(3)
            ->and($results->every(fn ($l) => $l->session_id === $sessionId))->toBeTrue();
    });

    test('recent scope filters by days', function (): void {
        $user = User::factory()->create();

        UserActivityLog::factory()->forUser($user)->recent(5)->create();
        UserActivityLog::factory()->forUser($user)->old(60)->create();

        $results = UserActivityLog::recent(30)->get();

        expect($results)->toHaveCount(1);
    });
});

describe('log static method', function () {
    test('creates activity log with required fields', function (): void {
        $user = User::factory()->create();

        $log = UserActivityLog::log(
            user: $user,
            activityType: ActivityType::POST_CREATED,
        );

        expect($log)->toBeInstanceOf(UserActivityLog::class)
            ->and($log->user_id)->toBe($user->id)
            ->and($log->tenant_id)->toBe($user->tenant_id)
            ->and($log->activity_type)->toBe(ActivityType::POST_CREATED)
            ->and($log->activity_category)->toBe(ActivityType::POST_CREATED->category());
    });

    test('creates activity log with optional resource', function (): void {
        $user = User::factory()->create();

        $log = UserActivityLog::log(
            user: $user,
            activityType: ActivityType::POST_CREATED,
            resourceType: 'post',
            resourceId: 'post-123',
        );

        expect($log->resource_type)->toBe('post')
            ->and($log->resource_id)->toBe('post-123');
    });

    test('creates activity log with metadata', function (): void {
        $user = User::factory()->create();
        $metadata = ['platform' => 'twitter', 'content_length' => 280];

        $log = UserActivityLog::log(
            user: $user,
            activityType: ActivityType::POST_PUBLISHED,
            metadata: $metadata,
        );

        expect($log->metadata)->toBe($metadata);
    });

    test('creates activity log with workspace', function (): void {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->forTenant($user->tenant)->create();

        $log = UserActivityLog::log(
            user: $user,
            activityType: ActivityType::POST_CREATED,
            workspaceId: $workspace->id,
        );

        expect($log->workspace_id)->toBe($workspace->id);
    });
});

test('factory creates valid model', function (): void {
    $log = UserActivityLog::factory()->create();

    expect($log)->toBeInstanceOf(UserActivityLog::class)
        ->and($log->id)->not->toBeNull()
        ->and($log->tenant_id)->not->toBeNull()
        ->and($log->user_id)->not->toBeNull()
        ->and($log->activity_type)->toBeInstanceOf(ActivityType::class)
        ->and($log->activity_category)->toBeInstanceOf(ActivityCategory::class)
        ->and($log->created_at)->not->toBeNull();
});
