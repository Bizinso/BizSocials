# Task 1.5: Workspace Migrations - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 1.5 Workspace Migrations
- **Dependencies**: Task 1.3 (User & Auth) - COMPLETED

---

## 1. Overview

This task implements the Workspace entity which is the core organizational unit for data isolation. Each workspace is an isolated container for social accounts, posts, and team collaboration.

### Entities to Implement
1. **Workspace** - Isolated organizational container
2. **WorkspaceMembership** - Join entity linking User to Workspace with role

---

## 2. Enums

### 2.1 WorkspaceStatus Enum
**File**: `app/Enums/Workspace/WorkspaceStatus.php`

```php
enum WorkspaceStatus: string
{
    case ACTIVE = 'active';       // Normal operation
    case SUSPENDED = 'suspended'; // Payment failed, limited access
    case DELETED = 'deleted';     // Soft-deleted, 30-day retention

    public function label(): string;
    public function hasAccess(): bool;  // ACTIVE only
    public function canTransitionTo(WorkspaceStatus $status): bool;
}
```

**Valid Transitions**:
- ACTIVE → SUSPENDED
- ACTIVE → DELETED
- SUSPENDED → ACTIVE
- SUSPENDED → DELETED

### 2.2 WorkspaceRole Enum
**File**: `app/Enums/Workspace/WorkspaceRole.php`

```php
enum WorkspaceRole: string
{
    case OWNER = 'owner';     // Full access, billing control
    case ADMIN = 'admin';     // Team + content management
    case EDITOR = 'editor';   // Content creation, no approvals
    case VIEWER = 'viewer';   // Read-only access

    public function label(): string;
    public function canManageWorkspace(): bool;    // OWNER, ADMIN
    public function canManageBilling(): bool;       // OWNER only
    public function canManageMembers(): bool;       // OWNER, ADMIN
    public function canManageSocialAccounts(): bool; // OWNER, ADMIN
    public function canCreateContent(): bool;       // OWNER, ADMIN, EDITOR
    public function canApproveContent(): bool;      // OWNER, ADMIN
    public function canPublishDirectly(): bool;     // OWNER, ADMIN
    public function canDeleteWorkspace(): bool;     // OWNER only
    public function isAtLeast(WorkspaceRole $role): bool;
    public function hierarchy(): int;  // OWNER=4, ADMIN=3, EDITOR=2, VIEWER=1
}
```

---

## 3. Migrations

### 3.1 Create Workspaces Table
**File**: `database/migrations/2026_02_06_500001_create_workspaces_table.php`

```php
Schema::create('workspaces', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id');
    $table->string('name', 100);
    $table->string('slug', 100);
    $table->string('description', 500)->nullable();
    $table->string('status', 20)->default('active');  // WorkspaceStatus
    $table->json('settings')->nullable();  // Workspace preferences
    $table->timestamps();
    $table->softDeletes();

    // Unique constraint: slug unique within tenant
    $table->unique(['tenant_id', 'slug']);

    // Indexes
    $table->index('status');
    $table->index('created_at');

    // Foreign key
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->cascadeOnDelete();
});
```

**Settings JSON Structure**:
```json
{
    "timezone": "Asia/Kolkata",
    "date_format": "DD/MM/YYYY",
    "approval_workflow": {
        "enabled": true,
        "required_for_roles": ["editor"]
    },
    "default_social_accounts": [],
    "content_categories": ["Marketing", "Product", "Support"],
    "hashtag_groups": {
        "brand": ["#BizSocials", "#SocialMedia"],
        "campaign": []
    }
}
```

### 3.2 Create Workspace Memberships Table
**File**: `database/migrations/2026_02_06_500002_create_workspace_memberships_table.php`

```php
Schema::create('workspace_memberships', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('workspace_id');
    $table->uuid('user_id');
    $table->string('role', 20);  // WorkspaceRole
    $table->timestamp('joined_at');
    $table->timestamps();

    // Unique constraint: one membership per user per workspace
    $table->unique(['workspace_id', 'user_id']);

    // Indexes
    $table->index('role');
    $table->index('user_id');

    // Foreign keys
    $table->foreign('workspace_id')
        ->references('id')
        ->on('workspaces')
        ->cascadeOnDelete();

    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();
});
```

---

## 4. Models

### 4.1 Workspace Model
**File**: `app/Models/Workspace/Workspace.php`

```php
<?php

declare(strict_types=1);

namespace App\Models\Workspace;

use App\Enums\Workspace\WorkspaceStatus;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class Workspace extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'workspaces';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'status',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'status' => WorkspaceStatus::class,
            'settings' => 'array',
        ];
    }

    // Boot method for auto-generating slug
    protected static function boot(): void;

    // Relationships
    public function tenant(): BelongsTo;
    public function memberships(): HasMany;
    public function members(): BelongsToMany;  // Through memberships

    // Scopes
    public function scopeActive(Builder $query): Builder;
    public function scopeForTenant(Builder $query, string $tenantId): Builder;
    public function scopeForUser(Builder $query, string $userId): Builder;

    // Static methods
    public static function generateSlug(string $name, string $tenantId): string;

    // Helper methods
    public function isActive(): bool;
    public function hasAccess(): bool;
    public function getOwner(): ?User;
    public function getMemberCount(): int;
    public function hasMember(string $userId): bool;
    public function getMemberRole(string $userId): ?WorkspaceRole;
    public function addMember(User $user, WorkspaceRole $role): WorkspaceMembership;
    public function removeMember(string $userId): bool;
    public function getSetting(string $key, mixed $default = null): mixed;
    public function setSetting(string $key, mixed $value): void;
    public function suspend(): void;
    public function activate(): void;
    public function isApprovalRequired(WorkspaceRole $role): bool;
}
```

### 4.2 WorkspaceMembership Model
**File**: `app/Models/Workspace/WorkspaceMembership.php`

```php
<?php

declare(strict_types=1);

namespace App\Models\Workspace;

use App\Enums\Workspace\WorkspaceRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WorkspaceMembership extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'workspace_memberships';

    protected $fillable = [
        'workspace_id',
        'user_id',
        'role',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => WorkspaceRole::class,
            'joined_at' => 'datetime',
        ];
    }

    // Relationships
    public function workspace(): BelongsTo;
    public function user(): BelongsTo;

    // Scopes
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder;
    public function scopeForUser(Builder $query, string $userId): Builder;
    public function scopeWithRole(Builder $query, WorkspaceRole $role): Builder;
    public function scopeOwners(Builder $query): Builder;
    public function scopeAdmins(Builder $query): Builder;

    // Helper methods
    public function isOwner(): bool;
    public function isAdmin(): bool;
    public function isEditor(): bool;
    public function isViewer(): bool;
    public function canManageWorkspace(): bool;
    public function canManageMembers(): bool;
    public function canCreateContent(): bool;
    public function canApproveContent(): bool;
    public function canPublishDirectly(): bool;
    public function updateRole(WorkspaceRole $newRole): void;
}
```

---

## 5. Factories

### 5.1 WorkspaceFactory
**File**: `database/factories/Workspace/WorkspaceFactory.php`

State methods:
- `active()`, `suspended()`, `deleted()`
- `forTenant(Tenant $tenant)`
- `withSettings(array $settings)`
- `withApprovalWorkflow()`
- `withoutApprovalWorkflow()`

### 5.2 WorkspaceMembershipFactory
**File**: `database/factories/Workspace/WorkspaceMembershipFactory.php`

State methods:
- `owner()`, `admin()`, `editor()`, `viewer()`
- `forWorkspace(Workspace $workspace)`
- `forUser(User $user)`

---

## 6. Seeders

### 6.1 WorkspaceSeeder
**File**: `database/seeders/Workspace/WorkspaceSeeder.php`

Create workspaces for each tenant:

1. **Acme Corporation** (Enterprise):
   - "Marketing Team" workspace
   - "Sales Team" workspace
   - "Product Team" workspace

2. **StartupXYZ** (SMB):
   - "Main" workspace

3. **Fashion Brand Co** (B2C):
   - "Brand Marketing" workspace
   - "Influencer Partnerships" workspace

4. **John Freelancer** (Individual):
   - "My Business" workspace

5. **Sarah Lifestyle** (Influencer):
   - "Content Creation" workspace

6. **Green Earth Foundation** (Non-Profit):
   - "Campaigns" workspace
   - "Community" workspace

### 6.2 WorkspaceMembershipSeeder
**File**: `database/seeders/Workspace/WorkspaceMembershipSeeder.php`

Assign members to workspaces with appropriate roles:
- Tenant owners get OWNER role in all workspaces
- Admins get ADMIN role
- Regular members get EDITOR role

### 6.3 WorkspaceSeeder (Orchestrator)
**File**: `database/seeders/WorkspaceSeeder.php`

Call all workspace seeders in order.

---

## 7. Test Requirements

### 7.1 Enum Tests

**File**: `tests/Unit/Enums/Workspace/WorkspaceStatusTest.php`
- Test all enum values
- Test `label()` method
- Test `hasAccess()` (only ACTIVE returns true)
- Test `canTransitionTo()` for all valid/invalid transitions

**File**: `tests/Unit/Enums/Workspace/WorkspaceRoleTest.php`
- Test all enum values
- Test `label()` method
- Test all permission methods (canManageWorkspace, canManageBilling, etc.)
- Test `isAtLeast()` comparison
- Test `hierarchy()` returns correct values

### 7.2 Model Tests

**File**: `tests/Unit/Models/Workspace/WorkspaceTest.php`

Required tests:
- Model has correct table name
- Model uses UUID primary key
- Model uses soft deletes
- All fillable attributes are correct
- Casts are applied correctly
- `tenant()` relationship returns BelongsTo
- `memberships()` relationship returns HasMany
- `members()` relationship returns BelongsToMany
- `scopeActive()` filters correctly
- `scopeForTenant()` filters by tenant_id
- `scopeForUser()` filters by membership
- Slug is auto-generated from name
- `generateSlug()` creates URL-safe slugs
- `generateSlug()` handles duplicate names
- `isActive()` returns true only for ACTIVE status
- `hasAccess()` returns true only for ACTIVE status
- `getOwner()` returns the owner user
- `getMemberCount()` returns correct count
- `hasMember()` checks membership
- `getMemberRole()` returns correct role
- `addMember()` creates membership
- `removeMember()` deletes membership
- `getSetting()` retrieves from settings JSON
- `setSetting()` updates settings JSON
- `suspend()` changes status to SUSPENDED
- `activate()` changes status to ACTIVE
- `isApprovalRequired()` checks workflow settings
- Factory creates valid model
- Slug unique within tenant constraint
- Soft delete works correctly

**File**: `tests/Unit/Models/Workspace/WorkspaceMembershipTest.php`

Required tests:
- Model has correct table name
- Model uses UUID primary key
- All fillable attributes are correct
- Casts are applied correctly
- `workspace()` relationship returns BelongsTo
- `user()` relationship returns BelongsTo
- `scopeForWorkspace()` filters correctly
- `scopeForUser()` filters correctly
- `scopeWithRole()` filters by role
- `scopeOwners()` filters owners
- `scopeAdmins()` filters admins
- `isOwner()` checks role
- `isAdmin()` checks role
- `isEditor()` checks role
- `isViewer()` checks role
- `canManageWorkspace()` delegates to role
- `canManageMembers()` delegates to role
- `canCreateContent()` delegates to role
- `canApproveContent()` delegates to role
- `canPublishDirectly()` delegates to role
- `updateRole()` changes role
- Factory creates valid model
- Unique constraint on (workspace_id, user_id)

---

## 8. Implementation Checklist

- [ ] Create WorkspaceStatus enum
- [ ] Create WorkspaceRole enum
- [ ] Create workspaces migration
- [ ] Create workspace_memberships migration
- [ ] Create Workspace model with all methods
- [ ] Create WorkspaceMembership model with all methods
- [ ] Create WorkspaceFactory
- [ ] Create WorkspaceMembershipFactory
- [ ] Create WorkspaceSeeder
- [ ] Create WorkspaceMembershipSeeder
- [ ] Create WorkspaceSeeder orchestrator
- [ ] Update DatabaseSeeder
- [ ] Create WorkspaceStatusTest
- [ ] Create WorkspaceRoleTest
- [ ] Create WorkspaceTest
- [ ] Create WorkspaceMembershipTest
- [ ] Run migrations and seeders
- [ ] All tests pass

---

## 9. Notes

1. **Slug Generation**: Slugs are auto-generated from the workspace name using `Str::slug()`. If a slug already exists within the tenant, append a number (e.g., `marketing-team-2`).

2. **Workspace Isolation**: This is the core entity for multi-tenancy. All business data (posts, social accounts, etc.) will reference workspace_id.

3. **Soft Deletes**: Workspaces use soft deletes with 30-day retention. The DELETED status is used alongside `deleted_at`.

4. **Owner Requirement**: Every workspace must have at least one OWNER. This should be enforced at the application level, not database level.

5. **Settings Default**: When creating a workspace, initialize settings with sensible defaults (approval workflow enabled for EDITOR role).
