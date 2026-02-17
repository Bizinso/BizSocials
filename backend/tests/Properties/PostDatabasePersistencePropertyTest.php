<?php

declare(strict_types=1);

namespace Tests\Properties;

use App\Data\Content\CreatePostData;
use App\Enums\Content\PostStatus;
use App\Enums\Content\PostType;
use App\Models\Content\Post;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\PostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\PropertyGenerators;
use Tests\Helpers\PropertyTestTrait;
use Tests\TestCase;

/**
 * Post Database Persistence Property Test
 *
 * Tests that post operations persist to the database correctly.
 *
 * Feature: platform-audit-and-testing
 */
class PostDatabasePersistencePropertyTest extends TestCase
{
    use PropertyTestTrait;
    use RefreshDatabase;

    /**
     * Override the default iteration count to reduce memory usage.
     */
    protected function getPropertyTestIterations(): int
    {
        return 5; // Minimal iterations for testing
    }

    /**
     * Property 7: Database Persistence Verification - Post Creation
     *
     * For any post creation operation, the post should be persisted to the database
     * and be retrievable with all its properties intact.
     *
     * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
     * Validates: Requirements 3.1
     */
    public function test_post_creation_persists_to_database(): void
    {
        $this->forAll(
            PropertyGenerators::string(10, 500)
        )
            ->then(function ($content) {
                // Create a user and workspace
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);

                // Create post data
                $postData = new CreatePostData(
                    content_text: $content,
                    post_type: PostType::STANDARD
                );

                // Create the post using the service
                $postService = app(PostService::class);
                $createdPost = $postService->create($workspace, $user, $postData);

                // Verify the post exists in the database
                $this->assertDatabaseHas('posts', [
                    'id' => $createdPost->id,
                    'workspace_id' => $workspace->id,
                    'created_by_user_id' => $user->id,
                    'content_text' => $content,
                    'status' => PostStatus::DRAFT->value,
                ]);

                // Verify we can retrieve the post from the database
                $retrievedPost = Post::find($createdPost->id);
                $this->assertNotNull($retrievedPost);
                $this->assertEquals($content, $retrievedPost->content_text);
                $this->assertEquals(PostStatus::DRAFT, $retrievedPost->status);
            });
    }

    /**
     * Property 7: Database Persistence Verification - Post Update
     *
     * For any post update operation, the changes should be persisted to the database
     * and be immediately queryable.
     *
     * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
     * Validates: Requirements 3.1
     */
    public function test_post_update_persists_to_database(): void
    {
        $this->forAll(
            PropertyGenerators::string(10, 500),
            PropertyGenerators::string(10, 500)
        )
            ->then(function ($originalContent, $updatedContent) {
                // Create a user and workspace
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);

                // Create a post
                $post = Post::factory()->create([
                    'workspace_id' => $workspace->id,
                    'created_by_user_id' => $user->id,
                    'content_text' => $originalContent,
                    'status' => PostStatus::DRAFT,
                ]);

                // Update the post directly
                $post->update(['content_text' => $updatedContent]);

                // Verify the update is persisted in the database
                $this->assertDatabaseHas('posts', [
                    'id' => $post->id,
                    'content_text' => $updatedContent,
                ]);

                // Verify we can retrieve the updated post
                $retrievedPost = Post::find($post->id);
                $this->assertNotNull($retrievedPost);
                $this->assertEquals($updatedContent, $retrievedPost->content_text);
                $this->assertNotEquals($originalContent, $retrievedPost->content_text);
            });
    }

    /**
     * Property 7: Database Persistence Verification - Post Deletion
     *
     * For any post deletion operation, the post should be soft deleted from the database
     * and no longer be retrievable via normal queries.
     *
     * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
     * Validates: Requirements 3.1
     */
    public function test_post_deletion_soft_deletes_from_database(): void
    {
        $this->forAll(
            PropertyGenerators::integer(1, 10)
        )
            ->then(function ($id) {
                // Create a user and workspace
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);

                // Create a post
                $post = Post::factory()->create([
                    'workspace_id' => $workspace->id,
                    'created_by_user_id' => $user->id,
                    'status' => PostStatus::DRAFT,
                ]);

                $postId = $post->id;

                // Verify the post exists
                $this->assertDatabaseHas('posts', ['id' => $postId]);

                // Delete the post
                $postService = app(PostService::class);
                $postService->delete($post);

                // Verify we cannot retrieve the deleted post via normal query
                $retrievedPost = Post::find($postId);
                $this->assertNull($retrievedPost);

                // Verify the post still exists in database but with deleted_at set (soft delete)
                $deletedPost = Post::withTrashed()->find($postId);
                $this->assertNotNull($deletedPost);
                $this->assertNotNull($deletedPost->deleted_at);
            });
    }

    /**
     * Property 7: Database Persistence Verification - Post Status Transitions
     *
     * For any post status transition (draft -> submitted -> approved -> scheduled), the status
     * change should be immediately persisted and queryable from the database.
     *
     * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
     * Validates: Requirements 3.1
     */
    public function test_post_status_transitions_persist_to_database(): void
    {
        $this->forAll(
            PropertyGenerators::integer(1, 24)
        )
            ->then(function ($hoursInFuture) {
                // Create a user and workspace
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);

                // Create a draft post
                $post = Post::factory()->create([
                    'workspace_id' => $workspace->id,
                    'created_by_user_id' => $user->id,
                    'status' => PostStatus::DRAFT,
                ]);

                // Verify initial status
                $this->assertDatabaseHas('posts', [
                    'id' => $post->id,
                    'status' => PostStatus::DRAFT->value,
                ]);

                // Transition to SUBMITTED
                $post->submit();

                // Verify status change is persisted
                $this->assertDatabaseHas('posts', [
                    'id' => $post->id,
                    'status' => PostStatus::SUBMITTED->value,
                ]);

                // Transition to APPROVED
                $post->approve();

                // Verify status change is persisted
                $this->assertDatabaseHas('posts', [
                    'id' => $post->id,
                    'status' => PostStatus::APPROVED->value,
                ]);

                // Verify we can query by status
                $retrievedPost = Post::find($post->id);
                $this->assertEquals(PostStatus::APPROVED, $retrievedPost->status);

                // Schedule the post
                $scheduledTime = now()->addHours($hoursInFuture);
                $post->schedule($scheduledTime);

                // Verify scheduled status is persisted
                $this->assertDatabaseHas('posts', [
                    'id' => $post->id,
                    'status' => PostStatus::SCHEDULED->value,
                ]);

                // Verify scheduled_at is persisted
                $retrievedPost = Post::find($post->id);
                $this->assertEquals(PostStatus::SCHEDULED, $retrievedPost->status);
                $this->assertNotNull($retrievedPost->scheduled_at);
            });
    }

    /**
     * Property 7: Database Persistence Verification - Post Scheduling
     *
     * For any post scheduling operation, the scheduled_at timestamp should be
     * persisted to the database and be retrievable.
     *
     * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
     * Validates: Requirements 3.1
     */
    public function test_post_scheduling_persists_to_database(): void
    {
        $this->forAll(
            PropertyGenerators::integer(1, 48)
        )
            ->then(function ($hoursInFuture) {
                // Create a user and workspace
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);

                // Create an approved post
                $post = Post::factory()->create([
                    'workspace_id' => $workspace->id,
                    'created_by_user_id' => $user->id,
                    'status' => PostStatus::APPROVED,
                ]);

                // Add a target to avoid validation errors
                \App\Models\Content\PostTarget::factory()->create([
                    'post_id' => $post->id,
                    'platform_code' => 'facebook',
                    'social_account_id' => \App\Models\Social\SocialAccount::factory()->facebook()->create([
                        'workspace_id' => $workspace->id,
                    ])->id,
                ]);

                // Schedule the post
                $scheduledTime = now()->addHours($hoursInFuture);
                $postService = app(PostService::class);
                $postService->schedule($post, $scheduledTime);

                // Verify the scheduled_at is persisted
                $retrievedPost = Post::find($post->id);
                $this->assertNotNull($retrievedPost->scheduled_at);
                $this->assertEquals(PostStatus::SCHEDULED, $retrievedPost->status);

                // Verify we can query scheduled posts
                $scheduledPosts = Post::scheduled()->where('id', $post->id)->get();
                $this->assertCount(1, $scheduledPosts);
            });
    }

    /**
     * Property 7: Database Persistence Verification - Post Duplication
     *
     * For any post duplication operation, a new post record should be created
     * in the database with the same content but a different ID.
     *
     * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
     * Validates: Requirements 3.1
     */
    public function test_post_duplication_creates_new_database_record(): void
    {
        $this->forAll(
            PropertyGenerators::string(10, 500)
        )
            ->then(function ($content) {
                // Create a user and workspace
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);

                // Create a post
                $originalPost = Post::factory()->create([
                    'workspace_id' => $workspace->id,
                    'created_by_user_id' => $user->id,
                    'content_text' => $content,
                    'status' => PostStatus::DRAFT,
                ]);

                // Duplicate the post
                $postService = app(PostService::class);
                $duplicatedPost = $postService->duplicate($originalPost, $user);

                // Verify both posts exist in the database
                $this->assertDatabaseHas('posts', ['id' => $originalPost->id]);
                $this->assertDatabaseHas('posts', ['id' => $duplicatedPost->id]);

                // Verify they have different IDs
                $this->assertNotEquals($originalPost->id, $duplicatedPost->id);

                // Verify the content is the same
                $this->assertEquals($originalPost->content_text, $duplicatedPost->content_text);

                // Verify we can retrieve both posts
                $retrievedOriginal = Post::find($originalPost->id);
                $retrievedDuplicate = Post::find($duplicatedPost->id);
                $this->assertNotNull($retrievedOriginal);
                $this->assertNotNull($retrievedDuplicate);
            });
    }

    /**
     * Property 7: Database Persistence Verification - Post Query Consistency
     *
     * For any post that exists in the database, querying it multiple times
     * should return consistent results.
     *
     * Feature: platform-audit-and-testing, Property 7: Database Persistence Verification
     * Validates: Requirements 3.1
     */
    public function test_post_queries_return_consistent_results(): void
    {
        $this->forAll(
            PropertyGenerators::integer(2, 5)
        )
            ->then(function ($queryCount) {
                // Create a user and workspace
                $user = User::factory()->create();
                $workspace = Workspace::factory()->create([
                    'tenant_id' => $user->tenant_id,
                ]);

                // Create a post
                $post = Post::factory()->create([
                    'workspace_id' => $workspace->id,
                    'created_by_user_id' => $user->id,
                    'status' => PostStatus::DRAFT,
                ]);

                // Query the post multiple times
                $results = [];
                for ($i = 0; $i < $queryCount; $i++) {
                    $results[] = Post::find($post->id);
                }

                // Verify all queries returned the same post
                foreach ($results as $result) {
                    $this->assertNotNull($result);
                    $this->assertEquals($post->id, $result->id);
                    $this->assertEquals($post->content_text, $result->content_text);
                    $this->assertEquals($post->status, $result->status);
                }
            });
    }
}
