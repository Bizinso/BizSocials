<?php

declare(strict_types=1);

use App\Data\Support\CreateTicketData;
use App\Data\Support\UpdateTicketData;
use App\Enums\Support\SupportTicketPriority;
use App\Enums\Support\SupportTicketStatus;
use App\Enums\Support\SupportTicketType;
use App\Models\Platform\SuperAdminUser;
use App\Models\Support\SupportCategory;
use App\Models\Support\SupportTicket;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\Support\SupportTicketService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = app(SupportTicketService::class);
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->admin = SuperAdminUser::factory()->active()->create();
    $this->category = SupportCategory::factory()->active()->create();
});

describe('listForUser', function () {
    it('returns only tickets for the specified user', function () {
        SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->count(3)
            ->create();

        $otherUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
        SupportTicket::factory()
            ->forUser($otherUser)
            ->forTenant($this->tenant)
            ->count(2)
            ->create();

        $result = $this->service->listForUser($this->user);

        expect($result->total())->toBe(3);
    });

    it('filters by status', function () {
        SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->open()
            ->count(2)
            ->create();

        SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->closed()
            ->create();

        $result = $this->service->listForUser($this->user, ['status' => 'open']);

        expect($result->total())->toBe(2);
    });
});

describe('create', function () {
    it('creates a new ticket', function () {
        $data = new CreateTicketData(
            subject: 'Test ticket',
            description: 'This is a test ticket description',
            type: SupportTicketType::QUESTION,
            priority: SupportTicketPriority::MEDIUM,
            category_id: $this->category->id,
        );

        $ticket = $this->service->create($this->user, $this->tenant, $data);

        expect($ticket->subject)->toBe('Test ticket');
        expect($ticket->status)->toBe(SupportTicketStatus::NEW);
        expect($ticket->user_id)->toBe($this->user->id);
        expect($ticket->tenant_id)->toBe($this->tenant->id);
        expect($ticket->category_id)->toBe($this->category->id);
    });

    it('generates ticket number automatically', function () {
        $data = new CreateTicketData(
            subject: 'Test ticket',
            description: 'Description',
        );

        $ticket = $this->service->create($this->user, $this->tenant, $data);

        expect($ticket->ticket_number)->toStartWith('TKT-');
    });

    it('throws exception for invalid category', function () {
        $data = new CreateTicketData(
            subject: 'Test ticket',
            description: 'Description',
            category_id: '00000000-0000-0000-0000-000000000000',
        );

        expect(fn () => $this->service->create($this->user, $this->tenant, $data))
            ->toThrow(ValidationException::class);
    });

    it('increments category ticket count', function () {
        $data = new CreateTicketData(
            subject: 'Test ticket',
            description: 'Description',
            category_id: $this->category->id,
        );

        $this->service->create($this->user, $this->tenant, $data);

        $this->category->refresh();
        expect($this->category->ticket_count)->toBe(1);
    });
});

describe('get', function () {
    it('returns ticket by id', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->create();

        $result = $this->service->get($ticket->id);

        expect($result->id)->toBe($ticket->id);
    });

    it('throws exception for non-existent ticket', function () {
        expect(fn () => $this->service->get('00000000-0000-0000-0000-000000000000'))
            ->toThrow(ModelNotFoundException::class);
    });
});

describe('getForUser', function () {
    it('returns ticket for owner', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->create();

        $result = $this->service->getForUser($this->user, $ticket->id);

        expect($result->id)->toBe($ticket->id);
    });

    it('throws exception for ticket of another user', function () {
        $otherUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $ticket = SupportTicket::factory()
            ->forUser($otherUser)
            ->forTenant($this->tenant)
            ->create();

        expect(fn () => $this->service->getForUser($this->user, $ticket->id))
            ->toThrow(ModelNotFoundException::class);
    });
});

describe('update', function () {
    it('updates ticket subject and description', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->create();

        $data = new UpdateTicketData(
            subject: 'Updated subject',
            description: 'Updated description',
        );

        $result = $this->service->update($ticket, $data);

        expect($result->subject)->toBe('Updated subject');
        expect($result->description)->toBe('Updated description');
    });
});

describe('close', function () {
    it('closes an open ticket', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->open()
            ->create();

        $result = $this->service->close($ticket);

        expect($result->status)->toBe(SupportTicketStatus::CLOSED);
        expect($result->closed_at)->not->toBeNull();
    });

    it('throws exception for already closed ticket', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->closed()
            ->create();

        expect(fn () => $this->service->close($ticket))
            ->toThrow(ValidationException::class);
    });
});

describe('reopen', function () {
    it('reopens a closed ticket', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->closed()
            ->create();

        $result = $this->service->reopen($ticket);

        expect($result->status)->toBe(SupportTicketStatus::REOPENED);
    });

    it('throws exception for open ticket', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->open()
            ->create();

        expect(fn () => $this->service->reopen($ticket))
            ->toThrow(ValidationException::class);
    });
});

describe('assign', function () {
    it('assigns ticket to an admin', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->newStatus()
            ->create();

        $result = $this->service->assign($ticket, $this->admin);

        expect($result->assigned_to)->toBe($this->admin->id);
        expect($result->status)->toBe(SupportTicketStatus::OPEN);
    });
});

describe('unassign', function () {
    it('removes assignment from ticket', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->assignedTo($this->admin)
            ->create();

        $result = $this->service->unassign($ticket);

        expect($result->assigned_to)->toBeNull();
    });
});

describe('updateStatus', function () {
    it('updates ticket status', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->open()
            ->create();

        $result = $this->service->updateStatus($ticket, SupportTicketStatus::IN_PROGRESS);

        expect($result->status)->toBe(SupportTicketStatus::IN_PROGRESS);
    });

    it('throws exception for invalid transition', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->closed()
            ->create();

        expect(fn () => $this->service->updateStatus($ticket, SupportTicketStatus::IN_PROGRESS))
            ->toThrow(ValidationException::class);
    });
});

describe('updatePriority', function () {
    it('updates ticket priority', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->mediumPriority()
            ->create();

        $result = $this->service->updatePriority($ticket, SupportTicketPriority::URGENT);

        expect($result->priority)->toBe(SupportTicketPriority::URGENT);
    });

    it('updates SLA due date when priority changes', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->mediumPriority()
            ->create();

        $originalSlaDue = $ticket->sla_due_at;

        $result = $this->service->updatePriority($ticket, SupportTicketPriority::URGENT);

        expect($result->sla_due_at)->not->toEqual($originalSlaDue);
    });
});

describe('getStats', function () {
    it('returns correct statistics', function () {
        SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->open()
            ->count(3)
            ->create();

        SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->closed()
            ->count(2)
            ->create();

        $stats = $this->service->getStats();

        expect($stats->total_tickets)->toBe(5);
        expect($stats->open_tickets)->toBe(3);
        expect($stats->closed_tickets)->toBe(2);
    });
});
