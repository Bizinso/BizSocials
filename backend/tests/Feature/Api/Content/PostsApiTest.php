<?php

declare(strict_types=1);

use App\Enums\Content\PostStatus;
use App\Enums\Content\PostType;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Content\Post;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    
    // Add user to workspace using the proper method
    $this->workspace->addMember($this->user, WorkspaceRole::EDITOR);
    
    $this->socialAccount = SocialAccount::factory()->linkedin()->connected()->create([
        'workspace_id' => $this->workspace->id,
        'connected_by_user_id' => $this->user->id,
    ]);

    // Authenticate as the user
    $this->actingAs($this->user);
});

describe('POST /api/v1/workspaces/{workspace}/posts', function () {
    it('creates a new post with valid data', function () {
        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts", [
            'content_text' => 'Test post content',
            'post_type' => 'standard',
            'hashtags' => ['#test', '#integration'],
            'social_account_ids' => [$this->socialAccount->id],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'content_text',
                    'status',
                    'post_type',
                    'hashtags',
                    'created_at',
                    'updated_at',
                ],
            ]);

        expect($response->json('data.content_text'))->toBe('Test post content');
        expect($response->json('data.status'))->toBe('draft');
        expect($response->json('data.post_type'))->toBe('standard');

        // Verify database persistence
        $this->assertDatabaseHas('posts', [
            'workspace_id' => $this->workspace->id,
            'content_text' => 'Test post content',
            'status' => 'draft',
        ]);
    });

    it('validates required fields', function () {
        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts", []);

        $response->assertStatus(422);
    });

    it('creates post with content variations', function () {
        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts", [
            'content_text' => 'Default content',
            'content_variations' => [
                'facebook' => 'Facebook specific content',
                'twitter' => 'Twitter specific content',
            ],
        ]);

        $response->assertStatus(201);
        expect($response->json('data.content_variations'))->toHaveKey('facebook');
    });
});

describe('GET /api/v1/workspaces/{workspace}/posts', function () {
    it('lists posts for the workspace', function () {
        Post::factory()->count(5)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        // Create posts in another workspace (should not be returned)
        $otherWorkspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        Post::factory()->count(3)->create([
            'workspace_id' => $otherWorkspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/posts");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'content_text', 'status', 'created_at'],
                ],
                'meta' => ['current_page', 'total'],
            ]);

        expect(count($response->json('data')))->toBe(5);
    });

    it('filters posts by status', function () {
        Post::factory()->draft()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);
        Post::factory()->published()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/posts?status=draft");

        $response->assertStatus(200);
        expect(count($response->json('data')))->toBe(3);
    });

    it('searches posts by content', function () {
        Post::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Unique searchable content',
        ]);
        Post::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Other content',
        ]);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/posts?search=searchable");

        $response->assertStatus(200);
        expect(count($response->json('data')))->toBe(1);
    });
});

describe('GET /api/v1/workspaces/{workspace}/posts/{post}', function () {
    it('retrieves a specific post', function () {
        $post = Post::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Specific post content',
        ]);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'content_text', 'status'],
            ]);

        expect($response->json('data.id'))->toBe($post->id);
        expect($response->json('data.content_text'))->toBe('Specific post content');
    });

    it('returns 404 for non-existent post', function () {
        $fakeId = '00000000-0000-0000-0000-000000000000';
        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$fakeId}");

        $response->assertStatus(404);
    });
});

describe('PUT /api/v1/workspaces/{workspace}/posts/{post}', function () {
    it('updates a draft post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Original content',
        ]);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}", [
            'content_text' => 'Updated content',
        ]);

        $response->assertStatus(200);
        expect($response->json('data.content_text'))->toBe('Updated content');

        // Verify database persistence
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'content_text' => 'Updated content',
        ]);
    });

    it('prevents updating published posts', function () {
        $post = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}", [
            'content_text' => 'Cannot update',
        ]);

        $response->assertStatus(422);
    });

    it('moves rejected post to draft when updated', function () {
        $post = Post::factory()->rejected()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'rejection_reason' => 'Needs fixes',
        ]);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}", [
            'content_text' => 'Fixed content',
        ]);

        $response->assertStatus(200);
        expect($response->json('data.status'))->toBe('draft');
        expect($response->json('data.rejection_reason'))->toBeNull();
    });
});

describe('DELETE /api/v1/workspaces/{workspace}/posts/{post}', function () {
    it('deletes a draft post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}");

        $response->assertStatus(200);

        // Verify soft delete
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    });

    it('prevents deleting published posts', function () {
        $post = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}");

        $response->assertStatus(422);
    });
});

describe('POST /api/v1/workspaces/{workspace}/posts/{post}/submit', function () {
    it('submits a draft post for approval', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Content to submit',
        ]);

        // Add a target
        $post->targets()->create([
            'social_account_id' => $this->socialAccount->id,
            'platform_code' => 'linkedin',
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/submit");

        $response->assertStatus(200);
        expect($response->json('data.status'))->toBe('submitted');

        // Verify database persistence
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'submitted',
        ]);
    });

    it('validates post has content before submitting', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => null,
        ]);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/submit");

        $response->assertStatus(422);
    });

    it('validates post has targets before submitting', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Content without targets',
        ]);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/submit");

        $response->assertStatus(422);
    });
});

describe('POST /api/v1/workspaces/{workspace}/posts/{post}/schedule', function () {
    it('schedules an approved post', function () {
        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Content to schedule',
        ]);

        // Add a target
        $post->targets()->create([
            'social_account_id' => $this->socialAccount->id,
            'platform_code' => 'linkedin',
            'status' => 'pending',
        ]);

        $scheduledAt = now()->addDays(1)->toIso8601String();

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/schedule", [
            'scheduled_at' => $scheduledAt,
            'timezone' => 'UTC',
        ]);

        $response->assertStatus(200);
        expect($response->json('data.status'))->toBe('scheduled');

        // Verify database persistence
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'scheduled',
            'scheduled_timezone' => 'UTC',
        ]);
    });

    it('validates scheduled time is in the future', function () {
        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $pastTime = now()->subDay()->toIso8601String();

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/schedule", [
            'scheduled_at' => $pastTime,
        ]);

        $response->assertStatus(422);
    });
});

describe('POST /api/v1/workspaces/{workspace}/posts/{post}/cancel', function () {
    it('cancels a scheduled post', function () {
        $post = Post::factory()->scheduled()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/cancel");

        $response->assertStatus(200);
        expect($response->json('data.status'))->toBe('cancelled');

        // Verify database persistence
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'cancelled',
        ]);
    });

    it('prevents cancelling published posts', function () {
        $post = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/cancel");

        $response->assertStatus(422);
    });
});

describe('POST /api/v1/workspaces/{workspace}/posts/{post}/duplicate', function () {
    it('duplicates a post', function () {
        $originalPost = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Original content',
            'hashtags' => ['#original'],
        ]);

        // Add target to original
        $originalPost->targets()->create([
            'social_account_id' => $this->socialAccount->id,
            'platform_code' => 'linkedin',
            'status' => 'published',
        ]);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$originalPost->id}/duplicate");

        $response->assertStatus(201);
        expect($response->json('data.content_text'))->toBe('Original content');
        expect($response->json('data.status'))->toBe('draft');
        expect($response->json('data.id'))->not->toBe($originalPost->id);

        // Verify database persistence
        $this->assertDatabaseHas('posts', [
            'workspace_id' => $this->workspace->id,
            'content_text' => 'Original content',
            'status' => 'draft',
            'created_by_user_id' => $this->user->id,
        ]);
    });
});

describe('authorization', function () {
    it('prevents accessing posts from other workspaces', function () {
        $otherWorkspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $post = Post::factory()->create([
            'workspace_id' => $otherWorkspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}");

        $response->assertStatus(404);
    });

    it('requires authentication', function () {
        auth()->logout();

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/posts");

        $response->assertStatus(401);
    });
});
