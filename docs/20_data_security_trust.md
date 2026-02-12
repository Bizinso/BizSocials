# Data Security & Trust Framework

## Document Information
- **Version**: 1.0.0
- **Created**: 2025-02-06
- **Module**: Security, Privacy & Compliance
- **Classification**: Internal

---

## 1. Overview

### 1.1 Trust Commitment
BizSocials is committed to protecting tenant data with the highest standards of security and privacy. Each tenant's data is:
- **Isolated**: Completely separated from other tenants
- **Encrypted**: At rest and in transit
- **Private**: Never shared, sold, or accessed without consent
- **Compliant**: Meets regulatory requirements (GDPR, DPDP, SOC 2)

### 1.2 Security Architecture
```
┌─────────────────────────────────────────────────────────────────┐
│                    SECURITY LAYERS                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                   APPLICATION LAYER                       │  │
│  │  • Authentication (MFA, SSO)                              │  │
│  │  • Authorization (RBAC, Tenant Isolation)                 │  │
│  │  • Input Validation & Sanitization                        │  │
│  │  • Rate Limiting & DDoS Protection                        │  │
│  └──────────────────────────────────────────────────────────┘  │
│                            ▼                                    │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                    DATA LAYER                             │  │
│  │  • Encryption at Rest (AES-256)                           │  │
│  │  • Encryption in Transit (TLS 1.3)                        │  │
│  │  • Database Row-Level Security                            │  │
│  │  • Secure Key Management                                  │  │
│  └──────────────────────────────────────────────────────────┘  │
│                            ▼                                    │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                 INFRASTRUCTURE LAYER                      │  │
│  │  • VPC Isolation                                          │  │
│  │  • Firewall Rules                                         │  │
│  │  • Intrusion Detection                                    │  │
│  │  • Security Monitoring                                    │  │
│  └──────────────────────────────────────────────────────────┘  │
│                            ▼                                    │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                  PHYSICAL LAYER                           │  │
│  │  • DigitalOcean Data Centers                              │  │
│  │  • ISO 27001 Certified                                    │  │
│  │  • SOC 2 Type II Compliant                                │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. Tenant Data Isolation

### 2.1 Database Isolation Strategy
```
┌─────────────────────────────────────────────────────────────────┐
│              TENANT DATA ISOLATION MODEL                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  LOGICAL ISOLATION (Row-Level)                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │  Every data table includes tenant_id column                 ││
│  │  All queries automatically filtered by tenant context       ││
│  │  Database triggers prevent cross-tenant access              ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                 │
│  QUERY ENFORCEMENT                                              │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │  Laravel Global Scopes automatically apply tenant filter    ││
│  │  API Gateway validates tenant context on every request      ││
│  │  Middleware prevents tenant context manipulation            ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                 │
│  ENTERPRISE OPTION: Dedicated Database                          │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │  Enterprise tenants can opt for completely separate DB      ││
│  │  Physical isolation with dedicated connection strings       ││
│  │  Premium add-on with additional security guarantees         ││
│  └─────────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Tenant Isolation Implementation
```php
<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // Automatically scope all queries to current tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            if ($tenantId = app('tenant.id')) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
            }
        });

        // Automatically set tenant_id on creation
        static::creating(function ($model) {
            if (!$model->tenant_id && $tenantId = app('tenant.id')) {
                $model->tenant_id = $tenantId;
            }
        });
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant\Tenant::class);
    }

    /**
     * Scope query without tenant isolation (for admin operations)
     */
    public function scopeWithoutTenantScope(Builder $builder): Builder
    {
        return $builder->withoutGlobalScope('tenant');
    }
}

// Middleware to set tenant context
class TenantContextMiddleware
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->tenant_id) {
            // Set tenant context for the entire request
            app()->instance('tenant.id', $user->tenant_id);
            app()->instance('tenant', $user->tenant);

            // Verify user belongs to claimed tenant
            if ($request->header('X-Tenant-ID') &&
                $request->header('X-Tenant-ID') !== $user->tenant_id) {
                abort(403, 'Tenant context mismatch');
            }
        }

        return $next($request);
    }
}
```

### 2.3 Cross-Tenant Access Prevention
```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Post\Post;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Ensure user can only access posts within their tenant
     */
    public function view(User $user, Post $post): bool
    {
        return $user->tenant_id === $post->tenant_id;
    }

    public function update(User $user, Post $post): bool
    {
        // Must be same tenant
        if ($user->tenant_id !== $post->tenant_id) {
            return false;
        }

        // Must have permission within workspace
        return $user->hasWorkspacePermission($post->workspace_id, 'post:edit');
    }

    public function delete(User $user, Post $post): bool
    {
        if ($user->tenant_id !== $post->tenant_id) {
            return false;
        }

        return $user->hasWorkspacePermission($post->workspace_id, 'post:delete');
    }
}
```

---

## 3. Data Encryption

### 3.1 Encryption Standards
```
┌─────────────────────────────────────────────────────────────────┐
│                  ENCRYPTION MATRIX                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  DATA AT REST                                                   │
│  ├── Database: AES-256 encryption (MySQL TDE)                   │
│  ├── File Storage: AES-256 (DigitalOcean Spaces)                │
│  ├── Backups: AES-256 encrypted before storage                  │
│  └── Sensitive Fields: Application-level encryption             │
│                                                                 │
│  DATA IN TRANSIT                                                │
│  ├── HTTPS: TLS 1.3 (minimum TLS 1.2)                           │
│  ├── API: Certificate pinning for mobile apps                   │
│  ├── Internal: Encrypted inter-service communication            │
│  └── Webhooks: HMAC signature verification                      │
│                                                                 │
│  SENSITIVE DATA HANDLING                                        │
│  ├── Passwords: Argon2id hashing                                │
│  ├── API Keys: HMAC-SHA256 hashed, only prefix visible          │
│  ├── OAuth Tokens: AES-256-GCM encrypted                        │
│  ├── PII: Field-level encryption with per-tenant keys           │
│  └── Credit Cards: Never stored (Razorpay handles)              │
└─────────────────────────────────────────────────────────────────┘
```

### 3.2 Application-Level Encryption
```php
<?php

namespace App\Services\Security;

use Illuminate\Contracts\Encryption\Encryptor;
use Illuminate\Support\Facades\Crypt;

class FieldEncryptionService
{
    private string $masterKey;

    public function __construct()
    {
        $this->masterKey = config('app.encryption_key');
    }

    /**
     * Encrypt sensitive field with tenant-specific key derivation
     */
    public function encryptForTenant(string $value, int $tenantId): string
    {
        $tenantKey = $this->deriveTenantKey($tenantId);

        return $this->encrypt($value, $tenantKey);
    }

    /**
     * Decrypt sensitive field
     */
    public function decryptForTenant(string $encrypted, int $tenantId): string
    {
        $tenantKey = $this->deriveTenantKey($tenantId);

        return $this->decrypt($encrypted, $tenantKey);
    }

    /**
     * Derive tenant-specific encryption key using HKDF
     */
    private function deriveTenantKey(int $tenantId): string
    {
        return hash_hkdf(
            'sha256',
            $this->masterKey,
            32,
            "tenant:{$tenantId}",
            random_bytes(16)
        );
    }

    private function encrypt(string $value, string $key): string
    {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt(
            $value,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return base64_encode($iv . $tag . $encrypted);
    }

    private function decrypt(string $encrypted, string $key): string
    {
        $data = base64_decode($encrypted);
        $iv = substr($data, 0, 16);
        $tag = substr($data, 16, 16);
        $ciphertext = substr($data, 32);

        return openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
    }
}

// Model trait for encrypted attributes
trait HasEncryptedAttributes
{
    protected array $encryptedAttributes = [];

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (in_array($key, $this->encryptedAttributes) && $value) {
            return app(FieldEncryptionService::class)
                ->decryptForTenant($value, $this->tenant_id);
        }

        return $value;
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->encryptedAttributes) && $value) {
            $value = app(FieldEncryptionService::class)
                ->encryptForTenant($value, $this->tenant_id ?? app('tenant.id'));
        }

        return parent::setAttribute($key, $value);
    }
}
```

### 3.3 OAuth Token Security
```php
<?php

namespace App\Models\Social;

use App\Models\Concerns\HasEncryptedAttributes;
use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    use HasEncryptedAttributes;

    // These fields are encrypted at application level
    protected array $encryptedAttributes = [
        'access_token',
        'refresh_token',
        'token_secret',
    ];

    // Token refresh with secure storage
    public function updateTokens(array $tokens): void
    {
        $this->update([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? $this->refresh_token,
            'token_expires_at' => $tokens['expires_at'] ?? null,
            'last_refreshed_at' => now(),
        ]);

        // Log token refresh for audit
        $this->logTokenRefresh();
    }

    private function logTokenRefresh(): void
    {
        \Log::channel('security')->info('OAuth token refreshed', [
            'social_account_id' => $this->id,
            'platform' => $this->platform,
            'tenant_id' => $this->tenant_id,
        ]);
    }
}
```

---

## 4. Access Control

### 4.1 Authentication Security
```
┌─────────────────────────────────────────────────────────────────┐
│               AUTHENTICATION SECURITY                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  PASSWORD POLICY                                                │
│  ├── Minimum 12 characters                                      │
│  ├── Must contain: uppercase, lowercase, number, symbol         │
│  ├── Cannot be in common password lists (10M+ entries)          │
│  ├── Cannot contain username or email                           │
│  └── Password history (last 10 passwords blocked)               │
│                                                                 │
│  MULTI-FACTOR AUTHENTICATION                                    │
│  ├── TOTP (Google Authenticator, Authy)                         │
│  ├── SMS (backup only, not recommended)                         │
│  ├── Email verification codes                                   │
│  ├── Recovery codes (10 single-use codes)                       │
│  └── Required for Super Admin, optional for tenants             │
│                                                                 │
│  SESSION SECURITY                                               │
│  ├── JWT tokens with 15-minute expiry                           │
│  ├── Refresh tokens with 7-day expiry                           │
│  ├── Single active session option                               │
│  ├── Session invalidation on password change                    │
│  └── Geographic anomaly detection                               │
│                                                                 │
│  BRUTE FORCE PROTECTION                                         │
│  ├── 5 failed attempts: 5-minute lockout                        │
│  ├── 10 failed attempts: 30-minute lockout                      │
│  ├── 20 failed attempts: Account locked, email notification     │
│  └── IP-based rate limiting: 100 attempts/hour                  │
└─────────────────────────────────────────────────────────────────┘
```

### 4.2 Authorization Model (RBAC)
```php
<?php

namespace App\Services\Authorization;

class PermissionService
{
    /**
     * Permission structure:
     * resource:action or resource:action:scope
     *
     * Examples:
     * - post:create
     * - post:publish:own
     * - workspace:manage:all
     */

    private const PERMISSIONS = [
        // Post permissions
        'post:view',
        'post:create',
        'post:edit:own',
        'post:edit:all',
        'post:delete:own',
        'post:delete:all',
        'post:publish',
        'post:schedule',

        // Social account permissions
        'social_account:view',
        'social_account:connect',
        'social_account:disconnect',
        'social_account:manage',

        // Analytics permissions
        'analytics:view',
        'analytics:export',
        'analytics:report:create',

        // Team permissions
        'user:view',
        'user:invite',
        'user:manage',
        'user:delete',

        // Workspace permissions
        'workspace:view',
        'workspace:create',
        'workspace:edit',
        'workspace:delete',
        'workspace:manage',

        // Billing permissions
        'billing:view',
        'billing:manage',
    ];

    private const ROLE_PERMISSIONS = [
        'viewer' => [
            'post:view',
            'analytics:view',
            'social_account:view',
        ],
        'contributor' => [
            'post:view',
            'post:create',
            'post:edit:own',
            'post:delete:own',
            'analytics:view',
            'social_account:view',
        ],
        'editor' => [
            'post:view',
            'post:create',
            'post:edit:all',
            'post:delete:all',
            'post:publish',
            'post:schedule',
            'analytics:view',
            'analytics:export',
            'social_account:view',
        ],
        'manager' => [
            'post:*',
            'analytics:*',
            'social_account:*',
            'user:view',
            'user:invite',
            'workspace:view',
            'workspace:edit',
        ],
        'admin' => [
            '*', // All permissions within tenant
        ],
    ];

    public function userCan(User $user, string $permission, $resource = null): bool
    {
        // Get user's role in relevant workspace
        $role = $user->getRoleInWorkspace($resource?->workspace_id);

        if (!$role) {
            return false;
        }

        // Check if role has permission
        return $this->roleHasPermission($role, $permission);
    }

    private function roleHasPermission(string $role, string $permission): bool
    {
        $rolePermissions = self::ROLE_PERMISSIONS[$role] ?? [];

        // Check for wildcard
        if (in_array('*', $rolePermissions)) {
            return true;
        }

        // Check for resource wildcard (e.g., post:*)
        [$resource, $action] = explode(':', $permission, 2);
        if (in_array("{$resource}:*", $rolePermissions)) {
            return true;
        }

        // Check exact permission
        return in_array($permission, $rolePermissions);
    }
}
```

---

## 5. Audit Logging

### 5.1 Comprehensive Audit Trail
```sql
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL UNIQUE,

    -- Context
    tenant_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    super_admin_id BIGINT UNSIGNED NULL,

    -- Action Details
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(100) NOT NULL,
    resource_id VARCHAR(100) NULL,

    -- Change Data
    old_values JSON NULL,
    new_values JSON NULL,

    -- Request Context
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    request_id VARCHAR(100) NULL,
    session_id VARCHAR(100) NULL,

    -- Location (if available)
    country_code CHAR(2) NULL,
    city VARCHAR(100) NULL,

    -- Classification
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
    category ENUM(
        'authentication',
        'authorization',
        'data_access',
        'data_modification',
        'configuration',
        'billing',
        'security',
        'admin_action'
    ) NOT NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_tenant (tenant_id, created_at),
    INDEX idx_user (user_id, created_at),
    INDEX idx_action (action, created_at),
    INDEX idx_resource (resource_type, resource_id),
    INDEX idx_severity (severity, created_at),
    INDEX idx_category (category, created_at)
);
```

### 5.2 Audit Events
```php
<?php

namespace App\Services\Audit;

class AuditService
{
    /**
     * Security-critical events that must be logged
     */
    private const CRITICAL_EVENTS = [
        // Authentication
        'auth.login.success',
        'auth.login.failed',
        'auth.logout',
        'auth.password.changed',
        'auth.mfa.enabled',
        'auth.mfa.disabled',
        'auth.session.revoked',

        // Authorization
        'permission.denied',
        'role.assigned',
        'role.removed',

        // Data Access
        'data.export',
        'data.bulk_download',
        'api.key.created',
        'api.key.revoked',

        // Social Accounts
        'social.account.connected',
        'social.account.disconnected',
        'social.token.refreshed',

        // Billing
        'subscription.created',
        'subscription.cancelled',
        'payment.processed',
        'payment.failed',

        // Admin Actions
        'admin.impersonation.started',
        'admin.impersonation.ended',
        'admin.tenant.suspended',
        'admin.tenant.activated',
    ];

    public function log(string $action, array $context = []): void
    {
        $entry = [
            'uuid' => \Str::uuid(),
            'tenant_id' => app('tenant.id'),
            'user_id' => auth()->id(),
            'super_admin_id' => $context['super_admin_id'] ?? null,
            'action' => $action,
            'resource_type' => $context['resource_type'] ?? null,
            'resource_id' => $context['resource_id'] ?? null,
            'old_values' => $context['old_values'] ?? null,
            'new_values' => $context['new_values'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'request_id' => request()->header('X-Request-ID'),
            'session_id' => session()->getId(),
            'severity' => $this->determineSeverity($action),
            'category' => $this->determineCategory($action),
        ];

        // Async write for performance
        dispatch(new WriteAuditLog($entry));

        // Alert on critical events
        if ($this->isCritical($action)) {
            dispatch(new AlertSecurityTeam($entry));
        }
    }

    private function determineSeverity(string $action): string
    {
        $critical = ['auth.login.failed', 'permission.denied', 'admin.impersonation'];
        $high = ['auth.password.changed', 'data.export', 'subscription.cancelled'];
        $medium = ['social.account.connected', 'role.assigned'];

        foreach ($critical as $pattern) {
            if (str_contains($action, $pattern)) return 'critical';
        }
        foreach ($high as $pattern) {
            if (str_contains($action, $pattern)) return 'high';
        }
        foreach ($medium as $pattern) {
            if (str_contains($action, $pattern)) return 'medium';
        }

        return 'low';
    }

    private function determineCategory(string $action): string
    {
        $prefix = explode('.', $action)[0];

        return match ($prefix) {
            'auth' => 'authentication',
            'permission', 'role' => 'authorization',
            'data' => 'data_access',
            'post', 'media' => 'data_modification',
            'config', 'setting' => 'configuration',
            'subscription', 'payment' => 'billing',
            'security' => 'security',
            'admin' => 'admin_action',
            default => 'data_access',
        };
    }

    private function isCritical(string $action): bool
    {
        return in_array($action, self::CRITICAL_EVENTS);
    }
}
```

---

## 6. Data Privacy (GDPR & DPDP Compliance)

### 6.1 Privacy Rights Implementation
```
┌─────────────────────────────────────────────────────────────────┐
│              DATA SUBJECT RIGHTS                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  RIGHT TO ACCESS                                                │
│  ├── Tenant can export all their data                           │
│  ├── Export includes: users, posts, media, analytics            │
│  ├── Available in JSON or CSV format                            │
│  └── Processed within 72 hours                                  │
│                                                                 │
│  RIGHT TO RECTIFICATION                                         │
│  ├── Users can update their profile anytime                     │
│  ├── Tenant admins can modify any user data                     │
│  └── Changes logged in audit trail                              │
│                                                                 │
│  RIGHT TO ERASURE (Right to be Forgotten)                       │
│  ├── User can request account deletion                          │
│  ├── Tenant can request complete data deletion                  │
│  ├── 30-day grace period before permanent deletion              │
│  └── Some data retained for legal compliance (audit logs)       │
│                                                                 │
│  RIGHT TO PORTABILITY                                           │
│  ├── Data export in machine-readable format                     │
│  ├── Standard JSON schema for interoperability                  │
│  └── Includes all user-generated content                        │
│                                                                 │
│  RIGHT TO RESTRICT PROCESSING                                   │
│  ├── Tenant can pause analytics collection                      │
│  ├── User can opt-out of specific data processing               │
│  └── Core functionality maintained                              │
└─────────────────────────────────────────────────────────────────┘
```

### 6.2 Data Retention Policy
```php
<?php

namespace App\Services\Privacy;

class DataRetentionService
{
    private const RETENTION_PERIODS = [
        // Active data - retained while account active
        'users' => null, // Retained until deletion request
        'posts' => null,
        'social_accounts' => null,
        'media' => null,

        // Analytics - time-limited retention
        'analytics_daily' => 730, // 2 years
        'analytics_hourly' => 90, // 3 months

        // Logs - compliance-driven retention
        'audit_logs' => 2555, // 7 years (legal requirement)
        'access_logs' => 365, // 1 year
        'error_logs' => 90, // 3 months

        // Temporary data
        'sessions' => 7, // 7 days
        'password_resets' => 1, // 24 hours
        'email_verifications' => 7, // 7 days

        // After account deletion
        'deleted_user_data' => 30, // 30-day recovery window
        'deleted_tenant_data' => 30,
    ];

    public function enforceRetention(): void
    {
        foreach (self::RETENTION_PERIODS as $dataType => $days) {
            if ($days === null) continue; // No automatic deletion

            $this->deleteOldData($dataType, $days);
        }
    }

    private function deleteOldData(string $dataType, int $days): void
    {
        $cutoffDate = now()->subDays($days);

        switch ($dataType) {
            case 'analytics_hourly':
                \DB::table('analytics_hourly')
                    ->where('created_at', '<', $cutoffDate)
                    ->delete();
                break;

            case 'audit_logs':
                // Never delete - only archive
                \DB::table('audit_logs')
                    ->where('created_at', '<', $cutoffDate)
                    ->where('archived', false)
                    ->update(['archived' => true]);
                break;

            case 'sessions':
                \DB::table('sessions')
                    ->where('last_activity', '<', $cutoffDate->timestamp)
                    ->delete();
                break;

            // ... other data types
        }
    }
}
```

### 6.3 Data Export
```php
<?php

namespace App\Services\Privacy;

use App\Jobs\Privacy\ProcessDataExport;
use Illuminate\Support\Facades\Storage;

class DataExportService
{
    public function requestExport(int $tenantId, array $options = []): string
    {
        $exportId = \Str::uuid();

        // Create export request record
        \DB::table('data_export_requests')->insert([
            'id' => $exportId,
            'tenant_id' => $tenantId,
            'requested_by' => auth()->id(),
            'status' => 'pending',
            'include_media' => $options['include_media'] ?? false,
            'format' => $options['format'] ?? 'json',
            'created_at' => now(),
        ]);

        // Queue export job
        dispatch(new ProcessDataExport($exportId));

        return $exportId;
    }

    public function processExport(string $exportId): void
    {
        $request = \DB::table('data_export_requests')->find($exportId);
        $tenantId = $request->tenant_id;

        $data = [
            'export_info' => [
                'export_id' => $exportId,
                'tenant_id' => $tenantId,
                'generated_at' => now()->toISOString(),
                'format_version' => '1.0',
            ],
            'tenant' => $this->exportTenantData($tenantId),
            'users' => $this->exportUsers($tenantId),
            'workspaces' => $this->exportWorkspaces($tenantId),
            'social_accounts' => $this->exportSocialAccounts($tenantId),
            'posts' => $this->exportPosts($tenantId),
            'analytics' => $this->exportAnalytics($tenantId),
        ];

        // Generate file
        $filename = "export_{$tenantId}_{$exportId}.json";
        $path = "exports/{$tenantId}/{$filename}";

        Storage::put($path, json_encode($data, JSON_PRETTY_PRINT));

        // Encrypt the file
        $this->encryptExportFile($path);

        // Update request status
        \DB::table('data_export_requests')
            ->where('id', $exportId)
            ->update([
                'status' => 'completed',
                'file_path' => $path,
                'completed_at' => now(),
                'expires_at' => now()->addDays(7),
            ]);

        // Notify tenant
        $this->notifyExportReady($request);
    }
}
```

---

## 7. Security Monitoring

### 7.1 Real-time Threat Detection
```php
<?php

namespace App\Services\Security;

use App\Events\Security\ThreatDetected;
use Illuminate\Support\Facades\Redis;

class ThreatDetectionService
{
    private const THRESHOLDS = [
        'failed_logins' => ['count' => 5, 'window' => 300], // 5 in 5 minutes
        'api_requests' => ['count' => 1000, 'window' => 60], // 1000 in 1 minute
        'data_exports' => ['count' => 3, 'window' => 3600], // 3 in 1 hour
        'permission_denied' => ['count' => 10, 'window' => 60], // 10 in 1 minute
    ];

    public function checkThreat(string $type, string $identifier): bool
    {
        $threshold = self::THRESHOLDS[$type] ?? null;

        if (!$threshold) {
            return false;
        }

        $key = "threat:{$type}:{$identifier}";
        $count = Redis::incr($key);

        if ($count === 1) {
            Redis::expire($key, $threshold['window']);
        }

        if ($count >= $threshold['count']) {
            $this->handleThreat($type, $identifier, $count);
            return true;
        }

        return false;
    }

    private function handleThreat(string $type, string $identifier, int $count): void
    {
        // Log the threat
        \Log::channel('security')->warning('Threat detected', [
            'type' => $type,
            'identifier' => $identifier,
            'count' => $count,
        ]);

        // Take action based on threat type
        switch ($type) {
            case 'failed_logins':
                $this->lockAccount($identifier);
                break;

            case 'api_requests':
                $this->throttleApiAccess($identifier);
                break;

            case 'permission_denied':
                $this->flagSuspiciousActivity($identifier);
                break;
        }

        // Dispatch event for real-time monitoring
        event(new ThreatDetected($type, $identifier, $count));
    }

    private function lockAccount(string $userId): void
    {
        \DB::table('users')
            ->where('id', $userId)
            ->update([
                'locked_at' => now(),
                'locked_reason' => 'Too many failed login attempts',
            ]);
    }
}
```

### 7.2 Security Alerts
```
┌─────────────────────────────────────────────────────────────────┐
│              SECURITY ALERT LEVELS                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  LEVEL 1: INFO (No action required)                             │
│  ├── New device login                                           │
│  ├── Password changed                                           │
│  └── MFA enabled/disabled                                       │
│                                                                 │
│  LEVEL 2: WARNING (Review within 24h)                           │
│  ├── Login from new country                                     │
│  ├── Unusual API usage pattern                                  │
│  ├── Failed permission checks                                   │
│  └── Large data export                                          │
│                                                                 │
│  LEVEL 3: HIGH (Review within 1h)                               │
│  ├── Multiple failed login attempts                             │
│  ├── Admin impersonation started                                │
│  ├── Billing information changed                                │
│  └── API key created/revoked                                    │
│                                                                 │
│  LEVEL 4: CRITICAL (Immediate action)                           │
│  ├── Brute force attack detected                                │
│  ├── Account takeover attempt                                   │
│  ├── Data breach indicators                                     │
│  ├── Unauthorized admin access                                  │
│  └── Mass data deletion                                         │
└─────────────────────────────────────────────────────────────────┘
```

---

## 8. Trust Center

### 8.1 Public Trust Page Content
```
┌─────────────────────────────────────────────────────────────────┐
│                    TRUST CENTER                                 │
│                 trust.bizsocials.com                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  SECURITY                                                       │
│  ├── SOC 2 Type II Certified                                    │
│  ├── ISO 27001 (In Progress)                                    │
│  ├── Penetration tested annually                                │
│  ├── Bug bounty program                                         │
│  └── Download our security whitepaper                           │
│                                                                 │
│  PRIVACY                                                        │
│  ├── GDPR Compliant                                             │
│  ├── India DPDP Act Compliant                                   │
│  ├── Privacy Policy                                             │
│  ├── Cookie Policy                                              │
│  └── Data Processing Agreement (DPA)                            │
│                                                                 │
│  INFRASTRUCTURE                                                 │
│  ├── DigitalOcean (SOC 2, ISO 27001)                            │
│  ├── Data Centers: India, Singapore                             │
│  ├── 99.9% Uptime SLA                                           │
│  └── Real-time status: status.bizsocials.com                    │
│                                                                 │
│  DATA PRACTICES                                                 │
│  ├── Your data is NEVER sold                                    │
│  ├── Your data is NEVER shared with third parties               │
│  ├── Your data is NEVER used to train AI models                 │
│  ├── Complete data isolation between tenants                    │
│  └── You own your data, always                                  │
│                                                                 │
│  DOCUMENTS                                                      │
│  ├── Terms of Service                                           │
│  ├── Privacy Policy                                             │
│  ├── Data Processing Agreement                                  │
│  ├── Acceptable Use Policy                                      │
│  ├── Security Whitepaper                                        │
│  └── Subprocessor List                                          │
└─────────────────────────────────────────────────────────────────┘
```

### 8.2 Data Processing Agreement Template
```markdown
# Data Processing Agreement

This Data Processing Agreement ("DPA") forms part of the Agreement between
BizSocials (Bizinso Technologies Private Limited) and the Customer.

## 1. Definitions
- "Customer Data" means all data provided by Customer to BizSocials
- "Processing" means any operation performed on Customer Data
- "Subprocessor" means any third party engaged by BizSocials

## 2. Data Processing

### 2.1 Scope of Processing
BizSocials will process Customer Data solely for:
- Providing the Services as described in the Agreement
- Maintaining and improving the Services
- Complying with legal obligations

### 2.2 Customer Instructions
BizSocials will process Customer Data only in accordance with Customer's
documented instructions, unless required by law.

## 3. Data Security

### 3.1 Security Measures
BizSocials implements appropriate technical and organizational measures:
- Encryption of data at rest and in transit
- Access controls and authentication
- Regular security assessments
- Incident response procedures

### 3.2 Confidentiality
BizSocials ensures that personnel authorized to process Customer Data:
- Have committed to confidentiality
- Receive appropriate data protection training

## 4. Subprocessors

### 4.1 Current Subprocessors
- DigitalOcean LLC (Cloud Infrastructure)
- Razorpay Software Pvt Ltd (Payment Processing)
- Amazon Web Services (Email Delivery via SES)

### 4.2 Subprocessor Changes
BizSocials will notify Customer of new subprocessors with 30 days notice.

## 5. Data Subject Rights

BizSocials will assist Customer in responding to data subject requests
for access, rectification, erasure, or portability.

## 6. Data Breach Notification

BizSocials will notify Customer of any data breach within 72 hours
of becoming aware of the breach.

## 7. Data Deletion

Upon termination, BizSocials will delete all Customer Data within
30 days, unless retention is required by law.

## 8. Audit Rights

Customer may audit BizSocials' compliance with this DPA upon
reasonable notice, not more than once per year.
```

---

## 9. Incident Response

### 9.1 Incident Response Plan
```
┌─────────────────────────────────────────────────────────────────┐
│               INCIDENT RESPONSE PHASES                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  PHASE 1: DETECTION (0-15 minutes)                              │
│  ├── Automated monitoring triggers alert                        │
│  ├── On-call engineer acknowledges                              │
│  ├── Initial severity assessment                                │
│  └── Incident commander assigned                                │
│                                                                 │
│  PHASE 2: CONTAINMENT (15-60 minutes)                           │
│  ├── Isolate affected systems                                   │
│  ├── Preserve evidence                                          │
│  ├── Prevent further damage                                     │
│  └── Communicate with stakeholders                              │
│                                                                 │
│  PHASE 3: INVESTIGATION (1-24 hours)                            │
│  ├── Root cause analysis                                        │
│  ├── Scope determination                                        │
│  ├── Impact assessment                                          │
│  └── Documentation                                              │
│                                                                 │
│  PHASE 4: REMEDIATION (24-72 hours)                             │
│  ├── Fix vulnerabilities                                        │
│  ├── Restore services                                           │
│  ├── Verify security                                            │
│  └── Customer notification (if required)                        │
│                                                                 │
│  PHASE 5: POST-INCIDENT (1-2 weeks)                             │
│  ├── Post-mortem analysis                                       │
│  ├── Lessons learned                                            │
│  ├── Process improvements                                       │
│  └── Final report                                               │
└─────────────────────────────────────────────────────────────────┘
```

---

## 10. Compliance Certifications

### 10.1 Current & Planned Certifications
```
┌─────────────────────────────────────────────────────────────────┐
│                  COMPLIANCE ROADMAP                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  CURRENT                                                        │
│  ├── GDPR Compliant                                             │
│  └── India DPDP Act Compliant                                   │
│                                                                 │
│  PHASE 1 (Year 1)                                               │
│  ├── SOC 2 Type I                                               │
│  └── PCI DSS (via Razorpay)                                     │
│                                                                 │
│  PHASE 2 (Year 2)                                               │
│  ├── SOC 2 Type II                                              │
│  └── ISO 27001                                                  │
│                                                                 │
│  PHASE 3 (Year 3)                                               │
│  ├── ISO 27701 (Privacy)                                        │
│  └── CSA STAR                                                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 11. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | 2025-02-06 | System | Initial specification |
