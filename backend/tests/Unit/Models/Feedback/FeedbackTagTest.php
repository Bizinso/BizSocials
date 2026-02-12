<?php

declare(strict_types=1);

/**
 * FeedbackTag Model Unit Tests
 *
 * Tests for the FeedbackTag model which represents tags for feedback.
 *
 * @see \App\Models\Feedback\FeedbackTag
 */

use App\Models\Feedback\Feedback;
use App\Models\Feedback\FeedbackTag;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create tag with factory', function (): void {
    $tag = FeedbackTag::factory()->create();

    expect($tag)->toBeInstanceOf(FeedbackTag::class)
        ->and($tag->id)->not->toBeNull()
        ->and($tag->name)->not->toBeNull()
        ->and($tag->slug)->not->toBeNull();
});

test('has correct table name', function (): void {
    $tag = new FeedbackTag();

    expect($tag->getTable())->toBe('feedback_tags');
});

test('casts usage_count correctly', function (): void {
    $tag = FeedbackTag::factory()->create();

    expect($tag->usage_count)->toBeInt();
});

test('feedback relationship works', function (): void {
    $tag = FeedbackTag::factory()->create();
    $feedback = Feedback::factory()->count(3)->create();

    $tag->feedback()->attach($feedback->pluck('id'));

    expect($tag->feedback)->toHaveCount(3)
        ->and($tag->feedback->first())->toBeInstanceOf(Feedback::class);
});

test('popular scope orders by usage count descending', function (): void {
    FeedbackTag::factory()->create(['usage_count' => 10]);
    FeedbackTag::factory()->create(['usage_count' => 50]);
    FeedbackTag::factory()->create(['usage_count' => 30]);

    $popular = FeedbackTag::popular()->get();

    expect($popular->first()->usage_count)->toBe(50)
        ->and($popular->last()->usage_count)->toBe(10);
});

test('ordered scope orders by name', function (): void {
    FeedbackTag::factory()->withName('Zebra')->create();
    FeedbackTag::factory()->withName('Apple')->create();
    FeedbackTag::factory()->withName('Banana')->create();

    $ordered = FeedbackTag::ordered()->get();

    expect($ordered->first()->name)->toBe('Apple')
        ->and($ordered->last()->name)->toBe('Zebra');
});

test('search scope filters by name', function (): void {
    FeedbackTag::factory()->withName('Performance')->create();
    FeedbackTag::factory()->withName('Security')->create();
    FeedbackTag::factory()->withName('Performance Testing')->create();

    expect(FeedbackTag::search('Performance')->count())->toBe(2);
});

test('incrementUsageCount increases usage count', function (): void {
    $tag = FeedbackTag::factory()->create(['usage_count' => 5]);

    $tag->incrementUsageCount();

    expect($tag->fresh()->usage_count)->toBe(6);
});

test('decrementUsageCount decreases usage count', function (): void {
    $tag = FeedbackTag::factory()->create(['usage_count' => 5]);

    $tag->decrementUsageCount();

    expect($tag->fresh()->usage_count)->toBe(4);
});

test('decrementUsageCount does not go below zero', function (): void {
    $tag = FeedbackTag::factory()->create(['usage_count' => 0]);

    $tag->decrementUsageCount();

    expect($tag->fresh()->usage_count)->toBe(0);
});
