<?php

declare(strict_types=1);

/**
 * FeedbackTagAssignment Model Unit Tests
 *
 * Tests for the FeedbackTagAssignment pivot model.
 *
 * @see \App\Models\Feedback\FeedbackTagAssignment
 */

use App\Models\Feedback\Feedback;
use App\Models\Feedback\FeedbackTag;
use App\Models\Feedback\FeedbackTagAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('has correct table name', function (): void {
    $assignment = new FeedbackTagAssignment();

    expect($assignment->getTable())->toBe('feedback_tag_assignments');
});

test('can create assignment through relationship', function (): void {
    $feedback = Feedback::factory()->create();
    $tag = FeedbackTag::factory()->create();

    $feedback->tags()->attach($tag->id);

    $assignment = FeedbackTagAssignment::where('feedback_id', $feedback->id)
        ->where('tag_id', $tag->id)
        ->first();

    expect($assignment)->not->toBeNull()
        ->and($assignment->feedback_id)->toBe($feedback->id)
        ->and($assignment->tag_id)->toBe($tag->id);
});

test('feedback relationship works', function (): void {
    $feedback = Feedback::factory()->create();
    $tag = FeedbackTag::factory()->create();
    $feedback->tags()->attach($tag->id);

    $assignment = FeedbackTagAssignment::where('feedback_id', $feedback->id)->first();

    expect($assignment->feedback)->toBeInstanceOf(Feedback::class)
        ->and($assignment->feedback->id)->toBe($feedback->id);
});

test('tag relationship works', function (): void {
    $feedback = Feedback::factory()->create();
    $tag = FeedbackTag::factory()->create();
    $feedback->tags()->attach($tag->id);

    $assignment = FeedbackTagAssignment::where('tag_id', $tag->id)->first();

    expect($assignment->tag)->toBeInstanceOf(FeedbackTag::class)
        ->and($assignment->tag->id)->toBe($tag->id);
});

test('has timestamps', function (): void {
    $feedback = Feedback::factory()->create();
    $tag = FeedbackTag::factory()->create();
    $feedback->tags()->attach($tag->id);

    $assignment = FeedbackTagAssignment::where('feedback_id', $feedback->id)->first();

    expect($assignment->created_at)->not->toBeNull()
        ->and($assignment->updated_at)->not->toBeNull();
});

test('deleting feedback deletes assignment', function (): void {
    $feedback = Feedback::factory()->create();
    $tag = FeedbackTag::factory()->create();
    $feedback->tags()->attach($tag->id);

    $feedback->delete();

    expect(FeedbackTagAssignment::where('feedback_id', $feedback->id)->exists())->toBeFalse();
});

test('deleting tag deletes assignment', function (): void {
    $feedback = Feedback::factory()->create();
    $tag = FeedbackTag::factory()->create();
    $feedback->tags()->attach($tag->id);

    $tag->delete();

    expect(FeedbackTagAssignment::where('tag_id', $tag->id)->exists())->toBeFalse();
});
