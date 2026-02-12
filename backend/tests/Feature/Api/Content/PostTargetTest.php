<?php

declare(strict_types=1);

use App\Enums\Content\PostTargetStatus;
use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
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

    $this->linkedinAccount = SocialAccount::factory()->linkedin()->connected()->create([
        'workspace_id' => $this->workspace->id,
        'connected_by_user_id' => $this->owner->id,
    ]);
    $this->twitterAccount = SocialAccount::factory()->twitter()->connected()->create([
        'workspace_id' => $this->workspace->id,
        'connected_by_user_id' => $this->owner->id,
    ]);
    $this->facebookAccount = SocialAccount::factory()->facebook()->connected()->create([
        'workspace_id' => $this->workspace->id,
        'connected_by_user_id' => $this->owner->id,
    ]);
});

describe('GET /api/v1/workspaces/{workspace}/posts/{post}/targets', function () {
    it('returns list of targets for a post', function () {
        $this->post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);
        $this->post->targets()->create([
            'social_account_id' => $this->twitterAccount->id,
            'platform_code' => 'twitter',
            'status' => PostTargetStatus::PENDING,
        ]);

        Sanctum::actingAs($this->viewer);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$this->post->id}/targets");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'post_id',
                        'social_account_id',
                        'platform',
                        'account_name',
                        'status',
                        'platform_post_id',
                        'platform_post_url',
                        'published_at',
                        'error_message',
                    ],
                ],
            ]);
    });
});

describe('PUT /api/v1/workspaces/{workspace}/posts/{post}/targets', function () {
    it('allows author to set targets for own post', function () {
        Sanctum::actingAs($this->editor);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$this->post->id}/targets", [
            'social_account_ids' => [$this->linkedinAccount->id, $this->twitterAccount->id],
        ]);

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        expect($this->post->targets()->count())->toBe(2);
    });

    it('replaces existing targets', function () {
        // Create initial target
        $this->post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        Sanctum::actingAs($this->editor);

        // Replace with different targets
        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$this->post->id}/targets", [
            'social_account_ids' => [$this->twitterAccount->id, $this->facebookAccount->id],
        ]);

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        $platforms = collect($response->json('data'))->pluck('platform')->all();
        expect($platforms)->toContain('twitter');
        expect($platforms)->toContain('facebook');
        expect($platforms)->not->toContain('linkedin');
    });

    it('validates social accounts belong to workspace', function () {
        $otherWorkspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $otherAccount = SocialAccount::factory()->linkedin()->connected()->create([
            'workspace_id' => $otherWorkspace->id,
            'connected_by_user_id' => $this->owner->id,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$this->post->id}/targets", [
            'social_account_ids' => [$otherAccount->id],
        ]);

        $response->assertUnprocessable();
    });

    it('requires at least one social account', function () {
        Sanctum::actingAs($this->editor);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$this->post->id}/targets", [
            'social_account_ids' => [],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['social_account_ids']);
    });

    it('denies viewer from setting targets', function () {
        Sanctum::actingAs($this->viewer);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$this->post->id}/targets", [
            'social_account_ids' => [$this->linkedinAccount->id],
        ]);

        $response->assertForbidden();
    });

    it('denies setting targets for non-editable post', function () {
        $publishedPost = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->admin->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$publishedPost->id}/targets", [
            'social_account_ids' => [$this->linkedinAccount->id],
        ]);

        $response->assertForbidden();
    });
});

describe('DELETE /api/v1/workspaces/{workspace}/posts/{post}/targets/{target}', function () {
    it('allows author to remove target from own post', function () {
        $target = $this->post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$this->post->id}/targets/{$target->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Target removed successfully');

        expect(PostTarget::find($target->id))->toBeNull();
    });

    it('allows admin to remove target from any post', function () {
        $target = $this->post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$this->post->id}/targets/{$target->id}");

        $response->assertOk();
    });

    it('returns 404 for target not in post', function () {
        $otherPost = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->admin->id,
        ]);
        $target = $otherPost->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$this->post->id}/targets/{$target->id}");

        $response->assertNotFound();
    });

    it('denies removing target from non-editable post', function () {
        $publishedPost = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->admin->id,
        ]);
        $target = $publishedPost->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PUBLISHED,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$publishedPost->id}/targets/{$target->id}");

        $response->assertUnprocessable();
    });
});
