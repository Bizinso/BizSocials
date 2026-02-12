# Task 2.11: Platform Admin Services & API - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 2.11 Platform Admin Services & API
- **Dependencies**: Task 2.1, Task 1.7 (Platform Migrations)

---

## 1. Overview

This task implements platform administration services for super admins. It covers tenant management, user management, plan management, feature flags, and platform configuration.

### Components to Implement
1. **AdminTenantService** - Tenant administration
2. **AdminUserService** - User administration
3. **PlanService** - Plan management
4. **FeatureFlagService** - Feature flag management
5. **PlatformConfigService** - Platform configuration
6. **Controllers** - Admin API endpoints
7. **Data Classes** - Request/response DTOs

---

## 2. Services

### 2.1 AdminTenantService
**File**: `app/Services/Admin/AdminTenantService.php`

```php
final class AdminTenantService extends BaseService
{
    public function list(array $filters = []): LengthAwarePaginator;
    public function get(string $id): Tenant;
    public function update(Tenant $tenant, UpdateTenantAdminData $data): Tenant;
    public function suspend(Tenant $tenant, string $reason): Tenant;
    public function activate(Tenant $tenant): Tenant;
    public function impersonate(Tenant $tenant, SuperAdminUser $admin): string;  // Return temp token
    public function getStats(): array;
}
```

### 2.2 AdminUserService
**File**: `app/Services/Admin/AdminUserService.php`

```php
final class AdminUserService extends BaseService
{
    public function list(array $filters = []): LengthAwarePaginator;
    public function get(string $id): User;
    public function update(User $user, UpdateUserAdminData $data): User;
    public function suspend(User $user, string $reason): User;
    public function activate(User $user): User;
    public function resetPassword(User $user): void;
    public function getStats(): array;
}
```

### 2.3 PlanService
**File**: `app/Services/Admin/PlanService.php`

```php
final class PlanService extends BaseService
{
    public function list(): Collection;
    public function get(string $id): PlanDefinition;
    public function create(CreatePlanData $data): PlanDefinition;
    public function update(PlanDefinition $plan, UpdatePlanData $data): PlanDefinition;
    public function delete(PlanDefinition $plan): void;
    public function updateLimits(PlanDefinition $plan, array $limits): PlanDefinition;
}
```

### 2.4 FeatureFlagService
**File**: `app/Services/Admin/FeatureFlagService.php`

```php
final class FeatureFlagService extends BaseService
{
    public function list(): Collection;
    public function get(string $id): FeatureFlag;
    public function create(CreateFeatureFlagData $data): FeatureFlag;
    public function update(FeatureFlag $flag, UpdateFeatureFlagData $data): FeatureFlag;
    public function toggle(FeatureFlag $flag): FeatureFlag;
    public function delete(FeatureFlag $flag): void;
    public function isEnabled(string $key, ?Tenant $tenant = null): bool;
}
```

### 2.5 PlatformConfigService
**File**: `app/Services/Admin/PlatformConfigService.php`

```php
final class PlatformConfigService extends BaseService
{
    public function list(): Collection;
    public function get(string $key): PlatformConfig;
    public function set(string $key, mixed $value): PlatformConfig;
    public function delete(string $key): void;
    public function getByGroup(string $group): Collection;
}
```

---

## 3. Data Classes

### 3.1 Admin Data
**Directory**: `app/Data/Admin/`

```php
// AdminTenantData.php
final class AdminTenantData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public string $type,
        public string $status,
        public ?string $plan_name,
        public int $user_count,
        public int $workspace_count,
        public string $created_at,
        public ?string $suspended_at,
        public ?string $suspension_reason,
    ) {}

    public static function fromModel(Tenant $tenant): self;
}

// AdminUserData.php
final class AdminUserData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $status,
        public ?string $tenant_id,
        public ?string $tenant_name,
        public ?string $email_verified_at,
        public ?string $last_login_at,
        public string $created_at,
    ) {}

    public static function fromModel(User $user): self;
}

// PlanData.php
final class PlanData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public bool $is_active,
        public bool $is_public,
        public int $sort_order,
        public string $monthly_price,
        public string $yearly_price,
        public string $currency,
        public ?int $trial_days,
        public array $limits,
        public array $features,
        public string $created_at,
    ) {}

    public static function fromModel(PlanDefinition $plan): self;
}

// CreatePlanData.php
final class CreatePlanData extends Data
{
    public function __construct(
        #[Required, Max(100)]
        public string $name,
        public ?string $description = null,
        public bool $is_active = true,
        public bool $is_public = true,
        #[Required]
        public float $monthly_price,
        #[Required]
        public float $yearly_price,
        public ?int $trial_days = null,
        public ?array $limits = null,
        public ?array $features = null,
    ) {}
}

// FeatureFlagData.php
final class FeatureFlagData extends Data
{
    public function __construct(
        public string $id,
        public string $key,
        public string $name,
        public ?string $description,
        public bool $is_enabled,
        public ?array $enabled_for_tenants,
        public ?array $enabled_for_plans,
        public string $created_at,
    ) {}

    public static function fromModel(FeatureFlag $flag): self;
}

// PlatformConfigData.php
final class PlatformConfigData extends Data
{
    public function __construct(
        public string $id,
        public string $key,
        public mixed $value,
        public string $value_type,
        public ?string $group,
        public ?string $description,
        public string $updated_at,
    ) {}

    public static function fromModel(PlatformConfig $config): self;
}

// PlatformStatsData.php
final class PlatformStatsData extends Data
{
    public function __construct(
        public int $total_tenants,
        public int $active_tenants,
        public int $total_users,
        public int $active_users,
        public int $total_subscriptions,
        public int $active_subscriptions,
        public array $tenants_by_plan,
        public array $signups_by_month,
    ) {}
}
```

---

## 4. Controllers

### 4.1 Admin Controllers (All require admin auth)

**AdminTenantController** - `app/Http/Controllers/Api/V1/Admin/AdminTenantController.php`
- `GET /admin/tenants` - List tenants
- `GET /admin/tenants/{id}` - Get tenant
- `PUT /admin/tenants/{id}` - Update tenant
- `POST /admin/tenants/{id}/suspend` - Suspend tenant
- `POST /admin/tenants/{id}/activate` - Activate tenant
- `POST /admin/tenants/{id}/impersonate` - Get impersonation token

**AdminUserController** - `app/Http/Controllers/Api/V1/Admin/AdminUserController.php`
- `GET /admin/users` - List users
- `GET /admin/users/{id}` - Get user
- `PUT /admin/users/{id}` - Update user
- `POST /admin/users/{id}/suspend` - Suspend user
- `POST /admin/users/{id}/activate` - Activate user
- `POST /admin/users/{id}/reset-password` - Reset password

**AdminPlanController** - `app/Http/Controllers/Api/V1/Admin/AdminPlanController.php`
- Full CRUD for plans
- `PUT /admin/plans/{id}/limits` - Update limits

**AdminFeatureFlagController** - `app/Http/Controllers/Api/V1/Admin/AdminFeatureFlagController.php`
- Full CRUD for feature flags
- `POST /admin/feature-flags/{id}/toggle` - Toggle flag

**AdminConfigController** - `app/Http/Controllers/Api/V1/Admin/AdminConfigController.php`
- `GET /admin/config` - List configs
- `GET /admin/config/{key}` - Get config
- `PUT /admin/config/{key}` - Set config
- `DELETE /admin/config/{key}` - Delete config

**AdminDashboardController** - `app/Http/Controllers/Api/V1/Admin/AdminDashboardController.php`
- `GET /admin/dashboard/stats` - Platform stats

---

## 5. Routes

```php
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats']);

    // Tenants
    Route::get('/tenants', [AdminTenantController::class, 'index']);
    Route::get('/tenants/{tenant}', [AdminTenantController::class, 'show']);
    Route::put('/tenants/{tenant}', [AdminTenantController::class, 'update']);
    Route::post('/tenants/{tenant}/suspend', [AdminTenantController::class, 'suspend']);
    Route::post('/tenants/{tenant}/activate', [AdminTenantController::class, 'activate']);
    Route::post('/tenants/{tenant}/impersonate', [AdminTenantController::class, 'impersonate']);

    // Users
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{user}', [AdminUserController::class, 'show']);
    Route::put('/users/{user}', [AdminUserController::class, 'update']);
    Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend']);
    Route::post('/users/{user}/activate', [AdminUserController::class, 'activate']);
    Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword']);

    // Plans
    Route::apiResource('plans', AdminPlanController::class);
    Route::put('/plans/{plan}/limits', [AdminPlanController::class, 'updateLimits']);

    // Feature Flags
    Route::apiResource('feature-flags', AdminFeatureFlagController::class);
    Route::post('/feature-flags/{featureFlag}/toggle', [AdminFeatureFlagController::class, 'toggle']);

    // Config
    Route::get('/config', [AdminConfigController::class, 'index']);
    Route::get('/config/{key}', [AdminConfigController::class, 'show']);
    Route::put('/config/{key}', [AdminConfigController::class, 'update']);
    Route::delete('/config/{key}', [AdminConfigController::class, 'destroy']);
});
```

---

## 6. Test Requirements

### Feature Tests
- AdminTenantController, AdminUserController, AdminPlanController
- AdminFeatureFlagController, AdminConfigController, AdminDashboardController

### Unit Tests
- All admin services

---

## 7. Business Rules

### Tenant Administration
- Cannot delete tenants, only suspend
- Suspension reason required
- Impersonation creates temporary token

### User Administration
- Cannot delete users, only suspend
- Password reset sends email notification

### Plan Management
- Cannot delete plans with active subscriptions
- Limits define workspace/user/account counts

### Feature Flags
- Can be enabled globally or per tenant/plan
- Toggle for quick enable/disable
