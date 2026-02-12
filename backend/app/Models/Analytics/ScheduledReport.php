<?php

declare(strict_types=1);

namespace App\Models\Analytics;

use App\Models\Workspace\Workspace;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ScheduledReport Model
 *
 * Represents a recurring scheduled report configuration for a workspace.
 *
 * @property string $id UUID primary key
 * @property string $workspace_id Workspace UUID
 * @property string $name Report name
 * @property string $report_type Type of report (performance, engagement, growth, etc.)
 * @property string $frequency Report frequency (weekly, monthly, quarterly)
 * @property array $recipients Email addresses to send the report to
 * @property array|null $parameters Additional report parameters
 * @property \Carbon\Carbon|null $next_send_at When the report should next be sent
 * @property bool $is_active Whether the report is active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 *
 * @method static Builder<static> forWorkspace(string $workspaceId)
 * @method static Builder<static> active()
 * @method static Builder<static> due()
 */
final class ScheduledReport extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'scheduled_reports';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workspace_id',
        'name',
        'report_type',
        'frequency',
        'recipients',
        'parameters',
        'next_send_at',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recipients' => 'array',
            'parameters' => 'array',
            'next_send_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the workspace that this scheduled report belongs to.
     *
     * @return BelongsTo<Workspace, ScheduledReport>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Scope to filter by workspace.
     *
     * @param  Builder<ScheduledReport>  $query
     * @return Builder<ScheduledReport>
     */
    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to filter active reports.
     *
     * @param  Builder<ScheduledReport>  $query
     * @return Builder<ScheduledReport>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter reports that are due to be sent.
     *
     * @param  Builder<ScheduledReport>  $query
     * @return Builder<ScheduledReport>
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereNotNull('next_send_at')
            ->where('next_send_at', '<=', Carbon::now());
    }
}
