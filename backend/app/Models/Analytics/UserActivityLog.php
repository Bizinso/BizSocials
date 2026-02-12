<?php

declare(strict_types=1);

namespace App\Models\Analytics;

use App\Enums\Analytics\ActivityCategory;
use App\Enums\Analytics\ActivityType;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Workspace\Workspace;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

/**
 * UserActivityLog Model
 *
 * Represents a user activity event for tracking and analytics purposes.
 * Captures page views, feature usage, and user behavior patterns.
 *
 * @property string $id UUID primary key
 * @property string $tenant_id Tenant UUID
 * @property string $user_id User UUID
 * @property string|null $workspace_id Workspace UUID (optional)
 * @property ActivityType $activity_type Type of activity
 * @property ActivityCategory $activity_category Category of activity
 * @property string|null $resource_type Type of resource involved
 * @property string|null $resource_id UUID of resource involved
 * @property string|null $page_url Page URL where activity occurred
 * @property string|null $referrer_url Referrer URL
 * @property string|null $session_id Session identifier
 * @property string|null $device_type Device type (desktop, mobile, tablet)
 * @property string|null $browser Browser name
 * @property string|null $os Operating system
 * @property array|null $metadata Additional activity data
 * @property \Carbon\Carbon $created_at
 *
 * @property-read Tenant $tenant
 * @property-read User $user
 * @property-read Workspace|null $workspace
 *
 * @method static Builder<static> forTenant(string $tenantId)
 * @method static Builder<static> forUser(string $userId)
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> forSession(string $sessionId)
 * @method static Builder<static> ofType(ActivityType $type)
 * @method static Builder<static> ofCategory(ActivityCategory $category)
 * @method static Builder<static> inDateRange(\Carbon\Carbon $start, \Carbon\Carbon $end)
 * @method static Builder<static> today()
 * @method static Builder<static> recent(int $days = 7)
 */
final class UserActivityLog extends Model
{
    use HasFactory;
    use HasUuids;
    use BelongsToTenant;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_activity_logs';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'workspace_id',
        'activity_type',
        'activity_category',
        'resource_type',
        'resource_id',
        'page_url',
        'referrer_url',
        'session_id',
        'device_type',
        'browser',
        'os',
        'metadata',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_type' => ActivityType::class,
            'activity_category' => ActivityCategory::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (UserActivityLog $log): void {
            if ($log->created_at === null) {
                $log->created_at = now();
            }

            // Auto-set category from activity type if not provided
            if ($log->activity_category === null && $log->activity_type !== null) {
                $log->activity_category = $log->activity_type->category();
            }
        });
    }

    /**
     * Get the user that this activity belongs to.
     *
     * @return BelongsTo<User, UserActivityLog>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the workspace that this activity belongs to.
     *
     * @return BelongsTo<Workspace, UserActivityLog>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Scope to filter by user.
     *
     * @param  Builder<UserActivityLog>  $query
     * @return Builder<UserActivityLog>
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<UserActivityLog>  $query
     * @return Builder<UserActivityLog>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter by session.
     *
     * @param  Builder<UserActivityLog>  $query
     * @return Builder<UserActivityLog>
     */
    public function scopeForSession(Builder $query, string $sessionId): Builder
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope to filter by activity type.
     *
     * @param  Builder<UserActivityLog>  $query
     * @return Builder<UserActivityLog>
     */
    public function scopeOfType(Builder $query, ActivityType $type): Builder
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Scope to filter by activity category.
     *
     * @param  Builder<UserActivityLog>  $query
     * @return Builder<UserActivityLog>
     */
    public function scopeOfCategory(Builder $query, ActivityCategory $category): Builder
    {
        return $query->where('activity_category', $category);
    }

    /**
     * Scope to filter by date range.
     *
     * @param  Builder<UserActivityLog>  $query
     * @return Builder<UserActivityLog>
     */
    public function scopeInDateRange(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Scope to get today's activities.
     *
     * @param  Builder<UserActivityLog>  $query
     * @return Builder<UserActivityLog>
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    /**
     * Scope to get recent activities.
     *
     * @param  Builder<UserActivityLog>  $query
     * @return Builder<UserActivityLog>
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Check if the activity is related to content creation.
     */
    public function isContentCreation(): bool
    {
        return $this->activity_category === ActivityCategory::CONTENT_CREATION;
    }

    /**
     * Check if the activity is related to publishing.
     */
    public function isPublishing(): bool
    {
        return $this->activity_category === ActivityCategory::PUBLISHING;
    }

    /**
     * Check if the activity is related to engagement.
     */
    public function isEngagement(): bool
    {
        return $this->activity_category === ActivityCategory::ENGAGEMENT;
    }

    /**
     * Check if the activity is related to analytics.
     */
    public function isAnalytics(): bool
    {
        return $this->activity_category === ActivityCategory::ANALYTICS;
    }

    /**
     * Check if the activity is related to AI features.
     */
    public function isAIFeature(): bool
    {
        return $this->activity_category === ActivityCategory::AI_FEATURES;
    }

    /**
     * Check if the activity is related to authentication.
     */
    public function isAuthentication(): bool
    {
        return $this->activity_category === ActivityCategory::AUTHENTICATION;
    }

    /**
     * Check if activity is from mobile device.
     */
    public function isMobile(): bool
    {
        return $this->device_type === 'mobile';
    }

    /**
     * Check if activity is from desktop device.
     */
    public function isDesktop(): bool
    {
        return $this->device_type === 'desktop';
    }

    /**
     * Check if activity is from tablet device.
     */
    public function isTablet(): bool
    {
        return $this->device_type === 'tablet';
    }

    /**
     * Get metadata value by key using dot notation.
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->metadata ?? [], $key, $default);
    }

    /**
     * Get the activity label.
     */
    public function getLabel(): string
    {
        return $this->activity_type->label();
    }

    /**
     * Get the category label.
     */
    public function getCategoryLabel(): string
    {
        return $this->activity_category->label();
    }

    /**
     * Log a user activity.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function log(
        User $user,
        ActivityType $activityType,
        ?string $workspaceId = null,
        ?string $resourceType = null,
        ?string $resourceId = null,
        ?string $pageUrl = null,
        ?string $sessionId = null,
        array $metadata = []
    ): static {
        return static::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'workspace_id' => $workspaceId,
            'activity_type' => $activityType,
            'activity_category' => $activityType->category(),
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'page_url' => $pageUrl,
            'session_id' => $sessionId,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log a page view activity.
     */
    public static function logPageView(
        User $user,
        string $pageUrl,
        ?string $workspaceId = null,
        ?string $referrerUrl = null,
        ?string $sessionId = null
    ): static {
        return static::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'workspace_id' => $workspaceId,
            'activity_type' => ActivityType::DASHBOARD_VIEWED,
            'activity_category' => ActivityCategory::ANALYTICS,
            'page_url' => $pageUrl,
            'referrer_url' => $referrerUrl,
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Log a login activity.
     *
     * @param  array<string, mixed>  $deviceInfo
     */
    public static function logLogin(
        User $user,
        ?string $sessionId = null,
        array $deviceInfo = []
    ): static {
        return static::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'activity_type' => ActivityType::USER_LOGIN,
            'activity_category' => ActivityCategory::AUTHENTICATION,
            'session_id' => $sessionId,
            'device_type' => $deviceInfo['device_type'] ?? null,
            'browser' => $deviceInfo['browser'] ?? null,
            'os' => $deviceInfo['os'] ?? null,
            'metadata' => $deviceInfo,
        ]);
    }

    /**
     * Log a logout activity.
     */
    public static function logLogout(User $user, ?string $sessionId = null): static
    {
        return static::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'activity_type' => ActivityType::USER_LOGOUT,
            'activity_category' => ActivityCategory::AUTHENTICATION,
            'session_id' => $sessionId,
        ]);
    }
}
