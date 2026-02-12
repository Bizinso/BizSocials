# Task 1.3: User & Auth Migrations - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 1.3 User & Auth Migrations
- **Dependencies**: Task 1.2 (Tenant Management Migrations) - COMPLETED

---

## 1. Overview

This task implements the user authentication and authorization entities. These include tenant users, session management, and the invitation workflow for team building.

### Entities to Implement
1. **User** - Tenant users (distinct from SuperAdminUser)
2. **UserSession** - Active session tracking
3. **UserInvitation** - Team invitation workflow

**Note**: Laravel's default `users` migration will be replaced with our custom implementation.

---

## 2. Enums

### 2.1 UserStatus Enum
**File**: `app/Enums/User/UserStatus.php`

```php
enum UserStatus: string
{
    case PENDING = 'pending';           // Invited, not yet accepted
    case ACTIVE = 'active';             // Active user
    case SUSPENDED = 'suspended';       // Temporarily suspended
    case DEACTIVATED = 'deactivated';   // Permanently deactivated

    public function label(): string;
    public function canLogin(): bool;  // Only ACTIVE can login
    public function canTransitionTo(UserStatus $status): bool;
}
```

**Valid Transitions**:
- PENDING → ACTIVE (accepted invitation/verified email)
- ACTIVE → SUSPENDED
- ACTIVE → DEACTIVATED
- SUSPENDED → ACTIVE
- SUSPENDED → DEACTIVATED

### 2.2 TenantRole Enum
**File**: `app/Enums/User/TenantRole.php`

```php
enum TenantRole: string
{
    case OWNER = 'owner';     // Billing, can delete tenant
    case ADMIN = 'admin';     // Full admin (cannot delete tenant)
    case MEMBER = 'member';   // Regular team member

    public function label(): string;
    public function canManageBilling(): bool;  // Only OWNER
    public function canManageUsers(): bool;    // OWNER and ADMIN
    public function canDeleteTenant(): bool;   // Only OWNER
    public function isAtLeast(TenantRole $role): bool;
}
```

### 2.3 DeviceType Enum
**File**: `app/Enums/User/DeviceType.php`

```php
enum DeviceType: string
{
    case DESKTOP = 'desktop';
    case MOBILE = 'mobile';
    case TABLET = 'tablet';
    case API = 'api';

    public function label(): string;
    public static function fromUserAgent(string $userAgent): self;
}
```

### 2.4 InvitationStatus Enum
**File**: `app/Enums/User/InvitationStatus.php`

```php
enum InvitationStatus: string
{
    case PENDING = 'pending';     // Awaiting response
    case ACCEPTED = 'accepted';   // User joined
    case EXPIRED = 'expired';     // TTL passed
    case REVOKED = 'revoked';     // Cancelled by inviter

    public function label(): string;
    public function isFinal(): bool;  // ACCEPTED, EXPIRED, REVOKED are final
    public function canTransitionTo(InvitationStatus $status): bool;
}
```

---

## 3. Migrations

### 3.1 Replace Users Table
**File**: `database/migrations/2026_02_06_300001_create_users_table.php`

**Note**: Delete the default Laravel migration `0001_01_01_000000_create_users_table.php` first.

```php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id');
    $table->string('email');
    $table->string('password')->nullable();  // NULL for SSO users
    $table->string('name', 100);
    $table->string('avatar_url', 500)->nullable();
    $table->string('phone', 20)->nullable();
    $table->string('timezone', 50)->nullable();  // Overrides tenant timezone
    $table->string('language', 10)->default('en');
    $table->string('status', 20)->default('pending');  // UserStatus
    $table->string('role_in_tenant', 20);  // TenantRole
    $table->timestamp('email_verified_at')->nullable();
    $table->timestamp('last_login_at')->nullable();
    $table->timestamp('last_active_at')->nullable();
    $table->boolean('mfa_enabled')->default(false);
    $table->string('mfa_secret')->nullable();  // Encrypted
    $table->json('settings')->nullable();  // User preferences
    $table->string('remember_token', 100)->nullable();
    $table->timestamps();
    $table->softDeletes();

    // Unique constraint: email unique within tenant
    $table->unique(['tenant_id', 'email']);

    // Indexes
    $table->index('email');
    $table->index('status');
    $table->index('role_in_tenant');
    $table->index('last_active_at');

    // Foreign key
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->cascadeOnDelete();
});

// Add owner_user_id FK to tenants table
Schema::table('tenants', function (Blueprint $table) {
    $table->foreign('owner_user_id')
        ->references('id')
        ->on('users')
        ->nullOnDelete();
});
```

**Settings JSON Structure**:
```json
{
    "notifications": {
        "email_on_mention": true,
        "email_on_comment": true,
        "email_digest": "daily",
        "push_enabled": false
    },
    "ui": {
        "theme": "system",
        "compact_mode": false,
        "sidebar_collapsed": false
    }
}
```

### 3.2 Create User Sessions Table
**File**: `database/migrations/2026_02_06_300002_create_user_sessions_table.php`

```php
Schema::create('user_sessions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id');
    $table->string('token_hash');  // Hashed session token
    $table->string('ip_address', 45)->nullable();  // IPv6 support
    $table->text('user_agent')->nullable();
    $table->string('device_type', 20)->nullable();  // DeviceType
    $table->json('location')->nullable();  // Geo data
    $table->timestamp('last_active_at');
    $table->timestamp('expires_at');
    $table->timestamp('created_at');

    // Indexes
    $table->index('token_hash');
    $table->index('expires_at');
    $table->index('last_active_at');

    // Foreign key
    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();
});
```

**Location JSON Structure**:
```json
{
    "country": "IN",
    "city": "Mumbai",
    "latitude": 19.0760,
    "longitude": 72.8777
}
```

### 3.3 Create User Invitations Table
**File**: `database/migrations/2026_02_06_300003_create_user_invitations_table.php`

```php
Schema::create('user_invitations', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id');
    $table->string('email');
    $table->string('role_in_tenant', 20);  // TenantRole
    $table->json('workspace_memberships')->nullable();  // Workspace roles
    $table->uuid('invited_by');
    $table->string('token', 100)->unique();
    $table->string('status', 20)->default('pending');  // InvitationStatus
    $table->timestamp('expires_at');
    $table->timestamp('accepted_at')->nullable();
    $table->timestamps();

    // Indexes
    $table->index(['tenant_id', 'email']);
    $table->index('status');
    $table->index('expires_at');

    // Foreign keys
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->cascadeOnDelete();

    $table->foreign('invited_by')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();
});
```

**Workspace Memberships JSON Structure**:
```json
[
    {
        "workspace_id": "uuid",
        "role": "ADMIN"
    },
    {
        "workspace_id": "uuid",
        "role": "EDITOR"
    }
]
```

---

## 4. Models

### 4.1 User Model
**File**: `app/Models/User.php`

**Note**: This replaces the default Laravel User model.

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\User\TenantRole;
use App\Enums\User\UserStatus;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $email
 * @property string|null $password
 * @property string $name
 * @property string|null $avatar_url
 * @property string|null $phone
 * @property string|null $timezone
 * @property string $language
 * @property UserStatus $status
 * @property TenantRole $role_in_tenant
 * @property \Carbon\Carbon|null $email_verified_at
 * @property \Carbon\Carbon|null $last_login_at
 * @property \Carbon\Carbon|null $last_active_at
 * @property bool $mfa_enabled
 * @property string|null $mfa_secret
 * @property array|null $settings
 */
final class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'tenant_id',
        'email',
        'password',
        'name',
        'avatar_url',
        'phone',
        'timezone',
        'language',
        'status',
        'role_in_tenant',
        'email_verified_at',
        'last_login_at',
        'last_active_at',
        'mfa_enabled',
        'mfa_secret',
        'settings',
    ];

    protected $hidden = [
        'password',
        'mfa_secret',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'status' => UserStatus::class,
            'role_in_tenant' => TenantRole::class,
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_active_at' => 'datetime',
            'mfa_enabled' => 'boolean',
            'password' => 'hashed',
            'settings' => 'array',
        ];
    }

    // Relationships
    public function tenant(): BelongsTo;
    public function sessions(): HasMany;
    public function sentInvitations(): HasMany;

    // Scopes
    public function scopeActive(Builder $query): Builder;
    public function scopeForTenant(Builder $query, string $tenantId): Builder;
    public function scopeWithRole(Builder $query, TenantRole $role): Builder;

    // Helper methods
    public function isActive(): bool;
    public function canLogin(): bool;
    public function isOwner(): bool;
    public function isAdmin(): bool;
    public function hasVerifiedEmail(): bool;
    public function markEmailAsVerified(): void;
    public function recordLogin(): void;
    public function updateLastActive(): void;
    public function getTimezone(): string;  // User's or tenant's
    public function getSetting(string $key, mixed $default = null): mixed;
    public function setSetting(string $key, mixed $value): void;
    public function activate(): void;
    public function suspend(): void;
    public function deactivate(): void;
}
```

### 4.2 UserSession Model
**File**: `app/Models/User/UserSession.php`

```php
<?php

declare(strict_types=1);

namespace App\Models\User;

use App\Enums\User\DeviceType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $user_id
 * @property string $token_hash
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property DeviceType|null $device_type
 * @property array|null $location
 * @property \Carbon\Carbon $last_active_at
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon $created_at
 */
final class UserSession extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_sessions';

    public $timestamps = false;  // Only created_at, no updated_at

    protected $fillable = [
        'user_id',
        'token_hash',
        'ip_address',
        'user_agent',
        'device_type',
        'location',
        'last_active_at',
        'expires_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'device_type' => DeviceType::class,
            'location' => 'array',
            'last_active_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    // Relationships
    public function user(): BelongsTo;

    // Scopes
    public function scopeForUser(Builder $query, string $userId): Builder;
    public function scopeActive(Builder $query): Builder;  // Not expired
    public function scopeExpired(Builder $query): Builder;

    // Static methods
    public static function createForUser(
        string $userId,
        string $token,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        int $expiresInDays = 7
    ): self;

    // Helper methods
    public function isExpired(): bool;
    public function isActive(): bool;
    public function touch(): void;  // Update last_active_at
    public function invalidate(): void;  // Delete session
    public static function hashToken(string $token): string;
    public static function generateToken(): string;
    public static function cleanExpired(): int;  // Delete expired, return count
}
```

### 4.3 UserInvitation Model
**File**: `app/Models/User/UserInvitation.php`

```php
<?php

declare(strict_types=1);

namespace App\Models\User;

use App\Enums\User\InvitationStatus;
use App\Enums\User\TenantRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $email
 * @property TenantRole $role_in_tenant
 * @property array|null $workspace_memberships
 * @property string $invited_by
 * @property string $token
 * @property InvitationStatus $status
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon|null $accepted_at
 */
final class UserInvitation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_invitations';

    public const TOKEN_LENGTH = 64;
    public const EXPIRES_IN_DAYS = 7;

    protected $fillable = [
        'tenant_id',
        'email',
        'role_in_tenant',
        'workspace_memberships',
        'invited_by',
        'token',
        'status',
        'expires_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'role_in_tenant' => TenantRole::class,
            'workspace_memberships' => 'array',
            'status' => InvitationStatus::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    // Boot method for auto-generating token
    protected static function boot(): void;

    // Relationships
    public function tenant(): BelongsTo;
    public function inviter(): BelongsTo;

    // Scopes
    public function scopeForTenant(Builder $query, string $tenantId): Builder;
    public function scopePending(Builder $query): Builder;
    public function scopeExpired(Builder $query): Builder;

    // Static methods
    public static function findByToken(string $token): ?self;
    public static function generateToken(): string;
    public static function expireOldInvitations(): int;

    // Helper methods
    public function isPending(): bool;
    public function isExpired(): bool;
    public function canBeAccepted(): bool;  // Pending and not expired
    public function accept(): void;
    public function revoke(): void;
    public function markExpired(): void;
    public function getWorkspaceMembership(string $workspaceId): ?array;
}
```

---

## 5. Factories

### 5.1 UserFactory
**File**: `database/factories/UserFactory.php`

**Note**: Replace Laravel's default UserFactory.

- Generate realistic user data
- Associate with random or specific tenant
- State methods: `pending()`, `active()`, `suspended()`, `deactivated()`, `owner()`, `admin()`, `member()`, `verified()`, `unverified()`, `withMfa()`, `forTenant(Tenant $tenant)`

### 5.2 UserSessionFactory
**File**: `database/factories/User/UserSessionFactory.php`

- Generate realistic session data
- Various device types
- State methods: `active()`, `expired()`, `forUser(User $user)`, `desktop()`, `mobile()`, `tablet()`, `api()`

### 5.3 UserInvitationFactory
**File**: `database/factories/User/UserInvitationFactory.php`

- Generate invitation data
- State methods: `pending()`, `accepted()`, `expired()`, `revoked()`, `forTenant(Tenant $tenant)`, `byUser(User $user)`, `asAdmin()`, `asMember()`

---

## 6. Seeders

### 6.1 UserSeeder
**File**: `database/seeders/User/UserSeeder.php`

Create users for each seeded tenant:

1. **Acme Corporation** (B2B Enterprise):
   - John Owner (owner, active, verified)
   - Jane Admin (admin, active, verified)
   - Bob Member (member, active, verified)

2. **StartupXYZ** (B2B SMB):
   - Sarah Startup (owner, active, verified)
   - Mike Developer (member, active, verified)

3. **John Freelancer** (Individual):
   - John Freelancer (owner, active, verified)

4. **Sarah Lifestyle** (Influencer):
   - Sarah Lifestyle (owner, active, verified)

5. **Green Earth Foundation** (Non-Profit):
   - Earth Admin (owner, active, verified)
   - Volunteer One (member, active, verified)

6. **Pending Corp**:
   - Pending User (owner, pending, unverified)

7. **Suspended Inc**:
   - Suspended User (owner, suspended, verified)

Also update `tenants.owner_user_id` with the first user of each tenant.

### 6.2 UserSessionSeeder
**File**: `database/seeders/User/UserSessionSeeder.php`

Create sample sessions for active users.

### 6.3 UserInvitationSeeder
**File**: `database/seeders/User/UserInvitationSeeder.php`

Create sample invitations:
- 2 pending invitations for Acme Corporation
- 1 expired invitation
- 1 accepted invitation

---

## 7. Test Requirements

### 7.1 Enum Tests

**File**: `tests/Unit/Enums/User/UserStatusTest.php`
- Test all enum values
- Test `label()` method
- Test `canLogin()` (only ACTIVE returns true)
- Test `canTransitionTo()` for all valid/invalid transitions

**File**: `tests/Unit/Enums/User/TenantRoleTest.php`
- Test all enum values
- Test `label()` method
- Test `canManageBilling()` (only OWNER)
- Test `canManageUsers()` (OWNER and ADMIN)
- Test `canDeleteTenant()` (only OWNER)
- Test `isAtLeast()` comparison

**File**: `tests/Unit/Enums/User/DeviceTypeTest.php`
- Test all enum values
- Test `label()` method
- Test `fromUserAgent()` detection

**File**: `tests/Unit/Enums/User/InvitationStatusTest.php`
- Test all enum values
- Test `label()` method
- Test `isFinal()` (ACCEPTED, EXPIRED, REVOKED)
- Test `canTransitionTo()`

### 7.2 Model Tests

**File**: `tests/Unit/Models/UserTest.php`

Required tests:
- Model has correct table name
- Model uses UUID primary key
- Model uses soft deletes
- All fillable attributes are correct
- Hidden attributes (password, mfa_secret, remember_token)
- Casts are applied correctly
- `tenant()` relationship returns BelongsTo
- `sessions()` relationship returns HasMany
- `sentInvitations()` relationship returns HasMany
- `scopeActive()` filters correctly
- `scopeForTenant()` filters by tenant_id
- `scopeWithRole()` filters by role
- `isActive()` returns true only for ACTIVE status
- `canLogin()` returns true only for ACTIVE status
- `isOwner()` checks role
- `isAdmin()` checks role
- `hasVerifiedEmail()` checks email_verified_at
- `markEmailAsVerified()` sets timestamp
- `recordLogin()` updates last_login_at
- `updateLastActive()` updates last_active_at
- `getTimezone()` returns user's or tenant's timezone
- `getSetting()` retrieves from settings JSON
- `setSetting()` updates settings JSON
- `activate()` changes status
- `suspend()` changes status
- `deactivate()` changes status
- Factory creates valid model
- Email unique within tenant constraint
- Password is hashed

**File**: `tests/Unit/Models/User/UserSessionTest.php`

Required tests:
- Model has correct table name
- Model uses UUID primary key
- All fillable attributes are correct
- Casts are applied correctly
- `user()` relationship returns BelongsTo
- `scopeForUser()` filters correctly
- `scopeActive()` filters non-expired
- `scopeExpired()` filters expired
- `createForUser()` creates with hashed token
- `isExpired()` checks expires_at
- `isActive()` inverse of isExpired
- `touch()` updates last_active_at
- `invalidate()` deletes session
- `hashToken()` returns consistent hash
- `generateToken()` returns unique tokens
- `cleanExpired()` deletes expired sessions
- Factory creates valid model

**File**: `tests/Unit/Models/User/UserInvitationTest.php`

Required tests:
- Model has correct table name
- Model uses UUID primary key
- All fillable attributes are correct
- TOKEN_LENGTH constant is 64
- EXPIRES_IN_DAYS constant is 7
- Casts are applied correctly
- `tenant()` relationship returns BelongsTo
- `inviter()` relationship returns BelongsTo
- `scopeForTenant()` filters correctly
- `scopePending()` filters PENDING status
- `scopeExpired()` filters expired
- Token is auto-generated on create
- `findByToken()` retrieves invitation
- `generateToken()` returns correct length
- `expireOldInvitations()` marks old as expired
- `isPending()` checks status
- `isExpired()` checks expires_at and status
- `canBeAccepted()` checks pending and not expired
- `accept()` changes status and sets accepted_at
- `revoke()` changes status
- `markExpired()` changes status
- `getWorkspaceMembership()` retrieves from JSON
- Factory creates valid model
- Token uniqueness constraint

---

## 8. Implementation Checklist

- [ ] Delete Laravel default `0001_01_01_000000_create_users_table.php`
- [ ] Create UserStatus enum
- [ ] Create TenantRole enum
- [ ] Create DeviceType enum
- [ ] Create InvitationStatus enum
- [ ] Create users migration
- [ ] Create user_sessions migration
- [ ] Create user_invitations migration
- [ ] Replace default User model with custom implementation
- [ ] Create UserSession model
- [ ] Create UserInvitation model
- [ ] Replace default UserFactory
- [ ] Create UserSessionFactory
- [ ] Create UserInvitationFactory
- [ ] Create UserSeeder
- [ ] Create UserSessionSeeder
- [ ] Create UserInvitationSeeder
- [ ] Update DatabaseSeeder to call new seeders
- [ ] Create UserStatusTest
- [ ] Create TenantRoleTest
- [ ] Create DeviceTypeTest
- [ ] Create InvitationStatusTest
- [ ] Create UserTest
- [ ] Create UserSessionTest
- [ ] Create UserInvitationTest
- [ ] Run all migrations successfully
- [ ] Run all seeders successfully
- [ ] All tests pass

---

## 9. Notes

1. **Default User Model Replacement**: The Laravel default User model must be replaced. This affects any code that references `App\Models\User`.

2. **Password Hashing**: Laravel automatically hashes passwords with the `hashed` cast. No need for manual hashing in code.

3. **Token Security**: Session tokens and invitation tokens must be stored as hashes. Use `hash('sha256', $token)` for hashing.

4. **Tenant Owner**: After creating users, the tenant's `owner_user_id` should be updated to point to the first OWNER user.

5. **Soft Deletes**: Only the User model uses soft deletes. Sessions and invitations are hard-deleted.

6. **Cascade Deletes**: When a tenant is deleted, all users cascade delete. When a user is deleted, sessions and sent invitations cascade delete.
