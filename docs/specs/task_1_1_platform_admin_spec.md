# Task 1.1: Platform Admin Migrations - Technical Specification

**Task ID:** 1.1
**Status:** In Progress
**Created:** 2026-02-06

---

## 1. Overview

Create database migrations, Eloquent models, factories, and seeders for the Platform Administration domain. This includes all entities required for SuperAdmin management and platform configuration.

---

## 2. Entities to Create

| Entity | Table Name | Description |
|--------|------------|-------------|
| SuperAdminUser | super_admin_users | Platform administrators (Bizinso team) |
| PlatformConfig | platform_configs | Global platform settings |
| FeatureFlag | feature_flags | Feature toggles for rollout |
| PlanDefinition | plan_definitions | Subscription plan definitions |
| PlanLimit | plan_limits | Limits per plan |

---

## 3. File Structure

```
backend/
├── app/
│   └── Models/
│       └── Platform/
│           ├── SuperAdminUser.php
│           ├── PlatformConfig.php
│           ├── FeatureFlag.php
│           ├── PlanDefinition.php
│           └── PlanLimit.php
│   └── Enums/
│       └── Platform/
│           ├── SuperAdminRole.php
│           ├── SuperAdminStatus.php
│           ├── PlanCode.php
│           └── ConfigCategory.php
├── database/
│   ├── migrations/
│   │   ├── 2026_02_06_100001_create_super_admin_users_table.php
│   │   ├── 2026_02_06_100002_create_platform_configs_table.php
│   │   ├── 2026_02_06_100003_create_feature_flags_table.php
│   │   ├── 2026_02_06_100004_create_plan_definitions_table.php
│   │   └── 2026_02_06_100005_create_plan_limits_table.php
│   ├── factories/
│   │   └── Platform/
│   │       ├── SuperAdminUserFactory.php
│   │       ├── PlatformConfigFactory.php
│   │       ├── FeatureFlagFactory.php
│   │       ├── PlanDefinitionFactory.php
│   │       └── PlanLimitFactory.php
│   └── seeders/
│       ├── Platform/
│       │   ├── SuperAdminUserSeeder.php
│       │   ├── PlatformConfigSeeder.php
│       │   ├── FeatureFlagSeeder.php
│       │   ├── PlanDefinitionSeeder.php
│       │   └── PlanLimitSeeder.php
│       └── PlatformSeeder.php (calls all platform seeders)
└── tests/
    └── Unit/
        └── Models/
            └── Platform/
                ├── SuperAdminUserTest.php
                ├── PlatformConfigTest.php
                ├── FeatureFlagTest.php
                ├── PlanDefinitionTest.php
                └── PlanLimitTest.php
```

---

## 4. Detailed Specifications

### 4.1 SuperAdminUser

#### Migration: `2026_02_06_100001_create_super_admin_users_table.php`

```php
Schema::create('super_admin_users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('email', 255)->unique();
    $table->string('password', 255);
    $table->string('name', 100);
    $table->string('role', 20); // SUPER_ADMIN, ADMIN, SUPPORT, VIEWER
    $table->string('status', 20)->default('ACTIVE'); // ACTIVE, INACTIVE, SUSPENDED
    $table->timestamp('last_login_at')->nullable();
    $table->boolean('mfa_enabled')->default(false);
    $table->string('mfa_secret', 255)->nullable();
    $table->rememberToken();
    $table->timestamps();
});
```

#### Enum: `SuperAdminRole.php`

```php
enum SuperAdminRole: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case ADMIN = 'ADMIN';
    case SUPPORT = 'SUPPORT';
    case VIEWER = 'VIEWER';
}
```

#### Enum: `SuperAdminStatus.php`

```php
enum SuperAdminStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case SUSPENDED = 'SUSPENDED';
}
```

#### Model Requirements:
- Use UUIDs (HasUuids trait)
- Cast `role` to SuperAdminRole enum
- Cast `status` to SuperAdminStatus enum
- Cast `mfa_enabled` to boolean
- Cast `last_login_at` to datetime
- Hide `password`, `mfa_secret`, `remember_token`
- Implement `Authenticatable` for Laravel auth
- Add relationship: `hasMany(PlatformConfig::class, 'updated_by')`

#### Factory Requirements:
- Generate valid email
- Hash password using bcrypt
- Random role from enum
- Status mostly ACTIVE (90%)
- mfa_enabled false by default

#### Seeder Requirements:
Create default super admin:
- Email: admin@bizinso.com
- Password: (use env or secure default)
- Role: SUPER_ADMIN
- Status: ACTIVE

---

### 4.2 PlatformConfig

#### Migration: `2026_02_06_100002_create_platform_configs_table.php`

```php
Schema::create('platform_configs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('key', 100)->unique();
    $table->json('value');
    $table->string('category', 50); // general, features, integrations, limits, notifications, security
    $table->text('description')->nullable();
    $table->boolean('is_sensitive')->default(false);
    $table->uuid('updated_by')->nullable();
    $table->timestamps();

    $table->foreign('updated_by')
        ->references('id')
        ->on('super_admin_users')
        ->onDelete('set null');

    $table->index('category');
});
```

#### Enum: `ConfigCategory.php`

```php
enum ConfigCategory: string
{
    case GENERAL = 'general';
    case FEATURES = 'features';
    case INTEGRATIONS = 'integrations';
    case LIMITS = 'limits';
    case NOTIFICATIONS = 'notifications';
    case SECURITY = 'security';
}
```

#### Model Requirements:
- Use UUIDs
- Cast `value` to array
- Cast `category` to ConfigCategory enum
- Cast `is_sensitive` to boolean
- Add relationship: `belongsTo(SuperAdminUser::class, 'updated_by')`
- Add scope: `scopeByCategory($query, ConfigCategory $category)`
- Add static method: `getValue(string $key, $default = null)`

#### Factory Requirements:
- Generate unique key (config.{random})
- Random category from enum
- Random JSON value
- is_sensitive false by default

#### Seeder Requirements:
Create default configs:
```php
[
    ['key' => 'platform.name', 'value' => 'BizSocials', 'category' => 'general'],
    ['key' => 'platform.url', 'value' => 'https://bizsocials.com', 'category' => 'general'],
    ['key' => 'platform.support_email', 'value' => 'support@bizsocials.com', 'category' => 'general'],
    ['key' => 'trial.duration_days', 'value' => 14, 'category' => 'limits'],
    ['key' => 'security.session_timeout_hours', 'value' => 24, 'category' => 'security'],
    ['key' => 'security.mfa_required_for_admins', 'value' => false, 'category' => 'security'],
]
```

---

### 4.3 FeatureFlag

#### Migration: `2026_02_06_100003_create_feature_flags_table.php`

```php
Schema::create('feature_flags', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('key', 100)->unique();
    $table->string('name', 255);
    $table->text('description')->nullable();
    $table->boolean('is_enabled')->default(false);
    $table->unsignedTinyInteger('rollout_percentage')->default(0);
    $table->json('allowed_plans')->nullable();
    $table->json('allowed_tenants')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();

    $table->index('is_enabled');
});
```

#### Model Requirements:
- Use UUIDs
- Cast `is_enabled` to boolean
- Cast `allowed_plans` to array
- Cast `allowed_tenants` to array
- Cast `metadata` to array
- Add method: `isEnabledForTenant(string $tenantId, string $planCode): bool`
- Add method: `isEnabledWithRollout(string $identifier): bool` (consistent hashing for rollout)
- Add scope: `scopeEnabled($query)`

#### Factory Requirements:
- Generate unique key (feature.{random})
- Random name
- is_enabled random (30% true)
- rollout_percentage 0-100

#### Seeder Requirements:
Create default feature flags:
```php
[
    ['key' => 'ai.caption_generation', 'name' => 'AI Caption Generation', 'is_enabled' => true],
    ['key' => 'ai.hashtag_suggestions', 'name' => 'AI Hashtag Suggestions', 'is_enabled' => true],
    ['key' => 'ai.best_time_posting', 'name' => 'AI Best Time to Post', 'is_enabled' => false],
    ['key' => 'social.tiktok', 'name' => 'TikTok Integration', 'is_enabled' => false],
    ['key' => 'social.youtube', 'name' => 'YouTube Integration', 'is_enabled' => false],
    ['key' => 'white_label.enabled', 'name' => 'White Label Features', 'is_enabled' => true],
]
```

---

### 4.4 PlanDefinition

#### Migration: `2026_02_06_100004_create_plan_definitions_table.php`

```php
Schema::create('plan_definitions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('code', 50)->unique(); // FREE, STARTER, PROFESSIONAL, BUSINESS, ENTERPRISE
    $table->string('name', 100);
    $table->text('description')->nullable();
    $table->decimal('price_inr_monthly', 10, 2);
    $table->decimal('price_inr_yearly', 10, 2);
    $table->decimal('price_usd_monthly', 10, 2);
    $table->decimal('price_usd_yearly', 10, 2);
    $table->unsignedSmallInteger('trial_days')->default(0);
    $table->boolean('is_active')->default(true);
    $table->boolean('is_public')->default(true);
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->json('features'); // Feature list for display
    $table->json('metadata')->nullable();
    $table->string('razorpay_plan_id_inr', 100)->nullable();
    $table->string('razorpay_plan_id_usd', 100)->nullable();
    $table->timestamps();

    $table->index(['is_active', 'is_public', 'sort_order']);
});
```

#### Enum: `PlanCode.php`

```php
enum PlanCode: string
{
    case FREE = 'FREE';
    case STARTER = 'STARTER';
    case PROFESSIONAL = 'PROFESSIONAL';
    case BUSINESS = 'BUSINESS';
    case ENTERPRISE = 'ENTERPRISE';
}
```

#### Model Requirements:
- Use UUIDs
- Cast `code` to PlanCode enum
- Cast `features` and `metadata` to array
- Cast `is_active` and `is_public` to boolean
- Cast price fields to decimal
- Add relationship: `hasMany(PlanLimit::class, 'plan_id')`
- Add scope: `scopeActive($query)`
- Add scope: `scopePublic($query)`
- Add method: `getLimit(string $limitKey): int` (-1 for unlimited)
- Add accessor: `getMonthlyPriceAttribute()` (based on currency context)
- Add accessor: `getYearlyPriceAttribute()`
- Add accessor: `getYearlyDiscountPercentAttribute()`

#### Factory Requirements:
- Generate random code (or sequential)
- Realistic prices
- Random features array
- is_active and is_public mostly true

#### Seeder Requirements:
Create all 5 plans as per architecture decision doc:
```php
[
    [
        'code' => 'FREE',
        'name' => 'Free',
        'price_inr_monthly' => 0,
        'price_inr_yearly' => 0,
        'price_usd_monthly' => 0,
        'price_usd_yearly' => 0,
        'trial_days' => 0,
        'features' => ['1 User', '1 Workspace', '2 Social Accounts', '30 Posts/month'],
        'sort_order' => 1,
    ],
    [
        'code' => 'STARTER',
        'name' => 'Starter',
        'price_inr_monthly' => 999,
        'price_inr_yearly' => 9590, // 20% discount
        'price_usd_monthly' => 15,
        'price_usd_yearly' => 144,
        'trial_days' => 14,
        'features' => ['2 Users', '2 Workspaces', '5 Social Accounts', '150 Posts/month'],
        'sort_order' => 2,
    ],
    // ... PROFESSIONAL, BUSINESS, ENTERPRISE
]
```

---

### 4.5 PlanLimit

#### Migration: `2026_02_06_100005_create_plan_limits_table.php`

```php
Schema::create('plan_limits', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('plan_id');
    $table->string('limit_key', 100);
    $table->integer('limit_value'); // -1 = unlimited
    $table->timestamps();

    $table->foreign('plan_id')
        ->references('id')
        ->on('plan_definitions')
        ->onDelete('cascade');

    $table->unique(['plan_id', 'limit_key']);
    $table->index('limit_key');
});
```

#### Model Requirements:
- Use UUIDs
- Add relationship: `belongsTo(PlanDefinition::class, 'plan_id')`
- Add constant array for LIMIT_KEYS
- Add method: `isUnlimited(): bool`

**Limit Keys (define as constants):**
```php
const LIMIT_KEYS = [
    'max_workspaces',
    'max_users',
    'max_social_accounts',
    'max_posts_per_month',
    'max_scheduled_posts',
    'max_team_members_per_workspace',
    'max_storage_gb',
    'max_api_calls_per_day',
    'ai_requests_per_month',
    'analytics_history_days',
];
```

#### Factory Requirements:
- Requires plan_id
- Random limit_key from LIMIT_KEYS
- Random limit_value (1-1000 or -1)

#### Seeder Requirements:
Create limits for all plans as per architecture decision:

| Limit Key | Free | Starter | Professional | Business | Enterprise |
|-----------|------|---------|--------------|----------|------------|
| max_workspaces | 1 | 2 | 5 | 10 | -1 |
| max_users | 1 | 2 | 5 | 15 | -1 |
| max_social_accounts | 2 | 5 | 15 | 50 | -1 |
| max_posts_per_month | 30 | 150 | 500 | -1 | -1 |
| max_scheduled_posts | 10 | 50 | 200 | -1 | -1 |
| max_storage_gb | 0.5 | 2 | 10 | 50 | -1 |
| ai_requests_per_month | 20 | 50 | 200 | 500 | -1 |
| analytics_history_days | 7 | 30 | 90 | 365 | -1 |

---

## 5. Unit Test Requirements

### 5.1 SuperAdminUserTest.php

Test cases:
1. `test_can_create_super_admin_user` - Create with valid data
2. `test_email_must_be_unique` - Duplicate email fails
3. `test_password_is_hashed` - Password stored as hash
4. `test_role_casts_to_enum` - Role casting works
5. `test_status_casts_to_enum` - Status casting works
6. `test_hidden_attributes_not_visible` - password, mfa_secret hidden
7. `test_has_many_platform_configs` - Relationship works
8. `test_factory_creates_valid_model` - Factory works

### 5.2 PlatformConfigTest.php

Test cases:
1. `test_can_create_config` - Create with valid data
2. `test_key_must_be_unique` - Duplicate key fails
3. `test_value_casts_to_array` - JSON casting works
4. `test_category_casts_to_enum` - Enum casting works
5. `test_get_value_returns_config` - Static getValue works
6. `test_get_value_returns_default_when_not_found` - Default fallback
7. `test_scope_by_category_filters_correctly` - Scope works
8. `test_belongs_to_super_admin` - Relationship works

### 5.3 FeatureFlagTest.php

Test cases:
1. `test_can_create_feature_flag` - Create with valid data
2. `test_key_must_be_unique` - Duplicate key fails
3. `test_is_enabled_for_tenant_with_allowed_tenants` - Tenant check
4. `test_is_enabled_for_tenant_with_allowed_plans` - Plan check
5. `test_rollout_percentage_consistent_for_same_identifier` - Consistent hashing
6. `test_scope_enabled_returns_only_enabled` - Scope works
7. `test_arrays_cast_correctly` - JSON casting works

### 5.4 PlanDefinitionTest.php

Test cases:
1. `test_can_create_plan` - Create with valid data
2. `test_code_must_be_unique` - Duplicate code fails
3. `test_code_casts_to_enum` - Enum casting works
4. `test_has_many_plan_limits` - Relationship works
5. `test_get_limit_returns_value` - getLimit method works
6. `test_get_limit_returns_negative_one_for_unlimited` - Unlimited handling
7. `test_scope_active_filters_correctly` - Active scope
8. `test_scope_public_filters_correctly` - Public scope
9. `test_yearly_discount_calculated_correctly` - Discount accessor

### 5.5 PlanLimitTest.php

Test cases:
1. `test_can_create_plan_limit` - Create with valid data
2. `test_plan_id_and_limit_key_unique_together` - Composite unique
3. `test_belongs_to_plan_definition` - Relationship works
4. `test_is_unlimited_returns_true_for_negative_one` - Unlimited check
5. `test_is_unlimited_returns_false_for_positive_value` - Bounded check
6. `test_cascades_on_plan_delete` - Cascade delete works

---

## 6. Documentation Requirements

### 6.1 PHPDoc Comments
Every class, method, and property must have PHPDoc comments:

```php
/**
 * SuperAdminUser Model
 *
 * Represents platform administrators (Bizinso team members).
 * These users have access to the super admin panel for managing
 * all tenants, configurations, and platform settings.
 *
 * @property string $id UUID primary key
 * @property string $email Unique login email
 * @property string $name Full name
 * @property SuperAdminRole $role Admin role level
 * @property SuperAdminStatus $status Account status
 * ...
 */
```

### 6.2 Inline Comments
Add comments explaining complex logic, especially in:
- Rollout percentage calculation
- Limit checking logic
- Any business rules

---

## 7. Coding Standards

1. **PSR-12** coding standard (enforced by Pint)
2. **Strict types** declaration in all PHP files
3. **Type hints** for all parameters and return types
4. **Final classes** for models (prevent inheritance issues)
5. **Readonly properties** where applicable (PHP 8.2+)
6. **Named arguments** for complex method calls

---

## 8. Acceptance Criteria

- [ ] All 5 migrations created and run successfully
- [ ] All 5 models created with proper relationships
- [ ] All 5 enums created
- [ ] All 5 factories created and working
- [ ] All 5 seeders created with realistic data
- [ ] PlatformSeeder created to run all seeders in order
- [ ] All unit tests written (minimum 35 test cases)
- [ ] All unit tests passing
- [ ] Code passes Pint linting
- [ ] PHPDoc comments on all classes and public methods
- [ ] No PHPStan errors at level 6

---

## 9. Commands to Run After Implementation

```bash
# Run migrations
php artisan migrate

# Run seeders
php artisan db:seed --class=PlatformSeeder

# Run tests
php artisan test --filter=Platform

# Run linting
./vendor/bin/pint

# Run static analysis
./vendor/bin/phpstan analyse app/Models/Platform app/Enums/Platform
```
