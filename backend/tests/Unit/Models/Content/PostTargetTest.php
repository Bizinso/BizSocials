<?php

declare(strict_types=1);

/**
 * PostTarget Model Unit Tests
 *
 * Tests for the PostTarget model which represents a post being
 * published to a specific social account.
 *
 * @see \App\Models\Content\PostTarget
 */

use App\Enums\Content\PostTargetStatus;
use App\Enums\Social\SocialPlatform;
use App\Models\Content\Post;
use App\Models\Content\PostTarget;
use App\Models\Social\SocialAccount;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;

test('has correct table name', function (): void {
    $target = new PostTarget();

    expect($target->getTable())->toBe('post_targets');
});

test('uses uuid primary key', function (): void {
    $target = PostTarget::factory()->create();

    expect($target->id)->not->toBeNull()
        ->and(strlen($target->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $target = new PostTarget();
    $fillable = $target->getFillable();

    expect($fillable)->toContain('post_id')
        ->and($fillable)->toContain('social_account_id')
        ->and($fillable)->toContain('platform_code')
        ->and($fillable)->toContain('content_override')
        ->and($fillable)->toContain('status')
        ->and($fillable)->toContain('external_post_id')
        ->and($fillable)->toContain('external_post_url')
        ->and($fillable)->toContain('published_at')
        ->and($fillable)->toContain('error_code')
        ->and($fillable)->toContain('error_message')
        ->and($fillable)->toContain('retry_count')
        ->and($fillable)->toContain('metrics');
});

test('status casts to enum', function (): void {
    $target = PostTarget::factory()->pending()->create();

    expect($target->status)->toBeInstanceOf(PostTargetStatus::class)
        ->and($target->status)->toBe(PostTargetStatus::PENDING);
});

test('published_at casts to datetime', function (): void {
    $target = PostTarget::factory()->published()->create();

    expect($target->published_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('metrics casts to array', function (): void {
    $metrics = ['likes' => 100, 'shares' => 50];
    $target = PostTarget::factory()->create(['metrics' => $metrics]);

    expect($target->metrics)->toBeArray()
        ->and($target->metrics['likes'])->toBe(100);
});

test('retry_count casts to integer', function (): void {
    $target = PostTarget::factory()->create(['retry_count' => 3]);

    expect($target->retry_count)->toBeInt()
        ->and($target->retry_count)->toBe(3);
});

test('post relationship returns belongs to', function (): void {
    $target = new PostTarget();

    expect($target->post())->toBeInstanceOf(BelongsTo::class);
});

test('post relationship works correctly', function (): void {
    $post = Post::factory()->create();
    $target = PostTarget::factory()->forPost($post)->create();

    expect($target->post)->toBeInstanceOf(Post::class)
        ->and($target->post->id)->toBe($post->id);
});

test('socialAccount relationship returns belongs to', function (): void {
    $target = new PostTarget();

    expect($target->socialAccount())->toBeInstanceOf(BelongsTo::class);
});

test('socialAccount relationship works correctly', function (): void {
    $socialAccount = SocialAccount::factory()->create();
    $target = PostTarget::factory()->forSocialAccount($socialAccount)->create();

    expect($target->socialAccount)->toBeInstanceOf(SocialAccount::class)
        ->and($target->socialAccount->id)->toBe($socialAccount->id);
});

test('scope forPost filters correctly', function (): void {
    $post1 = Post::factory()->create();
    $post2 = Post::factory()->create();

    PostTarget::factory()->count(3)->forPost($post1)->create();
    PostTarget::factory()->count(2)->forPost($post2)->create();

    $targets = PostTarget::forPost($post1->id)->get();

    expect($targets)->toHaveCount(3)
        ->and($targets->every(fn ($t) => $t->post_id === $post1->id))->toBeTrue();
});

test('scope forPlatform filters by platform enum', function (): void {
    PostTarget::factory()->forPlatform(SocialPlatform::LINKEDIN)->create();
    PostTarget::factory()->forPlatform(SocialPlatform::FACEBOOK)->create();
    PostTarget::factory()->forPlatform(SocialPlatform::TWITTER)->create();

    $linkedinTargets = PostTarget::forPlatform(SocialPlatform::LINKEDIN)->get();

    expect($linkedinTargets)->toHaveCount(1)
        ->and($linkedinTargets->first()->platform_code)->toBe('linkedin');
});

test('scope forPlatform filters by platform string', function (): void {
    PostTarget::factory()->forPlatform(SocialPlatform::LINKEDIN)->create();
    PostTarget::factory()->forPlatform(SocialPlatform::FACEBOOK)->create();

    $facebookTargets = PostTarget::forPlatform('facebook')->get();

    expect($facebookTargets)->toHaveCount(1)
        ->and($facebookTargets->first()->platform_code)->toBe('facebook');
});

test('scope pending filters pending status', function (): void {
    PostTarget::factory()->pending()->create();
    PostTarget::factory()->pending()->create();
    PostTarget::factory()->published()->create();

    $pendingTargets = PostTarget::pending()->get();

    expect($pendingTargets)->toHaveCount(2);
});

test('scope published filters published status', function (): void {
    PostTarget::factory()->pending()->create();
    PostTarget::factory()->published()->create();
    PostTarget::factory()->published()->create();

    $publishedTargets = PostTarget::published()->get();

    expect($publishedTargets)->toHaveCount(2);
});

test('scope failed filters failed status', function (): void {
    PostTarget::factory()->pending()->create();
    PostTarget::factory()->failed()->create();

    $failedTargets = PostTarget::failed()->get();

    expect($failedTargets)->toHaveCount(1);
});

test('isPublished returns correct value', function (): void {
    $published = PostTarget::factory()->published()->create();
    $pending = PostTarget::factory()->pending()->create();

    expect($published->isPublished())->toBeTrue()
        ->and($pending->isPublished())->toBeFalse();
});

test('hasFailed returns correct value', function (): void {
    $failed = PostTarget::factory()->failed()->create();
    $pending = PostTarget::factory()->pending()->create();

    expect($failed->hasFailed())->toBeTrue()
        ->and($pending->hasFailed())->toBeFalse();
});

test('markPublishing updates status', function (): void {
    $target = PostTarget::factory()->pending()->create();

    $target->markPublishing();

    expect($target->status)->toBe(PostTargetStatus::PUBLISHING);
});

test('markPublished updates status and external info', function (): void {
    $target = PostTarget::factory()->publishing()->create();
    $externalId = 'ext_123456';
    $externalUrl = 'https://linkedin.com/posts/123456';

    $target->markPublished($externalId, $externalUrl);

    expect($target->status)->toBe(PostTargetStatus::PUBLISHED)
        ->and($target->external_post_id)->toBe($externalId)
        ->and($target->external_post_url)->toBe($externalUrl)
        ->and($target->published_at)->not->toBeNull()
        ->and($target->error_code)->toBeNull()
        ->and($target->error_message)->toBeNull();
});

test('markFailed updates status and error info', function (): void {
    $target = PostTarget::factory()->publishing()->create();
    $errorCode = 'AUTH_ERROR';
    $errorMessage = 'Token expired';

    $target->markFailed($errorCode, $errorMessage);

    expect($target->status)->toBe(PostTargetStatus::FAILED)
        ->and($target->error_code)->toBe($errorCode)
        ->and($target->error_message)->toBe($errorMessage);
});

test('incrementRetry increases retry_count', function (): void {
    $target = PostTarget::factory()->create(['retry_count' => 0]);

    $target->incrementRetry();
    expect($target->retry_count)->toBe(1);

    $target->incrementRetry();
    expect($target->retry_count)->toBe(2);
});

test('getContent returns content_override when set', function (): void {
    $post = Post::factory()->create(['content_text' => 'Original content']);
    $target = PostTarget::factory()->forPost($post)->create([
        'content_override' => 'Override content',
    ]);

    expect($target->getContent())->toBe('Override content');
});

test('getContent returns post content when no override', function (): void {
    $post = Post::factory()->create(['content_text' => 'Original content']);
    $target = PostTarget::factory()->forPost($post)->create([
        'content_override' => null,
    ]);

    expect($target->getContent())->toBe('Original content');
});

test('unique constraint on post_id and social_account_id', function (): void {
    $post = Post::factory()->create();
    $socialAccount = SocialAccount::factory()->create();

    PostTarget::factory()->forPost($post)->forSocialAccount($socialAccount)->create();

    expect(fn () => PostTarget::factory()->forPost($post)->forSocialAccount($socialAccount)->create())
        ->toThrow(QueryException::class);
});

test('same social account can be used for different posts', function (): void {
    $post1 = Post::factory()->create();
    $post2 = Post::factory()->create();
    $socialAccount = SocialAccount::factory()->create();

    $target1 = PostTarget::factory()->forPost($post1)->forSocialAccount($socialAccount)->create();
    $target2 = PostTarget::factory()->forPost($post2)->forSocialAccount($socialAccount)->create();

    expect($target1->id)->not->toBe($target2->id)
        ->and($target1->social_account_id)->toBe($target2->social_account_id);
});

test('factory creates valid model', function (): void {
    $target = PostTarget::factory()->create();

    expect($target)->toBeInstanceOf(PostTarget::class)
        ->and($target->id)->not->toBeNull()
        ->and($target->post_id)->not->toBeNull()
        ->and($target->social_account_id)->not->toBeNull()
        ->and($target->platform_code)->not->toBeNull()
        ->and($target->status)->toBeInstanceOf(PostTargetStatus::class);
});

test('factory pending state works correctly', function (): void {
    $target = PostTarget::factory()->pending()->create();

    expect($target->status)->toBe(PostTargetStatus::PENDING);
});

test('factory publishing state works correctly', function (): void {
    $target = PostTarget::factory()->publishing()->create();

    expect($target->status)->toBe(PostTargetStatus::PUBLISHING);
});

test('factory published state works correctly', function (): void {
    $target = PostTarget::factory()->published()->create();

    expect($target->status)->toBe(PostTargetStatus::PUBLISHED)
        ->and($target->external_post_id)->not->toBeNull()
        ->and($target->external_post_url)->not->toBeNull()
        ->and($target->published_at)->not->toBeNull()
        ->and($target->metrics)->not->toBeNull();
});

test('factory failed state works correctly', function (): void {
    $target = PostTarget::factory()->failed()->create();

    expect($target->status)->toBe(PostTargetStatus::FAILED)
        ->and($target->error_code)->not->toBeNull()
        ->and($target->error_message)->not->toBeNull()
        ->and($target->retry_count)->toBeGreaterThanOrEqual(1);
});
