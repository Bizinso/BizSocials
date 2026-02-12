<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Audit;

use App\Enums\Audit\AuditAction;
use App\Enums\Audit\AuditableType;
use App\Models\Audit\AuditLog;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AuditLogControllerTest extends TestCase
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

    public function test_can_list_audit_logs_for_tenant(): void
    {
        Sanctum::actingAs($this->user);

        // Create some audit logs
        AuditLog::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/audit/logs');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'action',
                        'auditable_type',
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
            ->assertJson(['success' => true]);
    }

    public function test_can_filter_audit_logs_by_action(): void
    {
        Sanctum::actingAs($this->user);

        AuditLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'action' => AuditAction::CREATE,
        ]);

        AuditLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'action' => AuditAction::UPDATE,
        ]);

        $response = $this->getJson('/api/v1/audit/logs?action=create');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('create', $data[0]['action']);
    }

    public function test_can_filter_audit_logs_by_auditable_type(): void
    {
        Sanctum::actingAs($this->user);

        AuditLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => AuditableType::USER,
        ]);

        AuditLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => AuditableType::WORKSPACE,
        ]);

        $response = $this->getJson('/api/v1/audit/logs?auditable_type=user');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('user', $data[0]['auditable_type']);
    }

    public function test_can_get_logs_for_specific_auditable(): void
    {
        Sanctum::actingAs($this->user);

        // Create audit logs for the user
        AuditLog::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => AuditableType::USER,
            'auditable_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/audit/logs/user/{$this->user->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    public function test_returns_error_for_invalid_auditable_type(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/audit/logs/invalid_type/123');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid auditable type.',
            ]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/audit/logs');

        $response->assertUnauthorized();
    }

    public function test_audit_logs_are_scoped_to_tenant(): void
    {
        Sanctum::actingAs($this->user);

        // Create logs for current tenant
        AuditLog::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create logs for another tenant
        $otherTenant = Tenant::factory()->create();
        AuditLog::factory()->count(2)->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->getJson('/api/v1/audit/logs');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(3, $data);
    }
}
