<?php

declare(strict_types=1);

namespace App\Models\Audit;

use App\Enums\Audit\AuditAction;
use App\Enums\Audit\AuditableType;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * AuditLog Model
 *
 * Represents an audit log entry tracking user actions.
 *
 * @property string $id UUID primary key
 * @property string|null $tenant_id Tenant UUID
 * @property string|null $user_id User UUID
 * @property string|null $admin_id Admin UUID
 * @property AuditAction $action Action performed
 * @property AuditableType $auditable_type Type of resource
 * @property string|null $auditable_id Resource UUID
 * @property string|null $description Description of the action
 * @property array|null $old_values Previous values
 * @property array|null $new_values New values
 * @property array|null $metadata Additional metadata
 * @property string|null $ip_address IP address
 * @property string|null $user_agent User agent string
 * @property string|null $request_id Request ID for correlation
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Tenant|null $tenant
 * @property-read User|null $user
 * @property-read SuperAdminUser|null $admin
 * @property-read Model|null $auditable
 *
 * @method static Builder<static> forTenant(string $tenantId)
 * @method static Builder<static> forUser(string $userId)
 * @method static Builder<static> byAction(AuditAction $action)
 * @method static Builder<static> byType(AuditableType $type)
 * @method static Builder<static> recent(int $limit = 10)
 * @method static Builder<static> search(string $query)
 * @method static Builder<static> inDateRange(\Carbon\Carbon $start, \Carbon\Carbon $end)
 */
final class AuditLog extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'audit_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'admin_id',
        'action',
        'auditable_type',
        'auditable_id',
        'description',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'request_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'auditable_type' => AuditableType::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the tenant.
     *
     * @return BelongsTo<Tenant, AuditLog>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the user.
     *
     * @return BelongsTo<User, AuditLog>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the admin.
     *
     * @return BelongsTo<SuperAdminUser, AuditLog>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(SuperAdminUser::class, 'admin_id');
    }

    /**
     * Get the auditable model.
     *
     * @return MorphTo<Model, AuditLog>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo('auditable', 'auditable_type', 'auditable_id');
    }

    /**
     * Scope to filter by tenant.
     *
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by user.
     *
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by action.
     *
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    public function scopeByAction(Builder $query, AuditAction $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by auditable type.
     *
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    public function scopeByType(Builder $query, AuditableType $type): Builder
    {
        return $query->where('auditable_type', $type);
    }

    /**
     * Scope to get recent logs.
     *
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope to search logs.
     *
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    public function scopeSearch(Builder $query, string $searchQuery): Builder
    {
        return $query->where(function (Builder $q) use ($searchQuery) {
            $q->where('description', 'like', "%{$searchQuery}%")
                ->orWhere('request_id', 'like', "%{$searchQuery}%");
        });
    }

    /**
     * Scope to filter by date range.
     *
     * @param  Builder<AuditLog>  $query
     * @return Builder<AuditLog>
     */
    public function scopeInDateRange(Builder $query, \Carbon\Carbon $start, \Carbon\Carbon $end): Builder
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Check if this is a create action.
     */
    public function isCreate(): bool
    {
        return $this->action === AuditAction::CREATE;
    }

    /**
     * Check if this is an update action.
     */
    public function isUpdate(): bool
    {
        return $this->action === AuditAction::UPDATE;
    }

    /**
     * Check if this is a delete action.
     */
    public function isDelete(): bool
    {
        return $this->action === AuditAction::DELETE;
    }

    /**
     * Get the fields that changed.
     *
     * @return array<string>
     */
    public function getChangedFields(): array
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        return array_keys(array_diff_assoc($this->new_values, $this->old_values));
    }

    /**
     * Get the old value for a field.
     */
    public function getOldValue(string $field): mixed
    {
        return $this->old_values[$field] ?? null;
    }

    /**
     * Get the new value for a field.
     */
    public function getNewValue(string $field): mixed
    {
        return $this->new_values[$field] ?? null;
    }
}
