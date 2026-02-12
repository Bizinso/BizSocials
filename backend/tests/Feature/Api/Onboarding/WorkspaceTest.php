<?php

declare(strict_types=1);

use App\Enums\Tenant\TenantStatus;
use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Audit\AuditLog;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantOnboarding;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Models\Workspace\WorkspaceMembership;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->onboarding = TenantOnboarding::create([
        'tenant_id' => $this->tenant->id,
        'current_step' => 'organization_completed',
        'steps_completed' => ['account_created', 'email_verified', 'organization_completed'],
        'started_at' => now(),
    ]);
    $this->user = User::factory()->active()->verified()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
});

describe('POST /api/v1/onboarding/workspace', function () {
    it('creates workspace successfully', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/onboarding/workspace', [
            'name' => 'Marketing Team',
            'purpose' => 'marketing',
            'approval_mode' => 'auto',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Workspace created successfully');

        // Verify workspace created
        $workspace = Workspace::where('tenant_id', $this->tenant->id)->first();
        expect($workspace)->not->toBeNull();
        expect($workspace->name)->toBe('Marketing Team');

        // Verify membership
        $membership = WorkspaceMembership::where('workspace_id', $workspace->id)
            ->where('user_id', $this->user->id)
            ->first();
        expect($membership)->not->toBeNull();
        expect($membership->role)->toBe(WorkspaceRole::OWNER);

        // Verify onboarding advanced
        $this->onboarding->refresh();
        expect($this->onboarding->current_step)->toBe('first_workspace_created');
        expect($this->onboarding->steps_completed)->toContain('first_workspace_created');

        // Verify audit log
        $auditLog = AuditLog::where('description', 'workspace.created')->first();
        expect($auditLog)->not->toBeNull();
    });

    it('creates workspace + membership atomically', function () {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/onboarding/workspace', [
            'name' => 'Support Hub',
            'purpose' => 'support',
            'approval_mode' => 'manual',
        ]);

        $workspace = Workspace::where('tenant_id', $this->tenant->id)->first();
        $this->onboarding->refresh();

        expect($workspace)->not->toBeNull();
        expect($workspace->name)->toBe('Support Hub');
        expect($this->onboarding->steps_completed)->toContain('first_workspace_created');
    });

    it('returns 422 for missing required fields', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/onboarding/workspace', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['name', 'purpose', 'approval_mode']]);
    });

    it('returns 422 for invalid purpose', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/onboarding/workspace', [
            'name' => 'Marketing Team',
            'purpose' => 'invalid_purpose',
            'approval_mode' => 'auto',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['purpose']]);
    });

    it('returns 422 for invalid approval_mode', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/onboarding/workspace', [
            'name' => 'Marketing Team',
            'purpose' => 'marketing',
            'approval_mode' => 'invalid_mode',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['approval_mode']]);
    });

    it('is idempotent on duplicate submission', function () {
        Sanctum::actingAs($this->user);

        $first = $this->postJson('/api/v1/onboarding/workspace', [
            'name' => 'Marketing Team',
            'purpose' => 'marketing',
            'approval_mode' => 'auto',
        ]);

        $second = $this->postJson('/api/v1/onboarding/workspace', [
            'name' => 'Different Name',
            'purpose' => 'support',
            'approval_mode' => 'manual',
        ]);

        $first->assertOk();
        $second->assertOk();

        // Should only have one workspace
        expect(Workspace::where('tenant_id', $this->tenant->id)->count())->toBe(1);
        $workspace = Workspace::where('tenant_id', $this->tenant->id)->first();
        expect($workspace->name)->toBe('Marketing Team');
    });

    it('requires authentication', function () {
        $response = $this->postJson('/api/v1/onboarding/workspace', [
            'name' => 'Marketing Team',
            'purpose' => 'marketing',
            'approval_mode' => 'auto',
        ]);

        $response->assertStatus(401);
    });

    it('rolls back on failure', function () {
        Sanctum::actingAs($this->user);

        $originalSteps = $this->onboarding->steps_completed;

        // Delete onboarding to force an error
        $this->onboarding->delete();
        $this->tenant->refresh();

        $response = $this->postJson('/api/v1/onboarding/workspace', [
            'name' => 'Marketing Team',
            'purpose' => 'marketing',
            'approval_mode' => 'auto',
        ]);

        $response->assertStatus(422);

        // No workspace should have been created
        expect(Workspace::where('tenant_id', $this->tenant->id)->count())->toBe(0);
    });
});
