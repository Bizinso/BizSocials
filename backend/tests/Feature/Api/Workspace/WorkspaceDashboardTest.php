<?php

declare(strict_types=1);

use App\Enums\Content\PostStatus;
use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Content\Post;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->workspace->addMember($this->user, WorkspaceRole::ADMIN);
});

describe('GET /api/v1/workspaces/{workspace}/dashboard', function () {
    it('returns dashboard stats for workspace', function () {
        // Create some posts in different statuses
        Post::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'status' => PostStatus::DRAFT,
        ]);
        Post::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'status' => PostStatus::PUBLISHED,
        ]);
        Post::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'status' => PostStatus::SCHEDULED,
        ]);
        Post::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'status' => PostStatus::SUBMITTED,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/dashboard");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_posts',
                    'posts_published',
                    'posts_scheduled',
                    'posts_draft',
                    'pending_approvals',
                    'social_accounts_count',
                    'inbox_unread_count',
                    'member_count',
                    'recent_posts',
                ],
            ])
            ->assertJsonPath('data.total_posts', 7)
            ->assertJsonPath('data.posts_published', 2)
            ->assertJsonPath('data.posts_scheduled', 1)
            ->assertJsonPath('data.posts_draft', 3)
            ->assertJsonPath('data.pending_approvals', 1)
            ->assertJsonPath('data.member_count', 1);
    });

    it('returns empty stats for new workspace', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/dashboard");

        $response->assertOk()
            ->assertJsonPath('data.total_posts', 0)
            ->assertJsonPath('data.social_accounts_count', 0)
            ->assertJsonPath('data.inbox_unread_count', 0)
            ->assertJsonPath('data.recent_posts', []);
    });

    it('requires authentication', function () {
        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/dashboard");

        $response->assertUnauthorized();
    });
});
