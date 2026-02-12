<?php

declare(strict_types=1);

use App\Enums\Support\SupportTicketPriority;
use App\Enums\Support\SupportTicketStatus;
use App\Models\Platform\SuperAdminUser;
use App\Models\Support\SupportTicket;
use App\Enums\User\TenantRole;
use App\Models\Tenant\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->admin = SuperAdminUser::factory()->active()->create();
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::MEMBER,
    ]);
});

describe('GET /api/v1/admin/support/tickets', function () {
    it('returns all tickets for admin', function () {
        SupportTicket::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->count(5)
            ->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/support/tickets');

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
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonCount(5, 'data');
    });

    it('filters tickets by status', function () {
        SupportTicket::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->open()
            ->count(3)
            ->create();

        SupportTicket::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->closed()
            ->count(2)
            ->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/support/tickets?status=open');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('filters unassigned tickets', function () {
        SupportTicket::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->open()
            ->count(2)
            ->create();

        SupportTicket::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->assignedTo($this->admin)
            ->count(3)
            ->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/support/tickets?unassigned=true');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('requires admin authentication', function () {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/admin/support/tickets');

        $response->assertStatus(403);
    });
});

describe('GET /api/v1/admin/support/tickets/{ticket}', function () {
    it('returns ticket details', function () {
        $ticket = SupportTicket::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/admin/support/tickets/{$ticket->id}");

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
                    'tenant_id',
                    'assigned_to_id',
                    'comment_count',
                    'created_at',
                    'updated_at',
                ],
            ]);
    });
});

describe('POST /api/v1/admin/support/tickets/{ticket}/assign', function () {
    it('assigns ticket to an agent', function () {
        $ticket = SupportTicket::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->newStatus()
            ->create();

        $agent = SuperAdminUser::factory()->active()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/support/tickets/{$ticket->id}/assign", [
                'agent_id' => $agent->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'assigned_to_id' => $agent->id,
                    'status' => SupportTicketStatus::OPEN->value,
                ],
            ]);
    });

    it('validates agent exists', function () {
        $ticket = SupportTicket::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/support/tickets/{$ticket->id}/assign", [
                'agent_id' => '00000000-0000-0000-0000-000000000000',
            ]);

        $response->assertUnprocessable();
    });
});

describe('POST /api/v1/admin/support/tickets/{ticket}/unassign', function () {
    it('unassigns ticket', function () {
        $ticket = SupportTicket::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->assignedTo($this->admin)
            ->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/support/tickets/{$ticket->id}/unassign");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'assigned_to_id' => null,
                ],
            ]);
    });
});

describe('PUT /api/v1/admin/support/tickets/{ticket}/status', function () {
    it('updates ticket status', function () {
        $ticket = SupportTicket::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->open()
            ->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/admin/support/tickets/{$ticket->id}/status", [
                'status' => SupportTicketStatus::IN_PROGRESS->value,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status' => SupportTicketStatus::IN_PROGRESS->value,
                ],
            ]);
    });

    it('validates status transition', function () {
        $ticket = SupportTicket::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->closed()
            ->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/admin/support/tickets/{$ticket->id}/status", [
                'status' => SupportTicketStatus::IN_PROGRESS->value,
            ]);

        $response->assertUnprocessable();
    });
});

describe('PUT /api/v1/admin/support/tickets/{ticket}/priority', function () {
    it('updates ticket priority', function () {
        $ticket = SupportTicket::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->mediumPriority()
            ->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/admin/support/tickets/{$ticket->id}/priority", [
                'priority' => SupportTicketPriority::URGENT->value,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'priority' => SupportTicketPriority::URGENT->value,
                ],
            ]);
    });
});

describe('GET /api/v1/admin/support/stats', function () {
    it('returns support statistics', function () {
        // Create various tickets
        SupportTicket::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->open()
            ->count(3)
            ->create();

        SupportTicket::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->closed()
            ->count(2)
            ->create();

        SupportTicket::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->resolved()
            ->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/support/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_tickets',
                    'open_tickets',
                    'pending_tickets',
                    'resolved_tickets',
                    'closed_tickets',
                    'unassigned_tickets',
                    'by_priority',
                    'by_type',
                ],
            ])
            ->assertJson([
                'data' => [
                    'total_tickets' => 6,
                    'open_tickets' => 3,
                    'closed_tickets' => 2,
                    'resolved_tickets' => 1,
                ],
            ]);
    });
});
