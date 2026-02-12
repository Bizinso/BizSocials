<?php

declare(strict_types=1);

/**
 * Inbox API Feature Tests
 *
 * Tests for the inbox API endpoints.
 *
 * @see \App\Http\Controllers\Api\V1\Inbox\InboxController
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
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->forTenant($this->user->tenant)->create();
    $this->socialAccount = SocialAccount::factory()
        ->forWorkspace($this->workspace)
        ->create(['platform' => SocialPlatform::LINKEDIN]);

    // Add user as workspace owner
    WorkspaceMembership::factory()
        ->forWorkspace($this->workspace)
        ->forUser($this->user)
        ->owner()
        ->create();

    Sanctum::actingAs($this->user);
});

test('index returns paginated inbox items', function (): void {
    InboxItem::factory()
        ->count(5)
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox");

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'workspace_id',
                    'social_account_id',
                    'item_type',
                    'status',
                    'author_name',
                    'content_text',
                    'platform_created_at',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            'links',
        ]);

    expect($response->json('data'))->toHaveCount(5);
});

test('index filters by status', function (): void {
    InboxItem::factory()
        ->count(3)
        ->unread()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();
    InboxItem::factory()
        ->count(2)
        ->read()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox?status=unread");

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(3);
});

test('index filters by type', function (): void {
    InboxItem::factory()
        ->count(4)
        ->comment()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();
    InboxItem::factory()
        ->count(2)
        ->mention()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox?type=comment");

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(4);
});

test('index filters by platform', function (): void {
    $twitterAccount = SocialAccount::factory()
        ->forWorkspace($this->workspace)
        ->create(['platform' => SocialPlatform::TWITTER]);

    InboxItem::factory()
        ->count(3)
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();
    InboxItem::factory()
        ->count(2)
        ->forWorkspace($this->workspace)
        ->forSocialAccount($twitterAccount)
        ->create();

    $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox?platform=linkedin");

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(3);
});

test('index filters by assigned_to me', function (): void {
    InboxItem::factory()
        ->count(2)
        ->assigned($this->user)
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();
    InboxItem::factory()
        ->count(3)
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox?assigned_to=me");

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(2);
});

test('index requires workspace membership', function (): void {
    // User from different tenant - should get 404 (not revealing workspace exists)
    $otherUser = User::factory()->create();
    Sanctum::actingAs($otherUser);

    $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox");

    $response->assertNotFound();
});

test('index requires workspace membership for same tenant user', function (): void {
    // User from same tenant but not workspace member - should get 403
    // Use member() to ensure they don't have tenant-level admin access
    $otherUser = User::factory()->forTenant($this->user->tenant)->member()->create();
    Sanctum::actingAs($otherUser);

    $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox");

    $response->assertForbidden();
});

test('stats returns inbox statistics', function (): void {
    InboxItem::factory()
        ->count(3)
        ->unread()
        ->comment()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();
    InboxItem::factory()
        ->count(2)
        ->read()
        ->mention()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();
    InboxItem::factory()
        ->count(1)
        ->resolved()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();
    InboxItem::factory()
        ->count(1)
        ->read() // Need explicit read status
        ->assigned($this->user)
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox/stats");

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'total',
                'unread',
                'read',
                'resolved',
                'archived',
                'assigned_to_me',
                'by_type',
                'by_platform',
            ],
        ]);

    expect($response->json('data.total'))->toBe(7)
        ->and($response->json('data.unread'))->toBe(3);
});

test('show returns single inbox item', function (): void {
    $item = InboxItem::factory()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'workspace_id',
                'social_account_id',
                'item_type',
                'status',
                'author_name',
                'content_text',
            ],
        ]);

    expect($response->json('data.id'))->toBe($item->id);
});

test('show returns 404 for item in different workspace', function (): void {
    $otherWorkspace = Workspace::factory()->forTenant($this->user->tenant)->create();
    $item = InboxItem::factory()->forWorkspace($otherWorkspace)->create();

    $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}");

    $response->assertNotFound();
});

test('markRead updates item status to read', function (): void {
    $item = InboxItem::factory()
        ->unread()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/read");

    $response->assertOk();
    expect($response->json('data.status'))->toBe('read');

    $item->refresh();
    expect($item->status)->toBe(InboxItemStatus::READ);
});

test('markUnread updates item status to unread', function (): void {
    $item = InboxItem::factory()
        ->read()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/unread");

    $response->assertOk();
    expect($response->json('data.status'))->toBe('unread');
});

test('resolve updates item status to resolved', function (): void {
    $item = InboxItem::factory()
        ->read()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/resolve");

    $response->assertOk();
    expect($response->json('data.status'))->toBe('resolved')
        ->and($response->json('data.resolved_by_user_id'))->toBe($this->user->id);
});

test('resolve requires admin permission', function (): void {
    // Create user as member role in tenant (not admin) to ensure they don't have tenant-level admin access
    $editor = User::factory()->forTenant($this->user->tenant)->member()->create();
    WorkspaceMembership::factory()
        ->forWorkspace($this->workspace)
        ->forUser($editor)
        ->editor()
        ->create();

    Sanctum::actingAs($editor);

    $item = InboxItem::factory()
        ->read()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/resolve");

    $response->assertForbidden();
});

test('resolve fails for unread item', function (): void {
    $item = InboxItem::factory()
        ->unread()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/resolve");

    $response->assertStatus(422);
});

test('unresolve updates resolved item to read', function (): void {
    $item = InboxItem::factory()
        ->resolved()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/unresolve");

    $response->assertOk();
    expect($response->json('data.status'))->toBe('read');
});

test('archive updates item status to archived', function (): void {
    $item = InboxItem::factory()
        ->resolved()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/archive");

    $response->assertOk();
    expect($response->json('data.status'))->toBe('archived');
});

test('assign sets assignment info', function (): void {
    $assignee = User::factory()->forTenant($this->user->tenant)->create();
    WorkspaceMembership::factory()
        ->forWorkspace($this->workspace)
        ->forUser($assignee)
        ->editor()
        ->create();

    $item = InboxItem::factory()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/assign", [
        'user_id' => $assignee->id,
    ]);

    $response->assertOk();
    expect($response->json('data.assigned_to_user_id'))->toBe($assignee->id)
        ->and($response->json('data.assigned_to_name'))->toBe($assignee->name);
});

test('assign requires admin permission', function (): void {
    $editor = User::factory()->forTenant($this->user->tenant)->create();
    WorkspaceMembership::factory()
        ->forWorkspace($this->workspace)
        ->forUser($editor)
        ->editor()
        ->create();

    Sanctum::actingAs($editor);

    $item = InboxItem::factory()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/assign", [
        'user_id' => $this->user->id,
    ]);

    $response->assertForbidden();
});

test('assign fails for user not in workspace', function (): void {
    $outsideUser = User::factory()->forTenant($this->user->tenant)->create();

    $item = InboxItem::factory()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/assign", [
        'user_id' => $outsideUser->id,
    ]);

    $response->assertStatus(422);
});

test('unassign clears assignment info', function (): void {
    $item = InboxItem::factory()
        ->assigned($this->user)
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/unassign");

    $response->assertOk();
    expect($response->json('data.assigned_to_user_id'))->toBeNull();
});

test('bulkRead marks multiple items as read', function (): void {
    $items = InboxItem::factory()
        ->count(3)
        ->unread()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/bulk-read", [
        'item_ids' => $items->pluck('id')->toArray(),
    ]);

    $response->assertOk();
    expect($response->json('data.updated_count'))->toBe(3);
});

test('bulkResolve marks multiple items as resolved', function (): void {
    $items = InboxItem::factory()
        ->count(3)
        ->read()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/bulk-resolve", [
        'item_ids' => $items->pluck('id')->toArray(),
    ]);

    $response->assertOk();
    expect($response->json('data.updated_count'))->toBe(3);
});

test('bulkResolve requires admin permission', function (): void {
    // Create user as member role in tenant (not admin) to ensure they don't have tenant-level admin access
    $editor = User::factory()->forTenant($this->user->tenant)->member()->create();
    WorkspaceMembership::factory()
        ->forWorkspace($this->workspace)
        ->forUser($editor)
        ->editor()
        ->create();

    Sanctum::actingAs($editor);

    $items = InboxItem::factory()
        ->count(2)
        ->read()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/bulk-resolve", [
        'item_ids' => $items->pluck('id')->toArray(),
    ]);

    $response->assertForbidden();
});

test('bulk actions validate item_ids array', function (): void {
    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/bulk-read", [
        'item_ids' => [],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['item_ids']);
});
