<?php

declare(strict_types=1);

use App\Enums\Content\PostTargetStatus;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\PostTargetService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $this->linkedinAccount = SocialAccount::factory()->linkedin()->connected()->create([
        'workspace_id' => $this->workspace->id,
        'connected_by_user_id' => $this->user->id,
    ]);
    $this->twitterAccount = SocialAccount::factory()->twitter()->connected()->create([
        'workspace_id' => $this->workspace->id,
        'connected_by_user_id' => $this->user->id,
    ]);
    $this->facebookAccount = SocialAccount::factory()->facebook()->connected()->create([
        'workspace_id' => $this->workspace->id,
        'connected_by_user_id' => $this->user->id,
    ]);

    $this->postTargetService = new PostTargetService();
});

describe('listForPost', function () {
    it('returns targets for a post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);
        $post->targets()->create([
            'social_account_id' => $this->twitterAccount->id,
            'platform_code' => 'twitter',
            'status' => PostTargetStatus::PENDING,
        ]);

        $targets = $this->postTargetService->listForPost($post);

        expect($targets)->toHaveCount(2);
        expect($targets->pluck('socialAccount')->pluck('id'))
            ->toContain($this->linkedinAccount->id)
            ->toContain($this->twitterAccount->id);
    });

    it('returns empty collection when no targets', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $targets = $this->postTargetService->listForPost($post);

        expect($targets)->toBeEmpty();
    });
});

describe('setTargets', function () {
    it('sets targets for a draft post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $targets = $this->postTargetService->setTargets($post, [
            $this->linkedinAccount->id,
            $this->twitterAccount->id,
        ]);

        expect($targets)->toHaveCount(2);
        expect($post->targets()->count())->toBe(2);
    });

    it('replaces existing targets', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        // Set initial target
        $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        // Replace with new targets
        $targets = $this->postTargetService->setTargets($post, [
            $this->twitterAccount->id,
            $this->facebookAccount->id,
        ]);

        expect($targets)->toHaveCount(2);
        expect($post->targets()->count())->toBe(2);

        $platforms = $post->targets->pluck('platform_code')->toArray();
        expect($platforms)->toContain('twitter');
        expect($platforms)->toContain('facebook');
        expect($platforms)->not->toContain('linkedin');
    });

    it('throws when account not in workspace', function () {
        $otherWorkspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $otherAccount = SocialAccount::factory()->linkedin()->connected()->create([
            'workspace_id' => $otherWorkspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);

        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $this->postTargetService->setTargets($post, [$otherAccount->id]);
    })->throws(ValidationException::class);

    it('throws for non-editable post', function () {
        $post = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $this->postTargetService->setTargets($post, [$this->linkedinAccount->id]);
    })->throws(ValidationException::class);

    it('throws when account does not exist', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $this->postTargetService->setTargets($post, ['non-existent-id']);
    })->throws(ValidationException::class);
});

describe('addTarget', function () {
    it('adds a target to a post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $target = $this->postTargetService->addTarget($post, $this->linkedinAccount);

        expect($target)->toBeInstanceOf(PostTarget::class);
        expect($target->social_account_id)->toBe($this->linkedinAccount->id);
        expect($target->platform_code)->toBe('linkedin');
        expect($target->status)->toBe(PostTargetStatus::PENDING);
    });

    it('throws when target already exists', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        $this->postTargetService->addTarget($post, $this->linkedinAccount);
    })->throws(ValidationException::class);

    it('throws when account not in workspace', function () {
        $otherWorkspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $otherAccount = SocialAccount::factory()->linkedin()->connected()->create([
            'workspace_id' => $otherWorkspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);

        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $this->postTargetService->addTarget($post, $otherAccount);
    })->throws(ValidationException::class);

    it('throws for non-editable post', function () {
        $post = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $this->postTargetService->addTarget($post, $this->linkedinAccount);
    })->throws(ValidationException::class);
});

describe('removeTarget', function () {
    it('removes a target from a post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $target = $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PENDING,
        ]);

        $this->postTargetService->removeTarget($target);

        expect(PostTarget::find($target->id))->toBeNull();
    });

    it('throws for non-editable post', function () {
        $post = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $target = $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PUBLISHED,
        ]);

        $this->postTargetService->removeTarget($target);
    })->throws(ValidationException::class);
});

describe('updateTargetStatus', function () {
    it('updates target status to published', function () {
        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $target = $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PUBLISHING,
        ]);

        $updatedTarget = $this->postTargetService->updateTargetStatus(
            $target,
            PostTargetStatus::PUBLISHED,
            [
                'post_id' => 'ext_post_123',
                'post_url' => 'https://linkedin.com/post/123',
            ]
        );

        expect($updatedTarget->status)->toBe(PostTargetStatus::PUBLISHED);
        expect($updatedTarget->external_post_id)->toBe('ext_post_123');
        expect($updatedTarget->external_post_url)->toBe('https://linkedin.com/post/123');
        expect($updatedTarget->published_at)->not->toBeNull();
        expect($updatedTarget->error_code)->toBeNull();
        expect($updatedTarget->error_message)->toBeNull();
    });

    it('updates target status to failed', function () {
        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $target = $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::PUBLISHING,
        ]);

        $updatedTarget = $this->postTargetService->updateTargetStatus(
            $target,
            PostTargetStatus::FAILED,
            [
                'error_code' => 'RATE_LIMIT',
                'error_message' => 'Rate limit exceeded',
            ]
        );

        expect($updatedTarget->status)->toBe(PostTargetStatus::FAILED);
        expect($updatedTarget->error_code)->toBe('RATE_LIMIT');
        expect($updatedTarget->error_message)->toBe('Rate limit exceeded');
    });

    it('clears error info when publishing succeeds', function () {
        $post = Post::factory()->publishing()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $target = $post->targets()->create([
            'social_account_id' => $this->linkedinAccount->id,
            'platform_code' => 'linkedin',
            'status' => PostTargetStatus::FAILED,
            'error_code' => 'PREVIOUS_ERROR',
            'error_message' => 'Previous failure',
        ]);

        $updatedTarget = $this->postTargetService->updateTargetStatus(
            $target,
            PostTargetStatus::PUBLISHED,
            [
                'post_id' => 'ext_post_456',
            ]
        );

        expect($updatedTarget->error_code)->toBeNull();
        expect($updatedTarget->error_message)->toBeNull();
    });
});
