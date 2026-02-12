<?php

declare(strict_types=1);

use App\Enums\Tenant\TenantStatus;
use App\Enums\User\TenantRole;
use App\Models\Audit\AuditLog;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantOnboarding;
use App\Models\Tenant\TenantProfile;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->onboarding = TenantOnboarding::create([
        'tenant_id' => $this->tenant->id,
        'current_step' => 'organization_completed',
        'steps_completed' => ['account_created', 'email_verified'],
        'started_at' => now(),
    ]);
    $this->user = User::factory()->active()->verified()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
});

describe('POST /api/v1/onboarding/organization', function () {
    it('completes organization setup successfully', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/onboarding/organization', [
            'name' => 'Acme Corp',
            'timezone' => 'America/New_York',
            'industry' => 'technology',
            'country' => 'US',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Organization setup completed');

        // Verify tenant updated
        $this->tenant->refresh();
        expect($this->tenant->name)->toBe('Acme Corp');

        // Verify profile created
        $profile = TenantProfile::where('tenant_id', $this->tenant->id)->first();
        expect($profile)->not->toBeNull();
        expect($profile->industry)->toBe('technology');
        expect($profile->country)->toBe('US');

        // Verify onboarding advanced
        $this->onboarding->refresh();
        expect($this->onboarding->current_step)->toBe('organization_completed');
        expect($this->onboarding->steps_completed)->toContain('organization_completed');

        // Verify audit log
        $auditLog = AuditLog::where('description', 'tenant.organization_completed')->first();
        expect($auditLog)->not->toBeNull();
    });

    it('updates tenant + onboarding atomically', function () {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/onboarding/organization', [
            'name' => 'Acme Corp',
            'timezone' => 'UTC',
            'industry' => 'technology',
            'country' => 'US',
        ]);

        // Both tenant and onboarding should be updated together
        $this->tenant->refresh();
        $this->onboarding->refresh();

        expect($this->tenant->name)->toBe('Acme Corp');
        expect($this->onboarding->steps_completed)->toContain('organization_completed');
    });

    it('returns 422 for missing required fields', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/onboarding/organization', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['name', 'timezone', 'industry', 'country']]);
    });

    it('returns 422 for invalid timezone', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/onboarding/organization', [
            'name' => 'Acme Corp',
            'timezone' => 'Invalid/Timezone',
            'industry' => 'technology',
            'country' => 'US',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['timezone']]);
    });

    it('returns 422 for invalid country code', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/onboarding/organization', [
            'name' => 'Acme Corp',
            'timezone' => 'UTC',
            'industry' => 'technology',
            'country' => 'USA', // Should be 2 chars
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['country']]);
    });

    it('is idempotent on duplicate submission', function () {
        Sanctum::actingAs($this->user);

        $first = $this->postJson('/api/v1/onboarding/organization', [
            'name' => 'Acme Corp',
            'timezone' => 'UTC',
            'industry' => 'technology',
            'country' => 'US',
        ]);

        $second = $this->postJson('/api/v1/onboarding/organization', [
            'name' => 'Different Name',
            'timezone' => 'Europe/London',
            'industry' => 'finance',
            'country' => 'GB',
        ]);

        $first->assertOk();
        $second->assertOk();

        // Name should not have changed on second submission
        $this->tenant->refresh();
        expect($this->tenant->name)->toBe('Acme Corp');
    });

    it('requires authentication', function () {
        $response = $this->postJson('/api/v1/onboarding/organization', [
            'name' => 'Acme Corp',
            'timezone' => 'UTC',
            'industry' => 'technology',
            'country' => 'US',
        ]);

        $response->assertStatus(401);
    });

    it('rolls back on failure', function () {
        Sanctum::actingAs($this->user);

        $originalName = $this->tenant->name;

        // Delete onboarding to force an error in the service
        $this->onboarding->delete();
        $this->tenant->refresh();

        $response = $this->postJson('/api/v1/onboarding/organization', [
            'name' => 'Acme Corp',
            'timezone' => 'UTC',
            'industry' => 'technology',
            'country' => 'US',
        ]);

        $response->assertStatus(422);

        // Tenant name should not have changed
        $this->tenant->refresh();
        expect($this->tenant->name)->toBe($originalName);
    });
});
