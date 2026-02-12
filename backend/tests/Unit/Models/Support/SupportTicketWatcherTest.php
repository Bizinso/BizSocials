<?php

declare(strict_types=1);

/**
 * SupportTicketWatcher Model Unit Tests
 *
 * Tests for the SupportTicketWatcher model.
 *
 * @see \App\Models\Support\SupportTicketWatcher
 */

use App\Models\Platform\SuperAdminUser;
use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketWatcher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create watcher with factory', function (): void {
    $watcher = SupportTicketWatcher::factory()->create();

    expect($watcher)->toBeInstanceOf(SupportTicketWatcher::class)
        ->and($watcher->id)->not->toBeNull()
        ->and($watcher->ticket_id)->not->toBeNull();
});

test('has correct table name', function (): void {
    $watcher = new SupportTicketWatcher();

    expect($watcher->getTable())->toBe('support_ticket_watchers');
});

test('casts attributes correctly', function (): void {
    $watcher = SupportTicketWatcher::factory()->create();

    expect($watcher->notify_on_reply)->toBeBool()
        ->and($watcher->notify_on_status_change)->toBeBool()
        ->and($watcher->notify_on_assignment)->toBeBool();
});

test('ticket relationship works', function (): void {
    $ticket = SupportTicket::factory()->create();
    $watcher = SupportTicketWatcher::factory()->forTicket($ticket)->create();

    expect($watcher->ticket)->toBeInstanceOf(SupportTicket::class)
        ->and($watcher->ticket->id)->toBe($ticket->id);
});

test('user relationship works', function (): void {
    $user = User::factory()->create();
    $watcher = SupportTicketWatcher::factory()->asUser($user)->create();

    expect($watcher->user)->toBeInstanceOf(User::class)
        ->and($watcher->user->id)->toBe($user->id);
});

test('admin relationship works', function (): void {
    $admin = SuperAdminUser::factory()->create();
    $watcher = SupportTicketWatcher::factory()->asAdmin($admin)->create();

    expect($watcher->admin)->toBeInstanceOf(SuperAdminUser::class)
        ->and($watcher->admin->id)->toBe($admin->id);
});

test('forTicket scope filters by ticket', function (): void {
    $ticket = SupportTicket::factory()->create();
    SupportTicketWatcher::factory()->forTicket($ticket)->count(2)->create();
    SupportTicketWatcher::factory()->create();

    expect(SupportTicketWatcher::forTicket($ticket->id)->count())->toBe(2);
});

test('byUser scope filters by user', function (): void {
    $user = User::factory()->create();
    SupportTicketWatcher::factory()->asUser($user)->count(2)->create();
    SupportTicketWatcher::factory()->create();

    expect(SupportTicketWatcher::byUser($user->id)->count())->toBe(2);
});

test('byAdmin scope filters by admin', function (): void {
    $admin = SuperAdminUser::factory()->create();
    SupportTicketWatcher::factory()->asAdmin($admin)->count(2)->create();
    SupportTicketWatcher::factory()->create();

    expect(SupportTicketWatcher::byAdmin($admin->id)->count())->toBe(2);
});

test('shouldNotifyOnReply scope filters watchers', function (): void {
    SupportTicketWatcher::factory()->replyOnly()->count(2)->create();
    SupportTicketWatcher::factory()->noNotifications()->create();

    expect(SupportTicketWatcher::shouldNotifyOnReply()->count())->toBe(2);
});

test('shouldNotifyOnStatusChange scope filters watchers', function (): void {
    SupportTicketWatcher::factory()->allNotifications()->count(2)->create();
    SupportTicketWatcher::factory()->replyOnly()->create();

    expect(SupportTicketWatcher::shouldNotifyOnStatusChange()->count())->toBe(2);
});

test('isUser returns correct value', function (): void {
    $user = User::factory()->create();
    $userWatcher = SupportTicketWatcher::factory()->asUser($user)->create();
    $adminWatcher = SupportTicketWatcher::factory()->asAdmin(SuperAdminUser::factory()->create())->create();

    expect($userWatcher->isUser())->toBeTrue()
        ->and($adminWatcher->isUser())->toBeFalse();
});

test('isAdmin returns correct value', function (): void {
    $admin = SuperAdminUser::factory()->create();
    $adminWatcher = SupportTicketWatcher::factory()->asAdmin($admin)->create();
    $userWatcher = SupportTicketWatcher::factory()->asUser(User::factory()->create())->create();

    expect($adminWatcher->isAdmin())->toBeTrue()
        ->and($userWatcher->isAdmin())->toBeFalse();
});

test('getWatcherEmail returns email from watcher record', function (): void {
    $watcher = SupportTicketWatcher::factory()->create(['email' => 'test@example.com']);

    expect($watcher->getWatcherEmail())->toBe('test@example.com');
});

test('getWatcherEmail returns user email when no email set', function (): void {
    $user = User::factory()->create(['email' => 'user@example.com']);
    $watcher = SupportTicketWatcher::factory()->asUser($user)->create(['email' => null]);

    expect($watcher->getWatcherEmail())->toBe('user@example.com');
});

test('getWatcherEmail returns admin email when no email set', function (): void {
    $admin = SuperAdminUser::factory()->create(['email' => 'admin@example.com']);
    $watcher = SupportTicketWatcher::factory()->asAdmin($admin)->create(['email' => null]);

    expect($watcher->getWatcherEmail())->toBe('admin@example.com');
});

test('shouldNotifyFor returns correct value for reply', function (): void {
    $replyWatcher = SupportTicketWatcher::factory()->replyOnly()->create();
    $noNotifyWatcher = SupportTicketWatcher::factory()->noNotifications()->create();

    expect($replyWatcher->shouldNotifyFor('reply'))->toBeTrue()
        ->and($noNotifyWatcher->shouldNotifyFor('reply'))->toBeFalse();
});

test('shouldNotifyFor returns correct value for status_change', function (): void {
    $allWatcher = SupportTicketWatcher::factory()->allNotifications()->create();
    $replyOnlyWatcher = SupportTicketWatcher::factory()->replyOnly()->create();

    expect($allWatcher->shouldNotifyFor('status_change'))->toBeTrue()
        ->and($replyOnlyWatcher->shouldNotifyFor('status_change'))->toBeFalse();
});

test('shouldNotifyFor returns false for unknown event type', function (): void {
    $watcher = SupportTicketWatcher::factory()->allNotifications()->create();

    expect($watcher->shouldNotifyFor('unknown'))->toBeFalse();
});
