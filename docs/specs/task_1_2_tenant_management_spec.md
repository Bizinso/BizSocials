# Task 1.2: Tenant Management Migrations - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 1.2 Tenant Management Migrations
- **Dependencies**: Task 1.1 (Platform Admin Migrations) - COMPLETED

---

## 1. Overview

This task implements the core tenant management entities for the multi-tenant SaaS platform. These entities represent customer organizations/individuals and their associated profile, onboarding progress, and usage tracking.

### Entities to Implement
1. **Tenant** - Core billing entity (customer organization)
2. **TenantProfile** - Detailed business profile information
3. **TenantOnboarding** - Onboarding progress tracking
4. **TenantUsage** - Usage metrics for billing and limits

---

## 2. Enums

### 2.1 TenantType Enum
**File**: `app/Enums/Tenant/TenantType.php`

```php
enum TenantType: string
{
    case B2B_ENTERPRISE = 'b2b_enterprise';
    case B2B_SMB = 'b2b_smb';
    case B2C_BRAND = 'b2c_brand';
    case INDIVIDUAL = 'individual';
    case INFLUENCER = 'influencer';
    case NON_PROFIT = 'non_profit';

    public function label(): string;
    public function description(): string;
    public function requiresBusinessProfile(): bool;
    public function isB2B(): bool;
}
```

### 2.2 TenantStatus Enum
**File**: `app/Enums/Tenant/TenantStatus.php`

```php
enum TenantStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case TERMINATED = 'terminated';

    public function label(): string;
    public function canAccess(): bool;  // Only ACTIVE can access
    public function canTransitionTo(TenantStatus $status): bool;
}
```

**Valid Transitions**:
- PENDING → ACTIVE
- ACTIVE → SUSPENDED
- ACTIVE → TERMINATED
- SUSPENDED → ACTIVE
- SUSPENDED → TERMINATED

### 2.3 CompanySize Enum
**File**: `app/Enums/Tenant/CompanySize.php`

```php
enum CompanySize: string
{
    case SOLO = 'solo';           // 1 person
    case SMALL = 'small';         // 2-10
    case MEDIUM = 'medium';       // 11-50
    case LARGE = 'large';         // 51-200
    case ENTERPRISE = 'enterprise'; // 200+

    public function label(): string;
    public function range(): string;
    public function minEmployees(): int;
    public function maxEmployees(): ?int;
}
```

### 2.4 VerificationStatus Enum
**File**: `app/Enums/Tenant/VerificationStatus.php`

```php
enum VerificationStatus: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case FAILED = 'failed';

    public function label(): string;
    public function isVerified(): bool;
}
```

---

## 3. Migrations

### 3.1 Create Tenants Table
**File**: `database/migrations/2024_01_02_000001_create_tenants_table.php`

```php
Schema::create('tenants', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->string('slug')->unique();
    $table->string('type', 20);  // TenantType enum
    $table->string('status', 20)->default('pending');  // TenantStatus enum
    $table->uuid('owner_user_id')->nullable();  // Set after first user created
    $table->uuid('plan_id')->nullable();  // FK to plan_definitions
    $table->timestamp('trial_ends_at')->nullable();
    $table->json('settings')->nullable();  // Tenant-wide config
    $table->timestamp('onboarding_completed_at')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('status');
    $table->index('type');
    $table->index('plan_id');
    $table->index('created_at');

    // Foreign keys
    $table->foreign('plan_id')
        ->references('id')
        ->on('plan_definitions')
        ->nullOnDelete();
});
```

**Settings JSON Structure**:
```json
{
    "timezone": "Asia/Kolkata",
    "language": "en",
    "notifications": {
        "email": true,
        "in_app": true,
        "digest": "daily"
    },
    "branding": {
        "logo_url": null,
        "primary_color": "#2563eb"
    },
    "security": {
        "require_mfa": false,
        "session_timeout_minutes": 60
    }
}
```

### 3.2 Create Tenant Profiles Table
**File**: `database/migrations/2024_01_02_000002_create_tenant_profiles_table.php`

```php
Schema::create('tenant_profiles', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id')->unique();  // One profile per tenant
    $table->string('legal_name')->nullable();
    $table->string('business_type', 100)->nullable();
    $table->string('industry', 100)->nullable();
    $table->string('company_size', 20)->nullable();  // CompanySize enum
    $table->string('website')->nullable();
    $table->string('address_line1')->nullable();
    $table->string('address_line2')->nullable();
    $table->string('city', 100)->nullable();
    $table->string('state', 100)->nullable();
    $table->string('country', 2)->nullable();  // ISO 3166-1 alpha-2
    $table->string('postal_code', 20)->nullable();
    $table->string('phone', 20)->nullable();
    // Tax information (India-focused with international support)
    $table->string('gstin', 15)->nullable();  // India GST number
    $table->string('pan', 10)->nullable();     // India PAN
    $table->string('tax_id', 50)->nullable();  // International tax ID
    $table->string('verification_status', 20)->default('pending');
    $table->timestamp('verified_at')->nullable();
    $table->timestamps();

    // Foreign key
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->cascadeOnDelete();

    // Indexes
    $table->index('verification_status');
    $table->index('country');
    $table->index('industry');
});
```

### 3.3 Create Tenant Onboarding Table
**File**: `database/migrations/2024_01_02_000003_create_tenant_onboarding_table.php`

```php
Schema::create('tenant_onboarding', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id')->unique();  // One record per tenant
    $table->string('current_step', 50);
    $table->json('steps_completed')->nullable();  // Array of completed step keys
    $table->timestamp('started_at');
    $table->timestamp('completed_at')->nullable();
    $table->timestamp('abandoned_at')->nullable();
    $table->json('metadata')->nullable();  // Step-specific data
    $table->timestamps();

    // Foreign key
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->cascadeOnDelete();
});
```

**Onboarding Steps** (in order):
1. `account_created`
2. `email_verified`
3. `business_type_selected`
4. `profile_completed`
5. `plan_selected`
6. `payment_completed`
7. `first_workspace_created`
8. `first_social_account_connected`
9. `first_post_created`
10. `tour_completed`

### 3.4 Create Tenant Usage Table
**File**: `database/migrations/2024_01_02_000004_create_tenant_usage_table.php`

```php
Schema::create('tenant_usage', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id');
    $table->date('period_start');
    $table->date('period_end');
    $table->string('metric_key', 100);
    $table->bigInteger('metric_value')->default(0);
    $table->timestamps();

    // Unique constraint for one metric per tenant per period
    $table->unique(['tenant_id', 'period_start', 'metric_key'], 'tenant_usage_unique');

    // Foreign key
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->cascadeOnDelete();

    // Indexes
    $table->index(['tenant_id', 'metric_key']);
    $table->index(['period_start', 'period_end']);
});
```

**Metric Keys**:
- `workspaces_count` - Number of active workspaces
- `users_count` - Number of active users
- `social_accounts_count` - Connected social accounts
- `posts_published` - Posts published in period
- `posts_scheduled` - Scheduled posts count
- `storage_bytes_used` - Storage consumption
- `api_calls` - API requests made
- `ai_requests` - AI feature usage count

---

## 4. Models

### 4.1 Tenant Model
**File**: `app/Models/Tenant/Tenant.php`

```php
<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\TenantStatus;
use App\Enums\Tenant\TenantType;
use App\Models\Platform\PlanDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property TenantType $type
 * @property TenantStatus $status
 * @property string|null $owner_user_id
 * @property string|null $plan_id
 * @property \Carbon\Carbon|null $trial_ends_at
 * @property array|null $settings
 * @property \Carbon\Carbon|null $onboarding_completed_at
 * @property array|null $metadata
 */
final class Tenant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'status',
        'owner_user_id',
        'plan_id',
        'trial_ends_at',
        'settings',
        'onboarding_completed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => TenantType::class,
            'status' => TenantStatus::class,
            'trial_ends_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'settings' => 'array',
            'metadata' => 'array',
        ];
    }

    // Relationships
    public function plan(): BelongsTo;
    public function profile(): HasOne;
    public function onboarding(): HasOne;
    public function usageRecords(): HasMany;

    // Scopes
    public function scopeActive(Builder $query): Builder;
    public function scopeOfType(Builder $query, TenantType $type): Builder;
    public function scopeOnTrial(Builder $query): Builder;

    // Helper methods
    public function isActive(): bool;
    public function isOnTrial(): bool;
    public function trialDaysRemaining(): int;
    public function hasCompletedOnboarding(): bool;
    public function getSetting(string $key, mixed $default = null): mixed;
    public function setSetting(string $key, mixed $value): void;
    public function activate(): void;
    public function suspend(string $reason = null): void;
    public function terminate(): void;
}
```

### 4.2 TenantProfile Model
**File**: `app/Models/Tenant/TenantProfile.php`

```php
<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\CompanySize;
use App\Enums\Tenant\VerificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string|null $legal_name
 * @property string|null $business_type
 * @property string|null $industry
 * @property CompanySize|null $company_size
 * @property string|null $website
 * @property string|null $address_line1
 * @property string|null $address_line2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $country
 * @property string|null $postal_code
 * @property string|null $phone
 * @property string|null $gstin
 * @property string|null $pan
 * @property string|null $tax_id
 * @property VerificationStatus $verification_status
 * @property \Carbon\Carbon|null $verified_at
 */
final class TenantProfile extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'tenant_profiles';

    protected $fillable = [
        'tenant_id',
        'legal_name',
        'business_type',
        'industry',
        'company_size',
        'website',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'country',
        'postal_code',
        'phone',
        'gstin',
        'pan',
        'tax_id',
        'verification_status',
        'verified_at',
    ];

    protected function casts(): array;

    // Relationships
    public function tenant(): BelongsTo;

    // Helper methods
    public function isVerified(): bool;
    public function markAsVerified(): void;
    public function markAsFailed(): void;
    public function getFullAddress(): string;
    public function hasTaxInfo(): bool;
}
```

### 4.3 TenantOnboarding Model
**File**: `app/Models/Tenant/TenantOnboarding.php`

```php
<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $current_step
 * @property array|null $steps_completed
 * @property \Carbon\Carbon $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon|null $abandoned_at
 * @property array|null $metadata
 */
final class TenantOnboarding extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'tenant_onboarding';

    public const STEPS = [
        'account_created',
        'email_verified',
        'business_type_selected',
        'profile_completed',
        'plan_selected',
        'payment_completed',
        'first_workspace_created',
        'first_social_account_connected',
        'first_post_created',
        'tour_completed',
    ];

    protected $fillable = [
        'tenant_id',
        'current_step',
        'steps_completed',
        'started_at',
        'completed_at',
        'abandoned_at',
        'metadata',
    ];

    protected function casts(): array;

    // Relationships
    public function tenant(): BelongsTo;

    // Helper methods
    public function completeStep(string $step): void;
    public function isStepCompleted(string $step): bool;
    public function getCompletedStepsCount(): int;
    public function getProgressPercentage(): float;
    public function isComplete(): bool;
    public function isAbandoned(): bool;
    public function markComplete(): void;
    public function markAbandoned(): void;
    public function getNextStep(): ?string;
    public function getMetadata(string $key, mixed $default = null): mixed;
    public function setMetadata(string $key, mixed $value): void;
}
```

### 4.4 TenantUsage Model
**File**: `app/Models/Tenant/TenantUsage.php`

```php
<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property \Carbon\Carbon $period_start
 * @property \Carbon\Carbon $period_end
 * @property string $metric_key
 * @property int $metric_value
 */
final class TenantUsage extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'tenant_usage';

    public const METRIC_KEYS = [
        'workspaces_count',
        'users_count',
        'social_accounts_count',
        'posts_published',
        'posts_scheduled',
        'storage_bytes_used',
        'api_calls',
        'ai_requests',
    ];

    protected $fillable = [
        'tenant_id',
        'period_start',
        'period_end',
        'metric_key',
        'metric_value',
    ];

    protected function casts(): array;

    // Relationships
    public function tenant(): BelongsTo;

    // Scopes
    public function scopeForTenant(Builder $query, string $tenantId): Builder;
    public function scopeForPeriod(Builder $query, string $start, string $end): Builder;
    public function scopeForMetric(Builder $query, string $metricKey): Builder;
    public function scopeCurrentPeriod(Builder $query): Builder;

    // Static helpers
    public static function incrementMetric(string $tenantId, string $metricKey, int $amount = 1): void;
    public static function decrementMetric(string $tenantId, string $metricKey, int $amount = 1): void;
    public static function setMetric(string $tenantId, string $metricKey, int $value): void;
    public static function getMetric(string $tenantId, string $metricKey): int;
    public static function getCurrentPeriodUsage(string $tenantId): array;
}
```

---

## 5. Factories

### 5.1 TenantFactory
**File**: `database/factories/Tenant/TenantFactory.php`

- Generate realistic tenant names
- Create URL-safe slugs from names
- Random type selection
- Status weighted toward ACTIVE
- Optional trial period
- Default settings structure
- State methods: `pending()`, `active()`, `suspended()`, `terminated()`, `onTrial()`, `b2bEnterprise()`, `individual()`, etc.

### 5.2 TenantProfileFactory
**File**: `database/factories/Tenant/TenantProfileFactory.php`

- Generate realistic business information
- Country-appropriate tax IDs (India GST/PAN format)
- Industry and business type selection
- Company size distribution
- State methods: `verified()`, `pending()`, `failed()`, `withIndianTax()`, `withInternationalTax()`

### 5.3 TenantOnboardingFactory
**File**: `database/factories/Tenant/TenantOnboardingFactory.php`

- Generate realistic onboarding progress
- State methods: `justStarted()`, `inProgress()`, `completed()`, `abandoned()`
- Metadata with step-specific data

### 5.4 TenantUsageFactory
**File**: `database/factories/Tenant/TenantUsageFactory.php`

- Generate usage for current billing period
- Realistic metric values
- State methods: `forCurrentPeriod()`, `forPreviousPeriod()`, `highUsage()`, `lowUsage()`

---

## 6. Seeders

### 6.1 TenantSeeder
**File**: `database/seeders/Tenant/TenantSeeder.php`

Create sample tenants:
1. **Acme Corporation** - B2B Enterprise, Active, on Professional plan
2. **StartupXYZ** - B2B SMB, Active, on Starter plan, on trial
3. **Fashion Brand Co** - B2C Brand, Active, on Business plan
4. **John Freelancer** - Individual, Active, on Free plan
5. **Influencer Sarah** - Influencer, Active, on Professional plan
6. **Green Earth NGO** - Non-Profit, Active, on Non-Profit plan
7. **Pending Corp** - B2B SMB, Pending (just signed up)
8. **Suspended Inc** - B2B Enterprise, Suspended (payment failed)

### 6.2 TenantProfileSeeder
Create profiles for tenants requiring business information.

### 6.3 TenantOnboardingSeeder
Create onboarding records at various stages.

### 6.4 TenantUsageSeeder
Create usage metrics for active tenants.

---

## 7. Test Requirements

### 7.1 Enum Tests
**File**: `tests/Unit/Enums/Tenant/TenantTypeTest.php`
- Test all enum values
- Test `label()` method returns correct labels
- Test `description()` method
- Test `requiresBusinessProfile()` (B2B types should return true)
- Test `isB2B()` method

**File**: `tests/Unit/Enums/Tenant/TenantStatusTest.php`
- Test all enum values
- Test `canAccess()` (only ACTIVE returns true)
- Test `canTransitionTo()` for all valid/invalid transitions
- Test `label()` method

**File**: `tests/Unit/Enums/Tenant/CompanySizeTest.php`
- Test all enum values
- Test `label()`, `range()`, `minEmployees()`, `maxEmployees()`

**File**: `tests/Unit/Enums/Tenant/VerificationStatusTest.php`
- Test all enum values
- Test `isVerified()` method

### 7.2 Model Tests
**File**: `tests/Unit/Models/Tenant/TenantTest.php`

Required tests:
- Model has correct table name
- Model uses UUID primary key
- Model uses soft deletes
- All fillable attributes are correct
- Casts are applied correctly (type, status, dates, JSON)
- `plan()` relationship returns BelongsTo
- `profile()` relationship returns HasOne
- `onboarding()` relationship returns HasOne
- `usageRecords()` relationship returns HasMany
- `scopeActive()` filters correctly
- `scopeOfType()` filters by type
- `scopeOnTrial()` filters tenants on trial
- `isActive()` returns true only for ACTIVE status
- `isOnTrial()` returns true if trial_ends_at is in future
- `trialDaysRemaining()` calculates correctly
- `hasCompletedOnboarding()` checks onboarding_completed_at
- `getSetting()` retrieves nested settings
- `setSetting()` updates nested settings
- `activate()` changes status to ACTIVE
- `suspend()` changes status to SUSPENDED
- `terminate()` changes status to TERMINATED
- Factory creates valid model
- Slug must be unique

**File**: `tests/Unit/Models/Tenant/TenantProfileTest.php`

Required tests:
- Model has correct table name
- Model uses UUID primary key
- All fillable attributes are correct
- Casts are applied correctly
- `tenant()` relationship returns BelongsTo
- `isVerified()` returns correct boolean
- `markAsVerified()` updates status and timestamp
- `markAsFailed()` updates status
- `getFullAddress()` concatenates address parts
- `hasTaxInfo()` checks for any tax field
- Factory creates valid model
- One profile per tenant (unique constraint)

**File**: `tests/Unit/Models/Tenant/TenantOnboardingTest.php`

Required tests:
- Model has correct table name
- Model uses UUID primary key
- All fillable attributes are correct
- STEPS constant contains all expected steps
- `tenant()` relationship returns BelongsTo
- `completeStep()` adds step to completed array and updates current
- `isStepCompleted()` checks completed array
- `getCompletedStepsCount()` returns correct count
- `getProgressPercentage()` calculates correctly
- `isComplete()` checks completed_at
- `isAbandoned()` checks abandoned_at
- `markComplete()` sets completed_at
- `markAbandoned()` sets abandoned_at
- `getNextStep()` returns next uncompleted step
- `getMetadata()` retrieves from metadata JSON
- `setMetadata()` updates metadata JSON
- Factory creates valid model

**File**: `tests/Unit/Models/Tenant/TenantUsageTest.php`

Required tests:
- Model has correct table name
- Model uses UUID primary key
- All fillable attributes are correct
- METRIC_KEYS constant contains all expected keys
- `tenant()` relationship returns BelongsTo
- `scopeForTenant()` filters by tenant
- `scopeForPeriod()` filters by date range
- `scopeForMetric()` filters by metric key
- `scopeCurrentPeriod()` uses current billing period
- `incrementMetric()` creates or updates record
- `decrementMetric()` reduces value (not below 0)
- `setMetric()` sets absolute value
- `getMetric()` retrieves current value (0 if not found)
- `getCurrentPeriodUsage()` returns all metrics for tenant
- Factory creates valid model
- Unique constraint on (tenant_id, period_start, metric_key)

---

## 8. Implementation Checklist

- [ ] Create TenantType enum with all methods
- [ ] Create TenantStatus enum with all methods
- [ ] Create CompanySize enum with all methods
- [ ] Create VerificationStatus enum with all methods
- [ ] Create tenants migration
- [ ] Create tenant_profiles migration
- [ ] Create tenant_onboarding migration
- [ ] Create tenant_usage migration
- [ ] Create Tenant model with all relationships and methods
- [ ] Create TenantProfile model with all methods
- [ ] Create TenantOnboarding model with all methods
- [ ] Create TenantUsage model with all methods
- [ ] Create TenantFactory with all state methods
- [ ] Create TenantProfileFactory with all state methods
- [ ] Create TenantOnboardingFactory with all state methods
- [ ] Create TenantUsageFactory with all state methods
- [ ] Create TenantSeeder with sample data
- [ ] Create TenantProfileSeeder
- [ ] Create TenantOnboardingSeeder
- [ ] Create TenantUsageSeeder
- [ ] Create TenantTypeTest
- [ ] Create TenantStatusTest
- [ ] Create CompanySizeTest
- [ ] Create VerificationStatusTest
- [ ] Create TenantTest
- [ ] Create TenantProfileTest
- [ ] Create TenantOnboardingTest
- [ ] Create TenantUsageTest
- [ ] Run all migrations successfully
- [ ] Run all seeders successfully
- [ ] All tests pass

---

## 9. Notes

1. **Tenant ↔ User Relationship**: The `owner_user_id` foreign key will be added in Task 1.3 when the User model is created. For now, the column is nullable without a foreign key constraint.

2. **Multi-tenancy Package**: While stancl/tenancy is installed, we're using a custom implementation that better fits our workspace-scoped architecture. The package may be used for domain routing in the future.

3. **Usage Period**: The current period is typically the current month (1st to end of month) for monthly billing. The `getCurrentPeriod()` helper should use the subscription billing cycle when available.

4. **Soft Deletes**: Only the Tenant model uses soft deletes. Related records (profile, onboarding, usage) cascade delete when the tenant is force-deleted.
