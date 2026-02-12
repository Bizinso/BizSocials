<?php

declare(strict_types=1);

/**
 * InboxReplyService Unit Tests
 *
 * Tests for the InboxReplyService which handles reply management.
 *
 * @see \App\Services\Inbox\InboxReplyService
 */

use App\Data\Inbox\CreateReplyData;
use App\Enums\Inbox\InboxItemType;
use App\Models\Inbox\InboxItem;
use App\Models\Inbox\InboxReply;
use App\Models\User;
use App\Services\Inbox\InboxReplyService;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->service = app(InboxReplyService::class);
});

test('listForItem returns replies for inbox item', function (): void {
    $item = InboxItem::factory()->comment()->create();

    InboxReply::factory()->count(3)->forInboxItem($item)->create();

    // Create replies for another item (should not be included)
    $otherItem = InboxItem::factory()->comment()->create();
    InboxReply::factory()->count(2)->forInboxItem($otherItem)->create();

    $result = $this->service->listForItem($item);

    expect($result)->toHaveCount(3)
        ->and($result->every(fn ($r) => $r->inbox_item_id === $item->id))->toBeTrue();
});

test('listForItem returns replies ordered by sent_at descending', function (): void {
    $item = InboxItem::factory()->comment()->create();

    $oldest = InboxReply::factory()->forInboxItem($item)->create(['sent_at' => now()->subDays(3)]);
    $middle = InboxReply::factory()->forInboxItem($item)->create(['sent_at' => now()->subDays(2)]);
    $newest = InboxReply::factory()->forInboxItem($item)->create(['sent_at' => now()->subDays(1)]);

    $result = $this->service->listForItem($item);

    expect($result->first()->id)->toBe($newest->id)
        ->and($result->last()->id)->toBe($oldest->id);
});

test('create creates a reply for comment item', function (): void {
    $item = InboxItem::factory()->comment()->create();
    $user = User::factory()->create();
    $data = new CreateReplyData(content_text: 'Thank you for your comment!');

    $result = $this->service->create($item, $user, $data);

    expect($result)->toBeInstanceOf(InboxReply::class)
        ->and($result->inbox_item_id)->toBe($item->id)
        ->and($result->replied_by_user_id)->toBe($user->id)
        ->and($result->content_text)->toBe('Thank you for your comment!')
        ->and($result->sent_at)->not->toBeNull();
});

test('create throws exception for mention item', function (): void {
    $item = InboxItem::factory()->mention()->create();
    $user = User::factory()->create();
    $data = new CreateReplyData(content_text: 'Thank you for mentioning us!');

    expect(fn () => $this->service->create($item, $user, $data))
        ->toThrow(ValidationException::class);
});

test('create persists reply in database', function (): void {
    $item = InboxItem::factory()->comment()->create();
    $user = User::factory()->create();
    $data = new CreateReplyData(content_text: 'Test reply');

    $reply = $this->service->create($item, $user, $data);

    $this->assertDatabaseHas('inbox_replies', [
        'id' => $reply->id,
        'inbox_item_id' => $item->id,
        'replied_by_user_id' => $user->id,
        'content_text' => 'Test reply',
    ]);
});

test('get returns reply by id', function (): void {
    $reply = InboxReply::factory()->create();

    $result = $this->service->get($reply->id);

    expect($result->id)->toBe($reply->id);
});

test('get throws exception for non-existent reply', function (): void {
    expect(fn () => $this->service->get('non-existent-uuid'))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

test('get loads repliedBy relationship', function (): void {
    $user = User::factory()->create();
    $reply = InboxReply::factory()->byUser($user)->create();

    $result = $this->service->get($reply->id);

    expect($result->relationLoaded('repliedBy'))->toBeTrue()
        ->and($result->repliedBy->id)->toBe($user->id);
});

test('get loads inboxItem relationship', function (): void {
    $item = InboxItem::factory()->comment()->create();
    $reply = InboxReply::factory()->forInboxItem($item)->create();

    $result = $this->service->get($reply->id);

    expect($result->relationLoaded('inboxItem'))->toBeTrue()
        ->and($result->inboxItem->id)->toBe($item->id);
});

test('markAsSent updates platform_reply_id', function (): void {
    $reply = InboxReply::factory()->create();
    $platformReplyId = 'platform_reply_123';

    $result = $this->service->markAsSent($reply, $platformReplyId);

    expect($result->platform_reply_id)->toBe($platformReplyId)
        ->and($result->isSent())->toBeTrue();
});

test('markAsSent clears failure info if present', function (): void {
    $reply = InboxReply::factory()->failed()->create();
    $platformReplyId = 'platform_reply_123';

    $result = $this->service->markAsSent($reply, $platformReplyId);

    expect($result->platform_reply_id)->toBe($platformReplyId)
        ->and($result->failed_at)->toBeNull()
        ->and($result->failure_reason)->toBeNull();
});

test('markAsFailed sets failure info', function (): void {
    $reply = InboxReply::factory()->create();
    $reason = 'API rate limit exceeded';

    $result = $this->service->markAsFailed($reply, $reason);

    expect($result->hasFailed())->toBeTrue()
        ->and($result->failed_at)->not->toBeNull()
        ->and($result->failure_reason)->toBe($reason);
});

test('multiple replies can be created for same item', function (): void {
    $item = InboxItem::factory()->comment()->create();
    $user = User::factory()->create();

    $data1 = new CreateReplyData(content_text: 'First reply');
    $data2 = new CreateReplyData(content_text: 'Second reply');
    $data3 = new CreateReplyData(content_text: 'Third reply');

    $reply1 = $this->service->create($item, $user, $data1);
    $reply2 = $this->service->create($item, $user, $data2);
    $reply3 = $this->service->create($item, $user, $data3);

    $replies = $this->service->listForItem($item);

    expect($replies)->toHaveCount(3)
        ->and($reply1->content_text)->toBe('First reply')
        ->and($reply2->content_text)->toBe('Second reply')
        ->and($reply3->content_text)->toBe('Third reply');
});
