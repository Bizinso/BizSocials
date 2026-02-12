<?php

declare(strict_types=1);

namespace App\Models\Audit;

use App\Enums\Audit\DataRequestStatus;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DataDeletionRequest Model
 *
 * Represents a GDPR data deletion request.
 *
 * @property string $id UUID primary key
 * @property string $tenant_id Tenant UUID
 * @property string|null $user_id User UUID (whose data to delete)
 * @property string $requested_by Requester user UUID
 * @property DataRequestStatus $status Request status
 * @property array|null $data_categories Categories of data to delete
 * @property string|null $reason Reason for deletion
 * @property bool $requires_approval Whether approval is required
 * @property string|null $approved_by Approver admin UUID
 * @property \Carbon\Carbon|null $approved_at Approval timestamp
 * @property \Carbon\Carbon|null $scheduled_for Scheduled deletion timestamp
 * @property \Carbon\Carbon|null $completed_at Completion timestamp
 * @property array|null $deletion_summary Summary of deleted data
 * @property string|null $failure_reason Failure reason
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Tenant $tenant
 * @property-read User|null $user
 * @property-read User $requester
 * @property-read SuperAdminUser|null $approver
 *
 * @method static Builder<static> forTenant(string $tenantId)
 * @method static Builder<static> forUser(string $userId)
 * @method static Builder<static> pending()
 * @method static Builder<static> approved()
 * @method static Builder<static> scheduled()
 * @method static Builder<static> completed()
 */
final class DataDeletionRequest extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'data_deletion_requests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'requested_by',
        'status',
        'data_categories',
        'reason',
        'requires_approval',
        'approved_by',
        'approved_at',
        'scheduled_for',
        'completed_at',
        'deletion_summary',
        'failure_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DataRequestStatus::class,
            'data_categories' => 'array',
            'requires_approval' => 'boolean',
            'approved_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'completed_at' => 'datetime',
            'deletion_summary' => 'array',
        ];
    }

    /**
     * Get the tenant.
     *
     * @return BelongsTo<Tenant, DataDeletionRequest>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the user whose data is being deleted.
     *
     * @return BelongsTo<User, DataDeletionRequest>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who requested the deletion.
     *
     * @return BelongsTo<User, DataDeletionRequest>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the admin who approved the deletion.
     *
     * @return BelongsTo<SuperAdminUser, DataDeletionRequest>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(SuperAdminUser::class, 'approved_by');
    }

    /**
     * Scope to filter by tenant.
     *
     * @param  Builder<DataDeletionRequest>  $query
     * @return Builder<DataDeletionRequest>
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by user.
     *
     * @param  Builder<DataDeletionRequest>  $query
     * @return Builder<DataDeletionRequest>
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get pending requests.
     *
     * @param  Builder<DataDeletionRequest>  $query
     * @return Builder<DataDeletionRequest>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', DataRequestStatus::PENDING);
    }

    /**
     * Scope to get approved requests.
     *
     * @param  Builder<DataDeletionRequest>  $query
     * @return Builder<DataDeletionRequest>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->whereNotNull('approved_at');
    }

    /**
     * Scope to get scheduled requests.
     *
     * @param  Builder<DataDeletionRequest>  $query
     * @return Builder<DataDeletionRequest>
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->whereNotNull('scheduled_for')
            ->where('status', DataRequestStatus::PENDING);
    }

    /**
     * Scope to get completed requests.
     *
     * @param  Builder<DataDeletionRequest>  $query
     * @return Builder<DataDeletionRequest>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', DataRequestStatus::COMPLETED);
    }

    /**
     * Check if the request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === DataRequestStatus::PENDING;
    }

    /**
     * Check if the request is approved.
     */
    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /**
     * Check if the request needs approval.
     */
    public function needsApproval(): bool
    {
        return $this->requires_approval && !$this->isApproved();
    }

    /**
     * Approve the deletion request.
     */
    public function approve(SuperAdminUser $admin, ?\Carbon\Carbon $scheduledFor = null): void
    {
        $this->approved_by = $admin->id;
        $this->approved_at = now();
        $this->scheduled_for = $scheduledFor ?? now()->addDays(30);
        $this->save();
    }

    /**
     * Mark the request as completed.
     *
     * @param  array<string, mixed>  $summary
     */
    public function complete(array $summary): void
    {
        $this->status = DataRequestStatus::COMPLETED;
        $this->completed_at = now();
        $this->deletion_summary = $summary;
        $this->save();
    }

    /**
     * Mark the request as failed.
     */
    public function fail(string $reason): void
    {
        $this->status = DataRequestStatus::FAILED;
        $this->failure_reason = $reason;
        $this->save();
    }

    /**
     * Cancel the request.
     */
    public function cancel(): void
    {
        $this->status = DataRequestStatus::CANCELLED;
        $this->save();
    }
}
