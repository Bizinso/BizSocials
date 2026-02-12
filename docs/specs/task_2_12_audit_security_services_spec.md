# Task 2.12: Audit & Security Services & API - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Task**: 2.12 Audit & Security Services & API
- **Dependencies**: Task 2.1, Task 1.12 (Audit Migrations)

---

## 1. Overview

This task implements audit logging, security monitoring, and GDPR compliance services. It covers audit trails, login history, security events, and data export/deletion requests.

### Components to Implement
1. **AuditLogService** - Audit trail management
2. **SecurityService** - Security event monitoring
3. **LoginHistoryService** - Login tracking
4. **DataPrivacyService** - GDPR compliance (export/delete)
5. **Controllers** - API endpoints
6. **Data Classes** - Request/response DTOs

---

## 2. Services

### 2.1 AuditLogService
**File**: `app/Services/Audit/AuditLogService.php`

```php
final class AuditLogService extends BaseService
{
    public function log(AuditAction $action, Model $auditable, ?User $user = null, ?array $changes = null): AuditLog;
    public function listForTenant(Tenant $tenant, array $filters = []): LengthAwarePaginator;
    public function listForUser(User $user, array $filters = []): LengthAwarePaginator;
    public function listForAuditable(Model $auditable): Collection;
    public function getByType(Tenant $tenant, AuditableType $type, array $filters = []): LengthAwarePaginator;
}
```

### 2.2 SecurityService
**File**: `app/Services/Audit/SecurityService.php`

```php
final class SecurityService extends BaseService
{
    public function logEvent(SecurityEventType $type, ?User $user = null, array $metadata = []): SecurityEvent;
    public function listForTenant(Tenant $tenant, array $filters = []): LengthAwarePaginator;
    public function listForUser(User $user, array $filters = []): LengthAwarePaginator;
    public function getHighSeverityEvents(Tenant $tenant, int $limit = 10): Collection;
    public function getStats(Tenant $tenant): array;
}
```

### 2.3 LoginHistoryService
**File**: `app/Services/Audit/LoginHistoryService.php`

```php
final class LoginHistoryService extends BaseService
{
    public function logLogin(User $user, string $ip, string $userAgent): LoginHistory;
    public function logFailedLogin(string $email, string $ip, string $userAgent, string $reason): LoginHistory;
    public function listForUser(User $user, int $limit = 20): Collection;
    public function getActiveSessions(User $user): Collection;
    public function terminateSession(User $user, string $sessionId): void;
    public function terminateAllSessions(User $user, ?string $exceptSessionId = null): void;
}
```

### 2.4 DataPrivacyService
**File**: `app/Services/Audit/DataPrivacyService.php`

```php
final class DataPrivacyService extends BaseService
{
    // Export requests
    public function requestExport(User $user): DataExportRequest;
    public function getExportRequests(User $user): Collection;
    public function processExport(DataExportRequest $request): void;
    public function getExportDownloadUrl(DataExportRequest $request): string;

    // Deletion requests
    public function requestDeletion(User $user, string $reason): DataDeletionRequest;
    public function getDeletionRequests(User $user): Collection;
    public function cancelDeletion(DataDeletionRequest $request): void;
    public function processDeletion(DataDeletionRequest $request): void;

    // Admin methods
    public function listAllExportRequests(array $filters = []): LengthAwarePaginator;
    public function listAllDeletionRequests(array $filters = []): LengthAwarePaginator;
    public function approveDeletion(DataDeletionRequest $request, SuperAdminUser $admin): void;
    public function rejectDeletion(DataDeletionRequest $request, SuperAdminUser $admin, string $reason): void;
}
```

---

## 3. Data Classes

### 3.1 Audit Data
**Directory**: `app/Data/Audit/`

```php
// AuditLogData.php
final class AuditLogData extends Data
{
    public function __construct(
        public string $id,
        public string $action,
        public string $auditable_type,
        public string $auditable_id,
        public ?string $user_id,
        public ?string $user_name,
        public ?array $old_values,
        public ?array $new_values,
        public ?string $ip_address,
        public ?string $user_agent,
        public string $created_at,
    ) {}

    public static function fromModel(AuditLog $log): self;
}

// SecurityEventData.php
final class SecurityEventData extends Data
{
    public function __construct(
        public string $id,
        public string $event_type,
        public string $severity,
        public ?string $user_id,
        public ?string $user_name,
        public ?string $ip_address,
        public ?string $user_agent,
        public ?string $description,
        public ?array $metadata,
        public string $created_at,
    ) {}

    public static function fromModel(SecurityEvent $event): self;
}

// LoginHistoryData.php
final class LoginHistoryData extends Data
{
    public function __construct(
        public string $id,
        public string $user_id,
        public bool $is_successful,
        public ?string $failure_reason,
        public string $ip_address,
        public ?string $location,
        public ?string $device,
        public ?string $browser,
        public string $created_at,
    ) {}

    public static function fromModel(LoginHistory $history): self;
}

// SessionData.php
final class SessionData extends Data
{
    public function __construct(
        public string $id,
        public string $user_id,
        public string $ip_address,
        public ?string $device,
        public ?string $browser,
        public ?string $location,
        public bool $is_current,
        public string $last_activity_at,
        public string $created_at,
    ) {}

    public static function fromModel(SessionHistory $session): self;
}

// DataExportRequestData.php
final class DataExportRequestData extends Data
{
    public function __construct(
        public string $id,
        public string $user_id,
        public string $status,
        public ?string $file_path,
        public ?string $download_url,
        public ?string $expires_at,
        public ?string $completed_at,
        public string $created_at,
    ) {}

    public static function fromModel(DataExportRequest $request): self;
}

// DataDeletionRequestData.php
final class DataDeletionRequestData extends Data
{
    public function __construct(
        public string $id,
        public string $user_id,
        public string $status,
        public string $reason,
        public ?string $scheduled_for,
        public ?string $processed_at,
        public string $created_at,
    ) {}

    public static function fromModel(DataDeletionRequest $request): self;
}

// RequestDeletionData.php
final class RequestDeletionData extends Data
{
    public function __construct(
        #[Required]
        public string $reason,
    ) {}
}

// SecurityStatsData.php
final class SecurityStatsData extends Data
{
    public function __construct(
        public int $total_events,
        public int $critical_events,
        public int $high_events,
        public int $failed_logins_24h,
        public int $suspicious_activities,
        public array $events_by_type,
    ) {}
}
```

---

## 4. Controllers

### 4.1 User Controllers (Authenticated)

**AuditLogController** - `app/Http/Controllers/Api/V1/Audit/AuditLogController.php`
- `GET /audit/logs` - List audit logs for current tenant
- `GET /audit/logs/{auditableType}/{auditableId}` - Get logs for specific resource

**SecurityController** - `app/Http/Controllers/Api/V1/Audit/SecurityController.php`
- `GET /security/events` - List security events
- `GET /security/stats` - Get security statistics

**SessionController** - `app/Http/Controllers/Api/V1/Audit/SessionController.php`
- `GET /security/sessions` - Get active sessions
- `GET /security/login-history` - Get login history
- `DELETE /security/sessions/{id}` - Terminate session
- `POST /security/sessions/terminate-all` - Terminate all sessions

**DataPrivacyController** - `app/Http/Controllers/Api/V1/Audit/DataPrivacyController.php`
- `GET /privacy/export-requests` - List export requests
- `POST /privacy/export-requests` - Request data export
- `GET /privacy/export-requests/{id}/download` - Download export
- `GET /privacy/deletion-requests` - List deletion requests
- `POST /privacy/deletion-requests` - Request account deletion
- `DELETE /privacy/deletion-requests/{id}` - Cancel deletion

### 4.2 Admin Controllers

**AdminDataPrivacyController** - `app/Http/Controllers/Api/V1/Admin/Audit/AdminDataPrivacyController.php`
- `GET /admin/privacy/export-requests` - List all export requests
- `GET /admin/privacy/deletion-requests` - List all deletion requests
- `POST /admin/privacy/deletion-requests/{id}/approve` - Approve deletion
- `POST /admin/privacy/deletion-requests/{id}/reject` - Reject deletion

---

## 5. Routes

```php
// User audit/security routes
Route::middleware('auth:sanctum')->group(function () {
    // Audit logs
    Route::prefix('audit')->group(function () {
        Route::get('/logs', [AuditLogController::class, 'index']);
        Route::get('/logs/{auditableType}/{auditableId}', [AuditLogController::class, 'forAuditable']);
    });

    // Security
    Route::prefix('security')->group(function () {
        Route::get('/events', [SecurityController::class, 'index']);
        Route::get('/stats', [SecurityController::class, 'stats']);
        Route::get('/sessions', [SessionController::class, 'index']);
        Route::get('/login-history', [SessionController::class, 'loginHistory']);
        Route::delete('/sessions/{session}', [SessionController::class, 'terminate']);
        Route::post('/sessions/terminate-all', [SessionController::class, 'terminateAll']);
    });

    // Data privacy
    Route::prefix('privacy')->group(function () {
        Route::get('/export-requests', [DataPrivacyController::class, 'exportRequests']);
        Route::post('/export-requests', [DataPrivacyController::class, 'requestExport']);
        Route::get('/export-requests/{exportRequest}/download', [DataPrivacyController::class, 'downloadExport']);
        Route::get('/deletion-requests', [DataPrivacyController::class, 'deletionRequests']);
        Route::post('/deletion-requests', [DataPrivacyController::class, 'requestDeletion']);
        Route::delete('/deletion-requests/{deletionRequest}', [DataPrivacyController::class, 'cancelDeletion']);
    });
});

// Admin routes
Route::prefix('admin/privacy')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/export-requests', [AdminDataPrivacyController::class, 'exportRequests']);
    Route::get('/deletion-requests', [AdminDataPrivacyController::class, 'deletionRequests']);
    Route::post('/deletion-requests/{deletionRequest}/approve', [AdminDataPrivacyController::class, 'approveDeletion']);
    Route::post('/deletion-requests/{deletionRequest}/reject', [AdminDataPrivacyController::class, 'rejectDeletion']);
});
```

---

## 6. Test Requirements

### Feature Tests
- AuditLogController, SecurityController, SessionController
- DataPrivacyController, AdminDataPrivacyController

### Unit Tests
- AuditLogService, SecurityService, LoginHistoryService, DataPrivacyService

---

## 7. Business Rules

### Audit Log Rules
- Automatic logging for model changes (optional manual trigger)
- Logs include old/new values for changes
- Retained based on plan (90 days default)

### Security Rules
- Failed logins tracked
- Suspicious patterns detected
- High severity events trigger alerts

### Session Rules
- Users can view/terminate their sessions
- Current session cannot be terminated via API

### Data Privacy Rules
- Export request: 72 hours to complete
- Deletion request: 30-day grace period
- Deletion requires admin approval
- Can cancel within grace period
