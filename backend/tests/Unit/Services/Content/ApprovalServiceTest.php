<?php

declare(strict_types=1);

use App\Enums\Content\ApprovalDecisionType;
use App\Enums\Content\PostStatus;
use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Content\ApprovalDecision;
use App\Models\Content\Post;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\ApprovalService;
use Illuminate\Validation\ValidationException;

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

    $this->workspace->addMember($this->owner, WorkspaceRole::OWNER);
    $this->workspace->addMember($this->admin, WorkspaceRole::ADMIN);
    $this->workspace->addMember($this->editor, WorkspaceRole::EDITOR);

    $this->approvalService = new ApprovalService();
});

describe('getPendingForWorkspace', function () {
    it('returns only submitted posts', function () {
        Post::factory()->submitted()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        // Create non-submitted posts
        Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);
        Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);
        Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $pendingPosts = $this->approvalService->getPendingForWorkspace($this->workspace);

        expect($pendingPosts)->toHaveCount(3);
        foreach ($pendingPosts as $post) {
            expect($post->status)->toBe(PostStatus::SUBMITTED);
        }
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

        $pendingPosts = $this->approvalService->getPendingForWorkspace($this->workspace);

        expect($pendingPosts[0]->id)->toBe($post1->id);
        expect($pendingPosts[1]->id)->toBe($post3->id);
        expect($pendingPosts[2]->id)->toBe($post2->id);
    });

    it('returns empty collection when no pending posts', function () {
        $pendingPosts = $this->approvalService->getPendingForWorkspace($this->workspace);

        expect($pendingPosts)->toBeEmpty();
    });
});

describe('approve', function () {
    it('approves a submitted post', function () {
        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $decision = $this->approvalService->approve($post, $this->admin, 'Looks good!');

        expect($decision)->toBeInstanceOf(ApprovalDecision::class);
        expect($decision->decision)->toBe(ApprovalDecisionType::APPROVED);
        expect($decision->comment)->toBe('Looks good!');
        expect($decision->is_active)->toBeTrue();
        expect($decision->decided_by_user_id)->toBe($this->admin->id);

        $post->refresh();
        expect($post->status)->toBe(PostStatus::APPROVED);
    });

    it('approves without comment', function () {
        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $decision = $this->approvalService->approve($post, $this->admin);

        expect($decision->comment)->toBeNull();
    });

    it('deactivates previous decisions', function () {
        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        // Create a previous active decision
        ApprovalDecision::create([
            'post_id' => $post->id,
            'decided_by_user_id' => $this->owner->id,
            'decision' => ApprovalDecisionType::REJECTED,
            'is_active' => true,
            'decided_at' => now()->subDay(),
        ]);

        $this->approvalService->approve($post, $this->admin);

        $activeDecisions = ApprovalDecision::where('post_id', $post->id)
            ->where('is_active', true)
            ->get();

        expect($activeDecisions)->toHaveCount(1);
        expect($activeDecisions->first()->decision)->toBe(ApprovalDecisionType::APPROVED);
    });

    it('throws when approving non-submitted post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $this->approvalService->approve($post, $this->admin);
    })->throws(ValidationException::class);

    it('throws when approving published post', function () {
        $post = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $this->approvalService->approve($post, $this->admin);
    })->throws(ValidationException::class);
});

describe('reject', function () {
    it('rejects a submitted post', function () {
        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $decision = $this->approvalService->reject(
            $post,
            $this->admin,
            'Content policy violation',
            'Please remove the inappropriate content'
        );

        expect($decision)->toBeInstanceOf(ApprovalDecision::class);
        expect($decision->decision)->toBe(ApprovalDecisionType::REJECTED);
        expect($decision->comment)->toBe('Please remove the inappropriate content');
        expect($decision->is_active)->toBeTrue();

        $post->refresh();
        expect($post->status)->toBe(PostStatus::REJECTED);
        expect($post->rejection_reason)->toBe('Content policy violation');
    });

    it('rejects without comment but with reason', function () {
        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $decision = $this->approvalService->reject($post, $this->admin, 'Needs revision');

        expect($decision->comment)->toBeNull();

        $post->refresh();
        expect($post->rejection_reason)->toBe('Needs revision');
    });

    it('deactivates previous decisions', function () {
        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        ApprovalDecision::create([
            'post_id' => $post->id,
            'decided_by_user_id' => $this->owner->id,
            'decision' => ApprovalDecisionType::APPROVED,
            'is_active' => true,
            'decided_at' => now()->subDay(),
        ]);

        $this->approvalService->reject($post, $this->admin, 'Changed my mind');

        $activeDecisions = ApprovalDecision::where('post_id', $post->id)
            ->where('is_active', true)
            ->get();

        expect($activeDecisions)->toHaveCount(1);
        expect($activeDecisions->first()->decision)->toBe(ApprovalDecisionType::REJECTED);
    });

    it('throws when rejecting non-submitted post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $this->approvalService->reject($post, $this->admin, 'Reason');
    })->throws(ValidationException::class);

    it('throws when rejecting approved post', function () {
        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $this->approvalService->reject($post, $this->admin, 'Too late');
    })->throws(ValidationException::class);
});

describe('getDecisionHistory', function () {
    it('returns all decisions for a post', function () {
        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        ApprovalDecision::create([
            'post_id' => $post->id,
            'decided_by_user_id' => $this->admin->id,
            'decision' => ApprovalDecisionType::REJECTED,
            'is_active' => false,
            'decided_at' => now()->subDays(2),
        ]);
        ApprovalDecision::create([
            'post_id' => $post->id,
            'decided_by_user_id' => $this->owner->id,
            'decision' => ApprovalDecisionType::APPROVED,
            'is_active' => true,
            'decided_at' => now()->subDay(),
        ]);

        $history = $this->approvalService->getDecisionHistory($post);

        expect($history)->toHaveCount(2);
    });

    it('orders by decided_at descending', function () {
        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $decision1 = ApprovalDecision::create([
            'post_id' => $post->id,
            'decided_by_user_id' => $this->admin->id,
            'decision' => ApprovalDecisionType::REJECTED,
            'is_active' => false,
            'decided_at' => now()->subDays(2),
        ]);
        $decision2 = ApprovalDecision::create([
            'post_id' => $post->id,
            'decided_by_user_id' => $this->owner->id,
            'decision' => ApprovalDecisionType::APPROVED,
            'is_active' => true,
            'decided_at' => now()->subDay(),
        ]);

        $history = $this->approvalService->getDecisionHistory($post);

        expect($history->first()->id)->toBe($decision2->id);
        expect($history->last()->id)->toBe($decision1->id);
    });

    it('returns empty collection when no decisions', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $history = $this->approvalService->getDecisionHistory($post);

        expect($history)->toBeEmpty();
    });
});

describe('canUserApprove', function () {
    it('returns true for workspace owner', function () {
        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $canApprove = $this->approvalService->canUserApprove($this->owner, $post);

        expect($canApprove)->toBeTrue();
    });

    it('returns true for workspace admin', function () {
        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $canApprove = $this->approvalService->canUserApprove($this->admin, $post);

        expect($canApprove)->toBeTrue();
    });

    it('returns false for workspace editor', function () {
        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $canApprove = $this->approvalService->canUserApprove($this->editor, $post);

        expect($canApprove)->toBeFalse();
    });

    it('returns false for user from different tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'role_in_tenant' => TenantRole::OWNER,
        ]);

        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $canApprove = $this->approvalService->canUserApprove($otherUser, $post);

        expect($canApprove)->toBeFalse();
    });

    it('returns false for user not in workspace', function () {
        $outsider = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_in_tenant' => TenantRole::MEMBER,
        ]);

        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $canApprove = $this->approvalService->canUserApprove($outsider, $post);

        expect($canApprove)->toBeFalse();
    });
});

describe('validateCanApprove', function () {
    it('does not throw for authorized user', function () {
        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        // Should not throw
        $this->approvalService->validateCanApprove($this->admin, $post);

        expect(true)->toBeTrue();
    });

    it('throws for unauthorized user', function () {
        $post = Post::factory()->submitted()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->editor->id,
        ]);

        $this->approvalService->validateCanApprove($this->editor, $post);
    })->throws(ValidationException::class);
});
