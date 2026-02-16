<?php

declare(strict_types=1);

use App\Models\Inbox\InboxItem;
use App\Models\Inbox\InboxReply;
use App\Models\User;
use App\Services\Inbox\InboxReplyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(InboxReplyService::class);
});

test('lists all replies for an inbox item', function (): void {
    $inboxItem = InboxItem::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    InboxReply::factory()->create([
        'inbox_item_id' => $inboxItem->id,
        'replied_by_user_id' => $user1->id,
    ]);

    InboxReply::factory()->create([
        'inbox_item_id' => $inboxItem->id,
        'replied_by_user_id' => $user2->id,
    ]);

    $replies = $this->service->listForItem($inboxItem);

    expect($replies)->toHaveCount(2)
        ->and($replies->first())->toBeInstanceOf(InboxReply::class);
});

test('retrieves reply by id', function (): void {
    $user = User::factory()->create();
    $inboxItem = InboxItem::factory()->create();
    $reply = InboxReply::factory()->create([
        'inbox_item_id' => $inboxItem->id,
        'replied_by_user_id' => $user->id,
    ]);

    $retrieved = $this->service->get($reply->id);

    expect($retrieved->id)->toBe($reply->id)
        ->and($retrieved->inboxItem)->not->toBeNull()
        ->and($retrieved->repliedBy)->not->toBeNull();
});

test('throws exception when reply not found', function (): void {
    $this->service->get('non-existent-id');
})->throws(Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('marks reply as sent', function (): void {
    $reply = InboxReply::factory()->create([
        'platform_reply_id' => null,
        'failed_at' => now(),
        'failure_reason' => 'Previous error',
    ]);

    $updated = $this->service->markAsSent($reply, 'platform_123');

    expect($updated->platform_reply_id)->toBe('platform_123')
        ->and($updated->failed_at)->toBeNull()
        ->and($updated->failure_reason)->toBeNull();
});

test('marks reply as failed', function (): void {
    $reply = InboxReply::factory()->create([
        'failed_at' => null,
        'failure_reason' => null,
    ]);

    $updated = $this->service->markAsFailed($reply, 'API timeout');

    expect($updated->failed_at)->not->toBeNull()
        ->and($updated->failure_reason)->toBe('API timeout');
});

test('marks reply as failed clears previous success state', function (): void {
    $reply = InboxReply::factory()->create([
        'platform_reply_id' => 'platform_123',
        'failed_at' => null,
    ]);

    $updated = $this->service->markAsFailed($reply, 'Connection error');

    expect($updated->failed_at)->not->toBeNull()
        ->and($updated->failure_reason)->toBe('Connection error')
        ->and($updated->platform_reply_id)->toBe('platform_123');
});
