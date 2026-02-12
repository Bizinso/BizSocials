<?php

declare(strict_types=1);

/**
 * InboxReply Model Unit Tests
 *
 * Tests for the InboxReply model which represents replies sent
 * to inbox items (comments).
 *
 * @see \App\Models\Inbox\InboxReply
 */

use App\Models\Inbox\InboxItem;
use App\Models\Inbox\InboxReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

test('has correct table name', function (): void {
    $reply = new InboxReply();

    expect($reply->getTable())->toBe('inbox_replies');
});

test('uses uuid primary key', function (): void {
    $reply = InboxReply::factory()->create();

    expect($reply->id)->not->toBeNull()
        ->and(strlen($reply->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $reply = new InboxReply();
    $fillable = $reply->getFillable();

    expect($fillable)->toContain('inbox_item_id')
        ->and($fillable)->toContain('replied_by_user_id')
        ->and($fillable)->toContain('content_text')
        ->and($fillable)->toContain('platform_reply_id')
        ->and($fillable)->toContain('sent_at')
        ->and($fillable)->toContain('failed_at')
        ->and($fillable)->toContain('failure_reason');
});

test('sent_at casts to datetime', function (): void {
    $reply = InboxReply::factory()->create();

    expect($reply->sent_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('failed_at casts to datetime', function (): void {
    $reply = InboxReply::factory()->failed()->create();

    expect($reply->failed_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('inboxItem relationship returns belongs to', function (): void {
    $reply = new InboxReply();

    expect($reply->inboxItem())->toBeInstanceOf(BelongsTo::class);
});

test('inboxItem relationship works correctly', function (): void {
    $item = InboxItem::factory()->comment()->create();
    $reply = InboxReply::factory()->forInboxItem($item)->create();

    expect($reply->inboxItem)->toBeInstanceOf(InboxItem::class)
        ->and($reply->inboxItem->id)->toBe($item->id);
});

test('repliedBy relationship returns belongs to', function (): void {
    $reply = new InboxReply();

    expect($reply->repliedBy())->toBeInstanceOf(BelongsTo::class);
});

test('repliedBy relationship works correctly', function (): void {
    $user = User::factory()->create();
    $reply = InboxReply::factory()->byUser($user)->create();

    expect($reply->repliedBy)->toBeInstanceOf(User::class)
        ->and($reply->repliedBy->id)->toBe($user->id);
});

test('scope forItem filters by inbox item', function (): void {
    $item1 = InboxItem::factory()->comment()->create();
    $item2 = InboxItem::factory()->comment()->create();

    InboxReply::factory()->count(3)->forInboxItem($item1)->create();
    InboxReply::factory()->count(2)->forInboxItem($item2)->create();

    $replies = InboxReply::forItem($item1->id)->get();

    expect($replies)->toHaveCount(3)
        ->and($replies->every(fn ($r) => $r->inbox_item_id === $item1->id))->toBeTrue();
});

test('scope successful filters sent replies', function (): void {
    InboxReply::factory()->sent()->create();
    InboxReply::factory()->sent()->create();
    InboxReply::factory()->failed()->create();

    $replies = InboxReply::successful()->get();

    expect($replies)->toHaveCount(2);
});

test('scope failed filters failed replies', function (): void {
    InboxReply::factory()->sent()->create();
    InboxReply::factory()->failed()->create();
    InboxReply::factory()->failed()->create();

    $replies = InboxReply::failed()->get();

    expect($replies)->toHaveCount(2);
});

test('isSent returns correct value', function (): void {
    $sent = InboxReply::factory()->sent()->create();
    $pending = InboxReply::factory()->create();
    $failed = InboxReply::factory()->failed()->create();

    expect($sent->isSent())->toBeTrue()
        ->and($pending->isSent())->toBeFalse()
        ->and($failed->isSent())->toBeFalse();
});

test('hasFailed returns correct value', function (): void {
    $sent = InboxReply::factory()->sent()->create();
    $failed = InboxReply::factory()->failed()->create();

    expect($sent->hasFailed())->toBeFalse()
        ->and($failed->hasFailed())->toBeTrue();
});

test('markAsSent updates platform_reply_id and clears failure info', function (): void {
    $reply = InboxReply::factory()->failed()->create();
    $platformReplyId = 'reply_success_123';

    $reply->markAsSent($platformReplyId);

    expect($reply->platform_reply_id)->toBe($platformReplyId)
        ->and($reply->failed_at)->toBeNull()
        ->and($reply->failure_reason)->toBeNull();
});

test('markAsFailed sets failure info', function (): void {
    $reply = InboxReply::factory()->create();
    $reason = 'API rate limit exceeded';

    $reply->markAsFailed($reason);

    expect($reply->failed_at)->not->toBeNull()
        ->and($reply->failure_reason)->toBe($reason);
});

test('factory creates valid model', function (): void {
    $reply = InboxReply::factory()->create();

    expect($reply)->toBeInstanceOf(InboxReply::class)
        ->and($reply->id)->not->toBeNull()
        ->and($reply->inbox_item_id)->not->toBeNull()
        ->and($reply->replied_by_user_id)->not->toBeNull()
        ->and($reply->content_text)->not->toBeNull()
        ->and($reply->sent_at)->not->toBeNull();
});

test('factory sent state works correctly', function (): void {
    $reply = InboxReply::factory()->sent()->create();

    expect($reply->platform_reply_id)->not->toBeNull()
        ->and($reply->failed_at)->toBeNull()
        ->and($reply->failure_reason)->toBeNull();
});

test('factory failed state works correctly', function (): void {
    $reply = InboxReply::factory()->failed()->create();

    expect($reply->platform_reply_id)->toBeNull()
        ->and($reply->failed_at)->not->toBeNull()
        ->and($reply->failure_reason)->not->toBeNull();
});

test('factory withContent sets specific content', function (): void {
    $content = 'This is a custom reply content.';
    $reply = InboxReply::factory()->withContent($content)->create();

    expect($reply->content_text)->toBe($content);
});
