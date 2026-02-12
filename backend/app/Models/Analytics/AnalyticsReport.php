<?php

declare(strict_types=1);

namespace App\Models\Analytics;

use App\Enums\Analytics\ReportStatus;
use App\Enums\Analytics\ReportType;
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
 * AnalyticsReport Model
 *
 * Represents a generated analytics report for a workspace.
 * Stores report configuration, status, and output file information.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string $created_by_user_id User UUID who created the report
 * @property string $name Report name
 * @property string|null $description Report description
 * @property ReportType $report_type Type of report
 * @property \Carbon\Carbon $date_from Report date range start
 * @property \Carbon\Carbon $date_to Report date range end
 * @property array|null $social_account_ids Selected social account UUIDs
 * @property array|null $metrics Selected metrics to include
 * @property array|null $filters Applied filters
 * @property ReportStatus $status Report generation status
 * @property string|null $file_path Path to generated report file
 * @property string $file_format Report file format (pdf, csv, xlsx)
 * @property int|null $file_size_bytes Size of the generated file
 * @property \Carbon\Carbon|null $completed_at When report generation completed
 * @property \Carbon\Carbon|null $expires_at When the report file expires
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 * @property-read User $createdBy
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> forUser(string $userId)
 * @method static Builder<static> ofType(ReportType $type)
 * @method static Builder<static> withStatus(ReportStatus $status)
 * @method static Builder<static> pending()
 * @method static Builder<static> processing()
 * @method static Builder<static> completed()
 * @method static Builder<static> failed()
 * @method static Builder<static> expired()
 * @method static Builder<static> available()
 * @method static Builder<static> recent(int $days = 30)
 */
final class AnalyticsReport extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'analytics_reports';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'created_by_user_id',
        'name',
        'description',
        'report_type',
        'date_from',
        'date_to',
        'social_account_ids',
        'metrics',
        'filters',
        'status',
        'file_path',
        'file_format',
        'file_size_bytes',
        'completed_at',
        'expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
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
            'file_size_bytes' => 'integer',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the workspace that this report belongs to.
     *
     * @return BelongsTo<Workspace, AnalyticsReport>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the user who created this report.
     *
     * @return BelongsTo<User, AnalyticsReport>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<AnalyticsReport>  $query
     * @return Builder<AnalyticsReport>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter by user who created the report.
     *
     * @param  Builder<AnalyticsReport>  $query
     * @return Builder<AnalyticsReport>
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('created_by_user_id', $userId);
    }

    /**
     * Scope to filter by report type.
     *
     * @param  Builder<AnalyticsReport>  $query
     * @return Builder<AnalyticsReport>
     */
    public function scopeOfType(Builder $query, ReportType $type): Builder
    {
        return $query->where('report_type', $type);
    }

    /**
     * Scope to filter by status.
     *
     * @param  Builder<AnalyticsReport>  $query
     * @return Builder<AnalyticsReport>
     */
    public function scopeWithStatus(Builder $query, ReportStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending reports.
     *
     * @param  Builder<AnalyticsReport>  $query
     * @return Builder<AnalyticsReport>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ReportStatus::PENDING);
    }

    /**
     * Scope to get processing reports.
     *
     * @param  Builder<AnalyticsReport>  $query
     * @return Builder<AnalyticsReport>
     */
    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', ReportStatus::PROCESSING);
    }

    /**
     * Scope to get completed reports.
     *
     * @param  Builder<AnalyticsReport>  $query
     * @return Builder<AnalyticsReport>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', ReportStatus::COMPLETED);
    }

    /**
     * Scope to get failed reports.
     *
     * @param  Builder<AnalyticsReport>  $query
     * @return Builder<AnalyticsReport>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', ReportStatus::FAILED);
    }

    /**
     * Scope to get expired reports.
     *
     * @param  Builder<AnalyticsReport>  $query
     * @return Builder<AnalyticsReport>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', ReportStatus::EXPIRED);
    }

    /**
     * Scope to get available (completed and not expired) reports.
     *
     * @param  Builder<AnalyticsReport>  $query
     * @return Builder<AnalyticsReport>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', ReportStatus::COMPLETED)
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to get recent reports.
     *
     * @param  Builder<AnalyticsReport>  $query
     * @return Builder<AnalyticsReport>
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Check if the report is pending.
     */
    public function isPending(): bool
    {
        return $this->status === ReportStatus::PENDING;
    }

    /**
     * Check if the report is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === ReportStatus::PROCESSING;
    }

    /**
     * Check if the report is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === ReportStatus::COMPLETED;
    }

    /**
     * Check if the report generation failed.
     */
    public function isFailed(): bool
    {
        return $this->status === ReportStatus::FAILED;
    }

    /**
     * Check if the report has expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === ReportStatus::EXPIRED) {
            return true;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return true;
        }

        return false;
    }

    /**
     * Check if the report is available for download.
     */
    public function isAvailable(): bool
    {
        return $this->isCompleted() && !$this->isExpired() && $this->file_path !== null;
    }

    /**
     * Check if the report is in a terminal state.
     */
    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    /**
     * Get the date range in days.
     */
    public function getDateRangeDays(): int
    {
        return $this->date_from->diffInDays($this->date_to) + 1;
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanFileSize(): ?string
    {
        if ($this->file_size_bytes === null) {
            return null;
        }

        $bytes = $this->file_size_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the number of selected social accounts.
     */
    public function getSocialAccountCount(): int
    {
        return count($this->social_account_ids ?? []);
    }

    /**
     * Check if the report includes all social accounts.
     */
    public function includesAllAccounts(): bool
    {
        return empty($this->social_account_ids);
    }

    /**
     * Get a filter value by key.
     */
    public function getFilter(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->filters ?? [], $key, $default);
    }

    /**
     * Get a metric value by key.
     */
    public function getMetric(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->metrics ?? [], $key, $default);
    }

    /**
     * Get the report type label.
     */
    public function getTypeLabel(): string
    {
        return $this->report_type->label();
    }

    /**
     * Get the report type description.
     */
    public function getTypeDescription(): string
    {
        return $this->report_type->description();
    }

    /**
     * Get the status label.
     */
    public function getStatusLabel(): string
    {
        return $this->status->label();
    }

    /**
     * Mark the report as processing.
     */
    public function markAsProcessing(): void
    {
        $this->status = ReportStatus::PROCESSING;
        $this->save();
    }

    /**
     * Mark the report as completed.
     */
    public function markAsCompleted(
        string $filePath,
        int $fileSize,
        ?Carbon $expiresAt = null
    ): void {
        $this->status = ReportStatus::COMPLETED;
        $this->file_path = $filePath;
        $this->file_size_bytes = $fileSize;
        $this->completed_at = now();
        $this->expires_at = $expiresAt ?? now()->addDays(30);
        $this->save();
    }

    /**
     * Mark the report as failed.
     */
    public function markAsFailed(?string $error = null): void
    {
        $this->status = ReportStatus::FAILED;
        $this->save();
    }

    /**
     * Check if the report is downloadable.
     */
    public function isDownloadable(): bool
    {
        return $this->status->isDownloadable();
    }

    /**
     * Check if the report generation can be retried.
     */
    public function canRetry(): bool
    {
        return $this->status->canRetry();
    }

    /**
     * Get a formatted date range label.
     */
    public function getDateRangeLabel(): string
    {
        return $this->date_from->format('M j, Y') . ' - ' . $this->date_to->format('M j, Y');
    }

    /**
     * Get formatted file size for display.
     */
    public function getFileSizeFormatted(): string
    {
        return $this->getHumanFileSize() ?? 'N/A';
    }

    /**
     * Mark the report as expired.
     */
    public function markAsExpired(): void
    {
        $this->status = ReportStatus::EXPIRED;
        $this->save();
    }

    /**
     * Create a report for a workspace.
     *
     * @param  array<string>|null  $socialAccountIds
     * @param  array<string, mixed>|null  $metrics
     * @param  array<string, mixed>|null  $filters
     */
    public static function createForWorkspace(
        Workspace $workspace,
        User $user,
        string $name,
        ReportType $reportType,
        Carbon $dateFrom,
        Carbon $dateTo,
        ?array $socialAccountIds = null,
        ?array $metrics = null,
        ?array $filters = null,
        string $fileFormat = 'pdf',
        ?string $description = null
    ): static {
        return static::create([
            'workspace_id' => $workspace->id,
            'created_by_user_id' => $user->id,
            'name' => $name,
            'description' => $description,
            'report_type' => $reportType,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'social_account_ids' => $socialAccountIds,
            'metrics' => $metrics,
            'filters' => $filters,
            'status' => ReportStatus::PENDING,
            'file_format' => $fileFormat,
        ]);
    }
}
