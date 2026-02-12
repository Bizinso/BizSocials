<?php

declare(strict_types=1);

/**
 * SupportTicketTagAssignment Model Unit Tests
 *
 * Tests for the SupportTicketTagAssignment pivot model.
 *
 * @see \App\Models\Support\SupportTicketTagAssignment
 */

use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketTag;
use App\Models\Support\SupportTicketTagAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('has correct table name', function (): void {
    $assignment = new SupportTicketTagAssignment();

    expect($assignment->getTable())->toBe('support_ticket_tag_assignments');
});

test('can create assignment through relationship', function (): void {
    $ticket = SupportTicket::factory()->create();
    $tag = SupportTicketTag::factory()->create();

    $ticket->tags()->attach($tag->id);

    $assignment = SupportTicketTagAssignment::where('ticket_id', $ticket->id)
        ->where('tag_id', $tag->id)
        ->first();

    expect($assignment)->not->toBeNull()
        ->and($assignment->ticket_id)->toBe($ticket->id)
        ->and($assignment->tag_id)->toBe($tag->id);
});

test('ticket relationship works', function (): void {
    $ticket = SupportTicket::factory()->create();
    $tag = SupportTicketTag::factory()->create();
    $ticket->tags()->attach($tag->id);

    $assignment = SupportTicketTagAssignment::where('ticket_id', $ticket->id)->first();

    expect($assignment->ticket)->toBeInstanceOf(SupportTicket::class)
        ->and($assignment->ticket->id)->toBe($ticket->id);
});

test('tag relationship works', function (): void {
    $ticket = SupportTicket::factory()->create();
    $tag = SupportTicketTag::factory()->create();
    $ticket->tags()->attach($tag->id);

    $assignment = SupportTicketTagAssignment::where('tag_id', $tag->id)->first();

    expect($assignment->tag)->toBeInstanceOf(SupportTicketTag::class)
        ->and($assignment->tag->id)->toBe($tag->id);
});

test('has timestamps', function (): void {
    $ticket = SupportTicket::factory()->create();
    $tag = SupportTicketTag::factory()->create();
    $ticket->tags()->attach($tag->id);

    $assignment = SupportTicketTagAssignment::where('ticket_id', $ticket->id)->first();

    expect($assignment->created_at)->not->toBeNull()
        ->and($assignment->updated_at)->not->toBeNull();
});

test('deleting ticket deletes assignment', function (): void {
    $ticket = SupportTicket::factory()->create();
    $tag = SupportTicketTag::factory()->create();
    $ticket->tags()->attach($tag->id);

    $ticket->forceDelete();

    expect(SupportTicketTagAssignment::where('ticket_id', $ticket->id)->exists())->toBeFalse();
});

test('deleting tag deletes assignment', function (): void {
    $ticket = SupportTicket::factory()->create();
    $tag = SupportTicketTag::factory()->create();
    $ticket->tags()->attach($tag->id);

    $tag->delete();

    expect(SupportTicketTagAssignment::where('tag_id', $tag->id)->exists())->toBeFalse();
});

test('can attach multiple tags to ticket', function (): void {
    $ticket = SupportTicket::factory()->create();
    $tags = SupportTicketTag::factory()->count(3)->create();

    $ticket->tags()->attach($tags->pluck('id'));

    expect(SupportTicketTagAssignment::where('ticket_id', $ticket->id)->count())->toBe(3);
});

test('can attach tag to multiple tickets', function (): void {
    $tag = SupportTicketTag::factory()->create();
    $tickets = SupportTicket::factory()->count(3)->create();

    foreach ($tickets as $ticket) {
        $ticket->tags()->attach($tag->id);
    }

    expect(SupportTicketTagAssignment::where('tag_id', $tag->id)->count())->toBe(3);
});
