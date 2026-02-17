<?php

declare(strict_types=1);

use App\Enums\Content\PostStatus;
use App\Enums\Content\PostTargetStatus;
use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Content\Post;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->owner = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
    $this->editor = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::MEMBER,
    ]);

    // Add users to workspace
    $this->workspace->addMember($this->owner, WorkspaceRole::OWNER);
    $this->workspace->addMember($this->editor, WorkspaceRole::EDITOR);

    // Create social account
    $this->socialAccount = SocialAccount::factory()->linkedin()->connected()->create([
        'workspace_id' => $this->workspace->id,
        'connected_by_user_id' => $this->owner->id,
    ]);
});

describe('POST /api/v1/workspaces/{workspace}/posts/bulk-delete', function () {
    it('deletes multiple draft posts', function () {
        $posts = Post::factory()->count(3)->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $postIds = $posts->pluck('id')->toArray();

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/bulk-delete", [
            'post_ids' => $postIds,
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'deleted' => 3,
                    'errors' => [],
                ],
            ]);

        // Verify posts are soft deleted
        foreach ($postIds as $postId) {
            $this->assertSoftDeleted('posts', ['id' => $postId]);
        }
    });

    it('deletes multiple cancelled posts', function () {
        $posts = Post::factory()->count(3)->cancelled()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $postIds = $posts->pluck('id')->toArray();

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/bulk-delete", [
            'post_ids' => $postIds,
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'deleted' => 3,
                    'errors' => [],
                ],
            ]);
    });

    it('handles mixed deletable and non-deletable posts', function () {
        $draftPost = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $publishedPost = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $postIds = [$draftPost->id, $publishedPost->id];

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/bulk-delete", [
            'post_ids' => $postIds,
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'deleted' => 1,
                ],
            ]);

        // Verify only draft post was deleted
        $this->assertSoftDeleted('posts', ['id' => $draftPost->id]);
        $this->assertDatabaseHas('posts', [
            'id' => $publishedPost->id,
            'deleted_at' => null,
        ]);
    });

    it('validates post_ids is required', function () {
        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/bulk-delete", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['post_ids']);
    });

    it('validates post_ids is an array', function () {
        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/bulk-delete", [
            'post_ids' => 'not-an-array',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['post_ids']);
    });

    it('validates post_ids has minimum 1 item', function () {
        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/bulk-delete", [
            'post_ids' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['post_ids']);
    });

    it('validates post_ids has maximum 50 items', function () {
        $postIds = [];
        for ($i = 0; $i < 51; $i++) {
            $postIds[] = fake()->uuid();
        }

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/bulk-delete", [
            'post_ids' => $postIds,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['post_ids']);
    });

    it('validates each post_id is a valid UUID', function () {
        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/bulk-delete", [
            'post_ids' => ['not-a-uuid', 'also-not-a-uuid'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['post_ids.0', 'post_ids.1']);
    });

    it('only deletes posts from the specified workspace', function () {
        $otherWorkspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $ownPost = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $otherPost = Post::factory()->draft()->create([
            'workspace_id' => $otherWorkspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/bulk-delete", [
            'post_ids' => [$ownPost->id, $otherPost->id],
        ]);

        $response->assertOk();

        // Only the post from this workspace should be deleted
        $this->assertSoftDeleted('posts', ['id' => $ownPost->id]);
        $this->assertDatabaseHas('posts', [
            'id' => $otherPost->id,
            'deleted_at' => null,
        ]);
    });
});

describe('POST /api/v1/workspaces/{workspace}/posts/bulk-submit', function () {
    it('submits multiple draft posts for approval', function () {
        $posts = Post::factory()->count(3)->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
            'content_text' => 'Test content',
        ]);

        // Add targets to each post
        foreach ($posts as $post) {
            $post->targets()->create([
                'social_account_id' => $this->socialAccount->id,
                'platform_code' => 'linkedin',
                'status' => PostTargetStatus::PENDING,
            ]);
        }

        $postIds = $posts->pluck('id')->toArray();

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/bulk-submit", [
            'post_ids' => $postIds,
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'submitted' => 3,
                    'errors' => [],
                ],
            ]);

        // Verify all posts have SUBMITTED status
        foreach ($postIds as $postId) {
            $this->assertDatabaseHas('posts', [
                'id' => $postId,
                'status' => PostStatus::SUBMITTED->value,
            ]);
        }
    });

    it('handles posts without content', function () {
        $validPost = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
            'content_text' => 'Valid content',
        ]);
        $validPost->targets()->create([
            'social_account_id' => $this->socialAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        $invalidPost = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
            'content_text' => null,
        ]);

        $postIds = [$validPost->id, $invalidPost->id];

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/bulk-submit", [
            'post_ids' => $postIds,
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'submitted' => 1,
                ],
            ]);

        // Only valid post should be submitted
        $this->assertDatabaseHas('posts', [
            'id' => $validPost->id,
            'status' => PostStatus::SUBMITTED->value,
        ]);
        $this->assertDatabaseHas('posts', [
            'id' => $invalidPost->id,
            'status' => PostStatus::DRAFT->value,
        ]);
    });

    it('validates post_ids is required', function () {
        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/bulk-submit", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['post_ids']);
    });
});

describe('POST /api/v1/workspaces/{workspace}/posts/bulk-schedule', function () {
    it('schedules multiple approved posts', function () {
        $posts = Post::factory()->count(3)->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
            'content_text' => 'Test content',
        ]);

        // Add targets to each post
        foreach ($posts as $post) {
            $post->targets()->create([
                'social_account_id' => $this->socialAccount->id,
                'platform_code' => 'linkedin',
                'status' => PostTargetStatus::PENDING,
            ]);
        }

        $postIds = $posts->pluck('id')->toArray();
        $scheduledAt = Carbon::now()->addHours(2)->toIso8601String();

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/bulk-schedule", [
            'post_ids' => $postIds,
            'scheduled_at' => $scheduledAt,
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'scheduled' => 3,
                    'errors' => [],
                ],
            ]);

        // Verify all posts have SCHEDULED status
        foreach ($postIds as $postId) {
            $this->assertDatabaseHas('posts', [
                'id' => $postId,
                'status' => PostStatus::SCHEDULED->value,
            ]);
        }
    });

    it('validates scheduled_at is required', function () {
        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/bulk-schedule", [
            'post_ids' => [$post->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_at']);
    });

    it('validates scheduled_at is a valid date', function () {
        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/bulk-schedule", [
            'post_ids' => [$post->id],
            'scheduled_at' => 'not-a-date',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_at']);
    });

    it('validates scheduled_at is in the future', function () {
        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/bulk-schedule", [
            'post_ids' => [$post->id],
            'scheduled_at' => Carbon::now()->subHours(2)->toIso8601String(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_at']);
    });

    it('accepts timezone parameter', function () {
        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
            'content_text' => 'Test content',
        ]);

        $post->targets()->create([
            'social_account_id' => $this->socialAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        $scheduledAt = Carbon::now('America/New_York')->addHours(2)->toIso8601String();

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/bulk-schedule", [
            'post_ids' => [$post->id],
            'scheduled_at' => $scheduledAt,
            'timezone' => 'America/New_York',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'scheduled' => 1,
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => PostStatus::SCHEDULED->value,
            'scheduled_timezone' => 'America/New_York',
        ]);
    });

    it('validates timezone is a valid timezone', function () {
        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/bulk-schedule", [
            'post_ids' => [$post->id],
            'scheduled_at' => Carbon::now()->addHours(2)->toIso8601String(),
            'timezone' => 'Invalid/Timezone',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['timezone']);
    });
});
