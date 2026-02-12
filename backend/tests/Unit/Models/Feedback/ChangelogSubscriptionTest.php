<?php

declare(strict_types=1);

/**
 * ChangelogSubscription Model Unit Tests
 *
 * Tests for the ChangelogSubscription model which represents changelog subscriptions.
 *
 * @see \App\Models\Feedback\ChangelogSubscription
 */

use App\Enums\Feedback\ReleaseType;
use App\Models\Feedback\ChangelogSubscription;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create subscription with factory', function (): void {
    $subscription = ChangelogSubscription::factory()->create();

    expect($subscription)->toBeInstanceOf(ChangelogSubscription::class)
        ->and($subscription->id)->not->toBeNull()
        ->and($subscription->email)->not->toBeNull();
});

test('has correct table name', function (): void {
    $subscription = new ChangelogSubscription();

    expect($subscription->getTable())->toBe('changelog_subscriptions');
});

test('casts boolean attributes correctly', function (): void {
    $subscription = ChangelogSubscription::factory()->create();

    expect($subscription->notify_major)->toBeBool()
        ->and($subscription->notify_minor)->toBeBool()
        ->and($subscription->notify_patch)->toBeBool()
        ->and($subscription->is_active)->toBeBool();
});

test('user relationship works', function (): void {
    $user = User::factory()->create();
    $subscription = ChangelogSubscription::factory()->forUser($user)->create();

    expect($subscription->user)->toBeInstanceOf(User::class)
        ->and($subscription->user->id)->toBe($user->id);
});

test('tenant relationship works', function (): void {
    $tenant = Tenant::factory()->create();
    $subscription = ChangelogSubscription::factory()->create(['tenant_id' => $tenant->id]);

    expect($subscription->tenant)->toBeInstanceOf(Tenant::class)
        ->and($subscription->tenant->id)->toBe($tenant->id);
});

test('active scope filters active subscriptions', function (): void {
    ChangelogSubscription::factory()->active()->count(2)->create();
    ChangelogSubscription::factory()->inactive()->create();

    expect(ChangelogSubscription::active()->count())->toBe(2);
});

test('forEmail scope filters by email', function (): void {
    ChangelogSubscription::factory()->create(['email' => 'test@example.com']);
    ChangelogSubscription::factory()->create(['email' => 'other@example.com']);

    expect(ChangelogSubscription::forEmail('test@example.com')->count())->toBe(1);
});

test('notifyFor scope filters by release type for major', function (): void {
    ChangelogSubscription::factory()->create(['notify_major' => true]);
    ChangelogSubscription::factory()->create(['notify_major' => false]);

    expect(ChangelogSubscription::notifyFor(ReleaseType::MAJOR)->count())->toBe(1);
});

test('notifyFor scope filters by release type for minor', function (): void {
    ChangelogSubscription::factory()->create(['notify_minor' => true]);
    ChangelogSubscription::factory()->create(['notify_minor' => false]);

    expect(ChangelogSubscription::notifyFor(ReleaseType::MINOR)->count())->toBe(1);
});

test('notifyFor scope filters by release type for patch', function (): void {
    ChangelogSubscription::factory()->create(['notify_patch' => true]);
    ChangelogSubscription::factory()->create(['notify_patch' => false]);

    expect(ChangelogSubscription::notifyFor(ReleaseType::PATCH)->count())->toBe(1);
});

test('isActive returns correct value', function (): void {
    $active = ChangelogSubscription::factory()->active()->create();
    $inactive = ChangelogSubscription::factory()->inactive()->create();

    expect($active->isActive())->toBeTrue()
        ->and($inactive->isActive())->toBeFalse();
});

test('unsubscribe sets is_active to false', function (): void {
    $subscription = ChangelogSubscription::factory()->active()->create();

    $subscription->unsubscribe();

    $subscription->refresh();
    expect($subscription->is_active)->toBeFalse()
        ->and($subscription->unsubscribed_at)->not->toBeNull();
});

test('resubscribe sets is_active to true', function (): void {
    $subscription = ChangelogSubscription::factory()->inactive()->create();

    $subscription->resubscribe();

    $subscription->refresh();
    expect($subscription->is_active)->toBeTrue()
        ->and($subscription->unsubscribed_at)->toBeNull();
});

test('shouldNotifyFor returns false when inactive', function (): void {
    $subscription = ChangelogSubscription::factory()->inactive()->create([
        'notify_major' => true,
    ]);

    expect($subscription->shouldNotifyFor(ReleaseType::MAJOR))->toBeFalse();
});

test('shouldNotifyFor returns correct value for major', function (): void {
    $withMajor = ChangelogSubscription::factory()->active()->create(['notify_major' => true]);
    $withoutMajor = ChangelogSubscription::factory()->active()->create(['notify_major' => false]);

    expect($withMajor->shouldNotifyFor(ReleaseType::MAJOR))->toBeTrue()
        ->and($withoutMajor->shouldNotifyFor(ReleaseType::MAJOR))->toBeFalse();
});

test('shouldNotifyFor returns correct value for minor', function (): void {
    $withMinor = ChangelogSubscription::factory()->active()->create(['notify_minor' => true]);
    $withoutMinor = ChangelogSubscription::factory()->active()->create(['notify_minor' => false]);

    expect($withMinor->shouldNotifyFor(ReleaseType::MINOR))->toBeTrue()
        ->and($withoutMinor->shouldNotifyFor(ReleaseType::MINOR))->toBeFalse();
});

test('shouldNotifyFor returns correct value for patch', function (): void {
    $withPatch = ChangelogSubscription::factory()->active()->create(['notify_patch' => true]);
    $withoutPatch = ChangelogSubscription::factory()->active()->create(['notify_patch' => false]);

    expect($withPatch->shouldNotifyFor(ReleaseType::PATCH))->toBeTrue()
        ->and($withoutPatch->shouldNotifyFor(ReleaseType::PATCH))->toBeFalse();
});

test('shouldNotifyFor handles hotfix same as patch', function (): void {
    $subscription = ChangelogSubscription::factory()->active()->create(['notify_patch' => true]);

    expect($subscription->shouldNotifyFor(ReleaseType::HOTFIX))->toBeTrue();
});
