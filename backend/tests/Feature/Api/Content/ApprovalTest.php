<?php

declare(strict_types=1);

use App\Enums\Content\ApprovalDecisionType;
use App\Enums\Content\PostStatus;
use App\Enums\Content\PostTargetStatus;
use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Content\ApprovalDecision;
use App\Models\Content\Post;
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

    $this->socialAccount = SocialAccount::factory()->linkedin()->connected()->create([
        'workspace_id' => $this->workspace->id,
        'connected_by_user_id' => $this->owner->id,
    ]);
});

describe('GET /api/v1/workspaces/{workspace}/approvals', function () {
    it('returns list of pending approvals for admins', function () {
        Post::factory()->submitted()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        // Also create some non-submitted posts
        Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);
        Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/approvals");

        $response->assertOk()
            ->assertJsonCount(3, 'data');

        // All should be submitted status
        foreach ($response->json('data') as $post) {
            expect($post['status'])->toBe('submitted');
        }
    });

    it('denies editor from viewing pending approvals', function () {
        Sanctum::actingAs($this->editor);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/approvals");

        $response->assertForbidden();
    });

    it('denies viewer from viewing pending approvals', function () {
        Sanctum::actingAs($this->viewer);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/approvals");

        $response->assertForbidden();
    });

    it('orders by submission time ascending', function () {
        $post1 = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
            'submitted_at' => now()->subHours(3),
        ]);
        $post2 = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
            'submitted_at' => now()->subHours(1),
        ]);
        $post3 = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
            'submitted_at' => now()->subHours(2),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/approvals");

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        expect($ids[0])->toBe($post1->id);
        expect($ids[1])->toBe($post3->id);
        expect($ids[2])->toBe($post2->id);
    });
});

describe('POST /api/v1/workspaces/{workspace}/posts/{post}/approve', function () {
    it('allows admin to approve submitted post', function () {
        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/approve", [
            'comment' => 'Looks good!',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.decision', 'approved')
            ->assertJsonPath('data.comment', 'Looks good!')
            ->assertJsonPath('data.is_active', true);

        $post->refresh();
        expect($post->status)->toBe(PostStatus::APPROVED);
        expect($post->rejection_reason)->toBeNull();

        // Verify decision was created
        expect(ApprovalDecision::where('post_id', $post->id)->count())->toBe(1);
    });

    it('allows owner to approve submitted post', function () {
        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/approve");

        $response->assertOk()
            ->assertJsonPath('data.decision', 'approved');
    });

    it('denies editor from approving post', function () {
        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/approve");

        $response->assertForbidden();
    });

    it('denies approving non-submitted post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/approve");

        $response->assertForbidden();
    });

    it('deactivates previous decisions', function () {
        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        // Create a previous decision
        ApprovalDecision::create([
            'post_id' => $post->id,
            'decided_by_user_id' => $this->owner->id,
            'decision' => ApprovalDecisionType::REJECTED,
            'is_active' => true,
            'decided_at' => now()->subDay(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/approve");

        $response->assertOk();

        // Check only the new decision is active
        $activeDecisions = ApprovalDecision::where('post_id', $post->id)
            ->where('is_active', true)
            ->get();
        expect($activeDecisions->count())->toBe(1);
        expect($activeDecisions->first()->decision)->toBe(ApprovalDecisionType::APPROVED);
    });
});

describe('POST /api/v1/workspaces/{workspace}/posts/{post}/reject', function () {
    it('allows admin to reject submitted post', function () {
        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/reject", [
            'reason' => 'Content needs revision',
            'comment' => 'Please fix the typos',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.decision', 'rejected')
            ->assertJsonPath('data.comment', 'Please fix the typos')
            ->assertJsonPath('data.is_active', true);

        $post->refresh();
        expect($post->status)->toBe(PostStatus::REJECTED);
        expect($post->rejection_reason)->toBe('Content needs revision');
    });

    it('requires reason for rejection', function () {
        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/reject", [
            'comment' => 'No reason provided',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    });

    it('denies editor from rejecting post', function () {
        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->editor);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/reject", [
            'reason' => 'Self rejection',
        ]);

        $response->assertForbidden();
    });

    it('denies rejecting non-submitted post', function () {
        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/reject", [
            'reason' => 'Late rejection',
        ]);

        $response->assertForbidden();
    });
});

describe('GET /api/v1/workspaces/{workspace}/posts/{post}/approval-history', function () {
    it('returns approval history for a post', function () {
        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        // Create multiple decisions
        ApprovalDecision::create([
            'post_id' => $post->id,
            'decided_by_user_id' => $this->admin->id,
            'decision' => ApprovalDecisionType::REJECTED,
            'comment' => 'First attempt rejected',
            'is_active' => false,
            'decided_at' => now()->subDays(2),
        ]);
        ApprovalDecision::create([
            'post_id' => $post->id,
            'decided_by_user_id' => $this->owner->id,
            'decision' => ApprovalDecisionType::APPROVED,
            'comment' => 'Second attempt approved',
            'is_active' => true,
            'decided_at' => now()->subDay(),
        ]);

        Sanctum::actingAs($this->viewer);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/approval-history");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'post_id',
                        'decided_by_user_id',
                        'decided_by_name',
                        'decision',
                        'comment',
                        'is_active',
                        'decided_at',
                    ],
                ],
            ]);

        // Should be ordered by decided_at descending (newest first)
        $data = $response->json('data');
        expect($data[0]['decision'])->toBe('approved');
        expect($data[1]['decision'])->toBe('rejected');
    });

    it('returns empty array for post without decisions', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        Sanctum::actingAs($this->viewer);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/approval-history");

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('denies access for users from different workspace', function () {
        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $outsider = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_in_tenant' => TenantRole::MEMBER,
        ]);

        Sanctum::actingAs($outsider);

        $response = $this->getJson("/api/v1/workspaces/{$this->workspace->id}/posts/{$post->id}/approval-history");

        $response->assertForbidden();
    });
});
