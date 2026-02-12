<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Audit;

use App\Enums\Audit\AuditAction;
use App\Enums\Audit\AuditableType;
use App\Models\Audit\AuditLog;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogService $service;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AuditLogService();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_log_audit_event(): void
    {
        $log = $this->service->record(
            action: AuditAction::CREATE,
            auditable: $this->user,
            user: $this->user,
            oldValues: null,
            newValues: ['name' => 'New Name'],
            description: 'User profile created',
        );

        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertEquals(AuditAction::CREATE, $log->action);
        $this->assertEquals(AuditableType::USER, $log->auditable_type);
        $this->assertEquals($this->user->id, $log->auditable_id);
        $this->assertEquals($this->user->id, $log->user_id);
        $this->assertEquals('User profile created', $log->description);
    }

    public function test_can_log_update_with_changes(): void
    {
        $oldValues = ['name' => 'Old Name'];
        $newValues = ['name' => 'New Name'];

        $log = $this->service->record(
            action: AuditAction::UPDATE,
            auditable: $this->user,
            user: $this->user,
            oldValues: $oldValues,
            newValues: $newValues,
        );

        $this->assertEquals(AuditAction::UPDATE, $log->action);
        $this->assertEquals($oldValues, $log->old_values);
        $this->assertEquals($newValues, $log->new_values);
    }

    public function test_can_list_logs_for_tenant(): void
    {
        AuditLog::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $logs = $this->service->listForTenant($this->tenant);

        $this->assertCount(5, $logs);
    }

    public function test_list_logs_respects_pagination(): void
    {
        AuditLog::factory()->count(20)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $logs = $this->service->listForTenant($this->tenant, ['per_page' => 10]);

        $this->assertCount(10, $logs->items());
        $this->assertEquals(20, $logs->total());
    }

    public function test_can_filter_logs_by_action(): void
    {
        AuditLog::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'action' => AuditAction::CREATE,
        ]);

        AuditLog::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'action' => AuditAction::UPDATE,
        ]);

        $logs = $this->service->listForTenant($this->tenant, ['action' => 'create']);

        $this->assertCount(3, $logs);
    }

    public function test_can_filter_logs_by_auditable_type(): void
    {
        AuditLog::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => AuditableType::USER,
        ]);

        AuditLog::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => AuditableType::WORKSPACE,
        ]);

        $logs = $this->service->listForTenant($this->tenant, ['auditable_type' => 'user']);

        $this->assertCount(3, $logs);
    }

    public function test_can_list_logs_for_user(): void
    {
        AuditLog::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
        ]);

        $otherUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        AuditLog::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $otherUser->id,
        ]);

        $logs = $this->service->listForUser($this->user);

        $this->assertCount(3, $logs);
    }

    public function test_can_list_logs_for_auditable(): void
    {
        AuditLog::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => AuditableType::USER,
            'auditable_id' => $this->user->id,
        ]);

        AuditLog::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => AuditableType::USER,
            'auditable_id' => User::factory()->create(['tenant_id' => $this->tenant->id])->id,
        ]);

        $logs = $this->service->listForAuditable($this->user);

        $this->assertCount(3, $logs);
    }

    public function test_can_get_logs_by_type(): void
    {
        AuditLog::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => AuditableType::USER,
        ]);

        AuditLog::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => AuditableType::WORKSPACE,
        ]);

        $logs = $this->service->getByType($this->tenant, AuditableType::USER);

        $this->assertCount(3, $logs);
    }
}
