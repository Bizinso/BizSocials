<?php

declare(strict_types=1);

/**
 * SupportTicketTag Model Unit Tests
 *
 * Tests for the SupportTicketTag model.
 *
 * @see \App\Models\Support\SupportTicketTag
 */

use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketTag;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create tag with factory', function (): void {
    $tag = SupportTicketTag::factory()->create();

    expect($tag)->toBeInstanceOf(SupportTicketTag::class)
        ->and($tag->id)->not->toBeNull()
        ->and($tag->name)->not->toBeNull()
        ->and($tag->slug)->not->toBeNull();
});

test('has correct table name', function (): void {
    $tag = new SupportTicketTag();

    expect($tag->getTable())->toBe('support_ticket_tags');
});

test('casts attributes correctly', function (): void {
    $tag = SupportTicketTag::factory()->create();

    expect($tag->usage_count)->toBeInt();
});

test('tickets relationship works', function (): void {
    $tag = SupportTicketTag::factory()->create();
    $tickets = SupportTicket::factory()->count(3)->create();

    foreach ($tickets as $ticket) {
        $ticket->tags()->attach($tag->id);
    }

    expect($tag->tickets)->toHaveCount(3)
        ->and($tag->tickets->first())->toBeInstanceOf(SupportTicket::class);
});

test('popular scope orders by usage count descending', function (): void {
    SupportTicketTag::factory()->create(['usage_count' => 10]);
    SupportTicketTag::factory()->create(['usage_count' => 50]);
    SupportTicketTag::factory()->create(['usage_count' => 30]);

    $tags = SupportTicketTag::popular()->get();

    expect($tags->first()->usage_count)->toBe(50)
        ->and($tags->last()->usage_count)->toBe(10);
});

test('ordered scope orders by name', function (): void {
    SupportTicketTag::factory()->create(['name' => 'Zebra']);
    SupportTicketTag::factory()->create(['name' => 'Alpha']);
    SupportTicketTag::factory()->create(['name' => 'Beta']);

    $tags = SupportTicketTag::ordered()->get();

    expect($tags->first()->name)->toBe('Alpha')
        ->and($tags->last()->name)->toBe('Zebra');
});

test('search scope filters by name or slug', function (): void {
    SupportTicketTag::factory()->withName('Important')->create();
    SupportTicketTag::factory()->withName('Urgent')->create();
    SupportTicketTag::factory()->withName('Other')->create();

    expect(SupportTicketTag::search('import')->count())->toBe(1)
        ->and(SupportTicketTag::search('urg')->count())->toBe(1);
});

test('incrementUsageCount increases usage count', function (): void {
    $tag = SupportTicketTag::factory()->create(['usage_count' => 5]);

    $tag->incrementUsageCount();

    expect($tag->fresh()->usage_count)->toBe(6);
});

test('decrementUsageCount decreases usage count', function (): void {
    $tag = SupportTicketTag::factory()->create(['usage_count' => 5]);

    $tag->decrementUsageCount();

    expect($tag->fresh()->usage_count)->toBe(4);
});

test('can create tag with specific name', function (): void {
    $tag = SupportTicketTag::factory()->withName('Custom Tag')->create();

    expect($tag->name)->toBe('Custom Tag')
        ->and($tag->slug)->toBe('custom-tag');
});

test('popular state sets high usage count', function (): void {
    $tag = SupportTicketTag::factory()->popular()->create();

    expect($tag->usage_count)->toBeGreaterThanOrEqual(50);
});
