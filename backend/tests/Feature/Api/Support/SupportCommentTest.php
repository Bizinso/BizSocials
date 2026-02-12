<?php

declare(strict_types=1);

use App\Enums\Support\SupportCommentType;
use App\Models\Platform\SuperAdminUser;
use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketComment;
use App\Models\Tenant\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->admin = SuperAdminUser::factory()->active()->create();
    $this->ticket = SupportTicket::factory()
        ->forUser($this->user)
        ->forTenant($this->tenant)
        ->open()
        ->create();
});

describe('GET /api/v1/support/tickets/{ticket}/comments', function () {
    it('returns public comments for ticket owner', function () {
        // Create public comments
        SupportTicketComment::factory()
            ->forTicket($this->ticket)
            ->byUser($this->user)
            ->reply()
            ->count(2)
            ->create();

        // Create internal notes (should not be visible)
        SupportTicketComment::factory()
            ->forTicket($this->ticket)
            ->byAdmin($this->admin)
            ->note()
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/support/tickets/{$this->ticket->id}/comments");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'ticket_id',
                        'comment_type',
                        'content',
                        'is_internal',
                        'author_type',
                        'author_name',
                        'created_at',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data');
    });

    it('returns 404 for ticket belonging to another user', function () {
        $otherUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $otherTicket = SupportTicket::factory()
            ->forUser($otherUser)
            ->forTenant($this->tenant)
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/support/tickets/{$otherTicket->id}/comments");

        $response->assertNotFound();
    });
});

describe('POST /api/v1/support/tickets/{ticket}/comments', function () {
    it('adds a comment to open ticket', function () {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/support/tickets/{$this->ticket->id}/comments", [
                'content' => 'This is my comment on the ticket.',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'ticket_id',
                    'comment_type',
                    'content',
                    'is_internal',
                    'author_type',
                    'author_name',
                    'created_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'comment_type' => SupportCommentType::REPLY->value,
                    'is_internal' => false,
                    'author_type' => 'user',
                ],
            ]);

        $this->ticket->refresh();
        expect($this->ticket->comment_count)->toBe(1);
    });

    it('validates content is required', function () {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/support/tickets/{$this->ticket->id}/comments", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    });

    it('cannot add comment to closed ticket', function () {
        $closedTicket = SupportTicket::factory()
            ->forUser($this->user)
            ->forTenant($this->tenant)
            ->closed()
            ->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/support/tickets/{$closedTicket->id}/comments", [
                'content' => 'This should fail.',
            ]);

        $response->assertUnprocessable();
    });

    it('returns 404 for ticket belonging to another user', function () {
        $otherUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $otherTicket = SupportTicket::factory()
            ->forUser($otherUser)
            ->forTenant($this->tenant)
            ->open()
            ->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/support/tickets/{$otherTicket->id}/comments", [
                'content' => 'This should fail.',
            ]);

        $response->assertNotFound();
    });
});
