# Task 1.6: Social Account Migrations - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 1.6 Social Account Migrations
- **Dependencies**: Task 1.5 (Workspace Migrations) - COMPLETED

---

## 1. Overview

This task implements the SocialAccount entity for managing connected social media accounts. Each social account belongs to a workspace and stores OAuth credentials for posting to social platforms.

### Entities to Implement
1. **SocialAccount** - Connected social media account with OAuth tokens

---

## 2. Enums

### 2.1 SocialPlatform Enum
**File**: `app/Enums/Social/SocialPlatform.php`

```php
enum SocialPlatform: string
{
    case LINKEDIN = 'linkedin';
    case FACEBOOK = 'facebook';
    case INSTAGRAM = 'instagram';
    case TWITTER = 'twitter';

    public function label(): string;
    public function icon(): string;  // Icon name for UI
    public function color(): string;  // Brand color hex
    public function supportsScheduling(): bool;  // All true in Phase-1
    public function supportsImages(): bool;
    public function supportsVideos(): bool;
    public function supportsCarousel(): bool;  // Instagram, LinkedIn
    public function maxPostLength(): int;  // Character limit
    public function oauthScopes(): array;  // Required OAuth scopes
}
```

### 2.2 SocialAccountStatus Enum
**File**: `app/Enums/Social/SocialAccountStatus.php`

```php
enum SocialAccountStatus: string
{
    case CONNECTED = 'connected';         // OAuth valid, operational
    case TOKEN_EXPIRED = 'token_expired'; // Token expired, refresh failed
    case REVOKED = 'revoked';             // User revoked on platform
    case DISCONNECTED = 'disconnected';   // User disconnected in app

    public function label(): string;
    public function isHealthy(): bool;  // Only CONNECTED
    public function canPublish(): bool; // Only CONNECTED
    public function requiresReconnect(): bool;  // TOKEN_EXPIRED, REVOKED
}
```

---

## 3. Migrations

### 3.1 Create Social Accounts Table
**File**: `database/migrations/2026_02_06_600001_create_social_accounts_table.php`

```php
Schema::create('social_accounts', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('workspace_id');
    $table->string('platform', 20);  // SocialPlatform
    $table->string('platform_account_id', 100);  // ID from platform
    $table->string('account_name');  // Display name
    $table->string('account_username', 100)->nullable();  // @handle
    $table->string('profile_image_url', 500)->nullable();
    $table->string('status', 20)->default('connected');  // SocialAccountStatus
    $table->text('access_token_encrypted');  // Encrypted OAuth token
    $table->text('refresh_token_encrypted')->nullable();
    $table->timestamp('token_expires_at')->nullable();
    $table->uuid('connected_by_user_id');
    $table->timestamp('connected_at');
    $table->timestamp('last_refreshed_at')->nullable();
    $table->timestamp('disconnected_at')->nullable();
    $table->json('metadata')->nullable();  // Platform-specific data
    $table->timestamps();

    // Unique constraint: one platform account per workspace
    $table->unique(['workspace_id', 'platform', 'platform_account_id'], 'social_accounts_unique');

    // Indexes
    $table->index('platform');
    $table->index('status');
    $table->index('token_expires_at');

    // Foreign keys
    $table->foreign('workspace_id')
        ->references('id')
        ->on('workspaces')
        ->cascadeOnDelete();

    $table->foreign('connected_by_user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();
});
```

**Metadata JSON Examples**:

LinkedIn:
```json
{
    "organization_id": "urn:li:organization:12345",
    "vanity_name": "acme-corp",
    "page_type": "company"
}
```

Facebook:
```json
{
    "page_id": "123456789",
    "page_access_token_encrypted": "...",
    "category": "Business"
}
```

Instagram:
```json
{
    "facebook_page_id": "123456789",
    "account_type": "BUSINESS",
    "followers_count": 10500
}
```

Twitter:
```json
{
    "user_id": "123456789",
    "verified": false
}
```

---

## 4. Models

### 4.1 SocialAccount Model
**File**: `app/Models/Social/SocialAccount.php`

```php
<?php

declare(strict_types=1);

namespace App\Models\Social;

use App\Enums\Social\SocialAccountStatus;
use App\Enums\Social\SocialPlatform;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

final class SocialAccount extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'social_accounts';

    protected $fillable = [
        'workspace_id',
        'platform',
        'platform_account_id',
        'account_name',
        'account_username',
        'profile_image_url',
        'status',
        'access_token_encrypted',
        'refresh_token_encrypted',
        'token_expires_at',
        'connected_by_user_id',
        'connected_at',
        'last_refreshed_at',
        'disconnected_at',
        'metadata',
    ];

    protected $hidden = [
        'access_token_encrypted',
        'refresh_token_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'platform' => SocialPlatform::class,
            'status' => SocialAccountStatus::class,
            'token_expires_at' => 'datetime',
            'connected_at' => 'datetime',
            'last_refreshed_at' => 'datetime',
            'disconnected_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    // Encrypted token accessors
    protected function accessToken(): Attribute;  // Decrypt on get, encrypt on set
    protected function refreshToken(): Attribute; // Decrypt on get, encrypt on set

    // Relationships
    public function workspace(): BelongsTo;
    public function connectedBy(): BelongsTo;

    // Scopes
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder;
    public function scopeForPlatform(Builder $query, SocialPlatform $platform): Builder;
    public function scopeConnected(Builder $query): Builder;
    public function scopeNeedsTokenRefresh(Builder $query, int $daysBeforeExpiry = 7): Builder;
    public function scopeExpired(Builder $query): Builder;

    // Helper methods
    public function isConnected(): bool;
    public function isHealthy(): bool;
    public function canPublish(): bool;
    public function isTokenExpired(): bool;
    public function isTokenExpiringSoon(int $days = 7): bool;
    public function requiresReconnect(): bool;
    public function getDisplayName(): string;  // "Account Name (@username)" or just name
    public function disconnect(): void;
    public function markTokenExpired(): void;
    public function markRevoked(): void;
    public function updateTokens(string $accessToken, ?string $refreshToken, ?\DateTimeInterface $expiresAt): void;
    public function getMetadata(string $key, mixed $default = null): mixed;
    public function setMetadata(string $key, mixed $value): void;
}
```

---

## 5. Factories

### 5.1 SocialAccountFactory
**File**: `database/factories/Social/SocialAccountFactory.php`

State methods:
- `connected()`, `tokenExpired()`, `revoked()`, `disconnected()`
- `linkedin()`, `facebook()`, `instagram()`, `twitter()`
- `forWorkspace(Workspace $workspace)`
- `connectedBy(User $user)`
- `expiringIn(int $days)`
- `expiredToken()`
- `withMetadata(array $metadata)`

---

## 6. Seeders

### 6.1 SocialAccountSeeder
**File**: `database/seeders/Social/SocialAccountSeeder.php`

Create social accounts for workspaces:

1. **Acme Corporation - Marketing Team**:
   - LinkedIn Company Page (connected)
   - Facebook Page (connected)
   - Instagram Business (connected)
   - Twitter (token_expired - for testing)

2. **Acme Corporation - Sales Team**:
   - LinkedIn Company Page (connected)

3. **StartupXYZ - Main**:
   - LinkedIn Company Page (connected)
   - Twitter (connected)

4. **Fashion Brand Co - Brand Marketing**:
   - Instagram Business (connected)
   - Facebook Page (connected)

5. **Sarah Lifestyle - Content Creation**:
   - Instagram Business (connected)
   - Twitter (connected)

### 6.2 SocialAccountSeeder (Orchestrator)
**File**: `database/seeders/SocialAccountSeeder.php`

---

## 7. Test Requirements

### 7.1 Enum Tests

**File**: `tests/Unit/Enums/Social/SocialPlatformTest.php`
- Test all enum values (4 platforms)
- Test `label()` method
- Test `icon()` method
- Test `color()` method
- Test `supportsScheduling()` (all true)
- Test `supportsImages()` (all true)
- Test `supportsVideos()` (all true)
- Test `supportsCarousel()` (LinkedIn, Instagram)
- Test `maxPostLength()` per platform
- Test `oauthScopes()` returns array

**File**: `tests/Unit/Enums/Social/SocialAccountStatusTest.php`
- Test all enum values (4 statuses)
- Test `label()` method
- Test `isHealthy()` (only CONNECTED)
- Test `canPublish()` (only CONNECTED)
- Test `requiresReconnect()` (TOKEN_EXPIRED, REVOKED)

### 7.2 Model Tests

**File**: `tests/Unit/Models/Social/SocialAccountTest.php`

Required tests:
- Model has correct table name
- Model uses UUID primary key
- All fillable attributes are correct
- Hidden attributes (tokens)
- Casts are applied correctly
- `workspace()` relationship returns BelongsTo
- `connectedBy()` relationship returns BelongsTo
- `accessToken` accessor decrypts correctly
- `accessToken` mutator encrypts correctly
- `refreshToken` accessor decrypts correctly
- `refreshToken` mutator encrypts correctly
- `scopeForWorkspace()` filters correctly
- `scopeForPlatform()` filters by platform
- `scopeConnected()` filters CONNECTED status
- `scopeNeedsTokenRefresh()` finds expiring tokens
- `scopeExpired()` finds expired tokens
- `isConnected()` checks status
- `isHealthy()` checks status
- `canPublish()` checks status
- `isTokenExpired()` checks token_expires_at
- `isTokenExpiringSoon()` checks days until expiry
- `requiresReconnect()` checks status
- `getDisplayName()` formats correctly
- `disconnect()` updates status and timestamp
- `markTokenExpired()` updates status
- `markRevoked()` updates status
- `updateTokens()` encrypts and saves tokens
- `getMetadata()` retrieves from JSON
- `setMetadata()` updates JSON
- Factory creates valid model
- Unique constraint on (workspace_id, platform, platform_account_id)
- Tokens are never exposed in serialization

---

## 8. Implementation Checklist

- [ ] Create SocialPlatform enum
- [ ] Create SocialAccountStatus enum
- [ ] Create social_accounts migration
- [ ] Create SocialAccount model with encrypted token handling
- [ ] Create SocialAccountFactory
- [ ] Create SocialAccountSeeder
- [ ] Create SocialAccountSeeder orchestrator
- [ ] Update DatabaseSeeder
- [ ] Create SocialPlatformTest
- [ ] Create SocialAccountStatusTest
- [ ] Create SocialAccountTest
- [ ] Run migrations and seeders
- [ ] All tests pass

---

## 9. Notes

1. **Token Encryption**: Use Laravel's `Crypt::encryptString()` and `Crypt::decryptString()` for token handling. The model should expose `accessToken` and `refreshToken` as computed attributes that handle encryption/decryption automatically.

2. **Never Expose Tokens**: Tokens must be in `$hidden` array. API responses should never include token values.

3. **Token Refresh**: The actual OAuth refresh logic is not part of this task. This task only creates the database schema. A future task will implement the refresh service.

4. **Platform-Specific Metadata**: Use the `metadata` JSON field to store platform-specific data that doesn't fit the common schema.

5. **Workspace Isolation**: All social accounts are scoped to a workspace. Cross-workspace access is impossible by design.
