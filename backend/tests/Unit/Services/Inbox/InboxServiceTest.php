<?php

declare(strict_types=1);

/**
 * InboxService Unit Tests
 *
 * Tests for the InboxService which handles inbox item management.
 *
 * @see \App\Services\Inbox\InboxService
 */

use App\Enums\Inbox\InboxItemStatus;
use App\Enums\Inbox\InboxItemType;
use App\Enums\Social\SocialPlatform;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Inbox\InboxItem;
use App\Models\Social\SocialAccount;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Models\Workspace\WorkspaceMembership;
use App\Services\Inbox\InboxService;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->service = app(InboxService::class);
});

test('list returns paginated inbox items for workspace', function (): void {
    $workspace = Workspace::factory()->create();
    $socialAccount = SocialAccount::factory()->forWorkspace($workspace)->create();

    InboxItem::factory()
        ->count(5)
        ->forWorkspace($workspace)
        ->forSocialAccount($socialAccount)
        ->create();

    // Create items for another workspace (should not be included)
    $otherWorkspace = Workspace::factory()->create();
    InboxItem::factory()->count(3)->forWorkspace($otherWorkspace)->create();

    $result = $this->service->list($workspace);

    expect($result->total())->toBe(5)
        ->and($result->items())->each(fn ($item) => $item->workspace_id->toBe($workspace->id));
});

test('list filters by status', function (): void {
    $workspace = Workspace::factory()->create();

    InboxItem::factory()->count(3)->unread()->forWorkspace($workspace)->create();
    InboxItem::factory()->count(2)->read()->forWorkspace($workspace)->create();
    InboxItem::factory()->count(1)->resolved()->forWorkspace($workspace)->create();

    $result = $this->service->list($workspace, ['status' => 'unread']);

    expect($result->total())->toBe(3);
});

test('list filters by type', function (): void {
    $workspace = Workspace::factory()->create();

    InboxItem::factory()->count(4)->comment()->forWorkspace($workspace)->create();
    InboxItem::factory()->count(2)->mention()->forWorkspace($workspace)->create();

    $result = $this->service->list($workspace, ['type' => 'comment']);

    expect($result->total())->toBe(4);
});

test('list filters by platform', function (): void {
    $workspace = Workspace::factory()->create();

    $linkedinAccount = SocialAccount::factory()
        ->forWorkspace($workspace)
        ->create(['platform' => SocialPlatform::LINKEDIN]);
    $twitterAccount = SocialAccount::factory()
        ->forWorkspace($workspace)
        ->create(['platform' => SocialPlatform::TWITTER]);

    InboxItem::factory()
        ->count(3)
        ->forWorkspace($workspace)
        ->forSocialAccount($linkedinAccount)
        ->create();
    InboxItem::factory()
        ->count(2)
        ->forWorkspace($workspace)
        ->forSocialAccount($twitterAccount)
        ->create();

    $result = $this->service->list($workspace, ['platform' => 'linkedin']);

    expect($result->total())->toBe(3);
});

test('list filters by assigned user', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create();

    InboxItem::factory()->count(3)->assigned($user)->forWorkspace($workspace)->create();
    InboxItem::factory()->count(2)->forWorkspace($workspace)->create();

    $result = $this->service->list($workspace, ['assigned_to' => $user->id]);

    expect($result->total())->toBe(3);
});

test('list filters by assigned to me', function (): void {
    $workspace = Workspace::factory()->create();
    $currentUser = User::factory()->create();
    $otherUser = User::factory()->create();

    InboxItem::factory()->count(2)->assigned($currentUser)->forWorkspace($workspace)->create();
    InboxItem::factory()->count(3)->assigned($otherUser)->forWorkspace($workspace)->create();

    $result = $this->service->list($workspace, [
        'assigned_to' => 'me',
        'current_user_id' => $currentUser->id,
    ]);

    expect($result->total())->toBe(2);
});

test('list filters by search in content', function (): void {
    $workspace = Workspace::factory()->create();

    InboxItem::factory()->forWorkspace($workspace)->create(['content_text' => 'Great product launch!']);
    InboxItem::factory()->forWorkspace($workspace)->create(['content_text' => 'Nice work team']);
    InboxItem::factory()->forWorkspace($workspace)->create(['content_text' => 'Product is amazing']);

    $result = $this->service->list($workspace, ['search' => 'product']);

    expect($result->total())->toBe(2);
});

test('list filters by date range', function (): void {
    $workspace = Workspace::factory()->create();

    InboxItem::factory()->forWorkspace($workspace)->create([
        'platform_created_at' => now()->subDays(5),
    ]);
    InboxItem::factory()->forWorkspace($workspace)->create([
        'platform_created_at' => now()->subDays(3),
    ]);
    InboxItem::factory()->forWorkspace($workspace)->create([
        'platform_created_at' => now()->subDays(1),
    ]);

    $result = $this->service->list($workspace, [
        'date_from' => now()->subDays(4)->toDateString(),
    ]);

    expect($result->total())->toBe(2);
});

test('get returns inbox item by id', function (): void {
    $item = InboxItem::factory()->create();

    $result = $this->service->get($item->id);

    expect($result->id)->toBe($item->id);
});

test('get throws exception for non-existent item', function (): void {
    expect(fn () => $this->service->get('non-existent-uuid'))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

test('getByWorkspace returns inbox item within workspace', function (): void {
    $workspace = Workspace::factory()->create();
    $item = InboxItem::factory()->forWorkspace($workspace)->create();

    $result = $this->service->getByWorkspace($workspace, $item->id);

    expect($result->id)->toBe($item->id);
});

test('getByWorkspace throws exception for item in different workspace', function (): void {
    $workspace1 = Workspace::factory()->create();
    $workspace2 = Workspace::factory()->create();
    $item = InboxItem::factory()->forWorkspace($workspace2)->create();

    expect(fn () => $this->service->getByWorkspace($workspace1, $item->id))
        ->toThrow(ValidationException::class);
});

test('markAsRead updates unread item to read', function (): void {
    $item = InboxItem::factory()->unread()->create();

    $result = $this->service->markAsRead($item);

    expect($result->status)->toBe(InboxItemStatus::READ);
});

test('markAsRead throws exception for archived item', function (): void {
    $item = InboxItem::factory()->archived()->create();

    expect(fn () => $this->service->markAsRead($item))
        ->toThrow(ValidationException::class);
});

test('markAsUnread updates read item to unread', function (): void {
    $item = InboxItem::factory()->read()->create();

    $result = $this->service->markAsUnread($item);

    expect($result->status)->toBe(InboxItemStatus::UNREAD);
});

test('markAsUnread throws exception for archived item', function (): void {
    $item = InboxItem::factory()->archived()->create();

    expect(fn () => $this->service->markAsUnread($item))
        ->toThrow(ValidationException::class);
});

test('resolve updates read item to resolved', function (): void {
    $item = InboxItem::factory()->read()->create();
    $user = User::factory()->create();

    $result = $this->service->resolve($item, $user);

    expect($result->status)->toBe(InboxItemStatus::RESOLVED)
        ->and($result->resolved_by_user_id)->toBe($user->id)
        ->and($result->resolved_at)->not->toBeNull();
});

test('resolve throws exception for unread item', function (): void {
    $item = InboxItem::factory()->unread()->create();
    $user = User::factory()->create();

    expect(fn () => $this->service->resolve($item, $user))
        ->toThrow(ValidationException::class);
});

test('resolve throws exception for archived item', function (): void {
    $item = InboxItem::factory()->archived()->create();
    $user = User::factory()->create();

    expect(fn () => $this->service->resolve($item, $user))
        ->toThrow(ValidationException::class);
});

test('unresolve updates resolved item to read', function (): void {
    $item = InboxItem::factory()->resolved()->create();

    $result = $this->service->unresolve($item);

    expect($result->status)->toBe(InboxItemStatus::READ)
        ->and($result->resolved_at)->toBeNull()
        ->and($result->resolved_by_user_id)->toBeNull();
});

test('unresolve throws exception for archived item', function (): void {
    $item = InboxItem::factory()->archived()->create();

    expect(fn () => $this->service->unresolve($item))
        ->toThrow(ValidationException::class);
});

test('archive updates item status to archived', function (): void {
    $item = InboxItem::factory()->resolved()->create();

    $result = $this->service->archive($item);

    expect($result->status)->toBe(InboxItemStatus::ARCHIVED);
});

test('archive throws exception for already archived item', function (): void {
    $item = InboxItem::factory()->archived()->create();

    expect(fn () => $this->service->archive($item))
        ->toThrow(ValidationException::class);
});

test('assign sets assignment info', function (): void {
    $item = InboxItem::factory()->create();
    $user = User::factory()->create();

    $result = $this->service->assign($item, $user);

    expect($result->assigned_to_user_id)->toBe($user->id)
        ->and($result->assigned_at)->not->toBeNull();
});

test('unassign clears assignment info', function (): void {
    $user = User::factory()->create();
    $item = InboxItem::factory()->assigned($user)->create();

    $result = $this->service->unassign($item);

    expect($result->assigned_to_user_id)->toBeNull()
        ->and($result->assigned_at)->toBeNull();
});

test('getStats returns correct statistics', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create();

    $linkedinAccount = SocialAccount::factory()
        ->forWorkspace($workspace)
        ->create(['platform' => SocialPlatform::LINKEDIN]);

    // 3 unread comments
    InboxItem::factory()->count(3)->unread()->comment()->forWorkspace($workspace)->forSocialAccount($linkedinAccount)->create();
    // 2 read mentions
    InboxItem::factory()->count(2)->read()->mention()->forWorkspace($workspace)->forSocialAccount($linkedinAccount)->create();
    // 1 resolved comment
    InboxItem::factory()->count(1)->resolved()->comment()->forWorkspace($workspace)->forSocialAccount($linkedinAccount)->create();
    // 1 archived comment
    InboxItem::factory()->count(1)->archived()->comment()->forWorkspace($workspace)->forSocialAccount($linkedinAccount)->create();
    // 2 assigned read comments
    InboxItem::factory()->count(2)->read()->comment()->assigned($user)->forWorkspace($workspace)->forSocialAccount($linkedinAccount)->create();

    $stats = $this->service->getStats($workspace, $user->id);

    // Total: 3 + 2 + 1 + 1 + 2 = 9
    // Unread: 3
    // Read: 2 + 2 = 4
    // Resolved: 1
    // Archived: 1
    // Comments: 3 + 1 + 1 + 2 = 7
    // Mentions: 2
    expect($stats->total)->toBe(9)
        ->and($stats->unread)->toBe(3)
        ->and($stats->read)->toBe(4)
        ->and($stats->resolved)->toBe(1)
        ->and($stats->archived)->toBe(1)
        ->and($stats->assigned_to_me)->toBe(2)
        ->and($stats->by_type['comment'])->toBe(7)
        ->and($stats->by_type['mention'])->toBe(2)
        ->and($stats->by_platform['linkedin'])->toBe(9);
});

test('bulkMarkAsRead updates multiple items', function (): void {
    $workspace = Workspace::factory()->create();

    $items = InboxItem::factory()
        ->count(3)
        ->unread()
        ->forWorkspace($workspace)
        ->create();

    $itemIds = $items->pluck('id')->toArray();

    $count = $this->service->bulkMarkAsRead($workspace, $itemIds);

    expect($count)->toBe(3);

    foreach ($items as $item) {
        $item->refresh();
        expect($item->status)->toBe(InboxItemStatus::READ);
    }
});

test('bulkMarkAsRead only updates unread items', function (): void {
    $workspace = Workspace::factory()->create();

    $unreadItems = InboxItem::factory()->count(2)->unread()->forWorkspace($workspace)->create();
    $readItems = InboxItem::factory()->count(2)->read()->forWorkspace($workspace)->create();

    $itemIds = $unreadItems->pluck('id')->merge($readItems->pluck('id'))->toArray();

    $count = $this->service->bulkMarkAsRead($workspace, $itemIds);

    expect($count)->toBe(2);
});

test('bulkResolve updates multiple read items', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create();

    $items = InboxItem::factory()
        ->count(3)
        ->read()
        ->forWorkspace($workspace)
        ->create();

    $itemIds = $items->pluck('id')->toArray();

    $count = $this->service->bulkResolve($workspace, $itemIds, $user);

    expect($count)->toBe(3);

    foreach ($items as $item) {
        $item->refresh();
        expect($item->status)->toBe(InboxItemStatus::RESOLVED)
            ->and($item->resolved_by_user_id)->toBe($user->id);
    }
});

test('bulkResolve only updates read items', function (): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create();

    $readItems = InboxItem::factory()->count(2)->read()->forWorkspace($workspace)->create();
    $unreadItems = InboxItem::factory()->count(2)->unread()->forWorkspace($workspace)->create();

    $itemIds = $readItems->pluck('id')->merge($unreadItems->pluck('id'))->toArray();

    $count = $this->service->bulkResolve($workspace, $itemIds, $user);

    expect($count)->toBe(2);
});

test('bulkArchive updates multiple items', function (): void {
    $workspace = Workspace::factory()->create();

    $items = InboxItem::factory()
        ->count(3)
        ->resolved()
        ->forWorkspace($workspace)
        ->create();

    $itemIds = $items->pluck('id')->toArray();

    $count = $this->service->bulkArchive($workspace, $itemIds);

    expect($count)->toBe(3);

    foreach ($items as $item) {
        $item->refresh();
        expect($item->status)->toBe(InboxItemStatus::ARCHIVED);
    }
});
