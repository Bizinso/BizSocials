<?php

declare(strict_types=1);

use App\Enums\Support\SupportTicketPriority;
use App\Enums\Support\SupportTicketStatus;
use App\Enums\Support\SupportTicketType;
use App\Models\Support\SupportCategory;
use App\Models\Support\SupportTicket;
use App\Models\Tenant\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->category = SupportCategory::factory()->active()->create();
});

describe('GET /api/v1/support/tickets', function () {
    it('returns paginated tickets for authenticated user', function () {
        SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->count(3)
            ->create();

        // Create tickets for another user (should not appear)
        $otherUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
        SupportTicket::factory()
            ->forUser($otherUser)
            ->forTenant($this->tenant)
            ->count(2)
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/support/tickets');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'ticket_number',
                        'subject',
                        'status',
                        'priority',
                        'comment_count',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data');
    });

    it('filters tickets by status', function () {
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

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/support/tickets?status=open');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('filters tickets by priority', function () {
        SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->urgent()
            ->count(2)
            ->create();

        SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->lowPriority()
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/support/tickets?priority=urgent');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/support/tickets');

        $response->assertUnauthorized();
    });
});

describe('POST /api/v1/support/tickets', function () {
    it('creates a new ticket', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/support/tickets', [
                'subject' => 'Cannot login to my account',
                'description' => 'I am getting an error when trying to login.',
                'type' => SupportTicketType::PROBLEM->value,
                'priority' => SupportTicketPriority::HIGH->value,
                'category_id' => $this->category->id,
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'ticket_number',
                    'subject',
                    'description',
                    'status',
                    'priority',
                    'type',
                    'category_id',
                    'user_id',
                    'created_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'subject' => 'Cannot login to my account',
                    'status' => SupportTicketStatus::NEW->value,
                    'priority' => SupportTicketPriority::HIGH->value,
                ],
            ]);

        expect(SupportTicket::count())->toBe(1);
    });

    it('creates ticket with default values', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/support/tickets', [
                'subject' => 'Simple question',
                'description' => 'How do I change my password?',
            ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'type' => SupportTicketType::QUESTION->value,
                    'priority' => SupportTicketPriority::MEDIUM->value,
                ],
            ]);
    });

    it('validates required fields', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/support/tickets', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['subject', 'description']);
    });

    it('validates subject max length', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/support/tickets', [
                'subject' => str_repeat('a', 201),
                'description' => 'Description',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['subject']);
    });
});

describe('GET /api/v1/support/tickets/{ticket}', function () {
    it('returns ticket details for owner', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/support/tickets/{$ticket->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'ticket_number',
                    'subject',
                    'description',
                    'status',
                    'priority',
                    'type',
                    'channel',
                    'user_id',
                    'user_name',
                    'user_email',
                    'comment_count',
                    'created_at',
                    'updated_at',
                ],
            ]);
    });

    it('returns 404 for ticket belonging to another user', function () {
        $otherUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $ticket = SupportTicket::factory()
            ->forUser($otherUser)
            ->forTenant($this->tenant)
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/support/tickets/{$ticket->id}");

        $response->assertNotFound();
    });

    it('returns 404 for non-existent ticket', function () {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/support/tickets/00000000-0000-0000-0000-000000000000');

        $response->assertNotFound();
    });
});

describe('PUT /api/v1/support/tickets/{ticket}', function () {
    it('updates ticket subject and description', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->newStatus()
            ->create();

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/support/tickets/{$ticket->id}", [
                'subject' => 'Updated subject',
                'description' => 'Updated description',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'subject' => 'Updated subject',
                    'description' => 'Updated description',
                ],
            ]);
    });

    it('cannot update closed ticket', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->closed()
            ->create();

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/support/tickets/{$ticket->id}", [
                'subject' => 'Updated subject',
            ]);

        $response->assertUnprocessable();
    });
});

describe('POST /api/v1/support/tickets/{ticket}/close', function () {
    it('closes an open ticket', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->open()
            ->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/support/tickets/{$ticket->id}/close");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => SupportTicketStatus::CLOSED->value,
                ],
            ]);
    });

    it('closes a resolved ticket', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->resolved()
            ->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/support/tickets/{$ticket->id}/close");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => SupportTicketStatus::CLOSED->value,
                ],
            ]);
    });

    it('cannot close already closed ticket', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->closed()
            ->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/support/tickets/{$ticket->id}/close");

        $response->assertUnprocessable();
    });
});

describe('POST /api/v1/support/tickets/{ticket}/reopen', function () {
    it('reopens a closed ticket', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->closed()
            ->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/support/tickets/{$ticket->id}/reopen");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => SupportTicketStatus::REOPENED->value,
                ],
            ]);
    });

    it('reopens a resolved ticket', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->resolved()
            ->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/support/tickets/{$ticket->id}/reopen");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => SupportTicketStatus::REOPENED->value,
                ],
            ]);
    });

    it('cannot reopen an open ticket', function () {
        $ticket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->open()
            ->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/support/tickets/{$ticket->id}/reopen");

        $response->assertUnprocessable();
    });
});
