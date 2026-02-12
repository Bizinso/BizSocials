<?php

declare(strict_types=1);

/**
 * Post Model Unit Tests
 *
 * Tests for the Post model which represents a social media post
 * within a workspace.
 *
 * @see \App\Models\Content\Post
 */

use App\Enums\Content\PostStatus;
use App\Enums\Content\PostType;
use App\Models\Content\ApprovalDecision;
use App\Models\Content\Post;
use App\Models\Content\PostMedia;
use App\Models\Content\PostTarget;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

test('has correct table name', function (): void {
    $post = new Post();

    expect($post->getTable())->toBe('posts');
});

test('uses uuid primary key', function (): void {
    $post = Post::factory()->create();

    expect($post->id)->not->toBeNull()
        ->and(strlen($post->id))->toBe(36);
});

test('uses soft deletes', function (): void {
    $post = Post::factory()->create();

    $post->delete();

    expect($post->trashed())->toBeTrue()
        ->and(Post::withTrashed()->find($post->id))->not->toBeNull();
});

test('has correct fillable attributes', function (): void {
    $post = new Post();
    $fillable = $post->getFillable();

    expect($fillable)->toContain('workspace_id')
        ->and($fillable)->toContain('created_by_user_id')
        ->and($fillable)->toContain('content_text')
        ->and($fillable)->toContain('content_variations')
        ->and($fillable)->toContain('status')
        ->and($fillable)->toContain('post_type')
        ->and($fillable)->toContain('scheduled_at')
        ->and($fillable)->toContain('scheduled_timezone')
        ->and($fillable)->toContain('published_at')
        ->and($fillable)->toContain('submitted_at')
        ->and($fillable)->toContain('hashtags')
        ->and($fillable)->toContain('mentions')
        ->and($fillable)->toContain('link_url')
        ->and($fillable)->toContain('link_preview')
        ->and($fillable)->toContain('first_comment')
        ->and($fillable)->toContain('rejection_reason')
        ->and($fillable)->toContain('metadata');
});

test('status casts to enum', function (): void {
    $post = Post::factory()->draft()->create();

    expect($post->status)->toBeInstanceOf(PostStatus::class)
        ->and($post->status)->toBe(PostStatus::DRAFT);
});

test('post_type casts to enum', function (): void {
    $post = Post::factory()->create(['post_type' => PostType::STANDARD]);

    expect($post->post_type)->toBeInstanceOf(PostType::class)
        ->and($post->post_type)->toBe(PostType::STANDARD);
});

test('content_variations casts to array', function (): void {
    $variations = [
        'twitter' => 'Short version',
        'linkedin' => 'Long professional version',
    ];

    $post = Post::factory()->create(['content_variations' => $variations]);

    expect($post->content_variations)->toBeArray()
        ->and($post->content_variations['twitter'])->toBe('Short version');
});

test('hashtags casts to array', function (): void {
    $hashtags = ['#test', '#example'];

    $post = Post::factory()->create(['hashtags' => $hashtags]);

    expect($post->hashtags)->toBeArray()
        ->and($post->hashtags)->toContain('#test')
        ->and($post->hashtags)->toContain('#example');
});

test('scheduled_at casts to datetime', function (): void {
    $post = Post::factory()->scheduled()->create();

    expect($post->scheduled_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('published_at casts to datetime', function (): void {
    $post = Post::factory()->published()->create();

    expect($post->published_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('submitted_at casts to datetime', function (): void {
    $post = Post::factory()->submitted()->create();

    expect($post->submitted_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('workspace relationship returns belongs to', function (): void {
    $post = new Post();

    expect($post->workspace())->toBeInstanceOf(BelongsTo::class);
});

test('workspace relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $workspace = Workspace::factory()->forTenant($tenant)->create();
    $post = Post::factory()->forWorkspace($workspace)->create();

    expect($post->workspace)->toBeInstanceOf(Workspace::class)
        ->and($post->workspace->id)->toBe($workspace->id);
});

test('author relationship returns belongs to', function (): void {
    $post = new Post();

    expect($post->author())->toBeInstanceOf(BelongsTo::class);
});

test('author relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();
    $post = Post::factory()->byUser($user)->create();

    expect($post->author)->toBeInstanceOf(User::class)
        ->and($post->author->id)->toBe($user->id);
});

test('targets relationship returns has many', function (): void {
    $post = new Post();

    expect($post->targets())->toBeInstanceOf(HasMany::class);
});

test('targets relationship works correctly', function (): void {
    $post = Post::factory()->create();
    PostTarget::factory()->count(3)->forPost($post)->create();

    expect($post->targets)->toHaveCount(3);
});

test('media relationship returns has many', function (): void {
    $post = new Post();

    expect($post->media())->toBeInstanceOf(HasMany::class);
});

test('media relationship works correctly', function (): void {
    $post = Post::factory()->create();
    PostMedia::factory()->count(2)->forPost($post)->create();

    expect($post->media)->toHaveCount(2);
});

test('approvalDecisions relationship returns has many', function (): void {
    $post = new Post();

    expect($post->approvalDecisions())->toBeInstanceOf(HasMany::class);
});

test('approvalDecisions relationship works correctly', function (): void {
    $post = Post::factory()->create();
    ApprovalDecision::factory()->count(2)->forPost($post)->create();

    expect($post->approvalDecisions)->toHaveCount(2);
});

test('activeApprovalDecision relationship returns has one', function (): void {
    $post = new Post();

    expect($post->activeApprovalDecision())->toBeInstanceOf(HasOne::class);
});

test('activeApprovalDecision returns only active decision', function (): void {
    $post = Post::factory()->create();
    ApprovalDecision::factory()->forPost($post)->inactive()->create();
    $activeDecision = ApprovalDecision::factory()->forPost($post)->active()->create();

    expect($post->activeApprovalDecision)->not->toBeNull()
        ->and($post->activeApprovalDecision->id)->toBe($activeDecision->id);
});

test('scope forWorkspace filters correctly', function (): void {
    $workspace1 = Workspace::factory()->create();
    $workspace2 = Workspace::factory()->create();

    Post::factory()->count(3)->forWorkspace($workspace1)->create();
    Post::factory()->count(2)->forWorkspace($workspace2)->create();

    $posts = Post::forWorkspace($workspace1->id)->get();

    expect($posts)->toHaveCount(3)
        ->and($posts->every(fn ($p) => $p->workspace_id === $workspace1->id))->toBeTrue();
});

test('scope withStatus filters by status', function (): void {
    Post::factory()->draft()->create();
    Post::factory()->submitted()->create();
    Post::factory()->published()->create();

    $draftPosts = Post::withStatus(PostStatus::DRAFT)->get();

    expect($draftPosts)->toHaveCount(1)
        ->and($draftPosts->first()->status)->toBe(PostStatus::DRAFT);
});

test('scope scheduled filters scheduled posts', function (): void {
    Post::factory()->draft()->create();
    Post::factory()->scheduled()->create();
    Post::factory()->published()->create();

    $scheduledPosts = Post::scheduled()->get();

    expect($scheduledPosts)->toHaveCount(1)
        ->and($scheduledPosts->first()->status)->toBe(PostStatus::SCHEDULED);
});

test('scope published filters published posts', function (): void {
    Post::factory()->draft()->create();
    Post::factory()->published()->create();
    Post::factory()->published()->create();

    $publishedPosts = Post::published()->get();

    expect($publishedPosts)->toHaveCount(2);
});

test('scope draft filters draft posts', function (): void {
    Post::factory()->draft()->create();
    Post::factory()->draft()->create();
    Post::factory()->published()->create();

    $draftPosts = Post::draft()->get();

    expect($draftPosts)->toHaveCount(2);
});

test('scope requiresApproval filters submitted posts', function (): void {
    Post::factory()->draft()->create();
    Post::factory()->submitted()->create();
    Post::factory()->approved()->create();

    $pendingApproval = Post::requiresApproval()->get();

    expect($pendingApproval)->toHaveCount(1)
        ->and($pendingApproval->first()->status)->toBe(PostStatus::SUBMITTED);
});

test('canEdit returns correct value based on status', function (): void {
    $draft = Post::factory()->draft()->create();
    $rejected = Post::factory()->rejected()->create();
    $published = Post::factory()->published()->create();

    expect($draft->canEdit())->toBeTrue()
        ->and($rejected->canEdit())->toBeTrue()
        ->and($published->canEdit())->toBeFalse();
});

test('canDelete returns correct value based on status', function (): void {
    $draft = Post::factory()->draft()->create();
    $published = Post::factory()->published()->create();

    expect($draft->canDelete())->toBeTrue()
        ->and($published->canDelete())->toBeFalse();
});

test('canPublish returns correct value based on status', function (): void {
    $approved = Post::factory()->approved()->create();
    $scheduled = Post::factory()->scheduled()->create();
    $draft = Post::factory()->draft()->create();

    expect($approved->canPublish())->toBeTrue()
        ->and($scheduled->canPublish())->toBeTrue()
        ->and($draft->canPublish())->toBeFalse();
});

test('hasTargets returns true when targets exist', function (): void {
    $post = Post::factory()->create();
    PostTarget::factory()->forPost($post)->create();

    expect($post->hasTargets())->toBeTrue();
});

test('hasTargets returns false when no targets exist', function (): void {
    $post = Post::factory()->create();

    expect($post->hasTargets())->toBeFalse();
});

test('getTargetCount returns correct count', function (): void {
    $post = Post::factory()->create();
    PostTarget::factory()->count(3)->forPost($post)->create();

    expect($post->getTargetCount())->toBe(3);
});

test('submit transitions to submitted status', function (): void {
    $post = Post::factory()->draft()->create();

    $post->submit();

    expect($post->status)->toBe(PostStatus::SUBMITTED)
        ->and($post->submitted_at)->not->toBeNull();
});

test('submit does not transition from invalid status', function (): void {
    $post = Post::factory()->published()->create();

    $post->submit();

    expect($post->status)->toBe(PostStatus::PUBLISHED);
});

test('approve transitions to approved status', function (): void {
    $post = Post::factory()->submitted()->create();

    $post->approve();

    expect($post->status)->toBe(PostStatus::APPROVED)
        ->and($post->rejection_reason)->toBeNull();
});

test('reject transitions to rejected status', function (): void {
    $post = Post::factory()->submitted()->create();
    $reason = 'Content needs revision';

    $post->reject($reason);

    expect($post->status)->toBe(PostStatus::REJECTED)
        ->and($post->rejection_reason)->toBe($reason);
});

test('schedule transitions to scheduled status', function (): void {
    $post = Post::factory()->approved()->create();
    $scheduledAt = now()->addDays(3);

    $post->schedule($scheduledAt, 'America/New_York');

    expect($post->status)->toBe(PostStatus::SCHEDULED)
        ->and($post->scheduled_at->format('Y-m-d'))->toBe($scheduledAt->format('Y-m-d'))
        ->and($post->scheduled_timezone)->toBe('America/New_York');
});

test('markPublishing transitions to publishing status', function (): void {
    $post = Post::factory()->scheduled()->create();

    $post->markPublishing();

    expect($post->status)->toBe(PostStatus::PUBLISHING);
});

test('markPublished transitions to published status', function (): void {
    $post = Post::factory()->publishing()->create();

    $post->markPublished();

    expect($post->status)->toBe(PostStatus::PUBLISHED)
        ->and($post->published_at)->not->toBeNull();
});

test('markFailed transitions to failed status', function (): void {
    $post = Post::factory()->publishing()->create();

    $post->markFailed();

    expect($post->status)->toBe(PostStatus::FAILED);
});

test('cancel transitions to cancelled status', function (): void {
    $post = Post::factory()->scheduled()->create();

    $post->cancel();

    expect($post->status)->toBe(PostStatus::CANCELLED);
});

test('factory creates valid model', function (): void {
    $post = Post::factory()->create();

    expect($post)->toBeInstanceOf(Post::class)
        ->and($post->id)->not->toBeNull()
        ->and($post->workspace_id)->not->toBeNull()
        ->and($post->created_by_user_id)->not->toBeNull()
        ->and($post->status)->toBeInstanceOf(PostStatus::class)
        ->and($post->post_type)->toBeInstanceOf(PostType::class);
});

test('factory draft state works correctly', function (): void {
    $post = Post::factory()->draft()->create();

    expect($post->status)->toBe(PostStatus::DRAFT);
});

test('factory submitted state works correctly', function (): void {
    $post = Post::factory()->submitted()->create();

    expect($post->status)->toBe(PostStatus::SUBMITTED)
        ->and($post->submitted_at)->not->toBeNull();
});

test('factory approved state works correctly', function (): void {
    $post = Post::factory()->approved()->create();

    expect($post->status)->toBe(PostStatus::APPROVED);
});

test('factory rejected state works correctly', function (): void {
    $post = Post::factory()->rejected()->create();

    expect($post->status)->toBe(PostStatus::REJECTED)
        ->and($post->rejection_reason)->not->toBeNull();
});

test('factory scheduled state works correctly', function (): void {
    $post = Post::factory()->scheduled()->create();

    expect($post->status)->toBe(PostStatus::SCHEDULED)
        ->and($post->scheduled_at)->not->toBeNull()
        ->and($post->scheduled_timezone)->not->toBeNull();
});

test('factory published state works correctly', function (): void {
    $post = Post::factory()->published()->create();

    expect($post->status)->toBe(PostStatus::PUBLISHED)
        ->and($post->published_at)->not->toBeNull();
});

test('factory failed state works correctly', function (): void {
    $post = Post::factory()->failed()->create();

    expect($post->status)->toBe(PostStatus::FAILED);
});

test('factory cancelled state works correctly', function (): void {
    $post = Post::factory()->cancelled()->create();

    expect($post->status)->toBe(PostStatus::CANCELLED);
});

test('factory withContent state works correctly', function (): void {
    $content = 'Custom post content';
    $post = Post::factory()->withContent($content)->create();

    expect($post->content_text)->toBe($content);
});
