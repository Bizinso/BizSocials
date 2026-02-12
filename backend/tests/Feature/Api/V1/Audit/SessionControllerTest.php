<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Audit;

use App\Enums\Audit\SessionStatus;
use App\Models\Audit\LoginHistory;
use App\Models\Audit\SessionHistory;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class SessionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_list_active_sessions(): void
    {
        Sanctum::actingAs($this->user);

        SessionHistory::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'status' => SessionStatus::ACTIVE,
        ]);

        $response = $this->getJson('/api/v1/security/sessions');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'status',
                        'ip_address',
                        'is_current',
                        'created_at',
                    ],
                ],
            ])
            ->assertJson(['success' => true]);
    }

    public function test_can_get_login_history(): void
    {
        Sanctum::actingAs($this->user);

        LoginHistory::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->getJson('/api/v1/security/login-history');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'is_successful',
                        'ip_address',
                        'created_at',
                    ],
                ],
            ])
            ->assertJson(['success' => true]);
    }

    public function test_can_terminate_session(): void
    {
        Sanctum::actingAs($this->user);

        $session = SessionHistory::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'status' => SessionStatus::ACTIVE,
            'is_current' => false,
        ]);

        $response = $this->deleteJson("/api/v1/security/sessions/{$session->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Session terminated successfully',
            ]);

        $this->assertDatabaseHas('session_history', [
            'id' => $session->id,
            'status' => SessionStatus::REVOKED->value,
        ]);
    }

    public function test_cannot_terminate_current_session(): void
    {
        Sanctum::actingAs($this->user);

        $session = SessionHistory::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'status' => SessionStatus::ACTIVE,
            'is_current' => true,
        ]);

        $response = $this->deleteJson("/api/v1/security/sessions/{$session->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_cannot_terminate_another_users_session(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $session = SessionHistory::factory()->create([
            'user_id' => $otherUser->id,
            'tenant_id' => $this->tenant->id,
            'status' => SessionStatus::ACTIVE,
        ]);

        $response = $this->deleteJson("/api/v1/security/sessions/{$session->id}");

        $response->assertNotFound();
    }

    public function test_can_terminate_all_sessions(): void
    {
        Sanctum::actingAs($this->user);

        SessionHistory::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'status' => SessionStatus::ACTIVE,
            'is_current' => false,
        ]);

        $response = $this->postJson('/api/v1/security/sessions/terminate-all');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'All other sessions terminated successfully',
            ]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/security/sessions');

        $response->assertUnauthorized();
    }

    public function test_sessions_are_scoped_to_user(): void
    {
        Sanctum::actingAs($this->user);

        // Create sessions for current user
        SessionHistory::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'status' => SessionStatus::ACTIVE,
        ]);

        // Create sessions for another user
        $otherUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        SessionHistory::factory()->count(3)->create([
            'user_id' => $otherUser->id,
            'tenant_id' => $this->tenant->id,
            'status' => SessionStatus::ACTIVE,
        ]);

        $response = $this->getJson('/api/v1/security/sessions');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }
}
