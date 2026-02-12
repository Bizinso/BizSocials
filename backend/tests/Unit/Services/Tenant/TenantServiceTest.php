<?php

declare(strict_types=1);

use App\Data\Tenant\UpdateTenantData;
use App\Enums\User\TenantRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\Tenant\TenantService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = new TenantService();
    $this->tenant = Tenant::factory()->active()->create();
    $this->owner = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
        'name' => 'Owner User',
        'email' => 'owner@example.com',
    ]);
});

describe('getCurrent', function () {
    it('returns tenant for user', function () {
        $result = $this->service->getCurrent($this->owner);

        expect($result->id)->toBe($this->tenant->id);
    });

    // Note: Users always have a tenant_id (non-nullable in database)
    // so we cannot test the "no tenant" scenario
});

describe('update', function () {
    it('updates tenant name', function () {
        $data = new UpdateTenantData(name: 'Updated Name');

        $result = $this->service->update($this->tenant, $data);

        expect($result->name)->toBe('Updated Name');
    });

    it('updates timezone in settings', function () {
        $data = new UpdateTenantData(timezone: 'America/New_York');

        $result = $this->service->update($this->tenant, $data);

        expect($result->getSetting('timezone'))->toBe('America/New_York');
    });
});

describe('updateSettings', function () {
    it('merges settings', function () {
        $this->tenant->settings = ['existing' => 'value'];
        $this->tenant->save();

        $result = $this->service->updateSettings($this->tenant, ['new_key' => 'new_value']);

        expect($result->getSetting('existing'))->toBe('value');
        expect($result->getSetting('new_key'))->toBe('new_value');
    });
});

describe('getUsageStats', function () {
    it('returns usage statistics', function () {
        User::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $stats = $this->service->getUsageStats($this->tenant);

        expect($stats)->toHaveKeys(['users', 'workspaces', 'social_accounts', 'storage']);
        expect($stats['users']['count'])->toBe(4); // 3 + owner
    });
});

describe('getMembers', function () {
    it('returns paginated members', function () {
        User::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'role_in_tenant' => TenantRole::MEMBER,
        ]);

        $result = $this->service->getMembers($this->tenant);

        expect($result->total())->toBe(6); // 5 + owner
    });

    it('filters by role', function () {
        User::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'role_in_tenant' => TenantRole::ADMIN,
        ]);
        User::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'role_in_tenant' => TenantRole::MEMBER,
        ]);

        $result = $this->service->getMembers($this->tenant, ['role' => 'admin']);

        expect($result->total())->toBe(3);
    });

    it('filters by search', function () {
        User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $result = $this->service->getMembers($this->tenant, ['search' => 'john']);

        expect($result->total())->toBe(1);
    });
});

describe('updateMemberRole', function () {
    it('updates member role', function () {
        $member = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_in_tenant' => TenantRole::MEMBER,
        ]);

        $result = $this->service->updateMemberRole($this->tenant, $member, TenantRole::ADMIN);

        expect($result->role_in_tenant)->toBe(TenantRole::ADMIN);
    });

    it('throws exception for user from different tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        expect(fn () => $this->service->updateMemberRole($this->tenant, $otherUser, TenantRole::ADMIN))
            ->toThrow(ValidationException::class);
    });

    it('throws exception when demoting only owner', function () {
        expect(fn () => $this->service->updateMemberRole($this->tenant, $this->owner, TenantRole::ADMIN))
            ->toThrow(ValidationException::class);
    });

    it('allows demoting owner when there are multiple owners', function () {
        $secondOwner = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_in_tenant' => TenantRole::OWNER,
        ]);

        $result = $this->service->updateMemberRole($this->tenant, $this->owner, TenantRole::ADMIN);

        expect($result->role_in_tenant)->toBe(TenantRole::ADMIN);
    });
});

describe('removeMember', function () {
    it('removes member from tenant', function () {
        $member = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_in_tenant' => TenantRole::MEMBER,
        ]);

        $this->service->removeMember($this->tenant, $member);

        $member->refresh();
        expect($member->trashed())->toBeTrue();
    });

    it('throws exception for user from different tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        expect(fn () => $this->service->removeMember($this->tenant, $otherUser))
            ->toThrow(ValidationException::class);
    });

    it('throws exception when removing only owner', function () {
        expect(fn () => $this->service->removeMember($this->tenant, $this->owner))
            ->toThrow(ValidationException::class);
    });
});
