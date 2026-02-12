# Task 2.4: Social Account Services & API - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 2.4 Social Account Services & API
- **Dependencies**: Task 2.1, Task 2.3, Task 1.4 (Social Migrations)

---

## 1. Overview

This task implements social account management services and API endpoints. It covers connecting social accounts, OAuth flows, disconnection, and status management.

### Components to Implement
1. **SocialAccountService** - Core social account management
2. **OAuthService** - OAuth flow handling
3. **Controllers** - API endpoints
4. **Data Classes** - Request/response DTOs
5. **Form Requests** - Validation

---

## 2. Services

### 2.1 SocialAccountService
**File**: `app/Services/Social/SocialAccountService.php`

```php
final class SocialAccountService extends BaseService
{
    public function listForWorkspace(Workspace $workspace, array $filters = []): LengthAwarePaginator;
    public function getById(string $id): SocialAccount;
    public function getByWorkspaceAndId(Workspace $workspace, string $id): SocialAccount;
    public function connect(Workspace $workspace, User $user, ConnectAccountData $data): SocialAccount;
    public function disconnect(SocialAccount $account): void;
    public function refresh(SocialAccount $account): SocialAccount;
    public function updateStatus(SocialAccount $account, SocialAccountStatus $status): SocialAccount;
    public function getHealthStatus(Workspace $workspace): array;
    public function getAccountsNeedingRefresh(): Collection;
}
```

### 2.2 OAuthService
**File**: `app/Services/Social/OAuthService.php`

```php
final class OAuthService extends BaseService
{
    public function getAuthorizationUrl(SocialPlatform $platform, string $state): string;
    public function handleCallback(SocialPlatform $platform, string $code, string $state): OAuthTokenData;
    public function refreshToken(SocialAccount $account): OAuthTokenData;
    public function revokeToken(SocialAccount $account): void;
    public function validateToken(SocialAccount $account): bool;
}
```

---

## 3. Data Classes

### 3.1 Social Account Data
**Directory**: `app/Data/Social/`

```php
// SocialAccountData.php
final class SocialAccountData extends Data
{
    public function __construct(
        public string $id,
        public string $workspace_id,
        public string $platform,
        public string $platform_account_id,
        public string $account_name,
        public ?string $account_username,
        public ?string $profile_image_url,
        public string $status,
        public bool $is_healthy,
        public bool $can_publish,
        public bool $requires_reconnect,
        public ?string $token_expires_at,
        public string $connected_at,
        public ?string $last_refreshed_at,
    ) {}

    public static function fromModel(SocialAccount $account): self;
}

// ConnectAccountData.php
final class ConnectAccountData extends Data
{
    public function __construct(
        #[Required]
        public SocialPlatform $platform,
        #[Required]
        public string $platform_account_id,
        #[Required]
        public string $account_name,
        public ?string $account_username = null,
        public ?string $profile_image_url = null,
        #[Required]
        public string $access_token,
        public ?string $refresh_token = null,
        public ?string $token_expires_at = null,
        public ?array $metadata = null,
    ) {}
}

// OAuthTokenData.php
final class OAuthTokenData extends Data
{
    public function __construct(
        public string $access_token,
        public ?string $refresh_token,
        public ?int $expires_in,
        public string $platform_account_id,
        public string $account_name,
        public ?string $account_username,
        public ?string $profile_image_url,
        public ?array $metadata,
    ) {}
}

// OAuthUrlData.php
final class OAuthUrlData extends Data
{
    public function __construct(
        public string $url,
        public string $state,
        public string $platform,
    ) {}
}

// HealthStatusData.php
final class HealthStatusData extends Data
{
    public function __construct(
        public int $total_accounts,
        public int $connected_count,
        public int $expired_count,
        public int $revoked_count,
        public int $disconnected_count,
        public array $by_platform,
    ) {}
}
```

---

## 4. Controllers

### 4.1 SocialAccountController
**File**: `app/Http/Controllers/Api/V1/Social/SocialAccountController.php`

Endpoints:
- `GET /workspaces/{workspace}/social-accounts` - List accounts for workspace
- `GET /workspaces/{workspace}/social-accounts/{id}` - Get account details
- `POST /workspaces/{workspace}/social-accounts` - Connect new account (direct token)
- `DELETE /workspaces/{workspace}/social-accounts/{id}` - Disconnect account
- `POST /workspaces/{workspace}/social-accounts/{id}/refresh` - Refresh token
- `GET /workspaces/{workspace}/social-accounts/health` - Get health status

### 4.2 OAuthController
**File**: `app/Http/Controllers/Api/V1/Social/OAuthController.php`

Endpoints:
- `GET /oauth/{platform}/authorize` - Get OAuth authorization URL
- `GET /oauth/{platform}/callback` - Handle OAuth callback
- `POST /oauth/{platform}/connect` - Connect account after OAuth (workspace context)

---

## 5. Routes

```php
// OAuth routes (require auth)
Route::middleware('auth:sanctum')->prefix('oauth')->group(function () {
    Route::get('/{platform}/authorize', [OAuthController::class, 'authorize'])
        ->where('platform', 'linkedin|facebook|instagram|twitter');
    Route::get('/{platform}/callback', [OAuthController::class, 'callback'])
        ->where('platform', 'linkedin|facebook|instagram|twitter');
    Route::post('/{platform}/connect', [OAuthController::class, 'connect'])
        ->where('platform', 'linkedin|facebook|instagram|twitter');
});

// Social account routes within workspace
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('workspaces/{workspace}/social-accounts')->group(function () {
        Route::get('/', [SocialAccountController::class, 'index']);
        Route::get('/health', [SocialAccountController::class, 'health']);
        Route::post('/', [SocialAccountController::class, 'store']);
        Route::get('/{socialAccount}', [SocialAccountController::class, 'show']);
        Route::delete('/{socialAccount}', [SocialAccountController::class, 'destroy']);
        Route::post('/{socialAccount}/refresh', [SocialAccountController::class, 'refresh']);
    });
});
```

---

## 6. Form Requests

**Directory**: `app/Http/Requests/Social/`

- `ConnectAccountRequest.php` - Validate direct account connection
- `OAuthConnectRequest.php` - Validate OAuth connection completion

---

## 7. Test Requirements

### Feature Tests
- `tests/Feature/Api/Social/SocialAccountTest.php`
- `tests/Feature/Api/Social/OAuthTest.php`

### Unit Tests
- `tests/Unit/Services/Social/SocialAccountServiceTest.php`
- `tests/Unit/Services/Social/OAuthServiceTest.php`

---

## 8. Implementation Checklist

- [ ] Create SocialAccountService
- [ ] Create OAuthService
- [ ] Create Social Data classes
- [ ] Create SocialAccountController
- [ ] Create OAuthController
- [ ] Create Form Requests
- [ ] Update routes
- [ ] Create feature tests
- [ ] Create unit tests
- [ ] All tests pass

---

## 9. OAuth Flow Notes

### For implementation, stub OAuth providers
Since actual OAuth integration requires external APIs:

1. **OAuthService** should be structured to support real providers but tests should mock responses
2. Create platform-specific adapter interfaces for future implementation
3. Tests use mock token data simulating OAuth responses
4. State parameter stored in cache with 10-minute expiry

### Platform-specific considerations
- **LinkedIn**: Uses OAuth 2.0 with separate organization access
- **Facebook/Instagram**: Uses Facebook Graph API with page tokens
- **Twitter**: Uses OAuth 2.0 (v2 API)

---

## 10. Response Examples

### List Social Accounts
```json
{
  "success": true,
  "message": "Success",
  "data": [
    {
      "id": "uuid",
      "workspace_id": "uuid",
      "platform": "linkedin",
      "platform_account_id": "12345",
      "account_name": "Company Page",
      "account_username": null,
      "profile_image_url": "https://...",
      "status": "connected",
      "is_healthy": true,
      "can_publish": true,
      "requires_reconnect": false,
      "token_expires_at": "2026-03-06T10:00:00Z",
      "connected_at": "2026-02-06T10:00:00Z",
      "last_refreshed_at": "2026-02-06T10:00:00Z"
    }
  ]
}
```

### Health Status
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "total_accounts": 5,
    "connected_count": 3,
    "expired_count": 1,
    "revoked_count": 0,
    "disconnected_count": 1,
    "by_platform": {
      "linkedin": {"total": 2, "connected": 2},
      "facebook": {"total": 1, "connected": 0},
      "instagram": {"total": 1, "connected": 1},
      "twitter": {"total": 1, "connected": 0}
    }
  }
}
```
