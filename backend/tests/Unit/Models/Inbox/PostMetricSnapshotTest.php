<?php

declare(strict_types=1);

/**
 * PostMetricSnapshot Model Unit Tests
 *
 * Tests for the PostMetricSnapshot model which represents periodic
 * engagement metrics for published posts.
 *
 * @see \App\Models\Inbox\PostMetricSnapshot
 */

use App\Models\Content\PostTarget;
use App\Models\Inbox\PostMetricSnapshot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

test('has correct table name', function (): void {
    $snapshot = new PostMetricSnapshot();

    expect($snapshot->getTable())->toBe('post_metric_snapshots');
});

test('uses uuid primary key', function (): void {
    $snapshot = PostMetricSnapshot::factory()->create();

    expect($snapshot->id)->not->toBeNull()
        ->and(strlen($snapshot->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $snapshot = new PostMetricSnapshot();
    $fillable = $snapshot->getFillable();

    expect($fillable)->toContain('post_target_id')
        ->and($fillable)->toContain('captured_at')
        ->and($fillable)->toContain('likes_count')
        ->and($fillable)->toContain('comments_count')
        ->and($fillable)->toContain('shares_count')
        ->and($fillable)->toContain('impressions_count')
        ->and($fillable)->toContain('reach_count')
        ->and($fillable)->toContain('clicks_count')
        ->and($fillable)->toContain('engagement_rate')
        ->and($fillable)->toContain('raw_response');
});

test('captured_at casts to datetime', function (): void {
    $snapshot = PostMetricSnapshot::factory()->create();

    expect($snapshot->captured_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('likes_count casts to integer', function (): void {
    $snapshot = PostMetricSnapshot::factory()->create(['likes_count' => 100]);

    expect($snapshot->likes_count)->toBeInt()
        ->and($snapshot->likes_count)->toBe(100);
});

test('comments_count casts to integer', function (): void {
    $snapshot = PostMetricSnapshot::factory()->create(['comments_count' => 50]);

    expect($snapshot->comments_count)->toBeInt()
        ->and($snapshot->comments_count)->toBe(50);
});

test('shares_count casts to integer', function (): void {
    $snapshot = PostMetricSnapshot::factory()->create(['shares_count' => 25]);

    expect($snapshot->shares_count)->toBeInt()
        ->and($snapshot->shares_count)->toBe(25);
});

test('impressions_count casts to integer', function (): void {
    $snapshot = PostMetricSnapshot::factory()->create(['impressions_count' => 5000]);

    expect($snapshot->impressions_count)->toBeInt()
        ->and($snapshot->impressions_count)->toBe(5000);
});

test('reach_count casts to integer', function (): void {
    $snapshot = PostMetricSnapshot::factory()->create(['reach_count' => 3000]);

    expect($snapshot->reach_count)->toBeInt()
        ->and($snapshot->reach_count)->toBe(3000);
});

test('clicks_count casts to integer', function (): void {
    $snapshot = PostMetricSnapshot::factory()->create(['clicks_count' => 75]);

    expect($snapshot->clicks_count)->toBeInt()
        ->and($snapshot->clicks_count)->toBe(75);
});

test('engagement_rate casts to decimal', function (): void {
    $snapshot = PostMetricSnapshot::factory()->create(['engagement_rate' => 5.1234]);

    expect($snapshot->engagement_rate)->toBe('5.1234');
});

test('raw_response casts to array', function (): void {
    $response = ['platform' => 'linkedin', 'data' => ['likes' => 100]];
    $snapshot = PostMetricSnapshot::factory()->withRawResponse($response)->create();

    expect($snapshot->raw_response)->toBeArray()
        ->and($snapshot->raw_response['platform'])->toBe('linkedin');
});

test('postTarget relationship returns belongs to', function (): void {
    $snapshot = new PostMetricSnapshot();

    expect($snapshot->postTarget())->toBeInstanceOf(BelongsTo::class);
});

test('postTarget relationship works correctly', function (): void {
    $postTarget = PostTarget::factory()->create();
    $snapshot = PostMetricSnapshot::factory()->forPostTarget($postTarget)->create();

    expect($snapshot->postTarget)->toBeInstanceOf(PostTarget::class)
        ->and($snapshot->postTarget->id)->toBe($postTarget->id);
});

test('scope forPostTarget filters correctly', function (): void {
    $target1 = PostTarget::factory()->create();
    $target2 = PostTarget::factory()->create();

    PostMetricSnapshot::factory()->count(3)->forPostTarget($target1)->create();
    PostMetricSnapshot::factory()->count(2)->forPostTarget($target2)->create();

    $snapshots = PostMetricSnapshot::forPostTarget($target1->id)->get();

    expect($snapshots)->toHaveCount(3)
        ->and($snapshots->every(fn ($s) => $s->post_target_id === $target1->id))->toBeTrue();
});

test('scope inDateRange filters correctly', function (): void {
    $start = now()->subDays(7);
    $end = now();

    // Within range
    PostMetricSnapshot::factory()->capturedAt(now()->subDays(3))->create();
    PostMetricSnapshot::factory()->capturedAt(now()->subDays(5))->create();

    // Outside range
    PostMetricSnapshot::factory()->capturedAt(now()->subDays(10))->create();

    $snapshots = PostMetricSnapshot::inDateRange($start, $end)->get();

    expect($snapshots)->toHaveCount(2);
});

test('scope latestCaptured orders by captured_at desc', function (): void {
    $old = PostMetricSnapshot::factory()->capturedAt(now()->subDays(3))->create();
    $new = PostMetricSnapshot::factory()->capturedAt(now()->subDay())->create();
    $oldest = PostMetricSnapshot::factory()->capturedAt(now()->subDays(5))->create();

    $snapshots = PostMetricSnapshot::latestCaptured()->get();

    expect($snapshots->first()->id)->toBe($new->id)
        ->and($snapshots->last()->id)->toBe($oldest->id);
});

test('getTotalEngagement calculates correctly', function (): void {
    $snapshot = PostMetricSnapshot::factory()->create([
        'likes_count' => 100,
        'comments_count' => 50,
        'shares_count' => 25,
    ]);

    expect($snapshot->getTotalEngagement())->toBe(175);
});

test('getTotalEngagement handles null values', function (): void {
    $snapshot = PostMetricSnapshot::factory()->create([
        'likes_count' => 100,
        'comments_count' => null,
        'shares_count' => null,
    ]);

    expect($snapshot->getTotalEngagement())->toBe(100);
});

test('getTotalEngagement returns zero for all null values', function (): void {
    $snapshot = PostMetricSnapshot::factory()->create([
        'likes_count' => null,
        'comments_count' => null,
        'shares_count' => null,
    ]);

    expect($snapshot->getTotalEngagement())->toBe(0);
});

test('calculateEngagementRate calculates correctly', function (): void {
    $snapshot = PostMetricSnapshot::factory()->create([
        'likes_count' => 100,
        'comments_count' => 50,
        'shares_count' => 50,
        'impressions_count' => 2000,
    ]);

    // (100 + 50 + 50) / 2000 * 100 = 10%
    expect($snapshot->calculateEngagementRate())->toBe(10.0);
});

test('calculateEngagementRate returns null for zero impressions', function (): void {
    $snapshot = PostMetricSnapshot::factory()->create([
        'likes_count' => 100,
        'impressions_count' => 0,
    ]);

    expect($snapshot->calculateEngagementRate())->toBeNull();
});

test('calculateEngagementRate returns null for null impressions', function (): void {
    $snapshot = PostMetricSnapshot::factory()->create([
        'likes_count' => 100,
        'impressions_count' => null,
    ]);

    expect($snapshot->calculateEngagementRate())->toBeNull();
});

test('factory creates valid model', function (): void {
    $snapshot = PostMetricSnapshot::factory()->create();

    expect($snapshot)->toBeInstanceOf(PostMetricSnapshot::class)
        ->and($snapshot->id)->not->toBeNull()
        ->and($snapshot->post_target_id)->not->toBeNull()
        ->and($snapshot->captured_at)->not->toBeNull();
});

test('factory lowEngagement state works correctly', function (): void {
    $snapshot = PostMetricSnapshot::factory()->lowEngagement()->create();

    expect($snapshot->likes_count)->toBeLessThanOrEqual(10)
        ->and($snapshot->comments_count)->toBeLessThanOrEqual(2)
        ->and($snapshot->impressions_count)->toBeLessThanOrEqual(200);
});

test('factory mediumEngagement state works correctly', function (): void {
    $snapshot = PostMetricSnapshot::factory()->mediumEngagement()->create();

    expect($snapshot->likes_count)->toBeGreaterThanOrEqual(50)
        ->and($snapshot->likes_count)->toBeLessThanOrEqual(200)
        ->and($snapshot->impressions_count)->toBeGreaterThanOrEqual(1000);
});

test('factory highEngagement state works correctly', function (): void {
    $snapshot = PostMetricSnapshot::factory()->highEngagement()->create();

    expect($snapshot->likes_count)->toBeGreaterThanOrEqual(500)
        ->and($snapshot->impressions_count)->toBeGreaterThanOrEqual(10000);
});

test('factory viralEngagement state works correctly', function (): void {
    $snapshot = PostMetricSnapshot::factory()->viralEngagement()->create();

    expect($snapshot->likes_count)->toBeGreaterThanOrEqual(5000)
        ->and($snapshot->impressions_count)->toBeGreaterThanOrEqual(100000);
});

test('factory capturedAt sets specific timestamp', function (): void {
    $capturedAt = now()->subDays(2);
    $snapshot = PostMetricSnapshot::factory()->capturedAt($capturedAt)->create();

    expect($snapshot->captured_at->format('Y-m-d H:i:s'))
        ->toBe($capturedAt->format('Y-m-d H:i:s'));
});
