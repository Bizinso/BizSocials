# Task 2.3: Tenant & Workspace Services & API - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 2.3 Tenant & Workspace Services & API
- **Dependencies**: Task 2.1, Task 2.2, Task 1.2 (Tenant), Task 1.5 (Workspace)

---

## 1. Overview

This task implements tenant and workspace management services and API endpoints. It covers tenant settings, workspace CRUD, membership management, and invitations.

### Components to Implement
1. **TenantService** - Tenant management
2. **WorkspaceService** - Workspace CRUD and management
3. **WorkspaceMembershipService** - Member management
4. **InvitationService** - User invitations
5. **Controllers** - API endpoints
6. **Data Classes** - Request/response DTOs
7. **Form Requests** - Validation

---

## 2. Services

### 2.1 TenantService
**File**: `app/Services/Tenant/TenantService.php`

```php
final class TenantService extends BaseService
{
    public function getCurrent(User $user): Tenant;
    public function update(Tenant $tenant, UpdateTenantData $data): Tenant;
    public function updateSettings(Tenant $tenant, array $settings): Tenant;
    public function getUsageStats(Tenant $tenant): array;
    public function getMembers(Tenant $tenant, array $filters = []): LengthAwarePaginator;
    public function updateMemberRole(Tenant $tenant, User $user, TenantRole $role): User;
    public function removeMember(Tenant $tenant, User $user): void;
}
```

### 2.2 WorkspaceService
**File**: `app/Services/Workspace/WorkspaceService.php`

```php
final class WorkspaceService extends BaseService
{
    public function listForTenant(Tenant $tenant, array $filters = []): LengthAwarePaginator;
    public function create(Tenant $tenant, User $creator, CreateWorkspaceData $data): Workspace;
    public function get(string $workspaceId): Workspace;
    public function update(Workspace $workspace, UpdateWorkspaceData $data): Workspace;
    public function updateSettings(Workspace $workspace, array $settings): Workspace;
    public function delete(Workspace $workspace): void;
    public function archive(Workspace $workspace): Workspace;
    public function restore(Workspace $workspace): Workspace;
}
```

### 2.3 WorkspaceMembershipService
**File**: `app/Services/Workspace/WorkspaceMembershipService.php`

```php
final class WorkspaceMembershipService extends BaseService
{
    public function listMembers(Workspace $workspace, array $filters = []): LengthAwarePaginator;
    public function addMember(Workspace $workspace, User $user, WorkspaceRole $role): WorkspaceMembership;
    public function updateRole(Workspace $workspace, User $user, WorkspaceRole $role): WorkspaceMembership;
    public function removeMember(Workspace $workspace, User $user): void;
    public function getUserWorkspaces(User $user): Collection;
    public function checkPermission(User $user, Workspace $workspace, string $permission): bool;
}
```

### 2.4 InvitationService
**File**: `app/Services/Tenant/InvitationService.php`

```php
final class InvitationService extends BaseService
{
    public function invite(Tenant $tenant, InviteUserData $data, User $inviter): TenantInvitation;
    public function listPending(Tenant $tenant): Collection;
    public function resend(TenantInvitation $invitation): void;
    public function cancel(TenantInvitation $invitation): void;
    public function accept(string $token, User $user): void;
    public function decline(string $token): void;
}
```

---

## 3. Data Classes

### 3.1 Tenant Data
**Directory**: `app/Data/Tenant/`

```php
// TenantData.php
final class TenantData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public string $type,
        public string $status,
        public ?string $logo_url,
        public ?string $website,
        public ?string $timezone,
        public array $settings,
        public string $created_at,
    ) {}

    public static function fromModel(Tenant $tenant): self;
}

// UpdateTenantData.php
final class UpdateTenantData extends Data
{
    public function __construct(
        public ?string $name = null,
        public ?string $website = null,
        public ?string $timezone = null,
        public ?string $industry = null,
        public ?string $company_size = null,
    ) {}
}

// InviteUserData.php
final class InviteUserData extends Data
{
    public function __construct(
        #[Required, Email]
        public string $email,
        public TenantRole $role = TenantRole::MEMBER,
        public ?array $workspace_ids = null,
    ) {}
}

// TenantMemberData.php
final class TenantMemberData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $role,
        public string $status,
        public ?string $avatar_url,
        public string $joined_at,
    ) {}

    public static function fromModel(User $user): self;
}
```

### 3.2 Workspace Data
**Directory**: `app/Data/Workspace/`

```php
// WorkspaceData.php
final class WorkspaceData extends Data
{
    public function __construct(
        public string $id,
        public string $tenant_id,
        public string $name,
        public string $slug,
        public ?string $description,
        public string $status,
        public ?string $icon,
        public ?string $color,
        public array $settings,
        public int $member_count,
        public string $created_at,
    ) {}

    public static function fromModel(Workspace $workspace): self;
}

// CreateWorkspaceData.php
final class CreateWorkspaceData extends Data
{
    public function __construct(
        #[Required, Max(100)]
        public string $name,
        public ?string $description = null,
        public ?string $icon = null,
        public ?string $color = null,
    ) {}
}

// UpdateWorkspaceData.php
final class UpdateWorkspaceData extends Data
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?string $icon = null,
        public ?string $color = null,
    ) {}
}

// WorkspaceMemberData.php
final class WorkspaceMemberData extends Data
{
    public function __construct(
        public string $id,
        public string $user_id,
        public string $name,
        public string $email,
        public string $role,
        public ?string $avatar_url,
        public string $joined_at,
    ) {}

    public static function fromMembership(WorkspaceMembership $membership): self;
}

// AddMemberData.php
final class AddMemberData extends Data
{
    public function __construct(
        #[Required]
        public string $user_id,
        public WorkspaceRole $role = WorkspaceRole::VIEWER,
    ) {}
}

// UpdateMemberRoleData.php
final class UpdateMemberRoleData extends Data
{
    public function __construct(
        #[Required]
        public WorkspaceRole $role,
    ) {}
}
```

---

## 4. Controllers

### 4.1 TenantController
**File**: `app/Http/Controllers/Api/V1/Tenant/TenantController.php`

Endpoints:
- `GET /tenants/current` - Get current tenant
- `PUT /tenants/current` - Update tenant
- `PUT /tenants/current/settings` - Update settings
- `GET /tenants/current/stats` - Get usage stats

### 4.2 TenantMemberController
**File**: `app/Http/Controllers/Api/V1/Tenant/TenantMemberController.php`

Endpoints:
- `GET /tenants/current/members` - List members
- `PUT /tenants/current/members/{userId}` - Update member role
- `DELETE /tenants/current/members/{userId}` - Remove member

### 4.3 InvitationController
**File**: `app/Http/Controllers/Api/V1/Tenant/InvitationController.php`

Endpoints:
- `POST /tenants/current/invitations` - Invite user
- `GET /tenants/current/invitations` - List pending
- `POST /tenants/current/invitations/{id}/resend` - Resend
- `DELETE /tenants/current/invitations/{id}` - Cancel
- `POST /invitations/{token}/accept` - Accept (public)
- `POST /invitations/{token}/decline` - Decline (public)

### 4.4 WorkspaceController
**File**: `app/Http/Controllers/Api/V1/Workspace/WorkspaceController.php`

Endpoints:
- `GET /workspaces` - List workspaces
- `POST /workspaces` - Create workspace
- `GET /workspaces/{id}` - Get workspace
- `PUT /workspaces/{id}` - Update workspace
- `PUT /workspaces/{id}/settings` - Update settings
- `DELETE /workspaces/{id}` - Delete workspace
- `POST /workspaces/{id}/archive` - Archive
- `POST /workspaces/{id}/restore` - Restore

### 4.5 WorkspaceMemberController
**File**: `app/Http/Controllers/Api/V1/Workspace/WorkspaceMemberController.php`

Endpoints:
- `GET /workspaces/{id}/members` - List members
- `POST /workspaces/{id}/members` - Add member
- `PUT /workspaces/{id}/members/{userId}` - Update role
- `DELETE /workspaces/{id}/members/{userId}` - Remove member

---

## 5. Routes

```php
// Tenant routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('tenants/current')->group(function () {
        Route::get('/', [TenantController::class, 'show']);
        Route::put('/', [TenantController::class, 'update']);
        Route::put('/settings', [TenantController::class, 'updateSettings']);
        Route::get('/stats', [TenantController::class, 'stats']);

        // Members
        Route::get('/members', [TenantMemberController::class, 'index']);
        Route::put('/members/{userId}', [TenantMemberController::class, 'update']);
        Route::delete('/members/{userId}', [TenantMemberController::class, 'destroy']);

        // Invitations
        Route::get('/invitations', [InvitationController::class, 'index']);
        Route::post('/invitations', [InvitationController::class, 'store']);
        Route::post('/invitations/{id}/resend', [InvitationController::class, 'resend']);
        Route::delete('/invitations/{id}', [InvitationController::class, 'destroy']);
    });

    // Workspaces
    Route::apiResource('workspaces', WorkspaceController::class);
    Route::post('/workspaces/{workspace}/archive', [WorkspaceController::class, 'archive']);
    Route::post('/workspaces/{workspace}/restore', [WorkspaceController::class, 'restore']);
    Route::put('/workspaces/{workspace}/settings', [WorkspaceController::class, 'updateSettings']);

    // Workspace members
    Route::get('/workspaces/{workspace}/members', [WorkspaceMemberController::class, 'index']);
    Route::post('/workspaces/{workspace}/members', [WorkspaceMemberController::class, 'store']);
    Route::put('/workspaces/{workspace}/members/{userId}', [WorkspaceMemberController::class, 'update']);
    Route::delete('/workspaces/{workspace}/members/{userId}', [WorkspaceMemberController::class, 'destroy']);
});

// Public invitation routes
Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept']);
Route::post('/invitations/{token}/decline', [InvitationController::class, 'decline']);
```

---

## 6. Form Requests

**Directory**: `app/Http/Requests/Tenant/`
- `UpdateTenantRequest.php`
- `UpdateTenantSettingsRequest.php`
- `UpdateMemberRoleRequest.php`
- `InviteUserRequest.php`

**Directory**: `app/Http/Requests/Workspace/`
- `CreateWorkspaceRequest.php`
- `UpdateWorkspaceRequest.php`
- `UpdateWorkspaceSettingsRequest.php`
- `AddMemberRequest.php`
- `UpdateMemberRoleRequest.php`

---

## 7. Test Requirements

### Feature Tests
- `tests/Feature/Api/Tenant/TenantTest.php`
- `tests/Feature/Api/Tenant/TenantMemberTest.php`
- `tests/Feature/Api/Tenant/InvitationTest.php`
- `tests/Feature/Api/Workspace/WorkspaceTest.php`
- `tests/Feature/Api/Workspace/WorkspaceMemberTest.php`

### Unit Tests
- `tests/Unit/Services/Tenant/TenantServiceTest.php`
- `tests/Unit/Services/Tenant/InvitationServiceTest.php`
- `tests/Unit/Services/Workspace/WorkspaceServiceTest.php`
- `tests/Unit/Services/Workspace/WorkspaceMembershipServiceTest.php`

---

## 8. Implementation Checklist

- [ ] Create TenantService
- [ ] Create WorkspaceService
- [ ] Create WorkspaceMembershipService
- [ ] Create InvitationService
- [ ] Create Tenant Data classes
- [ ] Create Workspace Data classes
- [ ] Create TenantController
- [ ] Create TenantMemberController
- [ ] Create InvitationController
- [ ] Create WorkspaceController
- [ ] Create WorkspaceMemberController
- [ ] Create Form Requests
- [ ] Update routes
- [ ] Create feature tests
- [ ] Create unit tests
- [ ] All tests pass
