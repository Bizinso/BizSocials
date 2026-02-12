<?php

declare(strict_types=1);

/**
 * SupportTicket Model Unit Tests
 *
 * Tests for the SupportTicket model.
 *
 * @see \App\Models\Support\SupportTicket
 */

use App\Enums\Support\SupportChannel;
use App\Enums\Support\SupportCommentType;
use App\Enums\Support\SupportTicketPriority;
use App\Enums\Support\SupportTicketStatus;
use App\Enums\Support\SupportTicketType;
use App\Models\Platform\SuperAdminUser;
use App\Models\Support\SupportCategory;
use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketAttachment;
use App\Models\Support\SupportTicketComment;
use App\Models\Support\SupportTicketTag;
use App\Models\Support\SupportTicketWatcher;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create ticket with factory', function (): void {
    $ticket = SupportTicket::factory()->create();

    expect($ticket)->toBeInstanceOf(SupportTicket::class)
        ->and($ticket->id)->not->toBeNull()
        ->and($ticket->ticket_number)->not->toBeNull()
        ->and($ticket->subject)->not->toBeNull();
});

test('has correct table name', function (): void {
    $ticket = new SupportTicket();

    expect($ticket->getTable())->toBe('support_tickets');
});

test('auto-generates ticket number on creation', function (): void {
    $ticket = SupportTicket::factory()->create(['ticket_number' => null]);

    expect($ticket->ticket_number)->toMatch('/^TKT-[A-Z0-9]{6}$/');
});

test('casts attributes correctly', function (): void {
    $ticket = SupportTicket::factory()->create();

    expect($ticket->ticket_type)->toBeInstanceOf(SupportTicketType::class)
        ->and($ticket->priority)->toBeInstanceOf(SupportTicketPriority::class)
        ->and($ticket->status)->toBeInstanceOf(SupportTicketStatus::class)
        ->and($ticket->channel)->toBeInstanceOf(SupportChannel::class)
        ->and($ticket->is_sla_breached)->toBeBool()
        ->and($ticket->comment_count)->toBeInt();
});

test('tenant relationship works', function (): void {
    $tenant = Tenant::factory()->create();
    $ticket = SupportTicket::factory()->forTenant($tenant)->create();

    expect($ticket->tenant)->toBeInstanceOf(Tenant::class)
        ->and($ticket->tenant->id)->toBe($tenant->id);
});

test('user relationship works', function (): void {
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->forUser($user)->create();

    expect($ticket->user)->toBeInstanceOf(User::class)
        ->and($ticket->user->id)->toBe($user->id);
});

test('category relationship works', function (): void {
    $category = SupportCategory::factory()->create();
    $ticket = SupportTicket::factory()->inCategory($category)->create();

    expect($ticket->category)->toBeInstanceOf(SupportCategory::class)
        ->and($ticket->category->id)->toBe($category->id);
});

test('assignee relationship works', function (): void {
    $admin = SuperAdminUser::factory()->create();
    $ticket = SupportTicket::factory()->assignedTo($admin)->create();

    expect($ticket->assignee)->toBeInstanceOf(SuperAdminUser::class)
        ->and($ticket->assignee->id)->toBe($admin->id);
});

test('comments relationship works', function (): void {
    $ticket = SupportTicket::factory()->create();
    SupportTicketComment::factory()->forTicket($ticket)->count(3)->create();

    expect($ticket->comments)->toHaveCount(3)
        ->and($ticket->comments->first())->toBeInstanceOf(SupportTicketComment::class);
});

test('attachments relationship works', function (): void {
    $ticket = SupportTicket::factory()->create();
    SupportTicketAttachment::factory()->forTicket($ticket)->count(2)->create();

    expect($ticket->attachments)->toHaveCount(2)
        ->and($ticket->attachments->first())->toBeInstanceOf(SupportTicketAttachment::class);
});

test('tags relationship works', function (): void {
    $ticket = SupportTicket::factory()->create();
    $tags = SupportTicketTag::factory()->count(3)->create();

    $ticket->tags()->attach($tags->pluck('id'));

    expect($ticket->tags)->toHaveCount(3)
        ->and($ticket->tags->first())->toBeInstanceOf(SupportTicketTag::class);
});

test('watchers relationship works', function (): void {
    $ticket = SupportTicket::factory()->create();
    SupportTicketWatcher::factory()->forTicket($ticket)->count(2)->create();

    expect($ticket->watchers)->toHaveCount(2)
        ->and($ticket->watchers->first())->toBeInstanceOf(SupportTicketWatcher::class);
});

test('newTickets scope filters new tickets', function (): void {
    SupportTicket::factory()->newStatus()->count(2)->create();
    SupportTicket::factory()->open()->create();

    expect(SupportTicket::newTickets()->count())->toBe(2);
});

test('open scope filters open tickets', function (): void {
    SupportTicket::factory()->newStatus()->create();
    SupportTicket::factory()->open()->create();
    SupportTicket::factory()->inProgress()->create();
    SupportTicket::factory()->closed()->create();

    expect(SupportTicket::open()->count())->toBe(3);
});

test('pending scope filters pending tickets', function (): void {
    SupportTicket::factory()->waitingCustomer()->count(2)->create();
    SupportTicket::factory()->waitingInternal()->create();
    SupportTicket::factory()->open()->create();

    expect(SupportTicket::pending()->count())->toBe(3);
});

test('closed scope filters closed tickets', function (): void {
    SupportTicket::factory()->resolved()->create();
    SupportTicket::factory()->closed()->create();
    SupportTicket::factory()->open()->create();

    expect(SupportTicket::closed()->count())->toBe(2);
});

test('byStatus scope filters by status', function (): void {
    SupportTicket::factory()->open()->count(2)->create();
    SupportTicket::factory()->newStatus()->create();

    expect(SupportTicket::byStatus(SupportTicketStatus::OPEN)->count())->toBe(2);
});

test('byPriority scope filters by priority', function (): void {
    SupportTicket::factory()->urgent()->count(2)->create();
    SupportTicket::factory()->lowPriority()->create();

    expect(SupportTicket::byPriority(SupportTicketPriority::URGENT)->count())->toBe(2);
});

test('unassigned scope filters unassigned tickets', function (): void {
    $admin = SuperAdminUser::factory()->create();
    SupportTicket::factory()->newStatus()->count(2)->create();
    SupportTicket::factory()->assignedTo($admin)->create();

    expect(SupportTicket::unassigned()->count())->toBe(2);
});

test('isNew returns correct value', function (): void {
    $new = SupportTicket::factory()->newStatus()->create();
    $open = SupportTicket::factory()->open()->create();

    expect($new->isNew())->toBeTrue()
        ->and($open->isNew())->toBeFalse();
});

test('isOpen returns correct value', function (): void {
    $open = SupportTicket::factory()->open()->create();
    $closed = SupportTicket::factory()->closed()->create();

    expect($open->isOpen())->toBeTrue()
        ->and($closed->isOpen())->toBeFalse();
});

test('isPending returns correct value', function (): void {
    $waiting = SupportTicket::factory()->waitingCustomer()->create();
    $open = SupportTicket::factory()->open()->create();

    expect($waiting->isPending())->toBeTrue()
        ->and($open->isPending())->toBeFalse();
});

test('isClosed returns correct value', function (): void {
    $closed = SupportTicket::factory()->closed()->create();
    $open = SupportTicket::factory()->open()->create();

    expect($closed->isClosed())->toBeTrue()
        ->and($open->isClosed())->toBeFalse();
});

test('isOverdue returns true when sla_due_at is past', function (): void {
    $overdue = SupportTicket::factory()->overdue()->create();
    $notOverdue = SupportTicket::factory()->create();

    expect($overdue->isOverdue())->toBeTrue()
        ->and($notOverdue->isOverdue())->toBeFalse();
});

test('assign assigns ticket to admin', function (): void {
    $ticket = SupportTicket::factory()->newStatus()->create();
    $admin = SuperAdminUser::factory()->create();

    $ticket->assign($admin);

    expect($ticket->fresh()->assigned_to)->toBe($admin->id)
        ->and($ticket->fresh()->status)->toBe(SupportTicketStatus::OPEN);
});

test('unassign removes assignment', function (): void {
    $admin = SuperAdminUser::factory()->create();
    $ticket = SupportTicket::factory()->assignedTo($admin)->create();

    $ticket->unassign();

    expect($ticket->fresh()->assigned_to)->toBeNull();
});

test('changeStatus updates status correctly', function (): void {
    $ticket = SupportTicket::factory()->open()->create();

    $result = $ticket->changeStatus(SupportTicketStatus::IN_PROGRESS);

    expect($result)->toBeTrue()
        ->and($ticket->fresh()->status)->toBe(SupportTicketStatus::IN_PROGRESS);
});

test('changeStatus returns false for invalid transition', function (): void {
    $ticket = SupportTicket::factory()->newStatus()->create();

    $result = $ticket->changeStatus(SupportTicketStatus::REOPENED);

    expect($result)->toBeFalse()
        ->and($ticket->fresh()->status)->toBe(SupportTicketStatus::NEW);
});

test('resolve sets status and resolved_at', function (): void {
    $ticket = SupportTicket::factory()->inProgress()->create();

    $result = $ticket->resolve();

    expect($result)->toBeTrue()
        ->and($ticket->fresh()->status)->toBe(SupportTicketStatus::RESOLVED)
        ->and($ticket->fresh()->resolved_at)->not->toBeNull();
});

test('close sets status and closed_at', function (): void {
    $ticket = SupportTicket::factory()->resolved()->create();

    $result = $ticket->close();

    expect($result)->toBeTrue()
        ->and($ticket->fresh()->status)->toBe(SupportTicketStatus::CLOSED)
        ->and($ticket->fresh()->closed_at)->not->toBeNull();
});

test('reopen sets status to reopened', function (): void {
    $ticket = SupportTicket::factory()->closed()->create();

    $result = $ticket->reopen();

    expect($result)->toBeTrue()
        ->and($ticket->fresh()->status)->toBe(SupportTicketStatus::REOPENED);
});

test('addComment creates comment and increments count', function (): void {
    $ticket = SupportTicket::factory()->create(['comment_count' => 0]);

    $comment = $ticket->addComment('Test comment');

    expect($comment)->toBeInstanceOf(SupportTicketComment::class)
        ->and($comment->content)->toBe('Test comment')
        ->and($ticket->fresh()->comment_count)->toBe(1);
});

test('getRequesterDisplay returns name or email', function (): void {
    $ticketWithName = SupportTicket::factory()->create([
        'requester_name' => 'John Doe',
        'requester_email' => 'john@example.com',
    ]);
    $ticketWithoutName = SupportTicket::factory()->create([
        'requester_name' => '',
        'requester_email' => 'jane@example.com',
    ]);

    expect($ticketWithName->getRequesterDisplay())->toBe('John Doe')
        ->and($ticketWithoutName->getRequesterDisplay())->toBe('jane@example.com');
});
