<?php

declare(strict_types=1);

/**
 * InboxItem Model Unit Tests
 *
 * Tests for the InboxItem model which represents comments and mentions
 * from social platforms.
 *
 * @see \App\Models\Inbox\InboxItem
 */

use App\Enums\Inbox\InboxItemStatus;
use App\Enums\Inbox\InboxItemType;
use App\Models\Content\PostTarget;
use App\Models\Inbox\InboxItem;
use App\Models\Inbox\InboxReply;
use App\Models\Social\SocialAccount;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;

test('has correct table name', function (): void {
    $item = new InboxItem();

    expect($item->getTable())->toBe('inbox_items');
});

test('uses uuid primary key', function (): void {
    $item = InboxItem::factory()->create();

    expect($item->id)->not->toBeNull()
        ->and(strlen($item->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $item = new InboxItem();
    $fillable = $item->getFillable();

    expect($fillable)->toContain('workspace_id')
        ->and($fillable)->toContain('social_account_id')
        ->and($fillable)->toContain('post_target_id')
        ->and($fillable)->toContain('item_type')
        ->and($fillable)->toContain('status')
        ->and($fillable)->toContain('platform_item_id')
        ->and($fillable)->toContain('platform_post_id')
        ->and($fillable)->toContain('author_name')
        ->and($fillable)->toContain('author_username')
        ->and($fillable)->toContain('author_profile_url')
        ->and($fillable)->toContain('author_avatar_url')
        ->and($fillable)->toContain('content_text')
        ->and($fillable)->toContain('platform_created_at')
        ->and($fillable)->toContain('assigned_to_user_id')
        ->and($fillable)->toContain('assigned_at')
        ->and($fillable)->toContain('resolved_at')
        ->and($fillable)->toContain('resolved_by_user_id')
        ->and($fillable)->toContain('metadata');
});

test('item_type casts to enum', function (): void {
    $item = InboxItem::factory()->comment()->create();

    expect($item->item_type)->toBeInstanceOf(InboxItemType::class)
        ->and($item->item_type)->toBe(InboxItemType::COMMENT);
});

test('status casts to enum', function (): void {
    $item = InboxItem::factory()->unread()->create();

    expect($item->status)->toBeInstanceOf(InboxItemStatus::class)
        ->and($item->status)->toBe(InboxItemStatus::UNREAD);
});

test('platform_created_at casts to datetime', function (): void {
    $item = InboxItem::factory()->create();

    expect($item->platform_created_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('assigned_at casts to datetime', function (): void {
    $user = User::factory()->create();
    $item = InboxItem::factory()->assigned($user)->create();

    expect($item->assigned_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('resolved_at casts to datetime', function (): void {
    $item = InboxItem::factory()->resolved()->create();

    expect($item->resolved_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('metadata casts to array', function (): void {
    $metadata = ['key' => 'value', 'nested' => ['data' => true]];
    $item = InboxItem::factory()->withMetadata($metadata)->create();

    expect($item->metadata)->toBeArray()
        ->and($item->metadata['key'])->toBe('value');
});

test('workspace relationship returns belongs to', function (): void {
    $item = new InboxItem();

    expect($item->workspace())->toBeInstanceOf(BelongsTo::class);
});

test('workspace relationship works correctly', function (): void {
    $workspace = Workspace::factory()->create();
    $item = InboxItem::factory()->forWorkspace($workspace)->create();

    expect($item->workspace)->toBeInstanceOf(Workspace::class)
        ->and($item->workspace->id)->toBe($workspace->id);
});

test('socialAccount relationship returns belongs to', function (): void {
    $item = new InboxItem();

    expect($item->socialAccount())->toBeInstanceOf(BelongsTo::class);
});

test('socialAccount relationship works correctly', function (): void {
    $socialAccount = SocialAccount::factory()->create();
    $item = InboxItem::factory()->forSocialAccount($socialAccount)->create();

    expect($item->socialAccount)->toBeInstanceOf(SocialAccount::class)
        ->and($item->socialAccount->id)->toBe($socialAccount->id);
});

test('postTarget relationship returns belongs to', function (): void {
    $item = new InboxItem();

    expect($item->postTarget())->toBeInstanceOf(BelongsTo::class);
});

test('postTarget relationship works correctly', function (): void {
    $postTarget = PostTarget::factory()->create();
    $item = InboxItem::factory()->forPostTarget($postTarget)->create();

    expect($item->postTarget)->toBeInstanceOf(PostTarget::class)
        ->and($item->postTarget->id)->toBe($postTarget->id);
});

test('assignedTo relationship returns belongs to', function (): void {
    $item = new InboxItem();

    expect($item->assignedTo())->toBeInstanceOf(BelongsTo::class);
});

test('assignedTo relationship works correctly', function (): void {
    $user = User::factory()->create();
    $item = InboxItem::factory()->assigned($user)->create();

    expect($item->assignedTo)->toBeInstanceOf(User::class)
        ->and($item->assignedTo->id)->toBe($user->id);
});

test('resolvedBy relationship returns belongs to', function (): void {
    $item = new InboxItem();

    expect($item->resolvedBy())->toBeInstanceOf(BelongsTo::class);
});

test('resolvedBy relationship works correctly', function (): void {
    $item = InboxItem::factory()->resolved()->create();

    expect($item->resolvedBy)->toBeInstanceOf(User::class);
});

test('replies relationship returns has many', function (): void {
    $item = new InboxItem();

    expect($item->replies())->toBeInstanceOf(HasMany::class);
});

test('replies relationship works correctly', function (): void {
    $item = InboxItem::factory()->comment()->create();
    InboxReply::factory()->count(3)->forInboxItem($item)->create();

    expect($item->replies)->toHaveCount(3);
});

test('scope forWorkspace filters correctly', function (): void {
    $workspace1 = Workspace::factory()->create();
    $workspace2 = Workspace::factory()->create();

    InboxItem::factory()->count(3)->forWorkspace($workspace1)->create();
    InboxItem::factory()->count(2)->forWorkspace($workspace2)->create();

    $items = InboxItem::forWorkspace($workspace1->id)->get();

    expect($items)->toHaveCount(3)
        ->and($items->every(fn ($i) => $i->workspace_id === $workspace1->id))->toBeTrue();
});

test('scope unread filters unread items', function (): void {
    InboxItem::factory()->unread()->create();
    InboxItem::factory()->unread()->create();
    InboxItem::factory()->read()->create();

    $items = InboxItem::unread()->get();

    expect($items)->toHaveCount(2);
});

test('scope active filters non-archived items', function (): void {
    InboxItem::factory()->unread()->create();
    InboxItem::factory()->read()->create();
    InboxItem::factory()->resolved()->create();
    InboxItem::factory()->archived()->create();

    $items = InboxItem::active()->get();

    expect($items)->toHaveCount(3);
});

test('scope withStatus filters by status', function (): void {
    InboxItem::factory()->unread()->create();
    InboxItem::factory()->read()->create();
    InboxItem::factory()->read()->create();
    InboxItem::factory()->resolved()->create();

    $items = InboxItem::withStatus(InboxItemStatus::READ)->get();

    expect($items)->toHaveCount(2);
});

test('scope ofType filters by type', function (): void {
    InboxItem::factory()->comment()->create();
    InboxItem::factory()->comment()->create();
    InboxItem::factory()->mention()->create();

    $comments = InboxItem::ofType(InboxItemType::COMMENT)->get();
    $mentions = InboxItem::ofType(InboxItemType::MENTION)->get();

    expect($comments)->toHaveCount(2)
        ->and($mentions)->toHaveCount(1);
});

test('scope assignedToUser filters by user', function (): void {
    $user = User::factory()->create();
    InboxItem::factory()->assigned($user)->create();
    InboxItem::factory()->assigned($user)->create();
    InboxItem::factory()->create();

    $items = InboxItem::assignedToUser($user->id)->get();

    expect($items)->toHaveCount(2);
});

test('scope needsArchiving filters resolved items older than specified days', function (): void {
    // Create resolved item from 40 days ago
    $oldResolved = InboxItem::factory()->create([
        'status' => InboxItemStatus::RESOLVED,
        'resolved_at' => now()->subDays(40),
    ]);

    // Create resolved item from 10 days ago
    $recentResolved = InboxItem::factory()->create([
        'status' => InboxItemStatus::RESOLVED,
        'resolved_at' => now()->subDays(10),
    ]);

    // Create non-resolved item
    InboxItem::factory()->read()->create();

    $items = InboxItem::needsArchiving(30)->get();

    expect($items)->toHaveCount(1)
        ->and($items->first()->id)->toBe($oldResolved->id);
});

test('isUnread returns correct value', function (): void {
    $unread = InboxItem::factory()->unread()->create();
    $read = InboxItem::factory()->read()->create();

    expect($unread->isUnread())->toBeTrue()
        ->and($read->isUnread())->toBeFalse();
});

test('isResolved returns correct value', function (): void {
    $resolved = InboxItem::factory()->resolved()->create();
    $read = InboxItem::factory()->read()->create();

    expect($resolved->isResolved())->toBeTrue()
        ->and($read->isResolved())->toBeFalse();
});

test('isArchived returns correct value', function (): void {
    $archived = InboxItem::factory()->archived()->create();
    $read = InboxItem::factory()->read()->create();

    expect($archived->isArchived())->toBeTrue()
        ->and($read->isArchived())->toBeFalse();
});

test('canReply returns correct value based on type', function (): void {
    $comment = InboxItem::factory()->comment()->create();
    $mention = InboxItem::factory()->mention()->create();

    expect($comment->canReply())->toBeTrue()
        ->and($mention->canReply())->toBeFalse();
});

test('markAsRead updates status from UNREAD', function (): void {
    $item = InboxItem::factory()->unread()->create();

    $item->markAsRead();

    expect($item->status)->toBe(InboxItemStatus::READ);
});

test('markAsRead does not update from invalid status', function (): void {
    $item = InboxItem::factory()->resolved()->create();

    $item->markAsRead();

    // Should not change since RESOLVED -> READ is allowed (reopen)
    expect($item->status)->toBe(InboxItemStatus::READ);
});

test('markAsResolved updates status and sets resolved info', function (): void {
    $item = InboxItem::factory()->read()->create();
    $user = User::factory()->create();

    $item->markAsResolved($user);

    expect($item->status)->toBe(InboxItemStatus::RESOLVED)
        ->and($item->resolved_at)->not->toBeNull()
        ->and($item->resolved_by_user_id)->toBe($user->id);
});

test('markAsResolved does not update from UNREAD', function (): void {
    $item = InboxItem::factory()->unread()->create();
    $user = User::factory()->create();

    $item->markAsResolved($user);

    expect($item->status)->toBe(InboxItemStatus::UNREAD);
});

test('archive updates status', function (): void {
    $item = InboxItem::factory()->resolved()->create();

    $item->archive();

    expect($item->status)->toBe(InboxItemStatus::ARCHIVED);
});

test('reopen updates status and clears resolved info', function (): void {
    $item = InboxItem::factory()->resolved()->create();

    $item->reopen();

    expect($item->status)->toBe(InboxItemStatus::READ)
        ->and($item->resolved_at)->toBeNull()
        ->and($item->resolved_by_user_id)->toBeNull();
});

test('reopen works from ARCHIVED status', function (): void {
    $item = InboxItem::factory()->archived()->create();

    $item->reopen();

    expect($item->status)->toBe(InboxItemStatus::READ);
});

test('assignTo sets assignment info', function (): void {
    $item = InboxItem::factory()->create();
    $user = User::factory()->create();

    $item->assignTo($user);

    expect($item->assigned_to_user_id)->toBe($user->id)
        ->and($item->assigned_at)->not->toBeNull();
});

test('unassign clears assignment info', function (): void {
    $user = User::factory()->create();
    $item = InboxItem::factory()->assigned($user)->create();

    $item->unassign();

    expect($item->assigned_to_user_id)->toBeNull()
        ->and($item->assigned_at)->toBeNull();
});

test('getReplyCount returns correct count', function (): void {
    $item = InboxItem::factory()->comment()->create();
    InboxReply::factory()->count(3)->forInboxItem($item)->create();

    expect($item->getReplyCount())->toBe(3);
});

test('getReplyCount returns zero for no replies', function (): void {
    $item = InboxItem::factory()->comment()->create();

    expect($item->getReplyCount())->toBe(0);
});

test('unique constraint on social_account_id and platform_item_id', function (): void {
    $socialAccount = SocialAccount::factory()->create();
    $platformItemId = 'unique_item_123';

    InboxItem::factory()
        ->forSocialAccount($socialAccount)
        ->create(['platform_item_id' => $platformItemId]);

    expect(fn () => InboxItem::factory()
        ->forSocialAccount($socialAccount)
        ->create(['platform_item_id' => $platformItemId])
    )->toThrow(QueryException::class);
});

test('factory creates valid model', function (): void {
    $item = InboxItem::factory()->create();

    expect($item)->toBeInstanceOf(InboxItem::class)
        ->and($item->id)->not->toBeNull()
        ->and($item->workspace_id)->not->toBeNull()
        ->and($item->social_account_id)->not->toBeNull()
        ->and($item->item_type)->toBeInstanceOf(InboxItemType::class)
        ->and($item->status)->toBeInstanceOf(InboxItemStatus::class)
        ->and($item->author_name)->not->toBeNull()
        ->and($item->content_text)->not->toBeNull();
});

test('factory comment state works correctly', function (): void {
    $item = InboxItem::factory()->comment()->create();

    expect($item->item_type)->toBe(InboxItemType::COMMENT);
});

test('factory mention state works correctly', function (): void {
    $item = InboxItem::factory()->mention()->create();

    expect($item->item_type)->toBe(InboxItemType::MENTION);
});

test('factory resolved state works correctly', function (): void {
    $item = InboxItem::factory()->resolved()->create();

    expect($item->status)->toBe(InboxItemStatus::RESOLVED)
        ->and($item->resolved_at)->not->toBeNull()
        ->and($item->resolved_by_user_id)->not->toBeNull();
});
