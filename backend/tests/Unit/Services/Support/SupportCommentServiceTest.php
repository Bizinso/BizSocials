<?php

declare(strict_types=1);

use App\Data\Support\AddCommentData;
use App\Enums\Support\SupportCommentType;
use App\Enums\Support\SupportTicketStatus;
use App\Models\Platform\SuperAdminUser;
use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketComment;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\Support\SupportCommentService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = app(SupportCommentService::class);
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->admin = SuperAdminUser::factory()->active()->create();
    $this->ticket = SupportTicket::factory()
        ->forUser($this->user)
        ->forTenant($this->tenant)
        ->open()
        ->create();
});

describe('listForTicket', function () {
    it('returns only public comments when includeInternal is false', function () {
        SupportTicketComment::factory()
            ->forTicket($this->ticket)
            ->byUser($this->user)
            ->reply()
            ->count(2)
            ->create();

        SupportTicketComment::factory()
            ->forTicket($this->ticket)
            ->byAdmin($this->admin)
            ->note()
            ->count(3)
            ->create();

        $result = $this->service->listForTicket($this->ticket, includeInternal: false);

        expect($result)->toHaveCount(2);
    });

    it('returns all comments when includeInternal is true', function () {
        SupportTicketComment::factory()
            ->forTicket($this->ticket)
            ->byUser($this->user)
            ->reply()
            ->count(2)
            ->create();

        SupportTicketComment::factory()
            ->forTicket($this->ticket)
            ->byAdmin($this->admin)
            ->note()
            ->count(3)
            ->create();

        $result = $this->service->listForTicket($this->ticket, includeInternal: true);

        expect($result)->toHaveCount(5);
    });

    it('returns comments ordered by created_at ascending', function () {
        $first = SupportTicketComment::factory()
            ->forTicket($this->ticket)
            ->byUser($this->user)
            ->reply()
            ->create(['created_at' => now()->subDay()]);

        $second = SupportTicketComment::factory()
            ->forTicket($this->ticket)
            ->byAdmin($this->admin)
            ->reply()
            ->create(['created_at' => now()]);

        $result = $this->service->listForTicket($this->ticket, includeInternal: true);

        expect($result->first()->id)->toBe($first->id);
        expect($result->last()->id)->toBe($second->id);
    });
});

describe('addUserComment', function () {
    it('adds a user comment to ticket', function () {
        $data = new AddCommentData(
            content: 'This is my comment',
            is_internal: false,
        );

        $comment = $this->service->addUserComment($this->ticket, $this->user, $data);

        expect($comment->content)->toBe('This is my comment');
        expect($comment->comment_type)->toBe(SupportCommentType::REPLY);
        expect($comment->is_internal)->toBeFalse();
        expect($comment->user_id)->toBe($this->user->id);
    });

    it('increments ticket comment count', function () {
        $data = new AddCommentData(content: 'Comment');

        $this->service->addUserComment($this->ticket, $this->user, $data);

        $this->ticket->refresh();
        expect($this->ticket->comment_count)->toBe(1);
    });

    it('throws exception for closed ticket', function () {
        $closedTicket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->closed()
            ->create();

        $data = new AddCommentData(content: 'Comment');

        expect(fn () => $this->service->addUserComment($closedTicket, $this->user, $data))
            ->toThrow(ValidationException::class);
    });

    it('updates ticket status from waiting_customer to open', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->waitingCustomer()
            ->create();

        $data = new AddCommentData(content: 'Response from customer');

        $this->service->addUserComment($ticket, $this->user, $data);

        $ticket->refresh();
        expect($ticket->status)->toBe(SupportTicketStatus::OPEN);
    });
});

describe('addAgentComment', function () {
    it('adds an agent comment visible to user', function () {
        $data = new AddCommentData(
            content: 'Thank you for contacting support',
            is_internal: false,
        );

        $comment = $this->service->addAgentComment($this->ticket, $this->admin, $data);

        expect($comment->content)->toBe('Thank you for contacting support');
        expect($comment->comment_type)->toBe(SupportCommentType::REPLY);
        expect($comment->is_internal)->toBeFalse();
        expect($comment->admin_id)->toBe($this->admin->id);
    });

    it('updates ticket status to waiting_customer', function () {
        $data = new AddCommentData(content: 'Please provide more details');

        $this->service->addAgentComment($this->ticket, $this->admin, $data);

        $this->ticket->refresh();
        expect($this->ticket->status)->toBe(SupportTicketStatus::WAITING_CUSTOMER);
    });

    it('records first response time', function () {
        expect($this->ticket->first_response_at)->toBeNull();

        $data = new AddCommentData(content: 'First response');

        $this->service->addAgentComment($this->ticket, $this->admin, $data);

        $this->ticket->refresh();
        expect($this->ticket->first_response_at)->not->toBeNull();
    });

    it('throws exception for closed ticket', function () {
        $closedTicket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->closed()
            ->create();

        $data = new AddCommentData(content: 'Comment');

        expect(fn () => $this->service->addAgentComment($closedTicket, $this->admin, $data))
            ->toThrow(ValidationException::class);
    });
});

describe('addInternalNote', function () {
    it('adds an internal note not visible to user', function () {
        $data = new AddCommentData(
            content: 'Internal note about this customer',
            is_internal: true,
        );

        $comment = $this->service->addInternalNote($this->ticket, $this->admin, $data);

        expect($comment->content)->toBe('Internal note about this customer');
        expect($comment->comment_type)->toBe(SupportCommentType::NOTE);
        expect($comment->is_internal)->toBeTrue();
        expect($comment->admin_id)->toBe($this->admin->id);
    });

    it('does not update ticket status when adding note', function () {
        $originalStatus = $this->ticket->status;

        $data = new AddCommentData(content: 'Internal note');

        $this->service->addInternalNote($this->ticket, $this->admin, $data);

        $this->ticket->refresh();
        expect($this->ticket->status)->toBe($originalStatus);
    });
});
