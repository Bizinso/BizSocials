<?php

declare(strict_types=1);

use App\Enums\Support\SupportCommentType;
use App\Models\Platform\SuperAdminUser;
use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketComment;
use App\Models\Tenant\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->admin = SuperAdminUser::factory()->active()->create();
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->ticket = SupportTicket::factory()
        ->forTenant($this->tenant)
        ->forUser($this->user)
        ->open()
        ->create();
});

describe('GET /api/v1/admin/support/tickets/{ticket}/comments', function () {
    it('returns all comments including internal notes', function () {
        // Create public comments
        SupportTicketComment::factory()
            ->forTicket($this->ticket)
            ->byUser($this->user)
            ->reply()
            ->count(2)
            ->create();

        // Create internal notes
        SupportTicketComment::factory()
            ->forTicket($this->ticket)
            ->byAdmin($this->admin)
            ->note()
            ->count(3)
            ->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/admin/support/tickets/{$this->ticket->id}/comments");

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    });
});

describe('POST /api/v1/admin/support/tickets/{ticket}/comments', function () {
    it('adds an agent comment visible to user', function () {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/support/tickets/{$this->ticket->id}/comments", [
                'content' => 'Thank you for contacting support. We will look into this.',
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
                    'author_type' => 'admin',
                ],
            ]);
    });

    it('cannot add comment to closed ticket', function () {
        $closedTicket = SupportTicket::factory()
            ->forTenant($this->tenant)
            ->forUser($this->user)
            ->closed()
            ->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/support/tickets/{$closedTicket->id}/comments", [
                'content' => 'This should fail.',
            ]);

        $response->assertUnprocessable();
    });
});

describe('POST /api/v1/admin/support/tickets/{ticket}/notes', function () {
    it('adds an internal note not visible to user', function () {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/support/tickets/{$this->ticket->id}/notes", [
                'content' => 'Internal note: This user has had issues before.',
            ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'comment_type' => SupportCommentType::NOTE->value,
                    'is_internal' => true,
                    'author_type' => 'admin',
                ],
            ]);
    });

    it('validates content is required', function () {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/support/tickets/{$this->ticket->id}/notes", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    });
});
