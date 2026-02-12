<?php

declare(strict_types=1);

/**
 * RoadmapFeedbackLink Model Unit Tests
 *
 * Tests for the RoadmapFeedbackLink pivot model.
 *
 * @see \App\Models\Feedback\RoadmapFeedbackLink
 */

use App\Models\Feedback\Feedback;
use App\Models\Feedback\RoadmapFeedbackLink;
use App\Models\Feedback\RoadmapItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('has correct table name', function (): void {
    $link = new RoadmapFeedbackLink();

    expect($link->getTable())->toBe('roadmap_feedback_links');
});

test('can create link through relationship', function (): void {
    $item = RoadmapItem::factory()->create();
    $feedback = Feedback::factory()->create();

    $item->linkedFeedback()->attach($feedback->id);

    $link = RoadmapFeedbackLink::where('roadmap_item_id', $item->id)
        ->where('feedback_id', $feedback->id)
        ->first();

    expect($link)->not->toBeNull()
        ->and($link->roadmap_item_id)->toBe($item->id)
        ->and($link->feedback_id)->toBe($feedback->id);
});

test('roadmapItem relationship works', function (): void {
    $item = RoadmapItem::factory()->create();
    $feedback = Feedback::factory()->create();
    $item->linkedFeedback()->attach($feedback->id);

    $link = RoadmapFeedbackLink::where('roadmap_item_id', $item->id)->first();

    expect($link->roadmapItem)->toBeInstanceOf(RoadmapItem::class)
        ->and($link->roadmapItem->id)->toBe($item->id);
});

test('feedback relationship works', function (): void {
    $item = RoadmapItem::factory()->create();
    $feedback = Feedback::factory()->create();
    $item->linkedFeedback()->attach($feedback->id);

    $link = RoadmapFeedbackLink::where('feedback_id', $feedback->id)->first();

    expect($link->feedback)->toBeInstanceOf(Feedback::class)
        ->and($link->feedback->id)->toBe($feedback->id);
});

test('has timestamps', function (): void {
    $item = RoadmapItem::factory()->create();
    $feedback = Feedback::factory()->create();
    $item->linkedFeedback()->attach($feedback->id);

    $link = RoadmapFeedbackLink::where('roadmap_item_id', $item->id)->first();

    expect($link->created_at)->not->toBeNull()
        ->and($link->updated_at)->not->toBeNull();
});

test('deleting roadmap item deletes link', function (): void {
    $item = RoadmapItem::factory()->create();
    $feedback = Feedback::factory()->create();
    $item->linkedFeedback()->attach($feedback->id);

    $item->delete();

    expect(RoadmapFeedbackLink::where('roadmap_item_id', $item->id)->exists())->toBeFalse();
});

test('deleting feedback deletes link', function (): void {
    $item = RoadmapItem::factory()->create();
    $feedback = Feedback::factory()->create();
    $item->linkedFeedback()->attach($feedback->id);

    $feedback->delete();

    expect(RoadmapFeedbackLink::where('feedback_id', $feedback->id)->exists())->toBeFalse();
});
