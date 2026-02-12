<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Audit;

use App\Enums\Audit\DataRequestStatus;
use App\Models\Audit\DataDeletionRequest;
use App\Models\Audit\DataExportRequest;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\Audit\DataPrivacyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class DataPrivacyServiceTest extends TestCase
{
    use RefreshDatabase;

    private DataPrivacyService $service;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DataPrivacyService();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_request_data_export(): void
    {
        $request = $this->service->requestExport($this->user);

        $this->assertInstanceOf(DataExportRequest::class, $request);
        $this->assertEquals($this->user->id, $request->user_id);
        $this->assertEquals(DataRequestStatus::PENDING, $request->status);
    }

    public function test_cannot_create_duplicate_export_request(): void
    {
        $this->service->requestExport($this->user);

        $this->expectException(ValidationException::class);

        $this->service->requestExport($this->user);
    }

    public function test_can_get_export_requests_for_user(): void
    {
        DataExportRequest::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'requested_by' => $this->user->id,
        ]);

        $requests = $this->service->getExportRequests($this->user);

        $this->assertCount(3, $requests);
    }

    public function test_can_request_data_deletion(): void
    {
        $request = $this->service->requestDeletion($this->user, 'I want to delete my account.');

        $this->assertInstanceOf(DataDeletionRequest::class, $request);
        $this->assertEquals($this->user->id, $request->user_id);
        $this->assertEquals(DataRequestStatus::PENDING, $request->status);
        $this->assertEquals('I want to delete my account.', $request->reason);
        $this->assertTrue($request->requires_approval);
        $this->assertNotNull($request->scheduled_for);
    }

    public function test_cannot_create_duplicate_deletion_request(): void
    {
        $this->service->requestDeletion($this->user, 'First request');

        $this->expectException(ValidationException::class);

        $this->service->requestDeletion($this->user, 'Second request');
    }

    public function test_can_cancel_deletion_request(): void
    {
        $request = DataDeletionRequest::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'requested_by' => $this->user->id,
            'status' => DataRequestStatus::PENDING,
        ]);

        $this->service->cancelDeletion($request);

        $this->assertDatabaseHas('data_deletion_requests', [
            'id' => $request->id,
            'status' => DataRequestStatus::CANCELLED->value,
        ]);
    }

    public function test_cannot_cancel_non_pending_deletion_request(): void
    {
        $request = DataDeletionRequest::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'requested_by' => $this->user->id,
            'status' => DataRequestStatus::COMPLETED,
        ]);

        $this->expectException(ValidationException::class);

        $this->service->cancelDeletion($request);
    }

    public function test_can_get_deletion_requests_for_user(): void
    {
        DataDeletionRequest::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'requested_by' => $this->user->id,
        ]);

        $requests = $this->service->getDeletionRequests($this->user);

        $this->assertCount(2, $requests);
    }

    public function test_can_list_all_export_requests(): void
    {
        DataExportRequest::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $requests = $this->service->listAllExportRequests();

        $this->assertCount(5, $requests);
    }

    public function test_can_list_all_deletion_requests(): void
    {
        DataDeletionRequest::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $requests = $this->service->listAllDeletionRequests();

        $this->assertCount(5, $requests);
    }

    public function test_can_approve_deletion_request(): void
    {
        $request = DataDeletionRequest::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'requested_by' => $this->user->id,
            'status' => DataRequestStatus::PENDING,
            'requires_approval' => true,
        ]);

        $admin = SuperAdminUser::factory()->create();

        $this->service->approveDeletion($request, $admin);

        $request->refresh();

        $this->assertNotNull($request->approved_at);
        $this->assertEquals($admin->id, $request->approved_by);
        $this->assertNotNull($request->scheduled_for);
    }

    public function test_cannot_approve_non_pending_deletion_request(): void
    {
        $request = DataDeletionRequest::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'requested_by' => $this->user->id,
            'status' => DataRequestStatus::COMPLETED,
        ]);

        $admin = SuperAdminUser::factory()->create();

        $this->expectException(ValidationException::class);

        $this->service->approveDeletion($request, $admin);
    }

    public function test_can_reject_deletion_request(): void
    {
        $request = DataDeletionRequest::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'requested_by' => $this->user->id,
            'status' => DataRequestStatus::PENDING,
        ]);

        $admin = SuperAdminUser::factory()->create();

        $this->service->rejectDeletion($request, $admin, 'Request denied due to policy.');

        $request->refresh();

        $this->assertEquals(DataRequestStatus::CANCELLED, $request->status);
        $this->assertEquals('Request denied due to policy.', $request->failure_reason);
    }
}
