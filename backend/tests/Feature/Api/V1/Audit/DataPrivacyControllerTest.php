<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Audit;

use App\Enums\Audit\DataRequestStatus;
use App\Enums\Audit\DataRequestType;
use App\Models\Audit\DataDeletionRequest;
use App\Models\Audit\DataExportRequest;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class DataPrivacyControllerTest extends TestCase
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

    public function test_can_list_export_requests(): void
    {
        Sanctum::actingAs($this->user);

        DataExportRequest::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'requested_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/privacy/export-requests');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'status',
                        'request_type',
                        'format',
                        'created_at',
                    ],
                ],
            ])
            ->assertJson(['success' => true]);
    }

    public function test_can_request_data_export(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/privacy/export-requests');

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'status',
                    'request_type',
                    'format',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'pending',
                ],
            ]);

        $this->assertDatabaseHas('data_export_requests', [
            'user_id' => $this->user->id,
            'status' => DataRequestStatus::PENDING->value,
        ]);
    }

    public function test_cannot_request_duplicate_export(): void
    {
        Sanctum::actingAs($this->user);

        DataExportRequest::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'requested_by' => $this->user->id,
            'status' => DataRequestStatus::PENDING,
        ]);

        $response = $this->postJson('/api/v1/privacy/export-requests');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_can_list_deletion_requests(): void
    {
        Sanctum::actingAs($this->user);

        DataDeletionRequest::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'requested_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/privacy/deletion-requests');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'status',
                        'reason',
                        'scheduled_for',
                        'created_at',
                    ],
                ],
            ])
            ->assertJson(['success' => true]);
    }

    public function test_can_request_data_deletion(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/privacy/deletion-requests', [
            'reason' => 'I want to delete my account because I no longer need the service.',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'status',
                    'reason',
                    'scheduled_for',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'pending',
                    'requires_approval' => true,
                ],
            ]);

        $this->assertDatabaseHas('data_deletion_requests', [
            'user_id' => $this->user->id,
            'status' => DataRequestStatus::PENDING->value,
        ]);
    }

    public function test_deletion_request_requires_reason(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/privacy/deletion-requests', []);

        $response->assertStatus(422);
    }

    public function test_cannot_request_duplicate_deletion(): void
    {
        Sanctum::actingAs($this->user);

        DataDeletionRequest::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'requested_by' => $this->user->id,
            'status' => DataRequestStatus::PENDING,
        ]);

        $response = $this->postJson('/api/v1/privacy/deletion-requests', [
            'reason' => 'I want to delete my account.',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_can_cancel_deletion_request(): void
    {
        Sanctum::actingAs($this->user);

        $request = DataDeletionRequest::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'requested_by' => $this->user->id,
            'status' => DataRequestStatus::PENDING,
        ]);

        $response = $this->deleteJson("/api/v1/privacy/deletion-requests/{$request->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Deletion request cancelled successfully',
            ]);

        $this->assertDatabaseHas('data_deletion_requests', [
            'id' => $request->id,
            'status' => DataRequestStatus::CANCELLED->value,
        ]);
    }

    public function test_cannot_cancel_non_pending_deletion_request(): void
    {
        Sanctum::actingAs($this->user);

        $request = DataDeletionRequest::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'requested_by' => $this->user->id,
            'status' => DataRequestStatus::COMPLETED,
        ]);

        $response = $this->deleteJson("/api/v1/privacy/deletion-requests/{$request->id}");

        $response->assertStatus(422);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/privacy/export-requests');

        $response->assertUnauthorized();
    }

    public function test_export_requests_are_scoped_to_user(): void
    {
        Sanctum::actingAs($this->user);

        // Create requests for current user
        DataExportRequest::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'requested_by' => $this->user->id,
        ]);

        // Create requests for another user
        $otherUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        DataExportRequest::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $otherUser->id,
            'requested_by' => $otherUser->id,
        ]);

        $response = $this->getJson('/api/v1/privacy/export-requests');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }
}
