<?php

declare(strict_types=1);

/**
 * Inbox Reply API Feature Tests
 *
 * Tests for the inbox reply API endpoints.
 *
 * @see \App\Http\Controllers\Api\V1\Inbox\InboxReplyController
 */

use App\Enums\Inbox\InboxItemType;
use App\Enums\Social\SocialPlatform;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Inbox\InboxItem;
use App\Models\Inbox\InboxReply;
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

    // Add user as workspace editor (can create content/replies)
    WorkspaceMembership::factory()
        ->forWorkspace($this->workspace)
        ->forUser($this->user)
        ->editor()
        ->create();

    Sanctum::actingAs($this->user);
});

test('index returns replies for inbox item', function (): void {
    $item = InboxItem::factory()
        ->comment()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    InboxReply::factory()->count(3)->forInboxItem($item)->create();

    $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/replies");

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'inbox_item_id',
                    'replied_by_user_id',
                    'replied_by_name',
                    'content_text',
                    'sent_at',
                ],
            ],
        ]);

    expect($response->json('data'))->toHaveCount(3);
});

test('index returns replies ordered by sent_at descending', function (): void {
    $item = InboxItem::factory()
        ->comment()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $oldest = InboxReply::factory()->forInboxItem($item)->create(['sent_at' => now()->subDays(3)]);
    $newest = InboxReply::factory()->forInboxItem($item)->create(['sent_at' => now()->subDays(1)]);

    $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/replies");

    $response->assertOk();

    $data = $response->json('data');
    expect($data[0]['id'])->toBe($newest->id)
        ->and($data[1]['id'])->toBe($oldest->id);
});

test('index requires workspace membership', function (): void {
    // User from different tenant - should get 404 (not revealing workspace exists)
    $otherUser = User::factory()->create();
    Sanctum::actingAs($otherUser);

    $item = InboxItem::factory()
        ->comment()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/replies");

    $response->assertNotFound();
});

test('index requires workspace membership for same tenant user', function (): void {
    // User from same tenant but not workspace member - should get 403
    $otherUser = User::factory()->forTenant($this->user->tenant)->member()->create();
    Sanctum::actingAs($otherUser);

    $item = InboxItem::factory()
        ->comment()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/replies");

    $response->assertForbidden();
});

test('index returns 404 for item in different workspace', function (): void {
    $otherWorkspace = Workspace::factory()->forTenant($this->user->tenant)->create();
    $item = InboxItem::factory()->comment()->forWorkspace($otherWorkspace)->create();

    $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/replies");

    $response->assertNotFound();
});

test('store creates reply for comment item', function (): void {
    $item = InboxItem::factory()
        ->comment()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/replies", [
        'content_text' => 'Thank you for your comment!',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'inbox_item_id',
                'replied_by_user_id',
                'replied_by_name',
                'content_text',
                'sent_at',
            ],
        ]);

    expect($response->json('data.content_text'))->toBe('Thank you for your comment!')
        ->and($response->json('data.replied_by_user_id'))->toBe($this->user->id);
});

test('store persists reply in database', function (): void {
    $item = InboxItem::factory()
        ->comment()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/replies", [
        'content_text' => 'Test reply content',
    ]);

    $response->assertCreated();

    $this->assertDatabaseHas('inbox_replies', [
        'inbox_item_id' => $item->id,
        'replied_by_user_id' => $this->user->id,
        'content_text' => 'Test reply content',
    ]);
});

test('store fails for mention item', function (): void {
    $item = InboxItem::factory()
        ->mention()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/replies", [
        'content_text' => 'Thank you for mentioning us!',
    ]);

    $response->assertForbidden();
});

test('store requires content_text', function (): void {
    $item = InboxItem::factory()
        ->comment()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/replies", [
        'content_text' => '',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['content_text']);
});

test('store validates content_text max length', function (): void {
    $item = InboxItem::factory()
        ->comment()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/replies", [
        'content_text' => str_repeat('a', 1001),
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['content_text']);
});

test('store requires editor permission', function (): void {
    $viewer = User::factory()->forTenant($this->user->tenant)->create();
    WorkspaceMembership::factory()
        ->forWorkspace($this->workspace)
        ->forUser($viewer)
        ->viewer()
        ->create();

    Sanctum::actingAs($viewer);

    $item = InboxItem::factory()
        ->comment()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/replies", [
        'content_text' => 'Test reply',
    ]);

    $response->assertForbidden();
});

test('store works for admin users', function (): void {
    $admin = User::factory()->forTenant($this->user->tenant)->create();
    WorkspaceMembership::factory()
        ->forWorkspace($this->workspace)
        ->forUser($admin)
        ->admin()
        ->create();

    Sanctum::actingAs($admin);

    $item = InboxItem::factory()
        ->comment()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/replies", [
        'content_text' => 'Admin reply',
    ]);

    $response->assertCreated();
});

test('store works for owner users', function (): void {
    $owner = User::factory()->forTenant($this->user->tenant)->create();
    WorkspaceMembership::factory()
        ->forWorkspace($this->workspace)
        ->forUser($owner)
        ->owner()
        ->create();

    Sanctum::actingAs($owner);

    $item = InboxItem::factory()
        ->comment()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/replies", [
        'content_text' => 'Owner reply',
    ]);

    $response->assertCreated();
});

test('multiple replies can be created for same item', function (): void {
    $item = InboxItem::factory()
        ->comment()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/replies", [
        'content_text' => 'First reply',
    ])->assertCreated();

    $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/replies", [
        'content_text' => 'Second reply',
    ])->assertCreated();

    $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/replies", [
        'content_text' => 'Third reply',
    ])->assertCreated();

    $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/replies");

    expect($response->json('data'))->toHaveCount(3);
});

test('reply includes replied_by_name', function (): void {
    $item = InboxItem::factory()
        ->comment()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/replies", [
        'content_text' => 'Test reply',
    ]);

    $response->assertCreated();
    expect($response->json('data.replied_by_name'))->toBe($this->user->name);
});

test('viewer can list replies', function (): void {
    $viewer = User::factory()->forTenant($this->user->tenant)->create();
    WorkspaceMembership::factory()
        ->forWorkspace($this->workspace)
        ->forUser($viewer)
        ->viewer()
        ->create();

    Sanctum::actingAs($viewer);

    $item = InboxItem::factory()
        ->comment()
        ->forWorkspace($this->workspace)
        ->forSocialAccount($this->socialAccount)
        ->create();

    InboxReply::factory()->count(2)->forInboxItem($item)->create();

    $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$item->id}/replies");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});
