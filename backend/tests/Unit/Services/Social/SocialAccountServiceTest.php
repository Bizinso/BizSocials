<?php

declare(strict_types=1);

use App\Data\Social\ConnectAccountData;
use App\Enums\Social\SocialAccountStatus;
use App\Enums\Social\SocialPlatform;
use App\Enums\User\TenantRole;
use App\Enums\Workspace\WorkspaceRole;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Social\OAuthService;
use App\Services\Social\SocialAccountService;
use App\Services\Social\SocialPlatformAdapterFactory;
use Tests\Stubs\Services\FakeSocialPlatformAdapterFactory;

beforeEach(function () {
    $this->tenant = Tenant::factory()->active()->create();
    $this->workspace = Workspace::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role_in_tenant' => TenantRole::OWNER,
    ]);
    $this->workspace->addMember($this->user, WorkspaceRole::OWNER);

    // Use fake adapter factory to avoid real HTTP calls
    app()->instance(SocialPlatformAdapterFactory::class, new FakeSocialPlatformAdapterFactory());

    $this->oauthService = app(OAuthService::class);
    $this->service = new SocialAccountService($this->oauthService);
});

describe('listForWorkspace', function () {
    it('returns paginated social accounts for workspace', function () {
        SocialAccount::factory()->count(5)->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);

        $result = $this->service->listForWorkspace($this->workspace);

        expect($result->total())->toBe(5);
        expect($result->items())->toHaveCount(5);
    });

    it('filters by platform', function () {
        SocialAccount::factory()->linkedin()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);
        SocialAccount::factory()->facebook()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);

        $result = $this->service->listForWorkspace($this->workspace, ['platform' => 'linkedin']);

        expect($result->total())->toBe(1);
        expect($result->items()[0]->platform)->toBe(SocialPlatform::LINKEDIN);
    });

    it('filters by status', function () {
        SocialAccount::factory()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);
        SocialAccount::factory()->tokenExpired()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);

        $result = $this->service->listForWorkspace($this->workspace, ['status' => 'connected']);

        expect($result->total())->toBe(1);
        expect($result->items()[0]->status)->toBe(SocialAccountStatus::CONNECTED);
    });

    it('filters connected accounts only', function () {
        SocialAccount::factory()->connected()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);
        SocialAccount::factory()->disconnected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);

        $result = $this->service->listForWorkspace($this->workspace, ['connected' => true]);

        expect($result->total())->toBe(2);
    });

    it('does not return accounts from other workspaces', function () {
        $otherWorkspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        SocialAccount::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);
        SocialAccount::factory()->count(3)->create([
            'workspace_id' => $otherWorkspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);

        $result = $this->service->listForWorkspace($this->workspace);

        expect($result->total())->toBe(2);
    });
});

describe('getById', function () {
    it('returns social account by id', function () {
        $account = SocialAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);

        $result = $this->service->getById($account->id);

        expect($result->id)->toBe($account->id);
    });

    it('throws exception when account not found', function () {
        $this->service->getById('00000000-0000-0000-0000-000000000000');
    })->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

describe('getByWorkspaceAndId', function () {
    it('returns social account in workspace', function () {
        $account = SocialAccount::factory()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);

        $result = $this->service->getByWorkspaceAndId($this->workspace, $account->id);

        expect($result->id)->toBe($account->id);
    });

    it('throws exception when account not in workspace', function () {
        $otherWorkspace = Workspace::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $account = SocialAccount::factory()->create([
            'workspace_id' => $otherWorkspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);

        $this->service->getByWorkspaceAndId($this->workspace, $account->id);
    })->throws(\Illuminate\Validation\ValidationException::class);
});

describe('connect', function () {
    it('creates new social account', function () {
        $data = new ConnectAccountData(
            platform: SocialPlatform::LINKEDIN,
            platform_account_id: '123456789',
            account_name: 'Test Company',
            account_username: 'testcompany',
            profile_image_url: 'https://example.com/avatar.jpg',
            access_token: 'test_access_token',
            refresh_token: 'test_refresh_token',
            token_expires_at: now()->addDays(60)->toIso8601String(),
            metadata: ['organization_id' => 'urn:li:organization:12345'],
        );

        $result = $this->service->connect($this->workspace, $this->user, $data);

        expect($result->workspace_id)->toBe($this->workspace->id);
        expect($result->platform)->toBe(SocialPlatform::LINKEDIN);
        expect($result->platform_account_id)->toBe('123456789');
        expect($result->account_name)->toBe('Test Company');
        expect($result->status)->toBe(SocialAccountStatus::CONNECTED);
        expect($result->connected_by_user_id)->toBe($this->user->id);
        expect($result->accessToken)->toBe('test_access_token');
        expect($result->refreshToken)->toBe('test_refresh_token');
    });

    it('reconnects existing disconnected account', function () {
        $existingAccount = SocialAccount::factory()->disconnected()->create([
            'workspace_id' => $this->workspace->id,
            'platform' => SocialPlatform::LINKEDIN,
            'platform_account_id' => '123456789',
            'account_name' => 'Old Name',
            'connected_by_user_id' => $this->user->id,
        ]);

        $data = new ConnectAccountData(
            platform: SocialPlatform::LINKEDIN,
            platform_account_id: '123456789',
            account_name: 'New Name',
            account_username: null,
            profile_image_url: null,
            access_token: 'new_access_token',
            refresh_token: 'new_refresh_token',
        );

        $result = $this->service->connect($this->workspace, $this->user, $data);

        expect($result->id)->toBe($existingAccount->id);
        expect($result->account_name)->toBe('New Name');
        expect($result->status)->toBe(SocialAccountStatus::CONNECTED);
        expect($result->accessToken)->toBe('new_access_token');
    });

    it('reconnects account with expired token', function () {
        $existingAccount = SocialAccount::factory()->tokenExpired()->create([
            'workspace_id' => $this->workspace->id,
            'platform' => SocialPlatform::FACEBOOK,
            'platform_account_id' => '987654321',
            'connected_by_user_id' => $this->user->id,
        ]);

        $data = new ConnectAccountData(
            platform: SocialPlatform::FACEBOOK,
            platform_account_id: '987654321',
            account_name: 'Facebook Page',
            account_username: null,
            profile_image_url: null,
            access_token: 'fresh_access_token',
        );

        $result = $this->service->connect($this->workspace, $this->user, $data);

        expect($result->id)->toBe($existingAccount->id);
        expect($result->status)->toBe(SocialAccountStatus::CONNECTED);
    });
});

describe('disconnect', function () {
    it('disconnects social account', function () {
        $account = SocialAccount::factory()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);

        $this->service->disconnect($account);

        $account->refresh();
        expect($account->status)->toBe(SocialAccountStatus::DISCONNECTED);
        expect($account->disconnected_at)->not->toBeNull();
    });
});

describe('refresh', function () {
    it('refreshes tokens for account with refresh token', function () {
        $account = SocialAccount::factory()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
            'last_refreshed_at' => now()->subWeek(),
            'refresh_token_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString('test_refresh_token'),
        ]);

        $result = $this->service->refresh($account);

        expect($result->status)->toBe(SocialAccountStatus::CONNECTED);
        expect($result->last_refreshed_at->isToday())->toBeTrue();
    });

    it('throws exception when no refresh token', function () {
        $account = SocialAccount::factory()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
            'refresh_token_encrypted' => null,
        ]);

        $this->service->refresh($account);
    })->throws(\Illuminate\Validation\ValidationException::class);
});

describe('updateStatus', function () {
    it('updates account status', function () {
        $account = SocialAccount::factory()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);

        $result = $this->service->updateStatus($account, SocialAccountStatus::TOKEN_EXPIRED);

        expect($result->status)->toBe(SocialAccountStatus::TOKEN_EXPIRED);
    });

    it('sets disconnected_at when status is disconnected', function () {
        $account = SocialAccount::factory()->connected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);

        $result = $this->service->updateStatus($account, SocialAccountStatus::DISCONNECTED);

        expect($result->status)->toBe(SocialAccountStatus::DISCONNECTED);
        expect($result->disconnected_at)->not->toBeNull();
    });
});

describe('getHealthStatus', function () {
    it('returns health status summary', function () {
        SocialAccount::factory()->connected()->linkedin()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);
        SocialAccount::factory()->connected()->facebook()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);
        SocialAccount::factory()->tokenExpired()->twitter()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);
        SocialAccount::factory()->revoked()->instagram()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);
        SocialAccount::factory()->disconnected()->linkedin()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);

        $result = $this->service->getHealthStatus($this->workspace);

        expect($result->total_accounts)->toBe(5);
        expect($result->connected_count)->toBe(2);
        expect($result->expired_count)->toBe(1);
        expect($result->revoked_count)->toBe(1);
        expect($result->disconnected_count)->toBe(1);
        expect($result->by_platform['linkedin']['total'])->toBe(2);
        expect($result->by_platform['linkedin']['connected'])->toBe(1);
    });

    it('returns empty status for workspace with no accounts', function () {
        $result = $this->service->getHealthStatus($this->workspace);

        expect($result->total_accounts)->toBe(0);
        expect($result->connected_count)->toBe(0);
    });
});

describe('getAccountsNeedingRefresh', function () {
    it('returns accounts expiring within threshold', function () {
        // Expiring in 3 days (needs refresh)
        SocialAccount::factory()->expiringIn(3)->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);
        // Expiring in 30 days (doesn't need refresh)
        SocialAccount::factory()->expiringIn(30)->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
        ]);

        $result = $this->service->getAccountsNeedingRefresh(7);

        expect($result)->toHaveCount(1);
    });

    it('does not return disconnected accounts', function () {
        SocialAccount::factory()->disconnected()->create([
            'workspace_id' => $this->workspace->id,
            'connected_by_user_id' => $this->user->id,
            'token_expires_at' => now()->addDays(3),
        ]);

        $result = $this->service->getAccountsNeedingRefresh(7);

        expect($result)->toHaveCount(0);
    });
});

describe('validateUserCanManageSocialAccounts', function () {
    it('allows workspace owner', function () {
        $this->service->validateUserCanManageSocialAccounts($this->user, $this->workspace);

        // Should not throw
        expect(true)->toBeTrue();
    });

    it('allows workspace admin', function () {
        $admin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->workspace->addMember($admin, WorkspaceRole::ADMIN);

        $this->service->validateUserCanManageSocialAccounts($admin, $this->workspace);

        expect(true)->toBeTrue();
    });

    it('denies workspace editor', function () {
        $editor = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->workspace->addMember($editor, WorkspaceRole::EDITOR);

        $this->service->validateUserCanManageSocialAccounts($editor, $this->workspace);
    })->throws(\Illuminate\Validation\ValidationException::class);

    it('denies workspace viewer', function () {
        $viewer = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->workspace->addMember($viewer, WorkspaceRole::VIEWER);

        $this->service->validateUserCanManageSocialAccounts($viewer, $this->workspace);
    })->throws(\Illuminate\Validation\ValidationException::class);

    it('allows tenant admin even if not workspace member', function () {
        $tenantAdmin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_in_tenant' => TenantRole::ADMIN,
        ]);

        $this->service->validateUserCanManageSocialAccounts($tenantAdmin, $this->workspace);

        expect(true)->toBeTrue();
    });

    it('denies user from different tenant', function () {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $this->service->validateUserCanManageSocialAccounts($otherUser, $this->workspace);
    })->throws(\Illuminate\Validation\ValidationException::class);
});
