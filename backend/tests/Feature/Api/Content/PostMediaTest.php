<?php

declare(strict_types=1);

use App\Enums\Content\MediaType;
use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Content\Post;
use App\Models\Content\PostMedia;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Bus::fake();

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

    $this->workspace->addMember($this->owner, WorkspaceRole::OWNER);
    $this->workspace->addMember($this->admin, WorkspaceRole::ADMIN);
    $this->workspace->addMember($this->editor, WorkspaceRole::EDITOR);
    $this->workspace->addMember($this->viewer, WorkspaceRole::VIEWER);

    $this->post = Post::factory()->draft()->create([
        'workspace_id' => $this->workspace->id,
        'created_by_user_id' => $this->editor->id,
    ]);
});

describe('GET /api/v1/workspaces/{workspace}/posts/{post}/media', function () {
    it('returns list of media for a post', function () {
        PostMedia::factory()->count(3)->image()->create([
            'post_id' => $this->post->id,
        ]);

        Sanctum::actingAs($this->viewer);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$this->post->id}/media");

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'post_id',
                        'media_type',
                        'file_path',
                        'file_url',
                        'thumbnail_url',
                        'sort_order',
                        'processing_status',
                    ],
                ],
            ]);
    });

    it('returns media in sort order', function () {
        PostMedia::factory()->image()->create([
            'post_id' => $this->post->id,
            'sort_order' => 2,
        ]);
        PostMedia::factory()->image()->create([
            'post_id' => $this->post->id,
            'sort_order' => 0,
        ]);
        PostMedia::factory()->image()->create([
            'post_id' => $this->post->id,
            'sort_order' => 1,
        ]);

        Sanctum::actingAs($this->viewer);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$this->post->id}/media");

        $response->assertOk();

        $data = $response->json('data');
        expect($data[0]['sort_order'])->toBe(0);
        expect($data[1]['sort_order'])->toBe(1);
        expect($data[2]['sort_order'])->toBe(2);
    });
});

describe('POST /api/v1/workspaces/{workspace}/posts/{post}/media', function () {
    it('allows author to attach media to own post', function () {
        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$this->post->id}/media", [
            'media_type' => 'image',
            'file_path' => 'media/test-image.jpg',
            'file_url' => 'https://cdn.example.com/test-image.jpg',
            'original_filename' => 'test-image.jpg',
            'file_size' => 1024000,
            'mime_type' => 'image/jpeg',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.media_type', 'image')
            ->assertJsonPath('data.file_path', 'media/test-image.jpg');

        expect(PostMedia::where('post_id', $this->post->id)->count())->toBe(1);
    });

    it('allows admin to attach media to any post', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$this->post->id}/media", [
            'media_type' => 'video',
            'file_path' => 'media/test-video.mp4',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.media_type', 'video');
    });

    it('denies viewer from attaching media', function () {
        Sanctum::actingAs($this->viewer);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$this->post->id}/media", [
            'media_type' => 'image',
            'file_path' => 'media/test-image.jpg',
        ]);

        $response->assertForbidden();
    });

    it('denies attaching media to non-editable post', function () {
        $publishedPost = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->admin->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$publishedPost->id}/media", [
            'media_type' => 'image',
            'file_path' => 'media/test-image.jpg',
        ]);

        $response->assertForbidden();
    });

    it('validates media type', function () {
        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$this->post->id}/media", [
            'media_type' => 'invalid',
            'file_path' => 'media/test.file',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['media_type']);
    });

    it('auto-assigns sort order', function () {
        PostMedia::factory()->image()->create([
            'post_id' => $this->post->id,
            'sort_order' => 0,
        ]);
        PostMedia::factory()->image()->create([
            'post_id' => $this->post->id,
            'sort_order' => 1,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$this->post->id}/media", [
            'media_type' => 'image',
            'file_path' => 'media/new-image.jpg',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.sort_order', 2);
    });
});

describe('PUT /api/v1/workspaces/{workspace}/posts/{post}/media/order', function () {
    it('updates media order', function () {
        $media1 = PostMedia::factory()->image()->create([
            'post_id' => $this->post->id,
            'sort_order' => 0,
        ]);
        $media2 = PostMedia::factory()->image()->create([
            'post_id' => $this->post->id,
            'sort_order' => 1,
        ]);
        $media3 = PostMedia::factory()->image()->create([
            'post_id' => $this->post->id,
            'sort_order' => 2,
        ]);

        Sanctum::actingAs($this->editor);

        // Reverse the order
        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$this->post->id}/media/order", [
            'media_order' => [$media3->id, $media2->id, $media1->id],
        ]);

        $response->assertOk();

        // Verify new order
        $data = $response->json('data');
        expect($data[0]['id'])->toBe($media3->id);
        expect($data[0]['sort_order'])->toBe(0);
        expect($data[1]['id'])->toBe($media2->id);
        expect($data[1]['sort_order'])->toBe(1);
        expect($data[2]['id'])->toBe($media1->id);
        expect($data[2]['sort_order'])->toBe(2);
    });

    it('denies reordering for non-editable post', function () {
        $publishedPost = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->admin->id,
        ]);
        $media = PostMedia::factory()->image()->create([
            'post_id' => $publishedPost->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$publishedPost->id}/media/order", [
            'media_order' => [$media->id],
        ]);

        $response->assertForbidden();
    });
});

describe('DELETE /api/v1/workspaces/{workspace}/posts/{post}/media/{media}', function () {
    it('allows author to remove media from own post', function () {
        $media = PostMedia::factory()->image()->create([
            'post_id' => $this->post->id,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$this->post->id}/media/{$media->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Media removed successfully');

        expect(PostMedia::find($media->id))->toBeNull();
    });

    it('allows admin to remove media from any post', function () {
        $media = PostMedia::factory()->image()->create([
            'post_id' => $this->post->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$this->post->id}/media/{$media->id}");

        $response->assertOk();
    });

    it('returns 404 for media not in post', function () {
        $otherPost = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->admin->id,
        ]);
        $media = PostMedia::factory()->image()->create([
            'post_id' => $otherPost->id,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$this->post->id}/media/{$media->id}");

        $response->assertNotFound();
    });

    it('denies removing media from non-editable post', function () {
        $publishedPost = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->admin->id,
        ]);
        $media = PostMedia::factory()->image()->create([
            'post_id' => $publishedPost->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$publishedPost->id}/media/{$media->id}");

        $response->assertUnprocessable();
    });
});
