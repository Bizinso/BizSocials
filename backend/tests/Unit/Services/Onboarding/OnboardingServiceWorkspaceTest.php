<?php

declare(strict_types=1);

use App\Enums\Audit\AuditAction;
use App\Enums\Workspace\WorkspaceRole;
use App\Enums\Workspace\WorkspaceStatus;
use App\Models\Audit\AuditLog;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantOnboarding;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Models\Workspace\WorkspaceMembership;
use App\Services\Onboarding\OnboardingService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = app(OnboardingService::class);
    $this->tenant = Tenant::factory()->active()->create();
    $this->onboarding = TenantOnboarding::create([
        'tenant_id' => $this->tenant->id,
        'current_step' => 'organization_completed',
        'steps_completed' => ['account_created', 'email_verified', 'organization_completed'],
        'started_at' => now(),
    ]);
    $this->user = User::factory()->active()->verified()->create([
        'tenant_id' => $this->tenant->id,
    ]);
});

describe('OnboardingService::submitWorkspace', function () {
    it('creates a workspace with correct attributes', function () {
        $workspace = $this->service->submitWorkspace($this->tenant, $this->user, [
            'name' => 'Marketing Team',
            'purpose' => 'marketing',
            'approval_mode' => 'auto',
        ]);

        expect($workspace)->toBeInstanceOf(Workspace::class);
        expect($workspace->name)->toBe('Marketing Team');
        expect($workspace->tenant_id)->toBe($this->tenant->id);
        expect($workspace->status)->toBe(WorkspaceStatus::ACTIVE);
    });

    it('assigns creator as OWNER', function () {
        $workspace = $this->service->submitWorkspace($this->tenant, $this->user, [
            'name' => 'Marketing Team',
            'purpose' => 'marketing',
            'approval_mode' => 'auto',
        ]);

        $membership = WorkspaceMembership::where('workspace_id', $workspace->id)
            ->where('user_id', $this->user->id)
            ->first();

        expect($membership)->not->toBeNull();
        expect($membership->role)->toBe(WorkspaceRole::OWNER);
    });

    it('stores purpose in workspace settings', function () {
        $workspace = $this->service->submitWorkspace($this->tenant, $this->user, [
            'name' => 'Support Hub',
            'purpose' => 'support',
            'approval_mode' => 'auto',
        ]);

        expect($workspace->getSetting('purpose'))->toBe('support');
    });

    it('sets approval_workflow.enabled to true for manual mode', function () {
        $workspace = $this->service->submitWorkspace($this->tenant, $this->user, [
            'name' => 'Brand Team',
            'purpose' => 'brand',
            'approval_mode' => 'manual',
        ]);

        expect($workspace->getSetting('approval_workflow.enabled'))->toBeTrue();
    });

    it('sets approval_workflow.enabled to false for auto mode', function () {
        $workspace = $this->service->submitWorkspace($this->tenant, $this->user, [
            'name' => 'Marketing Team',
            'purpose' => 'marketing',
            'approval_mode' => 'auto',
        ]);

        expect($workspace->getSetting('approval_workflow.enabled'))->toBeFalse();
    });

    it('sets default_workspace_id in tenant settings', function () {
        $workspace = $this->service->submitWorkspace($this->tenant, $this->user, [
            'name' => 'Marketing Team',
            'purpose' => 'marketing',
            'approval_mode' => 'auto',
        ]);

        $this->tenant->refresh();
        expect($this->tenant->getSetting('default_workspace_id'))->toBe($workspace->id);
    });

    it('advances onboarding to first_workspace_created', function () {
        $this->service->submitWorkspace($this->tenant, $this->user, [
            'name' => 'Marketing Team',
            'purpose' => 'marketing',
            'approval_mode' => 'auto',
        ]);

        $this->onboarding->refresh();
        expect($this->onboarding->current_step)->toBe('first_workspace_created');
        expect($this->onboarding->steps_completed)->toContain('first_workspace_created');
    });

    it('writes audit log with description workspace.created', function () {
        $workspace = $this->service->submitWorkspace($this->tenant, $this->user, [
            'name' => 'Marketing Team',
            'purpose' => 'marketing',
            'approval_mode' => 'auto',
        ]);

        $auditLog = AuditLog::where('auditable_id', $workspace->id)
            ->where('description', 'workspace.created')
            ->first();

        expect($auditLog)->not->toBeNull();
        expect($auditLog->action)->toBe(AuditAction::CREATE);
        expect($auditLog->user_id)->toBe($this->user->id);
        expect($auditLog->new_values)->toHaveKey('name', 'Marketing Team');
        expect($auditLog->new_values)->toHaveKey('purpose', 'marketing');
        expect($auditLog->new_values)->toHaveKey('approval_mode', 'auto');
    });

    it('is idempotent — returns existing workspace when already completed', function () {
        // Complete it once
        $firstWorkspace = $this->service->submitWorkspace($this->tenant, $this->user, [
            'name' => 'Marketing Team',
            'purpose' => 'marketing',
            'approval_mode' => 'auto',
        ]);

        // Try again with different data
        $secondWorkspace = $this->service->submitWorkspace($this->tenant, $this->user, [
            'name' => 'Different Name',
            'purpose' => 'support',
            'approval_mode' => 'manual',
        ]);

        expect($secondWorkspace->id)->toBe($firstWorkspace->id);
        expect($secondWorkspace->name)->toBe('Marketing Team');
    });

    it('rejects submission when organization not completed', function () {
        $this->onboarding->update([
            'current_step' => 'email_verified',
            'steps_completed' => ['account_created', 'email_verified'],
        ]);

        $this->service->submitWorkspace($this->tenant, $this->user, [
            'name' => 'Marketing Team',
            'purpose' => 'marketing',
            'approval_mode' => 'auto',
        ]);
    })->throws(ValidationException::class);

    it('rejects submission when onboarding record is missing', function () {
        $this->onboarding->delete();
        $this->tenant->refresh();

        $this->service->submitWorkspace($this->tenant, $this->user, [
            'name' => 'Marketing Team',
            'purpose' => 'marketing',
            'approval_mode' => 'auto',
        ]);
    })->throws(ValidationException::class);
});
