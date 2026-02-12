# Task 3.1: Background Jobs & Notifications - Technical Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-07
- **Task**: 3.1 Background Jobs & Notifications
- **Dependencies**: Phase 2 Complete

---

## 1. Overview

This task implements the notification system and background job infrastructure. It covers in-app notifications, email notifications, and all scheduled/async jobs required for the platform.

### Components to Implement
1. **Notification Database** - Migration and model
2. **NotificationService** - Notification management
3. **NotificationController** - API endpoints
4. **Background Jobs** - All scheduled and async jobs
5. **Data Classes** - Request/response DTOs

---

## 2. Database Schema

### 2.1 Notifications Table
**File**: `database/migrations/xxxx_xx_xx_create_notifications_table.php`

```php
Schema::create('notifications', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->string('type', 50);                    // NotificationType enum
    $table->string('channel', 20)->default('in_app'); // NotificationChannel enum
    $table->string('title', 200);
    $table->text('message');
    $table->json('data')->nullable();              // Additional context
    $table->string('action_url')->nullable();      // Link to related resource
    $table->string('icon')->nullable();            // Icon identifier
    $table->timestamp('read_at')->nullable();
    $table->timestamp('sent_at')->nullable();      // For email notifications
    $table->timestamp('failed_at')->nullable();
    $table->string('failure_reason')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'read_at']);
    $table->index(['tenant_id', 'created_at']);
    $table->index(['user_id', 'type']);
});
```

### 2.2 Notification Preferences Table
**File**: `database/migrations/xxxx_xx_xx_create_notification_preferences_table.php`

```php
Schema::create('notification_preferences', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->string('notification_type', 50);
    $table->boolean('in_app_enabled')->default(true);
    $table->boolean('email_enabled')->default(true);
    $table->boolean('push_enabled')->default(false);
    $table->timestamps();

    $table->unique(['user_id', 'notification_type']);
});
```

---

## 3. Enums

### 3.1 NotificationType
**File**: `app/Enums/Notification/NotificationType.php`

```php
enum NotificationType: string
{
    // Content & Approval
    case POST_SUBMITTED = 'post_submitted';
    case POST_APPROVED = 'post_approved';
    case POST_REJECTED = 'post_rejected';
    case POST_PUBLISHED = 'post_published';
    case POST_FAILED = 'post_failed';
    case POST_SCHEDULED = 'post_scheduled';

    // Engagement
    case NEW_COMMENT = 'new_comment';
    case NEW_MENTION = 'new_mention';
    case INBOX_ASSIGNED = 'inbox_assigned';

    // Team
    case INVITATION_RECEIVED = 'invitation_received';
    case INVITATION_ACCEPTED = 'invitation_accepted';
    case MEMBER_ADDED = 'member_added';
    case MEMBER_REMOVED = 'member_removed';
    case ROLE_CHANGED = 'role_changed';

    // Billing
    case SUBSCRIPTION_CREATED = 'subscription_created';
    case SUBSCRIPTION_RENEWED = 'subscription_renewed';
    case SUBSCRIPTION_CANCELLED = 'subscription_cancelled';
    case PAYMENT_FAILED = 'payment_failed';
    case TRIAL_ENDING = 'trial_ending';
    case TRIAL_ENDED = 'trial_ended';

    // Social Accounts
    case ACCOUNT_CONNECTED = 'account_connected';
    case ACCOUNT_DISCONNECTED = 'account_disconnected';
    case ACCOUNT_TOKEN_EXPIRING = 'account_token_expiring';
    case ACCOUNT_TOKEN_EXPIRED = 'account_token_expired';

    // Support
    case TICKET_CREATED = 'ticket_created';
    case TICKET_REPLIED = 'ticket_replied';
    case TICKET_RESOLVED = 'ticket_resolved';

    // Data Privacy
    case DATA_EXPORT_READY = 'data_export_ready';
    case DATA_DELETION_SCHEDULED = 'data_deletion_scheduled';
    case DATA_DELETION_COMPLETED = 'data_deletion_completed';

    // System
    case SYSTEM_ANNOUNCEMENT = 'system_announcement';
    case MAINTENANCE_SCHEDULED = 'maintenance_scheduled';
}
```

### 3.2 NotificationChannel
**File**: `app/Enums/Notification/NotificationChannel.php`

```php
enum NotificationChannel: string
{
    case IN_APP = 'in_app';
    case EMAIL = 'email';
    case PUSH = 'push';
    case SMS = 'sms';
}
```

---

## 4. Model

### 4.1 Notification Model
**File**: `app/Models/Notification/Notification.php`

```php
final class Notification extends Model
{
    use HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'channel',
        'title',
        'message',
        'data',
        'action_url',
        'icon',
        'read_at',
        'sent_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'type' => NotificationType::class,
        'channel' => NotificationChannel::class,
        'data' => 'array',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo;
    public function tenant(): BelongsTo;

    // Scopes
    public function scopeUnread(Builder $query): Builder;
    public function scopeRead(Builder $query): Builder;
    public function scopeOfType(Builder $query, NotificationType $type): Builder;
    public function scopeOfChannel(Builder $query, NotificationChannel $channel): Builder;

    // Helpers
    public function isRead(): bool;
    public function markAsRead(): void;
}
```

### 4.2 NotificationPreference Model
**File**: `app/Models/Notification/NotificationPreference.php`

```php
final class NotificationPreference extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'notification_type',
        'in_app_enabled',
        'email_enabled',
        'push_enabled',
    ];

    protected $casts = [
        'notification_type' => NotificationType::class,
        'in_app_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'push_enabled' => 'boolean',
    ];

    public function user(): BelongsTo;
}
```

---

## 5. Services

### 5.1 NotificationService
**File**: `app/Services/Notification/NotificationService.php`

```php
final class NotificationService extends BaseService
{
    // Core notification methods
    public function send(
        User $user,
        NotificationType $type,
        string $title,
        string $message,
        ?array $data = null,
        ?string $actionUrl = null
    ): Notification;

    public function sendToUsers(
        Collection $users,
        NotificationType $type,
        string $title,
        string $message,
        ?array $data = null,
        ?string $actionUrl = null
    ): Collection;

    public function sendToTenant(
        Tenant $tenant,
        NotificationType $type,
        string $title,
        string $message,
        ?array $data = null,
        ?string $actionUrl = null
    ): Collection;

    // Read management
    public function markAsRead(Notification $notification): Notification;
    public function markAllAsRead(User $user): int;
    public function markMultipleAsRead(User $user, array $ids): int;

    // Retrieval
    public function listForUser(User $user, array $filters = []): LengthAwarePaginator;
    public function getUnreadCount(User $user): int;
    public function getRecent(User $user, int $limit = 10): Collection;

    // Preferences
    public function getPreferences(User $user): Collection;
    public function updatePreference(
        User $user,
        NotificationType $type,
        bool $inApp,
        bool $email,
        bool $push
    ): NotificationPreference;

    // Cleanup
    public function deleteOld(int $daysOld = 90): int;
}
```

---

## 6. Data Classes

### 6.1 Notification Data
**Directory**: `app/Data/Notification/`

```php
// NotificationData.php
final class NotificationData extends Data
{
    public function __construct(
        public string $id,
        public string $type,
        public string $channel,
        public string $title,
        public string $message,
        public ?array $data,
        public ?string $action_url,
        public ?string $icon,
        public bool $is_read,
        public ?string $read_at,
        public string $created_at,
    ) {}

    public static function fromModel(Notification $notification): self;
}

// NotificationPreferenceData.php
final class NotificationPreferenceData extends Data
{
    public function __construct(
        public string $notification_type,
        public bool $in_app_enabled,
        public bool $email_enabled,
        public bool $push_enabled,
    ) {}

    public static function fromModel(NotificationPreference $preference): self;
}

// UpdatePreferenceData.php
final class UpdatePreferenceData extends Data
{
    public function __construct(
        #[Required]
        public string $notification_type,
        public bool $in_app_enabled = true,
        public bool $email_enabled = true,
        public bool $push_enabled = false,
    ) {}
}
```

---

## 7. Controllers

### 7.1 NotificationController
**File**: `app/Http/Controllers/Api/V1/Notification/NotificationController.php`

```php
final class NotificationController extends Controller
{
    // GET /notifications
    public function index(Request $request): JsonResponse;

    // GET /notifications/unread-count
    public function unreadCount(): JsonResponse;

    // GET /notifications/recent
    public function recent(): JsonResponse;

    // POST /notifications/{notification}/read
    public function markAsRead(Notification $notification): JsonResponse;

    // POST /notifications/read-all
    public function markAllAsRead(): JsonResponse;

    // POST /notifications/read-multiple
    public function markMultipleAsRead(MarkMultipleReadRequest $request): JsonResponse;

    // GET /notifications/preferences
    public function preferences(): JsonResponse;

    // PUT /notifications/preferences
    public function updatePreferences(UpdatePreferencesRequest $request): JsonResponse;
}
```

---

## 8. Background Jobs

### 8.1 PublishScheduledPostsJob
**File**: `app/Jobs/Content/PublishScheduledPostsJob.php`
**Schedule**: Every minute

```php
final class PublishScheduledPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(PublishingService $publishingService): void
    {
        // Find all posts scheduled for now or past
        // Dispatch individual PublishPostJob for each
    }
}
```

### 8.2 PublishPostJob
**File**: `app/Jobs/Content/PublishPostJob.php`

```php
final class PublishPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly string $postId,
        public readonly string $workspaceId,
    ) {}

    public function handle(PublishingService $publishingService): void
    {
        // Publish the post to all targets
        // Update status
        // Send notification
    }

    public function failed(\Throwable $exception): void
    {
        // Mark post as failed
        // Send failure notification
    }
}
```

### 8.3 SyncInboxJob
**File**: `app/Jobs/Inbox/SyncInboxJob.php`
**Schedule**: Every 15 minutes

```php
final class SyncInboxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $workspaceId,
    ) {}

    public function handle(InboxService $inboxService): void
    {
        // Fetch new comments/mentions from all connected accounts
        // Create inbox items
        // Send notifications for new items
    }
}
```

### 8.4 FetchPostMetricsJob
**File**: `app/Jobs/Analytics/FetchPostMetricsJob.php`
**Schedule**: Every 6 hours

```php
final class FetchPostMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $workspaceId,
    ) {}

    public function handle(): void
    {
        // Fetch metrics for published posts
        // Create PostMetricSnapshot records
    }
}
```

### 8.5 ProcessDataExportJob
**File**: `app/Jobs/Privacy/ProcessDataExportJob.php`

```php
final class ProcessDataExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600; // 1 hour

    public function __construct(
        public readonly string $exportRequestId,
    ) {}

    public function handle(DataPrivacyService $service): void
    {
        // Generate data export
        // Create zip file
        // Upload to storage
        // Update request status
        // Send notification
    }
}
```

### 8.6 ProcessDataDeletionJob
**File**: `app/Jobs/Privacy/ProcessDataDeletionJob.php`

```php
final class ProcessDataDeletionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600; // 1 hour

    public function __construct(
        public readonly string $deletionRequestId,
    ) {}

    public function handle(DataPrivacyService $service): void
    {
        // Verify deletion is still requested
        // Delete all user data
        // Anonymize audit records
        // Update request status
        // Send confirmation
    }
}
```

### 8.7 SendNotificationEmailJob
**File**: `app/Jobs/Notification/SendNotificationEmailJob.php`

```php
final class SendNotificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $notificationId,
    ) {}

    public function handle(): void
    {
        // Load notification
        // Check user preferences
        // Send email
        // Update sent_at
    }
}
```

### 8.8 CleanupOldNotificationsJob
**File**: `app/Jobs/Notification/CleanupOldNotificationsJob.php`
**Schedule**: Daily

```php
final class CleanupOldNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $service): void
    {
        // Delete notifications older than 90 days
        $service->deleteOld(90);
    }
}
```

### 8.9 RefreshExpiringTokensJob
**File**: `app/Jobs/Social/RefreshExpiringTokensJob.php`
**Schedule**: Daily

```php
final class RefreshExpiringTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SocialAccountService $service): void
    {
        // Find tokens expiring in next 7 days
        // Attempt refresh
        // Send notifications for tokens that couldn't be refreshed
    }
}
```

### 8.10 ArchiveOldInboxItemsJob
**File**: `app/Jobs/Inbox/ArchiveOldInboxItemsJob.php`
**Schedule**: Weekly

```php
final class ArchiveOldInboxItemsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(InboxService $service): void
    {
        // Archive inbox items older than 90 days
    }
}
```

---

## 9. Scheduler Configuration

**File**: `app/Console/Kernel.php` or `routes/console.php`

```php
// Every minute - check for scheduled posts
Schedule::job(new PublishScheduledPostsJob)->everyMinute();

// Every 15 minutes - sync inbox for all active workspaces
Schedule::command('inbox:sync-all')->everyFifteenMinutes();

// Every 6 hours - fetch post metrics
Schedule::command('analytics:fetch-metrics')->everySixHours();

// Daily - cleanup and maintenance
Schedule::job(new CleanupOldNotificationsJob)->daily();
Schedule::job(new RefreshExpiringTokensJob)->daily();

// Weekly - archive old data
Schedule::job(new ArchiveOldInboxItemsJob)->weekly();
```

---

## 10. Routes

```php
// Notification routes
Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
    Route::get('/recent', [NotificationController::class, 'recent']);
    Route::post('/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::post('/read-multiple', [NotificationController::class, 'markMultipleAsRead']);
    Route::get('/preferences', [NotificationController::class, 'preferences']);
    Route::put('/preferences', [NotificationController::class, 'updatePreferences']);
});
```

---

## 11. Artisan Commands

### 11.1 SyncAllInboxCommand
**File**: `app/Console/Commands/SyncAllInboxCommand.php`

```php
final class SyncAllInboxCommand extends Command
{
    protected $signature = 'inbox:sync-all';
    protected $description = 'Dispatch inbox sync jobs for all active workspaces';

    public function handle(): int
    {
        // Get all active workspaces with connected accounts
        // Dispatch SyncInboxJob for each
    }
}
```

### 11.2 FetchAllMetricsCommand
**File**: `app/Console/Commands/FetchAllMetricsCommand.php`

```php
final class FetchAllMetricsCommand extends Command
{
    protected $signature = 'analytics:fetch-metrics';
    protected $description = 'Dispatch metrics fetch jobs for all active workspaces';

    public function handle(): int
    {
        // Get all active workspaces with published posts
        // Dispatch FetchPostMetricsJob for each
    }
}
```

---

## 12. Test Requirements

### Feature Tests
- `tests/Feature/Api/Notification/NotificationTest.php`
- `tests/Feature/Api/Notification/NotificationPreferenceTest.php`

### Unit Tests
- `tests/Unit/Services/Notification/NotificationServiceTest.php`
- `tests/Unit/Jobs/Content/PublishScheduledPostsJobTest.php`
- `tests/Unit/Jobs/Content/PublishPostJobTest.php`
- `tests/Unit/Jobs/Inbox/SyncInboxJobTest.php`
- `tests/Unit/Jobs/Analytics/FetchPostMetricsJobTest.php`
- `tests/Unit/Jobs/Privacy/ProcessDataExportJobTest.php`
- `tests/Unit/Jobs/Privacy/ProcessDataDeletionJobTest.php`

---

## 13. Implementation Checklist

- [ ] Create notifications migration
- [ ] Create notification_preferences migration
- [ ] Create NotificationType enum
- [ ] Create NotificationChannel enum
- [ ] Create Notification model
- [ ] Create NotificationPreference model
- [ ] Create NotificationService
- [ ] Create notification Data classes
- [ ] Create NotificationController
- [ ] Create Form Requests
- [ ] Create all background jobs
- [ ] Create artisan commands
- [ ] Configure scheduler
- [ ] Update routes
- [ ] Create feature tests
- [ ] Create unit tests
- [ ] All tests pass

---

## 14. Business Rules

### Notification Rules
- Users can configure preferences per notification type
- In-app notifications are always stored
- Email notifications respect user preferences
- Notifications older than 90 days are automatically deleted
- Read notifications are retained for 30 days before cleanup eligible

### Job Rules
- All jobs must carry workspace_id for tenant isolation
- Jobs must implement retries with backoff
- Failed jobs must log errors and notify admins
- Scheduled jobs run based on workspace timezone

### Publishing Rules
- Posts are published within 1 minute of scheduled time
- Failed publishes are retried 3 times with exponential backoff
- Users are notified of both success and failure

### Inbox Sync Rules
- Comments/mentions synced every 15 minutes
- New items trigger notifications
- Items older than 90 days are auto-archived
