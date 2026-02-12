<?php

declare(strict_types=1);

/**
 * ReleaseNote Model Unit Tests
 *
 * Tests for the ReleaseNote model which represents release notes.
 *
 * @see \App\Models\Feedback\ReleaseNote
 */

use App\Enums\Feedback\ChangeType;
use App\Enums\Feedback\ReleaseNoteStatus;
use App\Enums\Feedback\ReleaseType;
use App\Models\Feedback\ReleaseNote;
use App\Models\Feedback\ReleaseNoteItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create release note with factory', function (): void {
    $release = ReleaseNote::factory()->create();

    expect($release)->toBeInstanceOf(ReleaseNote::class)
        ->and($release->id)->not->toBeNull()
        ->and($release->version)->not->toBeNull()
        ->and($release->title)->not->toBeNull();
});

test('has correct table name', function (): void {
    $release = new ReleaseNote();

    expect($release->getTable())->toBe('release_notes');
});

test('casts attributes correctly', function (): void {
    $release = ReleaseNote::factory()->create();

    expect($release->release_type)->toBeInstanceOf(ReleaseType::class)
        ->and($release->status)->toBeInstanceOf(ReleaseNoteStatus::class)
        ->and($release->is_public)->toBeBool();
});

test('items relationship works', function (): void {
    $release = ReleaseNote::factory()->create();
    ReleaseNoteItem::factory()->forRelease($release)->count(3)->create();

    expect($release->items)->toHaveCount(3)
        ->and($release->items->first())->toBeInstanceOf(ReleaseNoteItem::class);
});

test('items are ordered by sort_order', function (): void {
    $release = ReleaseNote::factory()->create();
    ReleaseNoteItem::factory()->forRelease($release)->withOrder(3)->create();
    ReleaseNoteItem::factory()->forRelease($release)->withOrder(1)->create();
    ReleaseNoteItem::factory()->forRelease($release)->withOrder(2)->create();

    $items = $release->items;

    expect($items->first()->sort_order)->toBe(1)
        ->and($items->last()->sort_order)->toBe(3);
});

test('published scope filters published releases', function (): void {
    ReleaseNote::factory()->published()->count(2)->create();
    ReleaseNote::factory()->draft()->create();

    expect(ReleaseNote::published()->count())->toBe(2);
});

test('draft scope filters draft releases', function (): void {
    ReleaseNote::factory()->draft()->count(2)->create();
    ReleaseNote::factory()->published()->create();

    expect(ReleaseNote::draft()->count())->toBe(2);
});

test('scheduled scope filters scheduled releases', function (): void {
    ReleaseNote::factory()->scheduled()->count(2)->create();
    ReleaseNote::factory()->draft()->create();

    expect(ReleaseNote::scheduled()->count())->toBe(2);
});

test('byType scope filters by release type', function (): void {
    ReleaseNote::factory()->major()->count(2)->create();
    ReleaseNote::factory()->patch()->create();

    expect(ReleaseNote::byType(ReleaseType::MAJOR)->count())->toBe(2);
});

test('recent scope orders by published_at descending', function (): void {
    ReleaseNote::factory()->published()->create(['published_at' => now()->subDays(10)]);
    ReleaseNote::factory()->published()->create(['published_at' => now()->subDays(1)]);
    ReleaseNote::factory()->published()->create(['published_at' => now()->subDays(5)]);

    $recent = ReleaseNote::recent()->get();

    expect($recent->first()->published_at->diffInDays(now()))->toBeLessThan(2);
});

test('isPublished returns correct value', function (): void {
    $published = ReleaseNote::factory()->published()->create();
    $draft = ReleaseNote::factory()->draft()->create();

    expect($published->isPublished())->toBeTrue()
        ->and($draft->isPublished())->toBeFalse();
});

test('isDraft returns correct value', function (): void {
    $draft = ReleaseNote::factory()->draft()->create();
    $published = ReleaseNote::factory()->published()->create();

    expect($draft->isDraft())->toBeTrue()
        ->and($published->isDraft())->toBeFalse();
});

test('isScheduled returns correct value', function (): void {
    $scheduled = ReleaseNote::factory()->scheduled()->create();
    $draft = ReleaseNote::factory()->draft()->create();

    expect($scheduled->isScheduled())->toBeTrue()
        ->and($draft->isScheduled())->toBeFalse();
});

test('publish changes status and sets published_at', function (): void {
    $release = ReleaseNote::factory()->draft()->create();

    $release->publish();

    $release->refresh();
    expect($release->status)->toBe(ReleaseNoteStatus::PUBLISHED)
        ->and($release->published_at)->not->toBeNull()
        ->and($release->scheduled_at)->toBeNull();
});

test('schedule changes status and sets scheduled_at', function (): void {
    $release = ReleaseNote::factory()->draft()->create();
    $scheduleDate = now()->addDays(7);

    $release->schedule($scheduleDate);

    $release->refresh();
    expect($release->status)->toBe(ReleaseNoteStatus::SCHEDULED)
        ->and($release->scheduled_at)->not->toBeNull();
});

test('addItem creates a new release note item', function (): void {
    $release = ReleaseNote::factory()->create();

    $item = $release->addItem('New Feature', ChangeType::NEW_FEATURE, 'Description');

    expect($item)->toBeInstanceOf(ReleaseNoteItem::class)
        ->and($item->release_note_id)->toBe($release->id)
        ->and($item->title)->toBe('New Feature')
        ->and($item->change_type)->toBe(ChangeType::NEW_FEATURE);
});

test('addItem increments sort_order', function (): void {
    $release = ReleaseNote::factory()->create();

    $item1 = $release->addItem('First', ChangeType::NEW_FEATURE);
    $item2 = $release->addItem('Second', ChangeType::BUG_FIX);

    expect($item1->sort_order)->toBe(1)
        ->and($item2->sort_order)->toBe(2);
});
