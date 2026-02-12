# Phase 4: Analytics & Reports Specification

**Version:** 1.0.0
**Created:** 2026-02-07
**Module:** Analytics & Reporting
**Scope:** Social Media Analytics, Content Performance, Usage Tracking, Reports

---

## 1. Overview

Phase 4 implements comprehensive analytics and reporting capabilities:
- Social media metrics aggregation from connected platforms
- Content performance analysis with actionable insights
- User activity and feature usage tracking
- Customizable reports and data exports

### 1.1 Dependencies
- Phase 1 (Database): migrations, models
- Phase 2 (Services): SocialAccountService, ContentService
- Phase 3 (Jobs): FetchPostMetricsJob (already implemented)

### 1.2 Existing Infrastructure
- `post_metric_snapshots` table and model
- `FetchPostMetricsJob` for collecting metrics
- `PostMetricSnapshot` model with basic methods

---

## 2. Database Schema

### 2.1 New Migrations

```php
// 2026_02_07_000001_create_analytics_aggregates_table.php
Schema::create('analytics_aggregates', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('workspace_id');
    $table->uuid('social_account_id')->nullable();
    $table->date('date');
    $table->string('period_type', 10); // daily, weekly, monthly

    // Aggregate metrics
    $table->unsignedBigInteger('impressions')->default(0);
    $table->unsignedBigInteger('reach')->default(0);
    $table->unsignedBigInteger('engagements')->default(0);
    $table->unsignedBigInteger('likes')->default(0);
    $table->unsignedBigInteger('comments')->default(0);
    $table->unsignedBigInteger('shares')->default(0);
    $table->unsignedBigInteger('saves')->default(0);
    $table->unsignedBigInteger('clicks')->default(0);
    $table->unsignedBigInteger('video_views')->default(0);
    $table->unsignedBigInteger('posts_count')->default(0);
    $table->decimal('engagement_rate', 8, 4)->default(0);

    // Follower metrics
    $table->unsignedBigInteger('followers_start')->default(0);
    $table->unsignedBigInteger('followers_end')->default(0);
    $table->integer('followers_change')->default(0);

    $table->timestamps();

    $table->unique(['workspace_id', 'social_account_id', 'date', 'period_type'], 'analytics_unique');
    $table->index(['workspace_id', 'date']);
    $table->index(['social_account_id', 'date']);

    $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
    $table->foreign('social_account_id')->references('id')->on('social_accounts')->nullOnDelete();
});

// 2026_02_07_000002_create_user_activity_logs_table.php
Schema::create('user_activity_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id');
    $table->uuid('user_id');
    $table->uuid('workspace_id')->nullable();

    $table->string('activity_type', 50);
    $table->string('activity_category', 50);
    $table->string('resource_type', 50)->nullable();
    $table->uuid('resource_id')->nullable();

    $table->string('page_url', 500)->nullable();
    $table->string('referrer_url', 500)->nullable();
    $table->string('session_id', 100)->nullable();

    $table->string('device_type', 20)->nullable();
    $table->string('browser', 50)->nullable();
    $table->string('os', 50)->nullable();

    $table->json('metadata')->nullable();
    $table->timestamp('created_at');

    $table->index(['tenant_id', 'user_id', 'created_at']);
    $table->index(['workspace_id', 'created_at']);
    $table->index(['activity_type', 'created_at']);
    $table->index('session_id');

    $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
});

// 2026_02_07_000003_create_analytics_reports_table.php
Schema::create('analytics_reports', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('workspace_id');
    $table->uuid('created_by_user_id');

    $table->string('name', 200);
    $table->text('description')->nullable();
    $table->string('report_type', 50); // performance, engagement, growth, custom

    $table->date('date_from');
    $table->date('date_to');
    $table->json('social_account_ids')->nullable();
    $table->json('metrics')->nullable();
    $table->json('filters')->nullable();

    $table->string('status', 20)->default('pending');
    $table->string('file_path', 500)->nullable();
    $table->string('file_format', 10)->default('pdf');
    $table->bigInteger('file_size_bytes')->nullable();

    $table->timestamp('completed_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();

    $table->index(['workspace_id', 'created_at']);
    $table->index('status');

    $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
    $table->foreign('created_by_user_id')->references('id')->on('users')->cascadeOnDelete();
});
```

---

## 3. Enums

### 3.1 ActivityType Enum
```php
enum ActivityType: string
{
    // Content
    case POST_CREATED = 'post_created';
    case POST_EDITED = 'post_edited';
    case POST_DELETED = 'post_deleted';
    case POST_SCHEDULED = 'post_scheduled';
    case POST_PUBLISHED = 'post_published';
    case MEDIA_UPLOADED = 'media_uploaded';

    // Engagement
    case INBOX_VIEWED = 'inbox_viewed';
    case REPLY_SENT = 'reply_sent';
    case COMMENT_LIKED = 'comment_liked';

    // Analytics
    case DASHBOARD_VIEWED = 'dashboard_viewed';
    case REPORT_GENERATED = 'report_generated';
    case REPORT_EXPORTED = 'report_exported';

    // Settings
    case ACCOUNT_CONNECTED = 'account_connected';
    case ACCOUNT_DISCONNECTED = 'account_disconnected';
    case SETTINGS_CHANGED = 'settings_changed';
    case TEAM_MEMBER_INVITED = 'team_member_invited';

    // AI Features
    case AI_CAPTION_GENERATED = 'ai_caption_generated';
    case AI_HASHTAG_SUGGESTED = 'ai_hashtag_suggested';
    case AI_BEST_TIME_CHECKED = 'ai_best_time_checked';
}

enum ActivityCategory: string
{
    case CONTENT_CREATION = 'content_creation';
    case PUBLISHING = 'publishing';
    case ENGAGEMENT = 'engagement';
    case ANALYTICS = 'analytics';
    case SETTINGS = 'settings';
    case AI_FEATURES = 'ai_features';
}

enum ReportType: string
{
    case PERFORMANCE = 'performance';
    case ENGAGEMENT = 'engagement';
    case GROWTH = 'growth';
    case CONTENT = 'content';
    case CUSTOM = 'custom';
}

enum ReportStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}

enum PeriodType: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
}
```

---

## 4. Models

### 4.1 AnalyticsAggregate Model
```php
final class AnalyticsAggregate extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'workspace_id', 'social_account_id', 'date', 'period_type',
        'impressions', 'reach', 'engagements', 'likes', 'comments',
        'shares', 'saves', 'clicks', 'video_views', 'posts_count',
        'engagement_rate', 'followers_start', 'followers_end', 'followers_change',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'period_type' => PeriodType::class,
            'engagement_rate' => 'decimal:4',
        ];
    }

    // Relationships
    public function workspace(): BelongsTo;
    public function socialAccount(): BelongsTo;

    // Scopes
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder;
    public function scopeForAccount(Builder $query, string $accountId): Builder;
    public function scopeInDateRange(Builder $query, Carbon $start, Carbon $end): Builder;
    public function scopeForPeriod(Builder $query, PeriodType $periodType): Builder;
}
```

### 4.2 UserActivityLog Model
```php
final class UserActivityLog extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'user_id', 'workspace_id',
        'activity_type', 'activity_category', 'resource_type', 'resource_id',
        'page_url', 'referrer_url', 'session_id',
        'device_type', 'browser', 'os', 'metadata', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'activity_type' => ActivityType::class,
            'activity_category' => ActivityCategory::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    // Relationships
    public function tenant(): BelongsTo;
    public function user(): BelongsTo;
    public function workspace(): BelongsTo;
}
```

### 4.3 AnalyticsReport Model
```php
final class AnalyticsReport extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'workspace_id', 'created_by_user_id', 'name', 'description',
        'report_type', 'date_from', 'date_to', 'social_account_ids',
        'metrics', 'filters', 'status', 'file_path', 'file_format',
        'file_size_bytes', 'completed_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'report_type' => ReportType::class,
            'status' => ReportStatus::class,
            'date_from' => 'date',
            'date_to' => 'date',
            'social_account_ids' => 'array',
            'metrics' => 'array',
            'filters' => 'array',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
```

---

## 5. Services

### 5.1 AnalyticsService
```php
final class AnalyticsService extends BaseService
{
    // Dashboard metrics
    public function getDashboardMetrics(string $workspaceId, string $period = '30d'): array;
    public function getMetricsComparison(string $workspaceId, Carbon $start, Carbon $end): array;

    // Aggregation
    public function aggregateDailyMetrics(string $workspaceId, Carbon $date): AnalyticsAggregate;
    public function aggregateWeeklyMetrics(string $workspaceId, Carbon $weekStart): AnalyticsAggregate;
    public function aggregateMonthlyMetrics(string $workspaceId, Carbon $monthStart): AnalyticsAggregate;

    // Trends
    public function getEngagementTrend(string $workspaceId, Carbon $start, Carbon $end): array;
    public function getFollowerGrowthTrend(string $workspaceId, Carbon $start, Carbon $end): array;
    public function getPostingFrequency(string $workspaceId, Carbon $start, Carbon $end): array;

    // Platform breakdown
    public function getMetricsByPlatform(string $workspaceId, Carbon $start, Carbon $end): array;
    public function getMetricsByAccount(string $workspaceId, Carbon $start, Carbon $end): array;
}
```

### 5.2 ContentPerformanceService
```php
final class ContentPerformanceService extends BaseService
{
    public function getPerformanceOverview(string $workspaceId, string $period = '30d'): array;
    public function getTopPosts(string $workspaceId, int $limit = 10, string $sortBy = 'engagement'): Collection;
    public function getWorstPosts(string $workspaceId, int $limit = 10): Collection;
    public function getPerformanceByContentType(string $workspaceId, Carbon $start, Carbon $end): array;
    public function getBestPostingTimes(string $workspaceId): array;
    public function getHashtagPerformance(string $workspaceId, int $limit = 20): array;
}
```

### 5.3 UsageAnalyticsService
```php
final class UsageAnalyticsService extends BaseService
{
    // Activity tracking
    public function trackActivity(ActivityType $type, ?string $resourceType = null, ?string $resourceId = null, array $metadata = []): void;

    // Usage metrics
    public function getFeatureAdoption(string $tenantId, string $period = '30d'): array;
    public function getUserActivitySummary(string $userId, string $period = '30d'): array;
    public function getWorkspaceUsageSummary(string $workspaceId, string $period = '30d'): array;

    // Trends
    public function getActivityTrend(string $workspaceId, Carbon $start, Carbon $end): array;
    public function getActiveUsers(string $tenantId, string $period = '7d'): int;
}
```

### 5.4 ReportService
```php
final class ReportService extends BaseService
{
    public function createReport(string $workspaceId, string $userId, array $data): AnalyticsReport;
    public function generateReport(AnalyticsReport $report): void;
    public function getReportDownloadUrl(AnalyticsReport $report): string;
    public function listReports(string $workspaceId, array $filters = []): LengthAwarePaginator;
    public function deleteReport(AnalyticsReport $report): void;
    public function cleanupExpiredReports(): int;
}
```

---

## 6. Jobs

### 6.1 AggregateAnalyticsJob
```php
final class AggregateAnalyticsJob implements ShouldQueue, ShouldBeUnique
{
    public string $queue = 'analytics';
    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public readonly string $workspaceId,
        public readonly PeriodType $periodType,
        public readonly Carbon $date,
    ) {}

    public function handle(AnalyticsService $analyticsService): void;
    public function uniqueId(): string;
}
```

### 6.2 GenerateReportJob
```php
final class GenerateReportJob implements ShouldQueue
{
    public string $queue = 'reports';
    public int $tries = 2;
    public int $timeout = 600;

    public function __construct(
        public readonly string $reportId,
    ) {}

    public function handle(ReportService $reportService): void;
    public function failed(\Throwable $exception): void;
}
```

### 6.3 CleanupAnalyticsDataJob
```php
final class CleanupAnalyticsDataJob implements ShouldQueue, ShouldBeUnique
{
    public string $queue = 'maintenance';
    public int $tries = 1;
    public int $timeout = 1800;

    // Cleanup old activity logs (older than 90 days)
    // Cleanup old metric snapshots (aggregate into daily/weekly/monthly)
    // Cleanup expired reports
}
```

---

## 7. API Endpoints

### 7.1 Analytics Dashboard
```
GET  /api/v1/workspaces/{workspace}/analytics/dashboard
     Query: period (7d, 30d, 90d, custom), start_date, end_date

GET  /api/v1/workspaces/{workspace}/analytics/metrics
     Query: period, metrics[] (impressions, reach, engagement, etc.)

GET  /api/v1/workspaces/{workspace}/analytics/trends
     Query: period, metric (engagement, followers, posts)
```

### 7.2 Content Performance
```
GET  /api/v1/workspaces/{workspace}/analytics/content/overview
GET  /api/v1/workspaces/{workspace}/analytics/content/top-posts
GET  /api/v1/workspaces/{workspace}/analytics/content/by-platform
GET  /api/v1/workspaces/{workspace}/analytics/content/by-type
GET  /api/v1/workspaces/{workspace}/analytics/content/best-times
```

### 7.3 Account Analytics
```
GET  /api/v1/workspaces/{workspace}/social-accounts/{account}/analytics
GET  /api/v1/workspaces/{workspace}/social-accounts/{account}/followers
GET  /api/v1/workspaces/{workspace}/social-accounts/{account}/engagement
```

### 7.4 Reports
```
GET    /api/v1/workspaces/{workspace}/reports
POST   /api/v1/workspaces/{workspace}/reports
GET    /api/v1/workspaces/{workspace}/reports/{report}
GET    /api/v1/workspaces/{workspace}/reports/{report}/download
DELETE /api/v1/workspaces/{workspace}/reports/{report}
```

---

## 8. DTOs

### 8.1 Data Classes
```php
final class DashboardMetricsData extends Data
{
    public function __construct(
        public int $impressions,
        public int $reach,
        public int $engagements,
        public int $likes,
        public int $comments,
        public int $shares,
        public int $posts_published,
        public int $followers_gained,
        public float $engagement_rate,
        public ?float $impressions_change,
        public ?float $reach_change,
        public ?float $engagement_change,
    ) {}
}

final class ContentPerformanceData extends Data { ... }
final class TrendDataPoint extends Data { ... }
final class PlatformBreakdownData extends Data { ... }
final class TopPostData extends Data { ... }
final class AnalyticsReportData extends Data { ... }
```

---

## 9. Scheduler

```php
// routes/console.php additions
Schedule::job(new AggregateAnalyticsJob(
    workspaceId: '*', // All workspaces
    periodType: PeriodType::DAILY,
    date: now()->subDay(),
))->dailyAt('01:00');

Schedule::job(new AggregateAnalyticsJob(
    workspaceId: '*',
    periodType: PeriodType::WEEKLY,
    date: now()->startOfWeek()->subWeek(),
))->weeklyOn(1, '02:00'); // Monday 2am

Schedule::job(new CleanupAnalyticsDataJob())->dailyAt('04:00');
```

---

## 10. Implementation Order

1. **Migrations** - Create analytics tables
2. **Enums** - ActivityType, ReportType, PeriodType, etc.
3. **Models** - AnalyticsAggregate, UserActivityLog, AnalyticsReport
4. **Services** - AnalyticsService, ContentPerformanceService, UsageAnalyticsService, ReportService
5. **DTOs** - DashboardMetricsData, ContentPerformanceData, etc.
6. **Controllers** - AnalyticsController, ContentAnalyticsController, ReportController
7. **Jobs** - AggregateAnalyticsJob, GenerateReportJob, CleanupAnalyticsDataJob
8. **Routes** - API endpoints
9. **Tests** - Unit and feature tests

---

## 11. Testing

- Unit tests for all services
- Unit tests for all models
- Feature tests for all API endpoints
- Integration tests for aggregation jobs
- Test coverage target: 80%+
