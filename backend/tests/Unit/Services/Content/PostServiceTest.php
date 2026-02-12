<?php

declare(strict_types=1);

use App\Data\Content\CreatePostData;
use App\Data\Content\UpdatePostData;
use App\Enums\Content\PostStatus;
use App\Enums\Content\PostType;
use App\Models\Content\Post;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\PostService;
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
    $this->socialAccount = SocialAccount::factory()->linkedin()->connected()->create([
        'workspace_id' => $this->workspace->id,
        'connected_by_user_id' => $this->user->id,
    ]);

    $this->postTargetService = app(PostTargetService::class);
    $this->postService = new PostService($this->postTargetService);
});

describe('create', function () {
    it('creates a post with content', function () {
        $data = new CreatePostData(
            content_text: 'Test post content',
            post_type: PostType::STANDARD,
            hashtags: ['#test'],
        );

        $post = $this->postService->create($this->workspace, $this->user, $data);

        expect($post)->toBeInstanceOf(Post::class);
        expect($post->content_text)->toBe('Test post content');
        expect($post->status)->toBe(PostStatus::DRAFT);
        expect($post->post_type)->toBe(PostType::STANDARD);
        expect($post->created_by_user_id)->toBe($this->user->id);
    });

    it('creates a post with targets', function () {
        $data = new CreatePostData(
            content_text: 'Test post with targets',
            social_account_ids: [$this->socialAccount->id],
        );

        $post = $this->postService->create($this->workspace, $this->user, $data);

        expect($post->targets()->count())->toBe(1);
    });
});

describe('update', function () {
    it('updates a draft post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Original content',
        ]);

        $data = new UpdatePostData(
            content_text: 'Updated content',
        );

        $updatedPost = $this->postService->update($post, $data);

        expect($updatedPost->content_text)->toBe('Updated content');
    });

    it('throws when updating non-editable post', function () {
        $post = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $data = new UpdatePostData(content_text: 'Cannot update');

        $this->postService->update($post, $data);
    })->throws(ValidationException::class);

    it('moves rejected post to draft when updated', function () {
        $post = Post::factory()->rejected()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'rejection_reason' => 'Needs fixes',
        ]);

        $data = new UpdatePostData(content_text: 'Fixed content');

        $updatedPost = $this->postService->update($post, $data);

        expect($updatedPost->status)->toBe(PostStatus::DRAFT);
        expect($updatedPost->rejection_reason)->toBeNull();
    });
});

describe('delete', function () {
    it('deletes a draft post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $this->postService->delete($post);

        expect(Post::find($post->id))->toBeNull();
    });

    it('throws when deleting published post', function () {
        $post = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $this->postService->delete($post);
    })->throws(ValidationException::class);
});

describe('submit', function () {
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

        $submittedPost = $this->postService->submit($post);

        expect($submittedPost->status)->toBe(PostStatus::SUBMITTED);
        expect($submittedPost->submitted_at)->not->toBeNull();
    });

    it('throws when submitting without content', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => null,
        ]);

        $this->postService->submit($post);
    })->throws(ValidationException::class);

    it('throws when submitting without targets', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Content without targets',
        ]);

        $this->postService->submit($post);
    })->throws(ValidationException::class);
});

describe('schedule', function () {
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

        $scheduledAt = now()->addDays(1);
        $scheduledPost = $this->postService->schedule($post, $scheduledAt, 'UTC');

        expect($scheduledPost->status)->toBe(PostStatus::SCHEDULED);
        expect($scheduledPost->scheduled_timezone)->toBe('UTC');
    });

    it('throws when scheduling in the past', function () {
        $post = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $scheduledAt = now()->subDay();
        $this->postService->schedule($post, $scheduledAt);
    })->throws(ValidationException::class);

    it('throws when scheduling from invalid status', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $scheduledAt = now()->addDay();
        $this->postService->schedule($post, $scheduledAt);
    })->throws(ValidationException::class);
});

describe('reschedule', function () {
    it('reschedules a scheduled post to a new time', function () {
        $post = Post::factory()->scheduled()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Scheduled content',
            'scheduled_at' => now()->addDays(1),
            'scheduled_timezone' => 'UTC',
        ]);

        $newTime = now()->addDays(3);
        $rescheduledPost = $this->postService->reschedule($post, $newTime, 'America/New_York');

        expect($rescheduledPost->status)->toBe(PostStatus::SCHEDULED);
        expect($rescheduledPost->scheduled_timezone)->toBe('America/New_York');
        expect($rescheduledPost->scheduled_at->diffInMinutes($newTime))->toBeLessThan(1);
    });

    it('throws when rescheduling a non-scheduled post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $this->postService->reschedule($post, now()->addDay());
    })->throws(ValidationException::class, 'Only scheduled posts can be rescheduled.');

    it('throws when rescheduling to a past time', function () {
        $post = Post::factory()->scheduled()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'scheduled_at' => now()->addDays(1),
        ]);

        $this->postService->reschedule($post, now()->subHour());
    })->throws(ValidationException::class, 'Scheduled time must be in the future.');

    it('updates timezone when provided', function () {
        $post = Post::factory()->scheduled()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'scheduled_at' => now()->addDays(1),
            'scheduled_timezone' => 'UTC',
        ]);

        $rescheduledPost = $this->postService->reschedule($post, now()->addDays(2), 'Asia/Kolkata');

        expect($rescheduledPost->scheduled_timezone)->toBe('Asia/Kolkata');
    });

    it('preserves existing timezone when not provided', function () {
        $post = Post::factory()->scheduled()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'scheduled_at' => now()->addDays(1),
            'scheduled_timezone' => 'Europe/London',
        ]);

        $rescheduledPost = $this->postService->reschedule($post, now()->addDays(2));

        expect($rescheduledPost->scheduled_timezone)->toBe('Europe/London');
    });
});

describe('cancel', function () {
    it('cancels a draft post', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $cancelledPost = $this->postService->cancel($post);

        expect($cancelledPost->status)->toBe(PostStatus::CANCELLED);
    });

    it('cancels a scheduled post', function () {
        $post = Post::factory()->scheduled()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $cancelledPost = $this->postService->cancel($post);

        expect($cancelledPost->status)->toBe(PostStatus::CANCELLED);
    });

    it('throws when cancelling published post', function () {
        $post = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $this->postService->cancel($post);
    })->throws(ValidationException::class);
});

describe('duplicate', function () {
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

        $newUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $duplicatedPost = $this->postService->duplicate($originalPost, $newUser);

        expect($duplicatedPost->id)->not->toBe($originalPost->id);
        expect($duplicatedPost->content_text)->toBe('Original content');
        expect($duplicatedPost->hashtags)->toBe(['#original']);
        expect($duplicatedPost->status)->toBe(PostStatus::DRAFT);
        expect($duplicatedPost->created_by_user_id)->toBe($newUser->id);
        expect($duplicatedPost->targets()->count())->toBe(1);
    });
});

describe('list', function () {
    it('lists posts for a workspace', function () {
        Post::factory()->count(5)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        // Create posts in another workspace
        $otherWorkspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        Post::factory()->count(3)->create([
            'workspace_id' => $otherWorkspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $posts = $this->postService->list($this->workspace);

        expect($posts->total())->toBe(5);
    });

    it('filters by status', function () {
        Post::factory()->draft()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);
        Post::factory()->published()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $posts = $this->postService->list($this->workspace, ['status' => 'draft']);

        expect($posts->total())->toBe(3);
    });

    it('filters by author', function () {
        $anotherUser = User::factory()->create(['tenant_id' => $this->tenant->id]);

        Post::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);
        Post::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $anotherUser->id,
        ]);

        $posts = $this->postService->list($this->workspace, ['author_id' => $this->user->id]);

        expect($posts->total())->toBe(3);
    });
});
