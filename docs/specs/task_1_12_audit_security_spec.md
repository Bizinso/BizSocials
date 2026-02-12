# Task 1.12: Audit & Security Migrations - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 1.12 Audit & Security Migrations
- **Dependencies**: Task 1.1 (Platform Admin), Task 1.2 (Tenant Management), Task 1.3 (User & Auth)

---

## 1. Overview

This task implements the Audit Logging and Security systems for tracking user activities, security events, and maintaining compliance records. These are critical for enterprise customers and platform security.

### Entities to Implement
1. **AuditLog** - General audit trail for all actions
2. **SecurityEvent** - Security-related events (login attempts, suspicious activity)
3. **LoginHistory** - User login/logout history
4. **ApiAccessLog** - API request logging
5. **DataExportRequest** - GDPR data export requests
6. **DataDeletionRequest** - GDPR data deletion requests
7. **IpWhitelist** - IP address whitelisting for tenants
8. **SessionHistory** - Active and historical user sessions

---

## 2. Enums

### 2.1 AuditAction Enum
**File**: `app/Enums/Audit/AuditAction.php`

```php
enum AuditAction: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case RESTORE = 'restore';
    case VIEW = 'view';
    case EXPORT = 'export';
    case IMPORT = 'import';
    case LOGIN = 'login';
    case LOGOUT = 'logout';
    case PERMISSION_CHANGE = 'permission_change';
    case SETTINGS_CHANGE = 'settings_change';
    case SUBSCRIPTION_CHANGE = 'subscription_change';

    public function label(): string;
    public function isWrite(): bool;  // CREATE, UPDATE, DELETE
    public function isRead(): bool;   // VIEW, EXPORT
    public function isAuth(): bool;   // LOGIN, LOGOUT
}
```

### 2.2 AuditableType Enum
**File**: `app/Enums/Audit/AuditableType.php`

```php
enum AuditableType: string
{
    case USER = 'user';
    case TENANT = 'tenant';
    case WORKSPACE = 'workspace';
    case SOCIAL_ACCOUNT = 'social_account';
    case POST = 'post';
    case SUBSCRIPTION = 'subscription';
    case INVOICE = 'invoice';
    case SUPPORT_TICKET = 'support_ticket';
    case API_KEY = 'api_key';
    case SETTINGS = 'settings';
    case OTHER = 'other';

    public function label(): string;
    public function modelClass(): ?string;
}
```

### 2.3 SecurityEventType Enum
**File**: `app/Enums/Audit/SecurityEventType.php`

```php
enum SecurityEventType: string
{
    case LOGIN_SUCCESS = 'login_success';
    case LOGIN_FAILURE = 'login_failure';
    case LOGOUT = 'logout';
    case PASSWORD_CHANGE = 'password_change';
    case PASSWORD_RESET_REQUEST = 'password_reset_request';
    case PASSWORD_RESET_COMPLETE = 'password_reset_complete';
    case MFA_ENABLED = 'mfa_enabled';
    case MFA_DISABLED = 'mfa_disabled';
    case MFA_CHALLENGE_SUCCESS = 'mfa_challenge_success';
    case MFA_CHALLENGE_FAILURE = 'mfa_challenge_failure';
    case SUSPICIOUS_ACTIVITY = 'suspicious_activity';
    case ACCOUNT_LOCKED = 'account_locked';
    case ACCOUNT_UNLOCKED = 'account_unlocked';
    case SESSION_INVALIDATED = 'session_invalidated';
    case API_KEY_CREATED = 'api_key_created';
    case API_KEY_REVOKED = 'api_key_revoked';
    case IP_BLOCKED = 'ip_blocked';
    case IP_WHITELISTED = 'ip_whitelisted';

    public function label(): string;
    public function severity(): string;  // info, warning, critical
    public function requiresAlert(): bool;
}
```

### 2.4 SecuritySeverity Enum
**File**: `app/Enums/Audit/SecuritySeverity.php`

```php
enum SecuritySeverity: string
{
    case INFO = 'info';
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function label(): string;
    public function color(): string;
    public function weight(): int;  // 1-5
}
```

### 2.5 DataRequestType Enum
**File**: `app/Enums/Audit/DataRequestType.php`

```php
enum DataRequestType: string
{
    case EXPORT = 'export';
    case DELETION = 'deletion';
    case RECTIFICATION = 'rectification';
    case ACCESS = 'access';

    public function label(): string;
    public function gdprArticle(): string;  // Article 15, 17, 16, 15
}
```

### 2.6 DataRequestStatus Enum
**File**: `app/Enums/Audit/DataRequestStatus.php`

```php
enum DataRequestStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function label(): string;
    public function isFinal(): bool;  // COMPLETED, FAILED, CANCELLED
}
```

### 2.7 SessionStatus Enum
**File**: `app/Enums/Audit/SessionStatus.php`

```php
enum SessionStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case REVOKED = 'revoked';
    case LOGGED_OUT = 'logged_out';

    public function label(): string;
    public function isActive(): bool;
}
```

---

## 3. Migrations

### 3.1 Create Audit Logs Table
**File**: `database/migrations/2026_02_06_1200001_create_audit_logs_table.php`

```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id')->nullable();
    $table->uuid('user_id')->nullable();
    $table->uuid('admin_id')->nullable();
    $table->string('action', 30);  // AuditAction
    $table->string('auditable_type', 50);  // AuditableType
    $table->uuid('auditable_id')->nullable();
    $table->text('description')->nullable();
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->json('metadata')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->string('user_agent', 500)->nullable();
    $table->string('request_id', 50)->nullable();
    $table->timestamps();

    // Indexes
    $table->index('tenant_id');
    $table->index('user_id');
    $table->index('action');
    $table->index('auditable_type');
    $table->index(['auditable_type', 'auditable_id']);
    $table->index('created_at');
    $table->index('request_id');

    // Foreign keys
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->nullOnDelete();

    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->nullOnDelete();

    $table->foreign('admin_id')
        ->references('id')
        ->on('super_admin_users')
        ->nullOnDelete();
});
```

### 3.2 Create Security Events Table
**File**: `database/migrations/2026_02_06_1200002_create_security_events_table.php`

```php
Schema::create('security_events', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id')->nullable();
    $table->uuid('user_id')->nullable();
    $table->string('event_type', 30);  // SecurityEventType
    $table->string('severity', 10)->default('info');  // SecuritySeverity
    $table->text('description')->nullable();
    $table->json('metadata')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->string('user_agent', 500)->nullable();
    $table->string('country_code', 2)->nullable();
    $table->string('city', 100)->nullable();
    $table->boolean('is_resolved')->default(false);
    $table->uuid('resolved_by')->nullable();
    $table->timestamp('resolved_at')->nullable();
    $table->text('resolution_notes')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('tenant_id');
    $table->index('user_id');
    $table->index('event_type');
    $table->index('severity');
    $table->index('ip_address');
    $table->index('created_at');
    $table->index(['event_type', 'severity']);

    // Foreign keys
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->nullOnDelete();

    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->nullOnDelete();

    $table->foreign('resolved_by')
        ->references('id')
        ->on('super_admin_users')
        ->nullOnDelete();
});
```

### 3.3 Create Login History Table
**File**: `database/migrations/2026_02_06_1200003_create_login_history_table.php`

```php
Schema::create('login_history', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id');
    $table->uuid('tenant_id')->nullable();
    $table->boolean('successful')->default(true);
    $table->string('ip_address', 45)->nullable();
    $table->string('user_agent', 500)->nullable();
    $table->string('device_type', 20)->nullable();  // desktop, mobile, tablet
    $table->string('browser', 50)->nullable();
    $table->string('os', 50)->nullable();
    $table->string('country_code', 2)->nullable();
    $table->string('city', 100)->nullable();
    $table->string('failure_reason', 100)->nullable();
    $table->timestamp('logged_out_at')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('user_id');
    $table->index('tenant_id');
    $table->index('ip_address');
    $table->index('successful');
    $table->index('created_at');

    // Foreign keys
    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();

    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->nullOnDelete();
});
```

### 3.4 Create API Access Logs Table
**File**: `database/migrations/2026_02_06_1200004_create_api_access_logs_table.php`

```php
Schema::create('api_access_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id')->nullable();
    $table->uuid('user_id')->nullable();
    $table->uuid('api_key_id')->nullable();
    $table->string('method', 10);
    $table->string('endpoint', 500);
    $table->integer('status_code');
    $table->integer('response_time_ms')->nullable();
    $table->bigInteger('request_size_bytes')->nullable();
    $table->bigInteger('response_size_bytes')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->string('user_agent', 500)->nullable();
    $table->json('request_headers')->nullable();
    $table->json('request_params')->nullable();
    $table->text('error_message')->nullable();
    $table->string('request_id', 50)->nullable();
    $table->timestamps();

    // Indexes
    $table->index('tenant_id');
    $table->index('user_id');
    $table->index('api_key_id');
    $table->index('endpoint');
    $table->index('status_code');
    $table->index('created_at');
    $table->index('request_id');

    // Foreign keys
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->nullOnDelete();

    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->nullOnDelete();
});
```

### 3.5 Create Data Export Requests Table
**File**: `database/migrations/2026_02_06_1200005_create_data_export_requests_table.php`

```php
Schema::create('data_export_requests', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id');
    $table->uuid('user_id')->nullable();
    $table->uuid('requested_by');
    $table->string('request_type', 20)->default('export');  // DataRequestType
    $table->string('status', 20)->default('pending');  // DataRequestStatus
    $table->json('data_categories')->nullable();  // What data to export
    $table->string('format', 10)->default('json');  // json, csv, xml
    $table->string('file_path', 500)->nullable();
    $table->bigInteger('file_size_bytes')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->integer('download_count')->default(0);
    $table->timestamp('completed_at')->nullable();
    $table->text('failure_reason')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('tenant_id');
    $table->index('user_id');
    $table->index('status');
    $table->index('created_at');

    // Foreign keys
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->cascadeOnDelete();

    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->nullOnDelete();

    $table->foreign('requested_by')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();
});
```

### 3.6 Create Data Deletion Requests Table
**File**: `database/migrations/2026_02_06_1200006_create_data_deletion_requests_table.php`

```php
Schema::create('data_deletion_requests', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id');
    $table->uuid('user_id')->nullable();
    $table->uuid('requested_by');
    $table->string('status', 20)->default('pending');  // DataRequestStatus
    $table->json('data_categories')->nullable();  // What data to delete
    $table->text('reason')->nullable();
    $table->boolean('requires_approval')->default(true);
    $table->uuid('approved_by')->nullable();
    $table->timestamp('approved_at')->nullable();
    $table->timestamp('scheduled_for')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->json('deletion_summary')->nullable();  // What was deleted
    $table->text('failure_reason')->nullable();
    $table->timestamps();

    // Indexes
    $table->index('tenant_id');
    $table->index('user_id');
    $table->index('status');
    $table->index('scheduled_for');
    $table->index('created_at');

    // Foreign keys
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->cascadeOnDelete();

    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->nullOnDelete();

    $table->foreign('requested_by')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();

    $table->foreign('approved_by')
        ->references('id')
        ->on('super_admin_users')
        ->nullOnDelete();
});
```

### 3.7 Create IP Whitelist Table
**File**: `database/migrations/2026_02_06_1200007_create_ip_whitelist_table.php`

```php
Schema::create('ip_whitelist', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id');
    $table->string('ip_address', 45);
    $table->string('cidr_range', 50)->nullable();  // For IP ranges
    $table->string('label', 100)->nullable();
    $table->text('description')->nullable();
    $table->boolean('is_active')->default(true);
    $table->uuid('created_by');
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();

    // Unique constraint
    $table->unique(['tenant_id', 'ip_address']);

    // Indexes
    $table->index('tenant_id');
    $table->index('ip_address');
    $table->index('is_active');

    // Foreign keys
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->cascadeOnDelete();

    $table->foreign('created_by')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();
});
```

### 3.8 Create Session History Table
**File**: `database/migrations/2026_02_06_1200008_create_session_history_table.php`

```php
Schema::create('session_history', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id');
    $table->uuid('tenant_id')->nullable();
    $table->string('session_token', 100)->unique();
    $table->string('status', 20)->default('active');  // SessionStatus
    $table->string('ip_address', 45)->nullable();
    $table->string('user_agent', 500)->nullable();
    $table->string('device_type', 20)->nullable();
    $table->string('device_name', 100)->nullable();
    $table->string('browser', 50)->nullable();
    $table->string('os', 50)->nullable();
    $table->string('country_code', 2)->nullable();
    $table->string('city', 100)->nullable();
    $table->boolean('is_current')->default(false);
    $table->timestamp('last_activity_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamp('revoked_at')->nullable();
    $table->uuid('revoked_by')->nullable();
    $table->string('revocation_reason', 100)->nullable();
    $table->timestamps();

    // Indexes
    $table->index('user_id');
    $table->index('tenant_id');
    $table->index('session_token');
    $table->index('status');
    $table->index('last_activity_at');
    $table->index('expires_at');

    // Foreign keys
    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();

    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->nullOnDelete();

    $table->foreign('revoked_by')
        ->references('id')
        ->on('users')
        ->nullOnDelete();
});
```

---

## 4. Models

### 4.1 AuditLog Model
**File**: `app/Models/Audit/AuditLog.php`

Key methods:
- Relationships: `tenant()`, `user()`, `admin()`, `auditable()`
- Scopes: `forTenant()`, `forUser()`, `byAction()`, `byType()`, `recent()`, `search()`, `inDateRange()`
- Helpers: `isCreate()`, `isUpdate()`, `isDelete()`, `getChangedFields()`, `getOldValue()`, `getNewValue()`

### 4.2 SecurityEvent Model
**File**: `app/Models/Audit/SecurityEvent.php`

Key methods:
- Relationships: `tenant()`, `user()`, `resolver()`
- Scopes: `forTenant()`, `forUser()`, `byType()`, `bySeverity()`, `unresolved()`, `critical()`, `recent()`, `fromIp()`
- Helpers: `isResolved()`, `isCritical()`, `resolve()`, `requiresAlert()`

### 4.3 LoginHistory Model
**File**: `app/Models/Audit/LoginHistory.php`

Key methods:
- Relationships: `user()`, `tenant()`
- Scopes: `forUser()`, `successful()`, `failed()`, `fromIp()`, `recent()`, `byDevice()`
- Helpers: `isSuccessful()`, `isFailed()`, `getDeviceInfo()`, `getLocationInfo()`, `logout()`

### 4.4 ApiAccessLog Model
**File**: `app/Models/Audit/ApiAccessLog.php`

Key methods:
- Relationships: `tenant()`, `user()`
- Scopes: `forTenant()`, `forUser()`, `byEndpoint()`, `byStatus()`, `errors()`, `slow()`, `recent()`
- Helpers: `isSuccess()`, `isError()`, `isSlow()`, `getFormattedResponseTime()`

### 4.5 DataExportRequest Model
**File**: `app/Models/Audit/DataExportRequest.php`

Key methods:
- Relationships: `tenant()`, `user()`, `requester()`
- Scopes: `forTenant()`, `forUser()`, `pending()`, `completed()`, `expired()`, `byType()`
- Helpers: `isPending()`, `isCompleted()`, `isExpired()`, `start()`, `complete()`, `fail()`, `incrementDownloadCount()`, `getDownloadUrl()`

### 4.6 DataDeletionRequest Model
**File**: `app/Models/Audit/DataDeletionRequest.php`

Key methods:
- Relationships: `tenant()`, `user()`, `requester()`, `approver()`
- Scopes: `forTenant()`, `forUser()`, `pending()`, `approved()`, `scheduled()`, `completed()`
- Helpers: `isPending()`, `isApproved()`, `needsApproval()`, `approve()`, `complete()`, `fail()`, `cancel()`

### 4.7 IpWhitelist Model
**File**: `app/Models/Audit/IpWhitelist.php`

Key methods:
- Relationships: `tenant()`, `creator()`
- Scopes: `forTenant()`, `active()`, `expired()`, `byIp()`, `ordered()`
- Helpers: `isActive()`, `isExpired()`, `containsIp()`, `deactivate()`, `activate()`

### 4.8 SessionHistory Model
**File**: `app/Models/Audit/SessionHistory.php`

Key methods:
- Relationships: `user()`, `tenant()`, `revoker()`
- Scopes: `forUser()`, `active()`, `expired()`, `revoked()`, `current()`, `byDevice()`, `ordered()`
- Helpers: `isActive()`, `isExpired()`, `isCurrent()`, `revoke()`, `touch()`, `markAsCurrent()`

---

## 5. Factories & Seeders

### Factories
- `AuditLogFactory` - with states for actions, types
- `SecurityEventFactory` - with states for event types, severities
- `LoginHistoryFactory` - with states for successful/failed, devices
- `ApiAccessLogFactory` - with states for status codes, methods
- `DataExportRequestFactory` - with states for statuses
- `DataDeletionRequestFactory` - with states for statuses, approval
- `IpWhitelistFactory` - with states for active/expired
- `SessionHistoryFactory` - with states for active/expired/revoked

### Seeders
- `AuditLogSeeder` - Create sample audit logs
- `SecurityEventSeeder` - Create sample security events
- `AuditSecuritySeeder` (Orchestrator)

---

## 6. Test Requirements

Create tests for:
- 7 enum tests
- 8 model tests

---

## 7. Implementation Checklist

- [ ] Create AuditAction enum
- [ ] Create AuditableType enum
- [ ] Create SecurityEventType enum
- [ ] Create SecuritySeverity enum
- [ ] Create DataRequestType enum
- [ ] Create DataRequestStatus enum
- [ ] Create SessionStatus enum
- [ ] Create audit_logs migration
- [ ] Create security_events migration
- [ ] Create login_history migration
- [ ] Create api_access_logs migration
- [ ] Create data_export_requests migration
- [ ] Create data_deletion_requests migration
- [ ] Create ip_whitelist migration
- [ ] Create session_history migration
- [ ] Create all 8 models
- [ ] Create all factories
- [ ] Create all seeders
- [ ] Create all unit tests
- [ ] Run migrations and seeders
- [ ] All tests pass

---

## 8. Notes

1. **High Volume Tables**: `audit_logs`, `api_access_logs`, and `login_history` can grow very large. Consider implementing archival/partitioning strategies.

2. **GDPR Compliance**: Data export and deletion requests fulfill GDPR requirements for data portability (Article 20) and right to erasure (Article 17).

3. **Security Events**: Critical events should trigger alerts. The `requiresAlert()` method determines if immediate notification is needed.

4. **Session Management**: Users can view and revoke their active sessions from other devices.

5. **IP Whitelisting**: Enterprise feature allowing tenants to restrict access to specific IP addresses.

6. **Request ID Tracking**: `request_id` field allows correlating audit logs and API logs for the same request.

7. **Geolocation**: Store country and city information for security analysis. Consider using a GeoIP service.

8. **Retention Policy**: Define data retention periods for different log types. Implement cleanup jobs.
