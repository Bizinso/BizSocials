<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Audit;

use App\Enums\Audit\SecurityEventType;
use App\Enums\Audit\SecuritySeverity;
use App\Models\Audit\SecurityEvent;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\Audit\SecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SecurityServiceTest extends TestCase
{
    use RefreshDatabase;

    private SecurityService $service;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SecurityService();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_log_security_event(): void
    {
        $event = $this->service->logEvent(
            type: SecurityEventType::LOGIN_SUCCESS,
            user: $this->user,
            metadata: ['ip' => '127.0.0.1'],
            description: 'User logged in successfully',
        );

        $this->assertInstanceOf(SecurityEvent::class, $event);
        $this->assertEquals(SecurityEventType::LOGIN_SUCCESS, $event->event_type);
        $this->assertEquals($this->user->id, $event->user_id);
        $this->assertEquals($this->tenant->id, $event->tenant_id);
        $this->assertEquals('User logged in successfully', $event->description);
        $this->assertFalse($event->is_resolved);
    }

    public function test_event_severity_is_set_automatically(): void
    {
        $criticalEvent = $this->service->logEvent(
            type: SecurityEventType::ACCOUNT_LOCKED,
            user: $this->user,
        );

        $this->assertEquals(SecuritySeverity::CRITICAL, $criticalEvent->severity);

        $infoEvent = $this->service->logEvent(
            type: SecurityEventType::LOGIN_SUCCESS,
            user: $this->user,
        );

        $this->assertEquals(SecuritySeverity::INFO, $infoEvent->severity);
    }

    public function test_can_list_events_for_tenant(): void
    {
        SecurityEvent::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $events = $this->service->listForTenant($this->tenant);

        $this->assertCount(5, $events);
    }

    public function test_can_filter_events_by_type(): void
    {
        SecurityEvent::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'event_type' => SecurityEventType::LOGIN_SUCCESS,
        ]);

        SecurityEvent::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'event_type' => SecurityEventType::LOGIN_FAILURE,
        ]);

        $events = $this->service->listForTenant($this->tenant, [
            'event_type' => 'login_success',
        ]);

        $this->assertCount(3, $events);
    }

    public function test_can_filter_events_by_severity(): void
    {
        SecurityEvent::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'severity' => SecuritySeverity::CRITICAL,
        ]);

        SecurityEvent::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'severity' => SecuritySeverity::LOW,
        ]);

        $events = $this->service->listForTenant($this->tenant, [
            'severity' => 'critical',
        ]);

        $this->assertCount(3, $events);
    }

    public function test_can_get_high_severity_events(): void
    {
        SecurityEvent::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'severity' => SecuritySeverity::CRITICAL,
            'is_resolved' => false,
        ]);

        SecurityEvent::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'severity' => SecuritySeverity::HIGH,
            'is_resolved' => false,
        ]);

        SecurityEvent::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'severity' => SecuritySeverity::LOW,
            'is_resolved' => false,
        ]);

        $events = $this->service->getHighSeverityEvents($this->tenant);

        $this->assertCount(5, $events);
    }

    public function test_can_get_security_stats(): void
    {
        SecurityEvent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'severity' => SecuritySeverity::CRITICAL,
            'is_resolved' => false,
        ]);

        SecurityEvent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'severity' => SecuritySeverity::HIGH,
            'is_resolved' => false,
        ]);

        SecurityEvent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'event_type' => SecurityEventType::LOGIN_FAILURE,
            'is_resolved' => true,
        ]);

        $stats = $this->service->getStats($this->tenant);

        $this->assertEquals(3, $stats->total_events);
        $this->assertEquals(1, $stats->critical_events);
        $this->assertEquals(1, $stats->high_events);
        $this->assertEquals(2, $stats->unresolved_events);
    }

    public function test_can_list_events_for_user(): void
    {
        SecurityEvent::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
        ]);

        $otherUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        SecurityEvent::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $otherUser->id,
        ]);

        $events = $this->service->listForUser($this->user);

        $this->assertCount(3, $events);
    }
}
