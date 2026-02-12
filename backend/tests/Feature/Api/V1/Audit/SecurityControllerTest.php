<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Audit;

use App\Enums\Audit\SecurityEventType;
use App\Enums\Audit\SecuritySeverity;
use App\Models\Audit\SecurityEvent;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class SecurityControllerTest extends TestCase
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

    public function test_can_list_security_events(): void
    {
        Sanctum::actingAs($this->user);

        SecurityEvent::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/security/events');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'event_type',
                        'severity',
                        'is_resolved',
                        'created_at',
                    ],
                ],
                'meta',
            ])
            ->assertJson(['success' => true]);
    }

    public function test_can_filter_security_events_by_type(): void
    {
        Sanctum::actingAs($this->user);

        SecurityEvent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'event_type' => SecurityEventType::LOGIN_SUCCESS,
        ]);

        SecurityEvent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'event_type' => SecurityEventType::LOGIN_FAILURE,
        ]);

        $response = $this->getJson('/api/v1/security/events?event_type=login_success');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('login_success', $data[0]['event_type']);
    }

    public function test_can_filter_security_events_by_severity(): void
    {
        Sanctum::actingAs($this->user);

        SecurityEvent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'severity' => SecuritySeverity::CRITICAL,
        ]);

        SecurityEvent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'severity' => SecuritySeverity::LOW,
        ]);

        $response = $this->getJson('/api/v1/security/events?severity=critical');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('critical', $data[0]['severity']);
    }

    public function test_can_get_security_stats(): void
    {
        Sanctum::actingAs($this->user);

        SecurityEvent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'severity' => SecuritySeverity::CRITICAL,
        ]);

        SecurityEvent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'severity' => SecuritySeverity::HIGH,
        ]);

        SecurityEvent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'event_type' => SecurityEventType::LOGIN_FAILURE,
        ]);

        $response = $this->getJson('/api/v1/security/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_events',
                    'critical_events',
                    'high_events',
                    'medium_events',
                    'failed_logins_24h',
                    'suspicious_activities',
                    'unresolved_events',
                    'events_by_type',
                    'events_by_severity',
                ],
            ])
            ->assertJson(['success' => true]);
    }

    public function test_security_events_are_scoped_to_tenant(): void
    {
        Sanctum::actingAs($this->user);

        // Create events for current tenant
        SecurityEvent::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create events for another tenant
        $otherTenant = Tenant::factory()->create();
        SecurityEvent::factory()->count(2)->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->getJson('/api/v1/security/events');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/security/events');

        $response->assertUnauthorized();
    }
}
