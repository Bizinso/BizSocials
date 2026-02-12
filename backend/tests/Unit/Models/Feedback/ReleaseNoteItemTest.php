<?php

declare(strict_types=1);

/**
 * ReleaseNoteItem Model Unit Tests
 *
 * Tests for the ReleaseNoteItem model which represents items within release notes.
 *
 * @see \App\Models\Feedback\ReleaseNoteItem
 */

use App\Enums\Feedback\ChangeType;
use App\Models\Feedback\ReleaseNote;
use App\Models\Feedback\ReleaseNoteItem;
use App\Models\Feedback\RoadmapItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create release note item with factory', function (): void {
    $item = ReleaseNoteItem::factory()->create();

    expect($item)->toBeInstanceOf(ReleaseNoteItem::class)
        ->and($item->id)->not->toBeNull()
        ->and($item->title)->not->toBeNull();
});

test('has correct table name', function (): void {
    $item = new ReleaseNoteItem();

    expect($item->getTable())->toBe('release_note_items');
});

test('casts attributes correctly', function (): void {
    $item = ReleaseNoteItem::factory()->create();

    expect($item->change_type)->toBeInstanceOf(ChangeType::class)
        ->and($item->sort_order)->toBeInt();
});

test('releaseNote relationship works', function (): void {
    $release = ReleaseNote::factory()->create();
    $item = ReleaseNoteItem::factory()->forRelease($release)->create();

    expect($item->releaseNote)->toBeInstanceOf(ReleaseNote::class)
        ->and($item->releaseNote->id)->toBe($release->id);
});

test('roadmapItem relationship works', function (): void {
    $roadmapItem = RoadmapItem::factory()->create();
    $item = ReleaseNoteItem::factory()->linkedTo($roadmapItem)->create();

    expect($item->roadmapItem)->toBeInstanceOf(RoadmapItem::class)
        ->and($item->roadmapItem->id)->toBe($roadmapItem->id);
});

test('forRelease scope filters by release note', function (): void {
    $release = ReleaseNote::factory()->create();
    ReleaseNoteItem::factory()->forRelease($release)->count(2)->create();
    ReleaseNoteItem::factory()->create();

    expect(ReleaseNoteItem::forRelease($release->id)->count())->toBe(2);
});

test('byType scope filters by change type', function (): void {
    ReleaseNoteItem::factory()->newFeature()->count(2)->create();
    ReleaseNoteItem::factory()->bugFix()->create();

    expect(ReleaseNoteItem::byType(ChangeType::NEW_FEATURE)->count())->toBe(2);
});

test('ordered scope orders by sort_order', function (): void {
    ReleaseNoteItem::factory()->withOrder(3)->create();
    ReleaseNoteItem::factory()->withOrder(1)->create();
    ReleaseNoteItem::factory()->withOrder(2)->create();

    $ordered = ReleaseNoteItem::ordered()->get();

    expect($ordered->first()->sort_order)->toBe(1)
        ->and($ordered->last()->sort_order)->toBe(3);
});

test('deleting release note deletes items', function (): void {
    $release = ReleaseNote::factory()->create();
    ReleaseNoteItem::factory()->forRelease($release)->count(3)->create();

    $release->delete();

    expect(ReleaseNoteItem::forRelease($release->id)->count())->toBe(0);
});
