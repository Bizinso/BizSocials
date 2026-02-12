<?php

declare(strict_types=1);

namespace App\Services\Onboarding;

use App\Data\Workspace\CreateWorkspaceData;
use App\Enums\Audit\AuditAction;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantOnboarding;
use App\Models\Tenant\TenantProfile;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Audit\AuditLogService;
use App\Services\BaseService;
use App\Services\Workspace\WorkspaceService;
use Illuminate\Validation\ValidationException;

final class OnboardingService extends BaseService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly WorkspaceService $workspaceService,
    ) {}

    /**
     * Submit organization details during onboarding.
     *
     * Updates the tenant profile and advances onboarding to organization_completed.
     * Idempotent: returns success if already completed.
     *
     * @param  array{name: string, timezone: string, industry: string, country: string}  $data
     */
    public function submitOrganization(Tenant $tenant, User $actor, array $data): TenantProfile
    {
        $onboarding = $tenant->onboarding;

        if (! $onboarding) {
            throw ValidationException::withMessages([
                'onboarding' => ['Onboarding has not been initialized for this tenant.'],
            ]);
        }

        // Idempotent: if already completed, return existing profile
        if ($onboarding->isStepCompleted('organization_completed')) {
            return $tenant->profile;
        }

        // Guard: email must be verified before org setup
        if (! $onboarding->isStepCompleted('email_verified')) {
            throw ValidationException::withMessages([
                'onboarding' => ['Email verification must be completed before organization setup.'],
            ]);
        }

        return $this->transaction(function () use ($tenant, $actor, $data, $onboarding) {
            // Update tenant name
            $tenant->update(['name' => $data['name']]);

            // Create or update tenant profile
            $profile = TenantProfile::updateOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'industry' => $data['industry'],
                    'country' => $data['country'],
                ],
            );

            // Store timezone in tenant settings
            $tenant->setSetting('timezone', $data['timezone']);

            // Advance onboarding
            $completed = $onboarding->steps_completed ?? [];
            if (! in_array('organization_completed', $completed, true)) {
                $completed[] = 'organization_completed';
            }
            $onboarding->update([
                'steps_completed' => $completed,
                'current_step' => 'organization_completed',
            ]);

            // Audit log
            $this->auditLogService->record(
                action: AuditAction::UPDATE,
                auditable: $tenant,
                user: $actor,
                newValues: [
                    'name' => $data['name'],
                    'timezone' => $data['timezone'],
                    'industry' => $data['industry'],
                    'country' => $data['country'],
                ],
                description: 'tenant.organization_completed',
            );

            $this->log('Organization setup completed', [
                'tenant_id' => $tenant->id,
                'user_id' => $actor->id,
            ]);

            return $profile;
        });
    }

    /**
     * Submit workspace creation during onboarding.
     *
     * Creates the first workspace and advances onboarding to first_workspace_created.
     * Idempotent: returns existing workspace if already completed.
     *
     * @param  array{name: string, purpose: string, approval_mode: string}  $data
     */
    public function submitWorkspace(Tenant $tenant, User $actor, array $data): Workspace
    {
        $onboarding = $tenant->onboarding;

        if (! $onboarding) {
            throw ValidationException::withMessages([
                'onboarding' => ['Onboarding has not been initialized for this tenant.'],
            ]);
        }

        // Idempotent: if already completed, return the first workspace
        if ($onboarding->isStepCompleted('first_workspace_created')) {
            $workspace = Workspace::where('tenant_id', $tenant->id)->first();

            if ($workspace) {
                return $workspace;
            }
        }

        // Guard: organization must be completed before workspace creation
        if (! $onboarding->isStepCompleted('organization_completed')) {
            throw ValidationException::withMessages([
                'onboarding' => ['Organization setup must be completed before creating a workspace.'],
            ]);
        }

        return $this->transaction(function () use ($tenant, $actor, $data, $onboarding) {
            // Create workspace via WorkspaceService
            $workspace = $this->workspaceService->create(
                $tenant,
                $actor,
                new CreateWorkspaceData(
                    name: $data['name'],
                    description: null,
                    icon: null,
                    color: null,
                ),
            );

            // Store purpose and approval mode in workspace settings
            $workspace->setSetting('purpose', $data['purpose']);
            $workspace->setSetting('approval_workflow.enabled', $data['approval_mode'] === 'manual');

            // Set as default workspace in tenant settings
            $tenant->setSetting('default_workspace_id', $workspace->id);

            // Advance onboarding
            $completed = $onboarding->steps_completed ?? [];
            if (! in_array('first_workspace_created', $completed, true)) {
                $completed[] = 'first_workspace_created';
            }
            $onboarding->update([
                'steps_completed' => $completed,
                'current_step' => 'first_workspace_created',
            ]);

            // Audit log
            $this->auditLogService->record(
                action: AuditAction::CREATE,
                auditable: $workspace,
                user: $actor,
                newValues: [
                    'name' => $data['name'],
                    'purpose' => $data['purpose'],
                    'approval_mode' => $data['approval_mode'],
                ],
                description: 'workspace.created',
            );

            $this->log('Onboarding workspace created', [
                'tenant_id' => $tenant->id,
                'workspace_id' => $workspace->id,
                'user_id' => $actor->id,
            ]);

            return $workspace;
        });
    }
}
