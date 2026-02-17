<?php

declare(strict_types=1);

use App\Enums\Inbox\InboxItemType;
use App\Enums\Social\SocialPlatform;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Inbox\InboxItem;
use App\Models\Inbox\InboxReply;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->workspace = Workspace::factory()->create(['tenant_id' => $this->tenant->id]);
    
    // Use the proper method to add workspace member
    $this->workspace->addMember($this->user, WorkspaceRole::ADMIN);
});

test('can list replies for an inbox item', function () {
    $socialAccount = SocialAccount::factory()->create([
        'workspace_id' => $this->workspace->id,
        'platform' => SocialPlatform::FACEBOOK,
    ]);
    
    $inboxItem = InboxItem::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $socialAccount->id,
        'item_type' => InboxItemType::COMMENT,
    ]);
    
    InboxReply::factory()->count(3)->create([
        'inbox_item_id' => $inboxItem->id,
        'replied_by_user_id' => $this->user->id,
    ]);
    
    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$inboxItem->id}/replies");
    
    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can create a reply to an inbox item', function () {
    $socialAccount = SocialAccount::factory()->create([
        'workspace_id' => $this->workspace->id,
        'platform' => SocialPlatform::FACEBOOK,
        'access_token' => 'test-token',
    ]);
    
    $inboxItem = InboxItem::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $socialAccount->id,
        'item_type' => InboxItemType::COMMENT,
        'platform_item_id' => 'fb_comment_123',
    ]);
    
    // Mock the Facebook API call
    Http::fake([
        'https://graph.facebook.com/*' => Http::response([
            'id' => 'fb_reply_456',
        ], 200),
    ]);
    
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$inboxItem->id}/replies", [
            'content_text' => 'Test reply message',
        ]);
    
    $response->assertCreated()
        ->assertJsonPath('data.content_text', 'Test reply message')
        ->assertJsonPath('data.platform_reply_id', 'fb_reply_456');
    
    $this->assertDatabaseHas('inbox_replies', [
        'inbox_item_id' => $inboxItem->id,
        'content_text' => 'Test reply message',
        'platform_reply_id' => 'fb_reply_456',
        'replied_by_user_id' => $this->user->id,
    ]);
});

test('cannot create reply without access token', function () {
    $socialAccount = SocialAccount::factory()->create([
        'workspace_id' => $this->workspace->id,
        'platform' => SocialPlatform::FACEBOOK,
        'access_token' => null, // No token
    ]);
    
    $inboxItem = InboxItem::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $socialAccount->id,
        'item_type' => InboxItemType::COMMENT,
    ]);
    
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$inboxItem->id}/replies", [
            'content_text' => 'Test reply',
        ]);
    
    $response->assertStatus(422);
});

test('cannot create reply to non-replyable item type', function () {
    $socialAccount = SocialAccount::factory()->create([
        'workspace_id' => $this->workspace->id,
        'platform' => SocialPlatform::FACEBOOK,
        'access_token' => 'test-token',
    ]);
    
    $inboxItem = InboxItem::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $socialAccount->id,
        'item_type' => InboxItemType::STORY_MENTION, // Assuming this cannot be replied to
    ]);
    
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$inboxItem->id}/replies", [
            'content_text' => 'Test reply',
        ]);
    
    $response->assertStatus(422);
});

test('cannot access inbox item from different workspace', function () {
    $otherWorkspace = Workspace::factory()->create(['tenant_id' => $this->tenant->id]);
    
    $socialAccount = SocialAccount::factory()->create([
        'workspace_id' => $otherWorkspace->id,
        'platform' => SocialPlatform::FACEBOOK,
    ]);
    
    $inboxItem = InboxItem::factory()->create([
        'workspace_id' => $otherWorkspace->id,
        'social_account_id' => $socialAccount->id,
    ]);
    
    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$inboxItem->id}/replies");
    
    $response->assertNotFound();
});

test('reply validation requires content_text', function () {
    $socialAccount = SocialAccount::factory()->create([
        'workspace_id' => $this->workspace->id,
        'platform' => SocialPlatform::FACEBOOK,
        'access_token' => 'test-token',
    ]);
    
    $inboxItem = InboxItem::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $socialAccount->id,
        'item_type' => InboxItemType::COMMENT,
    ]);
    
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$inboxItem->id}/replies", [
            // Missing content_text
        ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['content_text']);
});

test('reply is stored with correct timestamp', function () {
    $socialAccount = SocialAccount::factory()->create([
        'workspace_id' => $this->workspace->id,
        'platform' => SocialPlatform::FACEBOOK,
        'access_token' => 'test-token',
    ]);
    
    $inboxItem = InboxItem::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $socialAccount->id,
        'item_type' => InboxItemType::COMMENT,
        'platform_item_id' => 'fb_comment_123',
    ]);
    
    Http::fake([
        'https://graph.facebook.com/*' => Http::response(['id' => 'fb_reply_456'], 200),
    ]);
    
    $beforeTime = now();
    
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$inboxItem->id}/replies", [
            'content_text' => 'Test reply',
        ]);
    
    $afterTime = now();
    
    $response->assertCreated();
    
    $reply = InboxReply::where('inbox_item_id', $inboxItem->id)->first();
    expect($reply->sent_at)->toBeGreaterThanOrEqual($beforeTime)
        ->and($reply->sent_at)->toBeLessThanOrEqual($afterTime);
});

test('reply tracks the user who created it', function () {
    $socialAccount = SocialAccount::factory()->create([
        'workspace_id' => $this->workspace->id,
        'platform' => SocialPlatform::FACEBOOK,
        'access_token' => 'test-token',
    ]);
    
    $inboxItem = InboxItem::factory()->create([
        'workspace_id' => $this->workspace->id,
        'social_account_id' => $socialAccount->id,
        'item_type' => InboxItemType::COMMENT,
        'platform_item_id' => 'fb_comment_123',
    ]);
    
    Http::fake([
        'https://graph.facebook.com/*' => Http::response(['id' => 'fb_reply_456'], 200),
    ]);
    
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/workspaces/{$this->workspace->id}/inbox/{$inboxItem->id}/replies", [
            'content_text' => 'Test reply',
        ]);
    
    $response->assertCreated();
    
    $reply = InboxReply::where('inbox_item_id', $inboxItem->id)->first();
    expect($reply->replied_by_user_id)->toBe($this->user->id);
});
