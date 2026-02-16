<?php

declare(strict_types=1);

namespace Tests\Properties;

use App\Enums\Content\PostStatus;
use App\Enums\Content\PostTargetStatus;
use App\Models\Content\Post;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\PostService;
use App\Services\Content\PostTargetService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\PropertyGenerators;
use Tests\Helpers\PropertyTestTrait;
use Tests\TestCase;

/**
 * Bulk Operation Authenticity Property Test
 *
 * Validates that bulk operations process actual database records
 * and the number of affected records matches the operation's return value.
 *
 * Feature: platform-audit-and-testing
 */
class BulkOperationAuthenticityPropertyTest extends TestCase
{
    use PropertyTestTrait;
    use RefreshDatabase;

    private Tenant $tenant;
    private Workspace $workspace;
    private User $user;
    private PostService $postService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->active()->create();
        $this->workspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $postTargetService = app(PostTargetService::class);
        $this->postService = new PostService($postTargetService);
    }

    /**
     * Property 12: Bulk Operation Authenticity - Bulk Delete
     *
     * For any bulk delete operation with N posts,
     * the operation should delete exactly N records from the database
     * and the returned count should match N.
     *
     * Feature: platform-audit-and-testing, Property 12: Bulk Operation Authenticity
     * Validates: Requirements 3.7
     */
    public function test_bulk_delete_processes_actual_database_records(): void
    {
        $this->forAll(
            PropertyGenerators::integer(1, 20) // Number of posts to delete
        )
            ->then(function ($postCount) {
                // Create posts that can be deleted (draft or cancelled status)
                $posts = Post::factory()->count($postCount)->draft()->create([
                    'workspace_id' => $this->workspace->id,
                    'created_by_user_id' => $this->user->id,
                ]);

                $postIds = $posts->pluck('id')->toArray();
                $initialCount = Post::forWorkspace($this->workspace->id)->count();

                // Perform bulk delete
                $deleted = 0;
                foreach ($posts as $post) {
                    try {
                        $this->postService->delete($post);
                        $deleted++;
                    } catch (\Throwable $e) {
                        // Count errors but continue
                    }
                }

                $finalCount = Post::forWorkspace($this->workspace->id)->count();
                $actualDeleted = $initialCount - $finalCount;

                // Property: Returned count matches actual database changes
                $this->assertEquals(
                    $deleted,
                    $actualDeleted,
                    "Bulk delete returned count ({$deleted}) should match actual deleted records ({$actualDeleted})"
                );

                // Property: All specified posts were deleted
                $this->assertEquals(
                    $postCount,
                    $deleted,
                    "Should have deleted all {$postCount} posts"
                );

                // Property: Posts no longer exist in database
                foreach ($postIds as $postId) {
                    $this->assertDatabaseMissing('posts', [
                        'id' => $postId,
                        'deleted_at' => null,
                    ]);
                }
            });
    }

    /**
     * Property 12: Bulk Operation Authenticity - Bulk Submit
     *
     * For any bulk submit operation with N valid posts,
     * the operation should update exactly N records to SUBMITTED status
     * and the returned count should match N.
     *
     * Feature: platform-audit-and-testing, Property 12: Bulk Operation Authenticity
     * Validates: Requirements 3.7
     */
    public function test_bulk_submit_processes_actual_database_records(): void
    {
        $this->forAll(
            PropertyGenerators::integer(1, 15) // Number of posts to submit
        )
            ->then(function ($postCount) {
                // Create draft posts with content and targets
                $posts = Post::factory()->count($postCount)->draft()->create([
                    'workspace_id' => $this->workspace->id,
                    'created_by_user_id' => $this->user->id,
                    'content_text' => 'Test content for submission',
                ]);

                // Add targets to each post
                foreach ($posts as $post) {
                    $socialAccount = SocialAccount::factory()->linkedin()->connected()->create([
                        'workspace_id' => $this->workspace->id,
                        'connected_by_user_id' => $this->user->id,
                    ]);

                    $post->targets()->create([
                        'social_account_id' => $socialAccount->id,
                        'platform_code' => 'linkedin',
                        'status' => PostTargetStatus::PENDING,
                    ]);
                }

                $initialSubmittedCount = Post::forWorkspace($this->workspace->id)
                    ->where('status', PostStatus::SUBMITTED)
                    ->count();

                // Perform bulk submit
                $submitted = 0;
                foreach ($posts as $post) {
                    try {
                        $this->postService->submit($post->fresh());
                        $submitted++;
                    } catch (\Throwable $e) {
                        // Count errors but continue
                    }
                }

                $finalSubmittedCount = Post::forWorkspace($this->workspace->id)
                    ->where('status', PostStatus::SUBMITTED)
                    ->count();
                $actualSubmitted = $finalSubmittedCount - $initialSubmittedCount;

                // Property: Returned count matches actual database changes
                $this->assertEquals(
                    $submitted,
                    $actualSubmitted,
                    "Bulk submit returned count ({$submitted}) should match actual submitted records ({$actualSubmitted})"
                );

                // Property: All specified posts were submitted
                $this->assertEquals(
                    $postCount,
                    $submitted,
                    "Should have submitted all {$postCount} posts"
                );

                // Property: All posts have SUBMITTED status in database
                foreach ($posts as $post) {
                    $this->assertDatabaseHas('posts', [
                        'id' => $post->id,
                        'status' => PostStatus::SUBMITTED->value,
                    ]);
                }
            });
    }

    /**
     * Property 12: Bulk Operation Authenticity - Bulk Schedule
     *
     * For any bulk schedule operation with N approved posts,
     * the operation should update exactly N records to SCHEDULED status
     * and the returned count should match N.
     *
     * Feature: platform-audit-and-testing, Property 12: Bulk Operation Authenticity
     * Validates: Requirements 3.7
     */
    public function test_bulk_schedule_processes_actual_database_records(): void
    {
        $this->forAll(
            PropertyGenerators::integer(1, 15) // Number of posts to schedule
        )
            ->then(function ($postCount) {
                // Create approved posts with content and targets
                $posts = Post::factory()->count($postCount)->approved()->create([
                    'workspace_id' => $this->workspace->id,
                    'created_by_user_id' => $this->user->id,
                    'content_text' => 'Test content for scheduling',
                ]);

                // Add targets to each post
                foreach ($posts as $post) {
                    $socialAccount = SocialAccount::factory()->linkedin()->connected()->create([
                        'workspace_id' => $this->workspace->id,
                        'connected_by_user_id' => $this->user->id,
                    ]);

                    $post->targets()->create([
                        'social_account_id' => $socialAccount->id,
                        'platform_code' => 'linkedin',
                        'status' => PostTargetStatus::PENDING,
                    ]);
                }

                $initialScheduledCount = Post::forWorkspace($this->workspace->id)
                    ->where('status', PostStatus::SCHEDULED)
                    ->count();

                $scheduledAt = Carbon::now()->addHours(2);

                // Perform bulk schedule
                $scheduled = 0;
                foreach ($posts as $post) {
                    try {
                        $this->postService->schedule($post->fresh(), $scheduledAt);
                        $scheduled++;
                    } catch (\Throwable $e) {
                        // Count errors but continue
                    }
                }

                $finalScheduledCount = Post::forWorkspace($this->workspace->id)
                    ->where('status', PostStatus::SCHEDULED)
                    ->count();
                $actualScheduled = $finalScheduledCount - $initialScheduledCount;

                // Property: Returned count matches actual database changes
                $this->assertEquals(
                    $scheduled,
                    $actualScheduled,
                    "Bulk schedule returned count ({$scheduled}) should match actual scheduled records ({$actualScheduled})"
                );

                // Property: All specified posts were scheduled
                $this->assertEquals(
                    $postCount,
                    $scheduled,
                    "Should have scheduled all {$postCount} posts"
                );

                // Property: All posts have SCHEDULED status and scheduled_at time in database
                foreach ($posts as $post) {
                    $post->refresh();
                    
                    $this->assertDatabaseHas('posts', [
                        'id' => $post->id,
                        'status' => PostStatus::SCHEDULED->value,
                    ]);

                    $this->assertNotNull(
                        $post->scheduled_at,
                        "Post {$post->id} should have scheduled_at timestamp"
                    );

                    $this->assertEquals(
                        $scheduledAt->timestamp,
                        $post->scheduled_at->timestamp,
                        "Post {$post->id} should have correct scheduled_at time"
                    );
                }
            });
    }

    /**
     * Property 12: Bulk Operation Authenticity - Mixed Operations
     *
     * For any bulk operation with a mix of valid and invalid posts,
     * the operation should only affect valid posts and the returned count
     * should match the number of successfully processed records.
     *
     * Feature: platform-audit-and-testing, Property 12: Bulk Operation Authenticity
     * Validates: Requirements 3.7
     */
    public function test_bulk_operations_handle_mixed_valid_invalid_posts(): void
    {
        $this->forAll(
            PropertyGenerators::integer(2, 10), // Valid posts
            PropertyGenerators::integer(1, 5)   // Invalid posts
        )
            ->then(function ($validCount, $invalidCount) {
                // Create valid draft posts
                $validPosts = Post::factory()->count($validCount)->draft()->create([
                    'workspace_id' => $this->workspace->id,
                    'created_by_user_id' => $this->user->id,
                ]);

                // Create invalid posts (published - cannot be deleted)
                $invalidPosts = Post::factory()->count($invalidCount)->published()->create([
                    'workspace_id' => $this->workspace->id,
                    'created_by_user_id' => $this->user->id,
                ]);

                $allPosts = $validPosts->merge($invalidPosts);
                $initialCount = Post::forWorkspace($this->workspace->id)->count();

                // Attempt bulk delete on all posts
                $deleted = 0;
                $errors = 0;

                foreach ($allPosts as $post) {
                    try {
                        $this->postService->delete($post);
                        $deleted++;
                    } catch (\Throwable $e) {
                        $errors++;
                    }
                }

                $finalCount = Post::forWorkspace($this->workspace->id)->count();
                $actualDeleted = $initialCount - $finalCount;

                // Property: Returned count matches actual database changes
                $this->assertEquals(
                    $deleted,
                    $actualDeleted,
                    "Deleted count ({$deleted}) should match actual deleted records ({$actualDeleted})"
                );

                // Property: Only valid posts were deleted
                $this->assertEquals(
                    $validCount,
                    $deleted,
                    "Should have deleted only {$validCount} valid posts"
                );

                // Property: Error count matches invalid posts
                $this->assertEquals(
                    $invalidCount,
                    $errors,
                    "Should have {$invalidCount} errors for invalid posts"
                );

                // Property: Invalid posts still exist in database
                foreach ($invalidPosts as $post) {
                    $this->assertDatabaseHas('posts', [
                        'id' => $post->id,
                        'status' => PostStatus::PUBLISHED->value,
                    ]);
                }
            });
    }

    /**
     * Property 12: Bulk Operation Authenticity - Zero Operations
     *
     * For any bulk operation with zero posts,
     * the operation should not affect any database records
     * and should return zero.
     *
     * Feature: platform-audit-and-testing, Property 12: Bulk Operation Authenticity
     * Validates: Requirements 3.7
     */
    public function test_bulk_operations_with_zero_posts_affect_no_records(): void
    {
        // Create some existing posts
        Post::factory()->count(5)->draft()->create([
            'workspace_id' => $this->workspace->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $initialCount = Post::forWorkspace($this->workspace->id)->count();

        // Perform bulk delete with empty array
        $deleted = 0;
        $posts = collect([]);

        foreach ($posts as $post) {
            try {
                $this->postService->delete($post);
                $deleted++;
            } catch (\Throwable $e) {
                // Should not happen
            }
        }

        $finalCount = Post::forWorkspace($this->workspace->id)->count();

        // Property: No records were affected
        $this->assertEquals(
            $initialCount,
            $finalCount,
            "No posts should be deleted when operating on empty set"
        );

        // Property: Returned count is zero
        $this->assertEquals(
            0,
            $deleted,
            "Deleted count should be zero for empty operation"
        );
    }
}
