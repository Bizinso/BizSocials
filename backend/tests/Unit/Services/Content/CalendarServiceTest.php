<?php

declare(strict_types=1);

use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\CalendarService;
use App\Services\Content\PostService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('CalendarService', function () {
    beforeEach(function () {
        $this->calendarService = app(CalendarService::class);
        $this->workspace = Workspace::factory()->create();
        $this->user = User::factory()->create();
    });

    describe('getCalendarPosts', function () {
        it('retrieves posts within date range', function () {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();

            // Create posts within range
            $post1 = Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(5),
                'status' => 'scheduled',
            ]);

            $post2 = Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(10),
                'status' => 'scheduled',
            ]);

            // Create post outside range
            Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addMonths(2),
                'status' => 'scheduled',
            ]);

            $posts = $this->calendarService->getCalendarPosts(
                $this->workspace,
                $startDate,
                $endDate
            );

            expect($posts)->toHaveCount(2)
                ->and($posts->pluck('id')->toArray())->toContain($post1->id, $post2->id);
        });

        it('filters posts by platform', function () {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();

            $facebookPost = Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(5),
                'status' => 'scheduled',
            ]);
            PostTarget::factory()->create([
                'post_id' => $facebookPost->id,
                'platform_code' => 'facebook',
            ]);

            $twitterPost = Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(7),
                'status' => 'scheduled',
            ]);
            PostTarget::factory()->create([
                'post_id' => $twitterPost->id,
                'platform_code' => 'twitter',
            ]);

            $posts = $this->calendarService->getCalendarPosts(
                $this->workspace,
                $startDate,
                $endDate,
                ['platforms' => ['facebook']]
            );

            expect($posts)->toHaveCount(1)
                ->and($posts->first()->id)->toBe($facebookPost->id);
        });

        it('filters posts by status', function () {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();

            $scheduledPost = Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(5),
                'status' => 'scheduled',
            ]);

            $publishedPost = Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(7),
                'status' => 'published',
            ]);

            $posts = $this->calendarService->getCalendarPosts(
                $this->workspace,
                $startDate,
                $endDate,
                ['status' => ['scheduled']]
            );

            expect($posts)->toHaveCount(1)
                ->and($posts->first()->id)->toBe($scheduledPost->id);
        });

        it('filters posts by author', function () {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();

            $author1 = User::factory()->create();
            $author2 = User::factory()->create();

            $post1 = Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $author1->id,
                'scheduled_at' => Carbon::now()->addDays(5),
                'status' => 'scheduled',
            ]);

            Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $author2->id,
                'scheduled_at' => Carbon::now()->addDays(7),
                'status' => 'scheduled',
            ]);

            $posts = $this->calendarService->getCalendarPosts(
                $this->workspace,
                $startDate,
                $endDate,
                ['author_id' => $author1->id]
            );

            expect($posts)->toHaveCount(1)
                ->and($posts->first()->id)->toBe($post1->id);
        });

        it('returns posts ordered by scheduled_at', function () {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();

            $post1 = Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(10),
                'status' => 'scheduled',
            ]);

            $post2 = Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(5),
                'status' => 'scheduled',
            ]);

            $posts = $this->calendarService->getCalendarPosts(
                $this->workspace,
                $startDate,
                $endDate
            );

            expect($posts->first()->id)->toBe($post2->id)
                ->and($posts->last()->id)->toBe($post1->id);
        });
    });

    describe('getCalendarPostsByDate', function () {
        it('groups posts by date', function () {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();

            $date1 = Carbon::now()->addDays(5);
            $date2 = Carbon::now()->addDays(10);

            Post::factory()->count(2)->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => $date1,
                'status' => 'scheduled',
            ]);

            Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => $date2,
                'status' => 'scheduled',
            ]);

            $grouped = $this->calendarService->getCalendarPostsByDate(
                $this->workspace,
                $startDate,
                $endDate
            );

            expect($grouped)->toHaveKey($date1->format('Y-m-d'))
                ->and($grouped)->toHaveKey($date2->format('Y-m-d'))
                ->and($grouped[$date1->format('Y-m-d')])->toHaveCount(2)
                ->and($grouped[$date2->format('Y-m-d')])->toHaveCount(1);
        });
    });

    describe('reschedulePost', function () {
        it('reschedules a post to new date', function () {
            $post = Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(5),
                'status' => 'scheduled',
            ]);

            $newScheduledAt = Carbon::now()->addDays(10);

            $updatedPost = $this->calendarService->reschedulePost(
                $post,
                $newScheduledAt
            );

            expect($updatedPost->scheduled_at->format('Y-m-d H:i'))->toBe($newScheduledAt->format('Y-m-d H:i'))
                ->and($updatedPost->status->value)->toBe('scheduled');

            // Verify in database
            $this->assertDatabaseHas('posts', [
                'id' => $post->id,
                'scheduled_at' => $newScheduledAt,
            ]);
        });

        it('reschedules post with timezone', function () {
            $post = Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(5),
                'status' => 'scheduled',
            ]);

            $newScheduledAt = Carbon::now()->addDays(10);
            $timezone = 'America/New_York';

            $updatedPost = $this->calendarService->reschedulePost(
                $post,
                $newScheduledAt,
                $timezone
            );

            expect($updatedPost->scheduled_at)->not->toBeNull()
                ->and($updatedPost->scheduled_timezone)->toBe($timezone);
        });
    });

    describe('getCalendarStats', function () {
        it('returns statistics for date range', function () {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();

            Post::factory()->count(2)->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(5),
                'status' => 'scheduled',
            ]);

            Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(7),
                'status' => 'published',
            ]);

            Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(10),
                'status' => 'failed',
            ]);

            $stats = $this->calendarService->getCalendarStats(
                $this->workspace,
                $startDate,
                $endDate
            );

            expect($stats)->toHaveKey('total_posts')
                ->and($stats)->toHaveKey('scheduled')
                ->and($stats)->toHaveKey('published')
                ->and($stats)->toHaveKey('failed')
                ->and($stats)->toHaveKey('by_platform')
                ->and($stats)->toHaveKey('by_date')
                ->and($stats['total_posts'])->toBe(4)
                ->and($stats['scheduled'])->toBe(2)
                ->and($stats['published'])->toBe(1)
                ->and($stats['failed'])->toBe(1);
        });

        it('counts posts by platform', function () {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();

            $post1 = Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(5),
                'status' => 'scheduled',
            ]);
            PostTarget::factory()->create([
                'post_id' => $post1->id,
                'platform_code' => 'facebook',
            ]);
            PostTarget::factory()->create([
                'post_id' => $post1->id,
                'platform_code' => 'twitter',
            ]);

            $post2 = Post::factory()->create([
                'workspace_id' => $this->workspace->id,
                'created_by_user_id' => $this->user->id,
                'scheduled_at' => Carbon::now()->addDays(7),
                'status' => 'scheduled',
            ]);
            PostTarget::factory()->create([
                'post_id' => $post2->id,
                'platform_code' => 'facebook',
            ]);

            $stats = $this->calendarService->getCalendarStats(
                $this->workspace,
                $startDate,
                $endDate
            );

            expect($stats['by_platform'])->toHaveKey('facebook')
                ->and($stats['by_platform'])->toHaveKey('twitter')
                ->and($stats['by_platform']['facebook'])->toBe(2)
                ->and($stats['by_platform']['twitter'])->toBe(1);
        });
    });
});
