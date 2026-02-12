<?php

declare(strict_types=1);

use App\Enums\Audit\AuditAction;
use App\Enums\Tenant\TenantStatus;
use App\Models\Audit\AuditLog;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantOnboarding;
use App\Models\Tenant\TenantProfile;
use App\Models\User;
use App\Services\Onboarding\OnboardingService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = app(OnboardingService::class);
    $this->tenant = Tenant::factory()->active()->create();
    $this->onboarding = TenantOnboarding::create([
        'tenant_id' => $this->tenant->id,
        'current_step' => 'organization_completed',
        'steps_completed' => ['account_created', 'email_verified'],
        'started_at' => now(),
    ]);
    $this->user = User::factory()->active()->verified()->create([
        'tenant_id' => $this->tenant->id,
    ]);
});

describe('OnboardingService::submitOrganization', function () {
    it('updates tenant name', function () {
        $this->service->submitOrganization($this->tenant, $this->user, [
            'name' => 'Acme Corp',
            'timezone' => 'America/New_York',
            'industry' => 'technology',
            'country' => 'US',
        ]);

        $this->tenant->refresh();
        expect($this->tenant->name)->toBe('Acme Corp');
    });

    it('creates tenant profile with industry and country', function () {
        $profile = $this->service->submitOrganization($this->tenant, $this->user, [
            'name' => 'Acme Corp',
            'timezone' => 'America/New_York',
            'industry' => 'technology',
            'country' => 'US',
        ]);

        expect($profile)->toBeInstanceOf(TenantProfile::class);
        expect($profile->industry)->toBe('technology');
        expect($profile->country)->toBe('US');
        expect($profile->tenant_id)->toBe($this->tenant->id);
    });

    it('stores timezone in tenant settings', function () {
        $this->service->submitOrganization($this->tenant, $this->user, [
            'name' => 'Acme Corp',
            'timezone' => 'Asia/Kolkata',
            'industry' => 'technology',
            'country' => 'IN',
        ]);

        $this->tenant->refresh();
        expect($this->tenant->getSetting('timezone'))->toBe('Asia/Kolkata');
    });

    it('advances onboarding to organization_completed', function () {
        $this->service->submitOrganization($this->tenant, $this->user, [
            'name' => 'Acme Corp',
            'timezone' => 'UTC',
            'industry' => 'technology',
            'country' => 'US',
        ]);

        $this->onboarding->refresh();
        expect($this->onboarding->current_step)->toBe('organization_completed');
        expect($this->onboarding->steps_completed)->toContain('organization_completed');
    });

    it('writes audit log with description tenant.organization_completed', function () {
        $this->service->submitOrganization($this->tenant, $this->user, [
            'name' => 'Acme Corp',
            'timezone' => 'UTC',
            'industry' => 'technology',
            'country' => 'US',
        ]);

        $auditLog = AuditLog::where('auditable_id', $this->tenant->id)
            ->where('description', 'tenant.organization_completed')
            ->first();

        expect($auditLog)->not->toBeNull();
        expect($auditLog->action)->toBe(AuditAction::UPDATE);
        expect($auditLog->user_id)->toBe($this->user->id);
        expect($auditLog->new_values)->toHaveKey('name', 'Acme Corp');
        expect($auditLog->new_values)->toHaveKey('timezone', 'UTC');
        expect($auditLog->new_values)->toHaveKey('industry', 'technology');
        expect($auditLog->new_values)->toHaveKey('country', 'US');
    });

    it('is idempotent — returns existing profile when already completed', function () {
        // Complete it once
        $firstProfile = $this->service->submitOrganization($this->tenant, $this->user, [
            'name' => 'Acme Corp',
            'timezone' => 'UTC',
            'industry' => 'technology',
            'country' => 'US',
        ]);

        // Try to complete again with different data
        $secondProfile = $this->service->submitOrganization($this->tenant, $this->user, [
            'name' => 'Different Name',
            'timezone' => 'Europe/London',
            'industry' => 'finance',
            'country' => 'GB',
        ]);

        // Should return the same profile, not update
        expect($secondProfile->id)->toBe($firstProfile->id);
        $this->tenant->refresh();
        expect($this->tenant->name)->toBe('Acme Corp'); // Not changed
    });

    it('rejects submission when email not verified', function () {
        // Reset onboarding to before email verification
        $this->onboarding->update([
            'current_step' => 'account_created',
            'steps_completed' => ['account_created'],
        ]);

        $this->service->submitOrganization($this->tenant, $this->user, [
            'name' => 'Acme Corp',
            'timezone' => 'UTC',
            'industry' => 'technology',
            'country' => 'US',
        ]);
    })->throws(ValidationException::class);

    it('rejects submission when onboarding record is missing', function () {
        $this->onboarding->delete();
        $this->tenant->refresh();

        $this->service->submitOrganization($this->tenant, $this->user, [
            'name' => 'Acme Corp',
            'timezone' => 'UTC',
            'industry' => 'technology',
            'country' => 'US',
        ]);
    })->throws(ValidationException::class);

    it('updates existing profile on re-submission before completion', function () {
        // Create a pre-existing profile
        TenantProfile::create([
            'tenant_id' => $this->tenant->id,
            'industry' => 'old_industry',
            'country' => 'XX',
        ]);

        $profile = $this->service->submitOrganization($this->tenant, $this->user, [
            'name' => 'Acme Corp',
            'timezone' => 'UTC',
            'industry' => 'technology',
            'country' => 'US',
        ]);

        expect($profile->industry)->toBe('technology');
        expect($profile->country)->toBe('US');
        expect(TenantProfile::where('tenant_id', $this->tenant->id)->count())->toBe(1);
    });
});
