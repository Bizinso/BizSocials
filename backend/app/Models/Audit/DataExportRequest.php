<?php

declare(strict_types=1);

namespace App\Models\Audit;

use App\Enums\Audit\DataRequestStatus;
use App\Enums\Audit\DataRequestType;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DataExportRequest Model
 *
 * Represents a GDPR data export request.
 *
 * @property string $id UUID primary key
 * @property string $tenant_id Tenant UUID
 * @property string|null $user_id User UUID (whose data to export)
 * @property string $requested_by Requester user UUID
 * @property DataRequestType $request_type Type of request
 * @property DataRequestStatus $status Request status
 * @property array|null $data_categories Categories of data to export
 * @property string $format Export format (json, csv, xml)
 * @property string|null $file_path Path to exported file
 * @property int|null $file_size_bytes File size in bytes
 * @property \Carbon\Carbon|null $expires_at Expiration timestamp
 * @property int $download_count Number of downloads
 * @property \Carbon\Carbon|null $completed_at Completion timestamp
 * @property string|null $failure_reason Failure reason
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Tenant $tenant
 * @property-read User|null $user
 * @property-read User $requester
 *
 * @method static Builder<static> forTenant(string $tenantId)
 * @method static Builder<static> forUser(string $userId)
 * @method static Builder<static> pending()
 * @method static Builder<static> completed()
 * @method static Builder<static> expired()
 * @method static Builder<static> byType(DataRequestType $type)
 */
final class DataExportRequest extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'data_export_requests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'requested_by',
        'request_type',
        'status',
        'data_categories',
        'format',
        'file_path',
        'file_size_bytes',
        'expires_at',
        'download_count',
        'completed_at',
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
            'request_type' => DataRequestType::class,
            'status' => DataRequestStatus::class,
            'data_categories' => 'array',
            'file_size_bytes' => 'integer',
            'expires_at' => 'datetime',
            'download_count' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant.
     *
     * @return BelongsTo<Tenant, DataExportRequest>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the user whose data is being exported.
     *
     * @return BelongsTo<User, DataExportRequest>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who requested the export.
     *
     * @return BelongsTo<User, DataExportRequest>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Scope to filter by tenant.
     *
     * @param  Builder<DataExportRequest>  $query
     * @return Builder<DataExportRequest>
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by user.
     *
     * @param  Builder<DataExportRequest>  $query
     * @return Builder<DataExportRequest>
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get pending requests.
     *
     * @param  Builder<DataExportRequest>  $query
     * @return Builder<DataExportRequest>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', DataRequestStatus::PENDING);
    }

    /**
     * Scope to get completed requests.
     *
     * @param  Builder<DataExportRequest>  $query
     * @return Builder<DataExportRequest>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', DataRequestStatus::COMPLETED);
    }

    /**
     * Scope to get expired requests.
     *
     * @param  Builder<DataExportRequest>  $query
     * @return Builder<DataExportRequest>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope to filter by request type.
     *
     * @param  Builder<DataExportRequest>  $query
     * @return Builder<DataExportRequest>
     */
    public function scopeByType(Builder $query, DataRequestType $type): Builder
    {
        return $query->where('request_type', $type);
    }

    /**
     * Check if the request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === DataRequestStatus::PENDING;
    }

    /**
     * Check if the request is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === DataRequestStatus::COMPLETED;
    }

    /**
     * Check if the export has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Start processing the request.
     */
    public function start(): void
    {
        $this->status = DataRequestStatus::PROCESSING;
        $this->save();
    }

    /**
     * Mark the request as completed.
     */
    public function complete(string $filePath, int $fileSize): void
    {
        $this->status = DataRequestStatus::COMPLETED;
        $this->file_path = $filePath;
        $this->file_size_bytes = $fileSize;
        $this->completed_at = now();
        $this->expires_at = now()->addDays(7);
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
     * Increment the download count.
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    /**
     * Get the download URL.
     */
    public function getDownloadUrl(): ?string
    {
        if (!$this->file_path || $this->isExpired()) {
            return null;
        }

        return url('storage/' . $this->file_path);
    }
}
