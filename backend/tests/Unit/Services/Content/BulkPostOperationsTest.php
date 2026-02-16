<?php

declare(strict_types=1);

use App\Enums\Content\PostStatus;
use App\Models\Content\Post;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\PostService;
use App\Services\Content\PostTargetService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $this->postTargetService = app(PostTargetService::class);
    $this->postService = new PostService($this->postTargetService);
});

describe('bulk delete operations', function () {
    it('deletes multiple draft posts', function () {
        $posts = Post::factory()->count(3)->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $deleted = 0;
        foreach ($posts as $post) {
            $this->postService->delete($post);
            $deleted++;
        }

        expect($deleted)->toBe(3);
        expect(Post::forWorkspace($this->workspace->id)->count())->toBe(0);
    });

    it('deletes multiple cancelled posts', function () {
        $posts = Post::factory()->count(3)->cancelled()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $deleted = 0;
        foreach ($posts as $post) {
            $this->postService->delete($post);
            $deleted++;
        }

        expect($deleted)->toBe(3);
        expect(Post::forWorkspace($this->workspace->id)->count())->toBe(0);
    });

    it('throws when trying to delete published posts', function () {
        $post = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $this->postService->delete($post);
    })->throws(ValidationException::class);

    it('handles mixed deletable and non-deletable posts', function () {
        $draftPost = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);
        $publishedPost = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $deleted = 0;
        $errors = 0;

        try {
            $this->postService->delete($draftPost);
            $deleted++;
        } catch (\Throwable $e) {
            $errors++;
        }

        try {
            $this->postService->delete($publishedPost);
            $deleted++;
        } catch (\Throwable $e) {
            $errors++;
        }

        expect($deleted)->toBe(1);
        expect($errors)->toBe(1);
        expect(Post::forWorkspace($this->workspace->id)->count())->toBe(1);
    });
});

describe('bulk submit operations', function () {
    it('submits multiple draft posts for approval', function () {
        $posts = Post::factory()->count(3)->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Test content',
        ]);

        // Add targets to each post
        foreach ($posts as $post) {
            $post->targets()->create([
                'social_account_id' => \App\Models\Social\SocialAccount::factory()->linkedin()->connected()->create([
                    'workspace_id' => $this->workspace->id,
                    'connected_by_user_id' => $this->user->id,
                ])->id,
                'platform_code' => 'linkedin',
                'status' => \App\Enums\Content\PostTargetStatus::PENDING,
            ]);
        }

        $submitted = 0;
        foreach ($posts as $post) {
            $this->postService->submit($post->fresh());
            $submitted++;
        }

        expect($submitted)->toBe(3);
        expect(Post::forWorkspace($this->workspace->id)->where('status', PostStatus::SUBMITTED)->count())->toBe(3);
    });

    it('throws when submitting post without content', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => null,
        ]);

        $this->postService->submit($post);
    })->throws(ValidationException::class);

    it('throws when submitting post without targets', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Test content',
        ]);

        $this->postService->submit($post);
    })->throws(ValidationException::class);

    it('handles mixed valid and invalid posts for submission', function () {
        $validPost = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Valid content',
        ]);
        $validPost->targets()->create([
            'social_account_id' => \App\Models\Social\SocialAccount::factory()->linkedin()->connected()->create([
                'workspace_id' => $this->workspace->id,
                'connected_by_user_id' => $this->user->id,
            ])->id,
            'platform_code' => 'linkedin',
            'status' => \App\Enums\Content\PostTargetStatus::PENDING,
        ]);

        $invalidPost = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => null, // Invalid - no content
        ]);

        $submitted = 0;
        $errors = 0;

        try {
            $this->postService->submit($validPost->fresh());
            $submitted++;
        } catch (\Throwable $e) {
            $errors++;
        }

        try {
            $this->postService->submit($invalidPost->fresh());
            $submitted++;
        } catch (\Throwable $e) {
            $errors++;
        }

        expect($submitted)->toBe(1);
        expect($errors)->toBe(1);
    });
});

describe('bulk schedule operations', function () {
    it('schedules multiple approved posts', function () {
        $posts = Post::factory()->count(3)->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Test content',
        ]);

        // Add targets to each post
        foreach ($posts as $post) {
            $post->targets()->create([
                'social_account_id' => \App\Models\Social\SocialAccount::factory()->linkedin()->connected()->create([
                    'workspace_id' => $this->workspace->id,
                    'connected_by_user_id' => $this->user->id,
                ])->id,
                'platform_code' => 'linkedin',
                'status' => \App\Enums\Content\PostTargetStatus::PENDING,
            ]);
        }

        $scheduledAt = Carbon::now()->addHours(2);
        $scheduled = 0;

        foreach ($posts as $post) {
            $this->postService->schedule($post->fresh(), $scheduledAt);
            $scheduled++;
        }

        expect($scheduled)->toBe(3);
        expect(Post::forWorkspace($this->workspace->id)->where('status', PostStatus::SCHEDULED)->count())->toBe(3);

        // Verify all posts have the correct scheduled time
        $posts->each(function ($post) use ($scheduledAt) {
            $post->refresh();
            expect($post->scheduled_at->timestamp)->toBe($scheduledAt->timestamp);
        });
    });

    it('throws when scheduling in the past', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Test content',
        ]);

        $post->targets()->create([
            'social_account_id' => \App\Models\Social\SocialAccount::factory()->linkedin()->connected()->create([
                'workspace_id' => $this->workspace->id,
                'connected_by_user_id' => $this->user->id,
            ])->id,
            'platform_code' => 'linkedin',
            'status' => \App\Enums\Content\PostTargetStatus::PENDING,
        ]);

        $pastTime = Carbon::now()->subHours(2);
        $this->postService->schedule($post, $pastTime);
    })->throws(ValidationException::class);

    it('throws when scheduling draft post without approval', function () {
        $post = Post::factory()->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Test content',
        ]);

        $post->targets()->create([
            'social_account_id' => \App\Models\Social\SocialAccount::factory()->linkedin()->connected()->create([
                'workspace_id' => $this->workspace->id,
                'connected_by_user_id' => $this->user->id,
            ])->id,
            'platform_code' => 'linkedin',
            'status' => \App\Enums\Content\PostTargetStatus::PENDING,
        ]);

        $scheduledAt = Carbon::now()->addHours(2);
        $this->postService->schedule($post, $scheduledAt);
    })->throws(ValidationException::class);

    it('handles mixed valid and invalid posts for scheduling', function () {
        $validPost = Post::factory()->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Valid content',
        ]);
        $validPost->targets()->create([
            'social_account_id' => \App\Models\Social\SocialAccount::factory()->linkedin()->connected()->create([
                'workspace_id' => $this->workspace->id,
                'connected_by_user_id' => $this->user->id,
            ])->id,
            'platform_code' => 'linkedin',
            'status' => \App\Enums\Content\PostTargetStatus::PENDING,
        ]);

        $invalidPost = Post::factory()->published()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Cannot schedule published post',
        ]);

        $scheduledAt = Carbon::now()->addHours(2);
        $scheduled = 0;
        $errors = 0;

        try {
            $this->postService->schedule($validPost->fresh(), $scheduledAt);
            $scheduled++;
        } catch (\Throwable $e) {
            $errors++;
        }

        try {
            $this->postService->schedule($invalidPost->fresh(), $scheduledAt);
            $scheduled++;
        } catch (\Throwable $e) {
            $errors++;
        }

        expect($scheduled)->toBe(1);
        expect($errors)->toBe(1);
    });
});

describe('bulk operation count verification', function () {
    it('returns accurate count for bulk delete operations', function () {
        $posts = Post::factory()->count(5)->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $initialCount = Post::forWorkspace($this->workspace->id)->count();
        expect($initialCount)->toBe(5);

        $deleted = 0;
        foreach ($posts as $post) {
            $this->postService->delete($post);
            $deleted++;
        }

        $finalCount = Post::forWorkspace($this->workspace->id)->count();

        expect($deleted)->toBe(5);
        expect($finalCount)->toBe(0);
        expect($initialCount - $finalCount)->toBe($deleted);
    });

    it('returns accurate count for bulk submit operations', function () {
        $posts = Post::factory()->count(5)->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Test content',
        ]);

        // Add targets to each post
        foreach ($posts as $post) {
            $post->targets()->create([
                'social_account_id' => \App\Models\Social\SocialAccount::factory()->linkedin()->connected()->create([
                    'workspace_id' => $this->workspace->id,
                    'connected_by_user_id' => $this->user->id,
                ])->id,
                'platform_code' => 'linkedin',
                'status' => \App\Enums\Content\PostTargetStatus::PENDING,
            ]);
        }

        $initialSubmittedCount = Post::forWorkspace($this->workspace->id)
            ->where('status', PostStatus::SUBMITTED)
            ->count();

        $submitted = 0;
        foreach ($posts as $post) {
            $this->postService->submit($post->fresh());
            $submitted++;
        }

        $finalSubmittedCount = Post::forWorkspace($this->workspace->id)
            ->where('status', PostStatus::SUBMITTED)
            ->count();

        expect($submitted)->toBe(5);
        expect($finalSubmittedCount - $initialSubmittedCount)->toBe($submitted);
    });

    it('returns accurate count for bulk schedule operations', function () {
        $posts = Post::factory()->count(5)->approved()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
            'content_text' => 'Test content',
        ]);

        // Add targets to each post
        foreach ($posts as $post) {
            $post->targets()->create([
                'social_account_id' => \App\Models\Social\SocialAccount::factory()->linkedin()->connected()->create([
                    'workspace_id' => $this->workspace->id,
                    'connected_by_user_id' => $this->user->id,
                ])->id,
                'platform_code' => 'linkedin',
                'status' => \App\Enums\Content\PostTargetStatus::PENDING,
            ]);
        }

        $initialScheduledCount = Post::forWorkspace($this->workspace->id)
            ->where('status', PostStatus::SCHEDULED)
            ->count();

        $scheduledAt = Carbon::now()->addHours(2);
        $scheduled = 0;

        foreach ($posts as $post) {
            $this->postService->schedule($post->fresh(), $scheduledAt);
            $scheduled++;
        }

        $finalScheduledCount = Post::forWorkspace($this->workspace->id)
            ->where('status', PostStatus::SCHEDULED)
            ->count();

        expect($scheduled)->toBe(5);
        expect($finalScheduledCount - $initialScheduledCount)->toBe($scheduled);
    });
});
