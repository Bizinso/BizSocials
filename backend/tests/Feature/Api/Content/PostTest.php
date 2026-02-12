<?php

declare(strict_types=1);

use App\Enums\Content\PostStatus;
use App\Enums\Content\PostType;
use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Content\Post;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Social\SocialPlatformAdapterFactory;
use Laravel\Sanctum\Sanctum;
use Tests\Stubs\Services\FakeSocialPlatformAdapterFactory;

beforeEach(function () {
    // Use fake adapter factory to avoid real HTTP calls during publishing
    app()->instance(SocialPlatformAdapterFactory::class, new FakeSocialPlatformAdapterFactory());

    $this->tenant = Tenant::factory()->active()->create();
    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->owner = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
    $this->admin = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::ADMIN,
    ]);
    $this->editor = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::MEMBER,
    ]);
    $this->viewer = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::MEMBER,
    ]);

    // Add users to workspace with different roles
    $this->workspace->addMember($this->owner, WorkspaceRole::OWNER);
    $this->workspace->addMember($this->admin, WorkspaceRole::ADMIN);
    $this->workspace->addMember($this->editor, WorkspaceRole::EDITOR);
    $this->workspace->addMember($this->viewer, WorkspaceRole::VIEWER);

    // Create social accounts for the workspace
    $this->socialAccount = SocialAccount::factory()->linkedin()->connected()->create([
        'workspace_id' => $this->workspace->id,
        'connected_by_user_id' => $this->owner->id,
    ]);
});

describe('GET /api/v1/workspaces/{workspace}/posts', function () {
    it('returns list of posts for workspace members', function () {
        Post::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->viewer);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/posts");

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'workspace_id',
                        'author_id',
                        'author_name',
                        'content_text',
                        'status',
                        'post_type',
                        'target_count',
                        'media_count',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta',
                'links',
            ]);
    });

    it('filters by status', function () {
        Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);
        Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->viewer);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/posts?status=draft");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'draft');
    });

    it('denies access for non-workspace members', function () {
        $outsider = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_in_tenant' => TenantRole::MEMBER,
        ]);

        Sanctum::actingAs($outsider);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/posts");

        $response->assertForbidden();
    });

    it('denies access for users from different tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/posts");

        $response->assertNotFound();
    });
});

describe('POST /api/v1/workspaces/{workspace}/posts', function () {
    it('allows editor to create a post', function () {
        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts", [
            'content_text' => 'Test post content',
            'post_type' => 'standard',
            'hashtags' => ['#test', '#laravel'],
            'social_account_ids' => [$this->socialAccount->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.post.content_text', 'Test post content')
            ->assertJsonPath('data.post.status', 'draft')
            ->assertJsonPath('data.post.post_type', 'standard');

        // Verify in database
        $postId = $response->json('data.post.id');
        $post = Post::find($postId);
        expect($post)->not->toBeNull();
        expect($post->created_by_user_id)->toBe($this->editor->id);
    });

    it('allows admin to create a post', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts", [
            'content_text' => 'Admin test post',
        ]);

        $response->assertCreated();
    });

    it('denies viewer from creating a post', function () {
        Sanctum::actingAs($this->viewer);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts", [
            'content_text' => 'Viewer test post',
        ]);

        $response->assertForbidden();
    });

    it('creates post with targets', function () {
        $anotherAccount = SocialAccount::factory()->twitter()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->owner->id,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts", [
            'content_text' => 'Multi-platform post',
            'social_account_ids' => [$this->socialAccount->id, $anotherAccount->id],
        ]);

        $response->assertCreated()
            ->assertJsonCount(2, 'data.targets');
    });
});

describe('GET /api/v1/workspaces/{workspace}/posts/{post}', function () {
    it('returns post details for workspace members', function () {
        $post = Post::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->viewer);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}");

        $response->assertOk()
            ->assertJsonPath('data.post.id', $post->id)
            ->assertJsonStructure([
                'data' => [
                    'post',
                    'targets',
                    'media',
                ],
            ]);
    });

    it('returns 404 for post not in workspace', function () {
        $otherWorkspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $post = Post::factory()->create([
            'workspace_id' => $otherWorkspace->id,
            'created_by_user_id' => $this->owner->id,
        ]);

        Sanctum::actingAs($this->viewer);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}");

        $response->assertNotFound();
    });
});

describe('PUT /api/v1/workspaces/{workspace}/posts/{post}', function () {
    it('allows editor to update own draft post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}", [
            'content_text' => 'Updated content',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.post.content_text', 'Updated content');
    });

    it('allows admin to update any draft post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}", [
            'content_text' => 'Admin updated content',
        ]);

        $response->assertOk();
    });

    it('denies editor from updating another users post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->admin->id,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}", [
            'content_text' => 'Unauthorized update',
        ]);

        $response->assertForbidden();
    });

    it('denies update of non-editable post', function () {
        $post = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}", [
            'content_text' => 'Cannot update published',
        ]);

        $response->assertForbidden();
    });

    it('allows editing rejected post and moves it to draft', function () {
        $post = Post::factory()->rejected()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}", [
            'content_text' => 'Fixed content',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.post.status', 'draft')
            ->assertJsonPath('data.post.rejection_reason', null);
    });
});

describe('DELETE /api/v1/workspaces/{workspace}/posts/{post}', function () {
    it('allows editor to delete own draft post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Post deleted successfully');

        expect(Post::find($post->id))->toBeNull();
    });

    it('allows admin to delete any post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}");

        $response->assertOk();
    });

    it('denies deletion of published post', function () {
        $post = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->admin->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}");

        $response->assertUnprocessable();
    });
});

describe('POST /api/v1/workspaces/{workspace}/posts/{post}/submit', function () {
    it('allows editor to submit own draft post for approval', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
            'content_text' => 'Post to submit',
        ]);

        // Add target
        $post->targets()->create([
            'social_account_id' => $this->socialAccount->id,
            'platform_code' => $this->socialAccount->platform->value,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/submit");

        $response->assertOk()
            ->assertJsonPath('data.post.status', 'submitted');
    });

    it('fails to submit post without content', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
            'content_text' => null,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/submit");

        $response->assertUnprocessable();
    });

    it('fails to submit post without targets', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
            'content_text' => 'Content without targets',
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/submit");

        $response->assertUnprocessable();
    });
});

describe('POST /api/v1/workspaces/{workspace}/posts/{post}/schedule', function () {
    it('allows admin to schedule approved post', function () {
        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
            'content_text' => 'Post to schedule',
        ]);

        // Add target
        $post->targets()->create([
            'social_account_id' => $this->socialAccount->id,
            'platform_code' => $this->socialAccount->platform->value,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->admin);

        $scheduledAt = now()->addDays(1)->toIso8601String();

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/schedule", [
            'scheduled_at' => $scheduledAt,
            'timezone' => 'Asia/Kolkata',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.post.status', 'scheduled')
            ->assertJsonPath('data.post.scheduled_timezone', 'Asia/Kolkata');
    });

    it('fails to schedule with past date', function () {
        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/schedule", [
            'scheduled_at' => now()->subDay()->toIso8601String(),
        ]);

        $response->assertUnprocessable();
    });

    it('denies editor from scheduling post', function () {
        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/schedule", [
            'scheduled_at' => now()->addDay()->toIso8601String(),
        ]);

        $response->assertForbidden();
    });
});

describe('POST /api/v1/workspaces/{workspace}/posts/{post}/publish', function () {
    it('allows admin to publish approved post immediately', function () {
        // Fake the queue so PublishPostJob doesn't run synchronously
        \Illuminate\Support\Facades\Queue::fake();

        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
            'content_text' => 'Post to publish',
        ]);

        // Add target
        $post->targets()->create([
            'social_account_id' => $this->socialAccount->id,
            'platform_code' => $this->socialAccount->platform->value,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/publish");

        $response->assertOk()
            ->assertJsonPath('data.post.status', 'publishing');
    });

    it('denies editor from publishing post', function () {
        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/publish");

        $response->assertForbidden();
    });
});

describe('POST /api/v1/workspaces/{workspace}/posts/{post}/cancel', function () {
    it('allows author to cancel draft post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.post.status', 'cancelled');
    });

    it('allows admin to cancel scheduled post', function () {
        $post = Post::factory()->scheduled()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.post.status', 'cancelled');
    });
});

describe('POST /api/v1/workspaces/{workspace}/posts/{post}/duplicate', function () {
    it('duplicates a post', function () {
        $originalPost = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->admin->id,
            'content_text' => 'Original content',
            'hashtags' => ['#original'],
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$originalPost->id}/duplicate");

        $response->assertCreated()
            ->assertJsonPath('data.post.status', 'draft')
            ->assertJsonPath('data.post.content_text', 'Original content')
            ->assertJsonPath('data.post.author_id', $this->editor->id);

        // Ensure it's a new post
        $newPostId = $response->json('data.post.id');
        expect($newPostId)->not->toBe($originalPost->id);
    });
});
