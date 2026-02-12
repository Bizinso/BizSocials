<?php

declare(strict_types=1);

/**
 * SocialAccount Model Unit Tests
 *
 * Tests for the SocialAccount model which represents connected
 * social media accounts within a workspace.
 *
 * @see \App\Models\Social\SocialAccount
 */

use App\Enums\Social\SocialAccountStatus;
use App\Enums\Social\SocialPlatform;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;

test('has correct table name', function (): void {
    $socialAccount = new SocialAccount();

    expect($socialAccount->getTable())->toBe('social_accounts');
});

test('uses uuid primary key', function (): void {
    $socialAccount = SocialAccount::factory()->create();

    expect($socialAccount->id)->not->toBeNull()
        ->and(strlen($socialAccount->id))->toBe(36);
});

test('has correct fillable attributes', function (): void {
    $socialAccount = new SocialAccount();
    $fillable = $socialAccount->getFillable();

    expect($fillable)->toContain('workspace_id')
        ->and($fillable)->toContain('platform')
        ->and($fillable)->toContain('platform_account_id')
        ->and($fillable)->toContain('account_name')
        ->and($fillable)->toContain('account_username')
        ->and($fillable)->toContain('profile_image_url')
        ->and($fillable)->toContain('status')
        ->and($fillable)->toContain('access_token_encrypted')
        ->and($fillable)->toContain('refresh_token_encrypted')
        ->and($fillable)->toContain('token_expires_at')
        ->and($fillable)->toContain('connected_by_user_id')
        ->and($fillable)->toContain('connected_at')
        ->and($fillable)->toContain('last_refreshed_at')
        ->and($fillable)->toContain('disconnected_at')
        ->and($fillable)->toContain('metadata');
});

test('has correct hidden attributes', function (): void {
    $socialAccount = new SocialAccount();
    $hidden = $socialAccount->getHidden();

    expect($hidden)->toContain('access_token_encrypted')
        ->and($hidden)->toContain('refresh_token_encrypted');
});

test('platform casts to enum', function (): void {
    $socialAccount = SocialAccount::factory()->linkedin()->create();

    expect($socialAccount->platform)->toBeInstanceOf(SocialPlatform::class)
        ->and($socialAccount->platform)->toBe(SocialPlatform::LINKEDIN);
});

test('status casts to enum', function (): void {
    $socialAccount = SocialAccount::factory()->connected()->create();

    expect($socialAccount->status)->toBeInstanceOf(SocialAccountStatus::class)
        ->and($socialAccount->status)->toBe(SocialAccountStatus::CONNECTED);
});

test('metadata casts to array', function (): void {
    $metadata = [
        'organization_id' => 'urn:li:organization:12345',
        'vanity_name' => 'test-company',
    ];

    $socialAccount = SocialAccount::factory()->withMetadata($metadata)->create();

    expect($socialAccount->metadata)->toBeArray()
        ->and($socialAccount->metadata['organization_id'])->toBe('urn:li:organization:12345');
});

test('token_expires_at casts to datetime', function (): void {
    $socialAccount = SocialAccount::factory()->create([
        'token_expires_at' => '2026-03-01 12:00:00',
    ]);

    expect($socialAccount->token_expires_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('connected_at casts to datetime', function (): void {
    $socialAccount = SocialAccount::factory()->create();

    expect($socialAccount->connected_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('last_refreshed_at casts to datetime', function (): void {
    $socialAccount = SocialAccount::factory()->create([
        'last_refreshed_at' => now(),
    ]);

    expect($socialAccount->last_refreshed_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('disconnected_at casts to datetime', function (): void {
    $socialAccount = SocialAccount::factory()->disconnected()->create();

    expect($socialAccount->disconnected_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('workspace relationship returns belongs to', function (): void {
    $socialAccount = new SocialAccount();

    expect($socialAccount->workspace())->toBeInstanceOf(BelongsTo::class);
});

test('workspace relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $workspace = Workspace::factory()->forTenant($tenant)->create();
    $socialAccount = SocialAccount::factory()->forWorkspace($workspace)->create();

    expect($socialAccount->workspace)->toBeInstanceOf(Workspace::class)
        ->and($socialAccount->workspace->id)->toBe($workspace->id);
});

test('connectedBy relationship returns belongs to', function (): void {
    $socialAccount = new SocialAccount();

    expect($socialAccount->connectedBy())->toBeInstanceOf(BelongsTo::class);
});

test('connectedBy relationship works correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();
    $socialAccount = SocialAccount::factory()->connectedBy($user)->create();

    expect($socialAccount->connectedBy)->toBeInstanceOf(User::class)
        ->and($socialAccount->connectedBy->id)->toBe($user->id);
});

test('accessToken accessor decrypts correctly', function (): void {
    $originalToken = 'test_access_token_12345';
    $socialAccount = SocialAccount::factory()->create([
        'access_token_encrypted' => Crypt::encryptString($originalToken),
    ]);

    expect($socialAccount->access_token)->toBe($originalToken);
});

test('accessToken mutator encrypts correctly', function (): void {
    $socialAccount = SocialAccount::factory()->create();
    $newToken = 'new_access_token_67890';

    $socialAccount->access_token = $newToken;
    $socialAccount->save();

    // Verify encryption by decrypting the raw value
    $decrypted = Crypt::decryptString($socialAccount->access_token_encrypted);
    expect($decrypted)->toBe($newToken);
});

test('refreshToken accessor decrypts correctly', function (): void {
    $originalToken = 'test_refresh_token_12345';
    $socialAccount = SocialAccount::factory()->create([
        'refresh_token_encrypted' => Crypt::encryptString($originalToken),
    ]);

    expect($socialAccount->refresh_token)->toBe($originalToken);
});

test('refreshToken accessor returns null when not set', function (): void {
    $socialAccount = SocialAccount::factory()->create([
        'refresh_token_encrypted' => null,
    ]);

    expect($socialAccount->refresh_token)->toBeNull();
});

test('refreshToken mutator encrypts correctly', function (): void {
    $socialAccount = SocialAccount::factory()->create();
    $newToken = 'new_refresh_token_67890';

    $socialAccount->refresh_token = $newToken;
    $socialAccount->save();

    // Verify encryption by decrypting the raw value
    $decrypted = Crypt::decryptString($socialAccount->refresh_token_encrypted);
    expect($decrypted)->toBe($newToken);
});

test('refreshToken mutator handles null', function (): void {
    $socialAccount = SocialAccount::factory()->create();

    $socialAccount->refresh_token = null;
    $socialAccount->save();

    expect($socialAccount->refresh_token_encrypted)->toBeNull();
});

test('scope forWorkspace filters correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $workspace1 = Workspace::factory()->forTenant($tenant)->create();
    $workspace2 = Workspace::factory()->forTenant($tenant)->create();

    SocialAccount::factory()->count(3)->forWorkspace($workspace1)->create();
    SocialAccount::factory()->count(2)->forWorkspace($workspace2)->create();

    $workspace1Accounts = SocialAccount::forWorkspace($workspace1->id)->get();

    expect($workspace1Accounts)->toHaveCount(3)
        ->and($workspace1Accounts->every(fn ($a) => $a->workspace_id === $workspace1->id))->toBeTrue();
});

test('scope forPlatform filters by platform', function (): void {
    $workspace = Workspace::factory()->create();
    SocialAccount::factory()->forWorkspace($workspace)->linkedin()->create();
    SocialAccount::factory()->forWorkspace($workspace)->facebook()->create();
    SocialAccount::factory()->forWorkspace($workspace)->instagram()->create();

    $linkedinAccounts = SocialAccount::forWorkspace($workspace->id)->forPlatform(SocialPlatform::LINKEDIN)->get();

    expect($linkedinAccounts)->toHaveCount(1)
        ->and($linkedinAccounts->first()->platform)->toBe(SocialPlatform::LINKEDIN);
});

test('scope connected filters connected status', function (): void {
    $workspace = Workspace::factory()->create();
    SocialAccount::factory()->count(3)->forWorkspace($workspace)->connected()->create();
    SocialAccount::factory()->forWorkspace($workspace)->tokenExpired()->create();
    SocialAccount::factory()->forWorkspace($workspace)->disconnected()->create();

    $connectedAccounts = SocialAccount::forWorkspace($workspace->id)->connected()->get();

    expect($connectedAccounts)->toHaveCount(3)
        ->and($connectedAccounts->every(fn ($a) => $a->status === SocialAccountStatus::CONNECTED))->toBeTrue();
});

test('scope needsTokenRefresh finds expiring tokens', function (): void {
    $workspace = Workspace::factory()->create();

    // Create account expiring in 3 days
    SocialAccount::factory()->forWorkspace($workspace)->connected()->expiringIn(3)->create();
    // Create account expiring in 10 days (should not match default 7-day check)
    SocialAccount::factory()->forWorkspace($workspace)->connected()->expiringIn(10)->create();
    // Create account with no expiration
    SocialAccount::factory()->forWorkspace($workspace)->connected()->create(['token_expires_at' => null]);

    $needsRefresh = SocialAccount::forWorkspace($workspace->id)->needsTokenRefresh(7)->get();

    expect($needsRefresh)->toHaveCount(1);
});

test('scope expired finds expired tokens', function (): void {
    $workspace = Workspace::factory()->create();

    // Create expired token
    SocialAccount::factory()->forWorkspace($workspace)->create([
        'token_expires_at' => now()->subDays(5),
    ]);
    // Create valid token
    SocialAccount::factory()->forWorkspace($workspace)->create([
        'token_expires_at' => now()->addDays(30),
    ]);
    // Create account with no expiration
    SocialAccount::factory()->forWorkspace($workspace)->create(['token_expires_at' => null]);

    $expired = SocialAccount::forWorkspace($workspace->id)->expired()->get();

    expect($expired)->toHaveCount(1);
});

test('isConnected checks status', function (): void {
    $connected = SocialAccount::factory()->connected()->create();
    $disconnected = SocialAccount::factory()->disconnected()->create();

    expect($connected->isConnected())->toBeTrue()
        ->and($disconnected->isConnected())->toBeFalse();
});

test('isHealthy checks status', function (): void {
    $connected = SocialAccount::factory()->connected()->create();
    $expired = SocialAccount::factory()->tokenExpired()->create();

    expect($connected->isHealthy())->toBeTrue()
        ->and($expired->isHealthy())->toBeFalse();
});

test('canPublish checks status', function (): void {
    $connected = SocialAccount::factory()->connected()->create();
    $revoked = SocialAccount::factory()->revoked()->create();

    expect($connected->canPublish())->toBeTrue()
        ->and($revoked->canPublish())->toBeFalse();
});

test('isTokenExpired checks token_expires_at', function (): void {
    $expired = SocialAccount::factory()->create([
        'token_expires_at' => now()->subDays(1),
    ]);
    $valid = SocialAccount::factory()->create([
        'token_expires_at' => now()->addDays(30),
    ]);
    $noExpiry = SocialAccount::factory()->create([
        'token_expires_at' => null,
    ]);

    expect($expired->isTokenExpired())->toBeTrue()
        ->and($valid->isTokenExpired())->toBeFalse()
        ->and($noExpiry->isTokenExpired())->toBeFalse();
});

test('isTokenExpiringSoon checks days until expiry', function (): void {
    $expiringSoon = SocialAccount::factory()->create([
        'token_expires_at' => now()->addDays(3),
    ]);
    $notExpiring = SocialAccount::factory()->create([
        'token_expires_at' => now()->addDays(30),
    ]);
    $noExpiry = SocialAccount::factory()->create([
        'token_expires_at' => null,
    ]);

    expect($expiringSoon->isTokenExpiringSoon(7))->toBeTrue()
        ->and($notExpiring->isTokenExpiringSoon(7))->toBeFalse()
        ->and($noExpiry->isTokenExpiringSoon(7))->toBeFalse();
});

test('requiresReconnect checks status', function (): void {
    $tokenExpired = SocialAccount::factory()->tokenExpired()->create();
    $revoked = SocialAccount::factory()->revoked()->create();
    $connected = SocialAccount::factory()->connected()->create();
    $disconnected = SocialAccount::factory()->disconnected()->create();

    expect($tokenExpired->requiresReconnect())->toBeTrue()
        ->and($revoked->requiresReconnect())->toBeTrue()
        ->and($connected->requiresReconnect())->toBeFalse()
        ->and($disconnected->requiresReconnect())->toBeFalse();
});

test('getDisplayName formats with username', function (): void {
    $socialAccount = SocialAccount::factory()->create([
        'account_name' => 'Test Company',
        'account_username' => 'testcompany',
    ]);

    expect($socialAccount->getDisplayName())->toBe('Test Company (@testcompany)');
});

test('getDisplayName formats without username', function (): void {
    $socialAccount = SocialAccount::factory()->create([
        'account_name' => 'Test Company',
        'account_username' => null,
    ]);

    expect($socialAccount->getDisplayName())->toBe('Test Company');
});

test('disconnect updates status and timestamp', function (): void {
    $socialAccount = SocialAccount::factory()->connected()->create();

    $socialAccount->disconnect();

    expect($socialAccount->status)->toBe(SocialAccountStatus::DISCONNECTED)
        ->and($socialAccount->disconnected_at)->not->toBeNull();
});

test('markTokenExpired updates status', function (): void {
    $socialAccount = SocialAccount::factory()->connected()->create();

    $socialAccount->markTokenExpired();

    expect($socialAccount->status)->toBe(SocialAccountStatus::TOKEN_EXPIRED);
});

test('markRevoked updates status', function (): void {
    $socialAccount = SocialAccount::factory()->connected()->create();

    $socialAccount->markRevoked();

    expect($socialAccount->status)->toBe(SocialAccountStatus::REVOKED);
});

test('updateTokens encrypts and saves tokens', function (): void {
    $socialAccount = SocialAccount::factory()->tokenExpired()->create();
    $newAccessToken = 'new_access_token_xyz';
    $newRefreshToken = 'new_refresh_token_xyz';
    $expiresAt = now()->addDays(30);

    $socialAccount->updateTokens($newAccessToken, $newRefreshToken, $expiresAt);

    $socialAccount->refresh();

    expect($socialAccount->access_token)->toBe($newAccessToken)
        ->and($socialAccount->refresh_token)->toBe($newRefreshToken)
        ->and($socialAccount->token_expires_at->format('Y-m-d'))->toBe($expiresAt->format('Y-m-d'))
        ->and($socialAccount->last_refreshed_at)->not->toBeNull()
        ->and($socialAccount->status)->toBe(SocialAccountStatus::CONNECTED);
});

test('updateTokens handles null refresh token', function (): void {
    $socialAccount = SocialAccount::factory()->create();
    $newAccessToken = 'new_access_token_xyz';
    $expiresAt = now()->addDays(30);

    $socialAccount->updateTokens($newAccessToken, null, $expiresAt);

    $socialAccount->refresh();

    expect($socialAccount->access_token)->toBe($newAccessToken)
        ->and($socialAccount->refresh_token)->toBeNull();
});

test('getMetadata retrieves from JSON', function (): void {
    $socialAccount = SocialAccount::factory()->create([
        'metadata' => [
            'organization_id' => 'urn:li:organization:12345',
            'nested' => [
                'key' => 'value',
            ],
        ],
    ]);

    expect($socialAccount->getMetadata('organization_id'))->toBe('urn:li:organization:12345')
        ->and($socialAccount->getMetadata('nested.key'))->toBe('value')
        ->and($socialAccount->getMetadata('nonexistent', 'default'))->toBe('default');
});

test('setMetadata updates JSON', function (): void {
    $socialAccount = SocialAccount::factory()->create([
        'metadata' => ['key1' => 'value1'],
    ]);

    $socialAccount->setMetadata('key2', 'value2');
    $socialAccount->setMetadata('nested.deep', 'nested_value');

    $socialAccount->refresh();

    expect($socialAccount->metadata['key1'])->toBe('value1')
        ->and($socialAccount->metadata['key2'])->toBe('value2')
        ->and($socialAccount->metadata['nested']['deep'])->toBe('nested_value');
});

test('factory creates valid model', function (): void {
    $socialAccount = SocialAccount::factory()->create();

    expect($socialAccount)->toBeInstanceOf(SocialAccount::class)
        ->and($socialAccount->id)->not->toBeNull()
        ->and($socialAccount->workspace_id)->not->toBeNull()
        ->and($socialAccount->platform)->toBeInstanceOf(SocialPlatform::class)
        ->and($socialAccount->platform_account_id)->toBeString()
        ->and($socialAccount->account_name)->toBeString()
        ->and($socialAccount->status)->toBeInstanceOf(SocialAccountStatus::class)
        ->and($socialAccount->connected_by_user_id)->not->toBeNull()
        ->and($socialAccount->connected_at)->not->toBeNull();
});

test('unique constraint on workspace platform and platform_account_id', function (): void {
    $workspace = Workspace::factory()->create();
    $platformAccountId = 'unique_platform_id_123';

    SocialAccount::factory()->forWorkspace($workspace)->linkedin()->create([
        'platform_account_id' => $platformAccountId,
    ]);

    expect(fn () => SocialAccount::factory()->forWorkspace($workspace)->linkedin()->create([
        'platform_account_id' => $platformAccountId,
    ]))->toThrow(QueryException::class);
});

test('same platform_account_id allowed in different workspaces', function (): void {
    $workspace1 = Workspace::factory()->create();
    $workspace2 = Workspace::factory()->create();
    $platformAccountId = 'same_platform_id_123';

    $account1 = SocialAccount::factory()->forWorkspace($workspace1)->linkedin()->create([
        'platform_account_id' => $platformAccountId,
    ]);
    $account2 = SocialAccount::factory()->forWorkspace($workspace2)->linkedin()->create([
        'platform_account_id' => $platformAccountId,
    ]);

    expect($account1->id)->not->toBe($account2->id)
        ->and($account1->platform_account_id)->toBe($account2->platform_account_id);
});

test('same platform_account_id allowed for different platforms in same workspace', function (): void {
    $workspace = Workspace::factory()->create();
    $platformAccountId = 'same_platform_id_456';

    $linkedinAccount = SocialAccount::factory()->forWorkspace($workspace)->linkedin()->create([
        'platform_account_id' => $platformAccountId,
    ]);
    $facebookAccount = SocialAccount::factory()->forWorkspace($workspace)->facebook()->create([
        'platform_account_id' => $platformAccountId,
    ]);

    expect($linkedinAccount->id)->not->toBe($facebookAccount->id)
        ->and($linkedinAccount->platform)->toBe(SocialPlatform::LINKEDIN)
        ->and($facebookAccount->platform)->toBe(SocialPlatform::FACEBOOK);
});

test('tokens are never exposed in serialization', function (): void {
    $socialAccount = SocialAccount::factory()->create();

    $array = $socialAccount->toArray();
    $json = $socialAccount->toJson();

    expect($array)->not->toHaveKey('access_token_encrypted')
        ->and($array)->not->toHaveKey('refresh_token_encrypted')
        ->and($json)->not->toContain('"access_token_encrypted":')
        ->and($json)->not->toContain('"refresh_token_encrypted":');
});

test('factory linkedin state works correctly', function (): void {
    $socialAccount = SocialAccount::factory()->linkedin()->create();

    expect($socialAccount->platform)->toBe(SocialPlatform::LINKEDIN)
        ->and($socialAccount->metadata)->toHaveKey('organization_id')
        ->and($socialAccount->metadata)->toHaveKey('vanity_name')
        ->and($socialAccount->metadata)->toHaveKey('page_type');
});

test('factory facebook state works correctly', function (): void {
    $socialAccount = SocialAccount::factory()->facebook()->create();

    expect($socialAccount->platform)->toBe(SocialPlatform::FACEBOOK)
        ->and($socialAccount->metadata)->toHaveKey('page_id')
        ->and($socialAccount->metadata)->toHaveKey('category');
});

test('factory instagram state works correctly', function (): void {
    $socialAccount = SocialAccount::factory()->instagram()->create();

    expect($socialAccount->platform)->toBe(SocialPlatform::INSTAGRAM)
        ->and($socialAccount->metadata)->toHaveKey('facebook_page_id')
        ->and($socialAccount->metadata)->toHaveKey('account_type')
        ->and($socialAccount->metadata)->toHaveKey('followers_count');
});

test('factory twitter state works correctly', function (): void {
    $socialAccount = SocialAccount::factory()->twitter()->create();

    expect($socialAccount->platform)->toBe(SocialPlatform::TWITTER)
        ->and($socialAccount->metadata)->toHaveKey('user_id')
        ->and($socialAccount->metadata)->toHaveKey('verified');
});

test('factory tokenExpired state works correctly', function (): void {
    $socialAccount = SocialAccount::factory()->tokenExpired()->create();

    expect($socialAccount->status)->toBe(SocialAccountStatus::TOKEN_EXPIRED)
        ->and($socialAccount->token_expires_at)->toBeLessThan(now());
});

test('factory revoked state works correctly', function (): void {
    $socialAccount = SocialAccount::factory()->revoked()->create();

    expect($socialAccount->status)->toBe(SocialAccountStatus::REVOKED);
});

test('factory disconnected state works correctly', function (): void {
    $socialAccount = SocialAccount::factory()->disconnected()->create();

    expect($socialAccount->status)->toBe(SocialAccountStatus::DISCONNECTED)
        ->and($socialAccount->disconnected_at)->not->toBeNull();
});

test('factory expiringIn state works correctly', function (): void {
    $socialAccount = SocialAccount::factory()->expiringIn(5)->create();

    // Token should expire in approximately 5 days (with tolerance for test execution time)
    expect($socialAccount->token_expires_at->isFuture())->toBeTrue()
        ->and(now()->diffInDays($socialAccount->token_expires_at))->toBeBetween(4, 6)
        ->and($socialAccount->status)->toBe(SocialAccountStatus::CONNECTED);
});
