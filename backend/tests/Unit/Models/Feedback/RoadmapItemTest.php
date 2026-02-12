<?php

declare(strict_types=1);

/**
 * RoadmapItem Model Unit Tests
 *
 * Tests for the RoadmapItem model which represents roadmap items.
 *
 * @see \App\Models\Feedback\RoadmapItem
 */

use App\Enums\Feedback\AdminPriority;
use App\Enums\Feedback\RoadmapCategory;
use App\Enums\Feedback\RoadmapStatus;
use App\Models\Feedback\Feedback;
use App\Models\Feedback\ReleaseNoteItem;
use App\Models\Feedback\RoadmapItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create roadmap item with factory', function (): void {
    $item = RoadmapItem::factory()->create();

    expect($item)->toBeInstanceOf(RoadmapItem::class)
        ->and($item->id)->not->toBeNull()
        ->and($item->title)->not->toBeNull();
});

test('has correct table name', function (): void {
    $item = new RoadmapItem();

    expect($item->getTable())->toBe('roadmap_items');
});

test('casts attributes correctly', function (): void {
    $item = RoadmapItem::factory()->create();

    expect($item->category)->toBeInstanceOf(RoadmapCategory::class)
        ->and($item->status)->toBeInstanceOf(RoadmapStatus::class)
        ->and($item->priority)->toBeInstanceOf(AdminPriority::class)
        ->and($item->is_public)->toBeBool()
        ->and($item->progress_percentage)->toBeInt();
});

test('linkedFeedback relationship works', function (): void {
    $item = RoadmapItem::factory()->create();
    $feedback = Feedback::factory()->count(3)->create();

    $item->linkedFeedback()->attach($feedback->pluck('id'));

    expect($item->linkedFeedback)->toHaveCount(3)
        ->and($item->linkedFeedback->first())->toBeInstanceOf(Feedback::class);
});

test('releaseNoteItems relationship works', function (): void {
    $item = RoadmapItem::factory()->create();
    ReleaseNoteItem::factory()->linkedTo($item)->count(2)->create();

    expect($item->releaseNoteItems)->toHaveCount(2)
        ->and($item->releaseNoteItems->first())->toBeInstanceOf(ReleaseNoteItem::class);
});

test('public scope filters public items', function (): void {
    RoadmapItem::factory()->count(2)->create(['is_public' => true]);
    RoadmapItem::factory()->private()->create();

    expect(RoadmapItem::public()->count())->toBe(2);
});

test('byStatus scope filters by status', function (): void {
    RoadmapItem::factory()->planned()->count(2)->create();
    RoadmapItem::factory()->shipped()->create();

    expect(RoadmapItem::byStatus(RoadmapStatus::PLANNED)->count())->toBe(2);
});

test('byCategory scope filters by category', function (): void {
    RoadmapItem::factory()->inCategory(RoadmapCategory::ANALYTICS)->count(2)->create();
    RoadmapItem::factory()->inCategory(RoadmapCategory::PUBLISHING)->create();

    expect(RoadmapItem::byCategory(RoadmapCategory::ANALYTICS)->count())->toBe(2);
});

test('byQuarter scope filters by quarter', function (): void {
    RoadmapItem::factory()->forQuarter('Q1 2026')->count(2)->create();
    RoadmapItem::factory()->forQuarter('Q2 2026')->create();

    expect(RoadmapItem::byQuarter('Q1 2026')->count())->toBe(2);
});

test('active scope filters active items', function (): void {
    RoadmapItem::factory()->planned()->create();
    RoadmapItem::factory()->inProgress()->create();
    RoadmapItem::factory()->beta()->create();
    RoadmapItem::factory()->shipped()->create();
    RoadmapItem::factory()->considering()->create();

    expect(RoadmapItem::active()->count())->toBe(3);
});

test('shipped scope filters shipped items', function (): void {
    RoadmapItem::factory()->shipped()->count(2)->create();
    RoadmapItem::factory()->planned()->create();

    expect(RoadmapItem::shipped()->count())->toBe(2);
});

test('isPublic returns correct value', function (): void {
    $public = RoadmapItem::factory()->create(['is_public' => true]);
    $private = RoadmapItem::factory()->private()->create();

    expect($public->isPublic())->toBeTrue()
        ->and($private->isPublic())->toBeFalse();
});

test('isActive returns correct value', function (): void {
    $planned = RoadmapItem::factory()->planned()->create();
    $shipped = RoadmapItem::factory()->shipped()->create();

    expect($planned->isActive())->toBeTrue()
        ->and($shipped->isActive())->toBeFalse();
});

test('isShipped returns correct value', function (): void {
    $shipped = RoadmapItem::factory()->shipped()->create();
    $planned = RoadmapItem::factory()->planned()->create();

    expect($shipped->isShipped())->toBeTrue()
        ->and($planned->isShipped())->toBeFalse();
});

test('updateProgress updates progress percentage', function (): void {
    $item = RoadmapItem::factory()->create(['progress_percentage' => 0]);

    $item->updateProgress(50);

    expect($item->fresh()->progress_percentage)->toBe(50);
});

test('updateProgress clamps to 0-100', function (): void {
    $item = RoadmapItem::factory()->create();

    $item->updateProgress(-10);
    expect($item->fresh()->progress_percentage)->toBe(0);

    $item->updateProgress(150);
    expect($item->fresh()->progress_percentage)->toBe(100);
});

test('markAsShipped sets status and dates', function (): void {
    $item = RoadmapItem::factory()->inProgress()->create();

    $item->markAsShipped();

    $item->refresh();
    expect($item->status)->toBe(RoadmapStatus::SHIPPED)
        ->and($item->progress_percentage)->toBe(100)
        ->and($item->shipped_date)->not->toBeNull();
});

test('linkFeedback links feedback to roadmap item', function (): void {
    $item = RoadmapItem::factory()->create();
    $feedback = Feedback::factory()->create(['vote_count' => 10]);

    $item->linkFeedback($feedback);

    expect($item->linkedFeedback->contains($feedback))->toBeTrue()
        ->and($item->fresh()->linked_feedback_count)->toBe(1)
        ->and($item->fresh()->total_votes)->toBe(10);
});

test('unlinkFeedback unlinks feedback from roadmap item', function (): void {
    $item = RoadmapItem::factory()->create();
    $feedback = Feedback::factory()->create();
    $item->linkedFeedback()->attach($feedback->id);

    $item->unlinkFeedback($feedback);

    expect($item->linkedFeedback->contains($feedback))->toBeFalse();
});

test('recalculateCounts updates counts correctly', function (): void {
    $item = RoadmapItem::factory()->create();
    $feedback1 = Feedback::factory()->create(['vote_count' => 10]);
    $feedback2 = Feedback::factory()->create(['vote_count' => 20]);
    $item->linkedFeedback()->attach([$feedback1->id, $feedback2->id]);

    $item->recalculateCounts();

    expect($item->linked_feedback_count)->toBe(2)
        ->and($item->total_votes)->toBe(30);
});
