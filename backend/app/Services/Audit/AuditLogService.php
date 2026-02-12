<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Enums\Audit\AuditAction;
use App\Enums\Audit\AuditableType;
use App\Models\Audit\AuditLog;
use App\Models\Platform\SuperAdminUser;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

final class AuditLogService extends BaseService
{
    /**
     * Record an audit event.
     */
    public function record(
        AuditAction $action,
        Model $auditable,
        ?User $user = null,
        ?SuperAdminUser $admin = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        ?array $metadata = null,
    ): AuditLog {
        $auditableType = $this->resolveAuditableType($auditable);
        $tenantId = $this->resolveTenantId($auditable, $user);

        return AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $user?->id,
            'admin_id' => $admin?->id,
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditable->getKey(),
            'description' => $description ?? $this->generateDescription($action, $auditableType),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'request_id' => Request::header('X-Request-ID'),
        ]);
    }

    /**
     * List audit logs for a tenant.
     *
     * @param array<string, mixed> $filters
     */
    public function listForTenant(Tenant $tenant, array $filters = []): LengthAwarePaginator
    {
        $query = AuditLog::forTenant($tenant->id)
            ->with(['user', 'admin']);

        $this->applyFilters($query, $filters);

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }

    /**
     * List audit logs for a user.
     *
     * @param array<string, mixed> $filters
     */
    public function listForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = AuditLog::forUser($user->id)
            ->with(['user', 'admin']);

        $this->applyFilters($query, $filters);

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }

    /**
     * List audit logs for a specific auditable resource.
     */
    public function listForAuditable(Model $auditable): Collection
    {
        $auditableType = $this->resolveAuditableType($auditable);

        return AuditLog::where('auditable_type', $auditableType)
            ->where('auditable_id', $auditable->getKey())
            ->with(['user', 'admin'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * List audit logs for a specific auditable resource (paginated).
     */
    public function listForAuditablePaginated(Model $auditable, int $perPage = 25): LengthAwarePaginator
    {
        $auditableType = $this->resolveAuditableType($auditable);

        return AuditLog::where('auditable_type', $auditableType)
            ->where('auditable_id', $auditable->getKey())
            ->with(['user', 'admin'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get audit logs by type for a tenant.
     *
     * @param array<string, mixed> $filters
     */
    public function getByType(Tenant $tenant, AuditableType $type, array $filters = []): LengthAwarePaginator
    {
        $query = AuditLog::forTenant($tenant->id)
            ->byType($type)
            ->with(['user', 'admin']);

        $this->applyFilters($query, $filters);

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Resolve the auditable type from a model.
     */
    private function resolveAuditableType(Model $model): AuditableType
    {
        return match (get_class($model)) {
            \App\Models\User::class => AuditableType::USER,
            \App\Models\Tenant\Tenant::class => AuditableType::TENANT,
            \App\Models\Workspace\Workspace::class => AuditableType::WORKSPACE,
            \App\Models\Social\SocialAccount::class => AuditableType::SOCIAL_ACCOUNT,
            \App\Models\Content\Post::class => AuditableType::POST,
            \App\Models\Billing\Subscription::class => AuditableType::SUBSCRIPTION,
            \App\Models\Billing\Invoice::class => AuditableType::INVOICE,
            \App\Models\Support\SupportTicket::class => AuditableType::SUPPORT_TICKET,
            \App\Models\Workspace\Team::class => AuditableType::TEAM,
            \App\Models\Platform\SocialPlatformIntegration::class => AuditableType::PLATFORM_INTEGRATION,
            default => AuditableType::OTHER,
        };
    }

    /**
     * Resolve the tenant ID from a model or user.
     */
    private function resolveTenantId(Model $model, ?User $user): ?string
    {
        if (method_exists($model, 'tenant') && $model->tenant_id) {
            return $model->tenant_id;
        }

        if ($model instanceof Tenant) {
            return $model->id;
        }

        return $user?->tenant_id;
    }

    /**
     * Generate a description for the audit log.
     */
    private function generateDescription(AuditAction $action, AuditableType $type): string
    {
        return sprintf('%s performed on %s', $action->label(), $type->label());
    }

    /**
     * Apply filters to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder<AuditLog> $query
     * @param array<string, mixed> $filters
     */
    private function applyFilters($query, array $filters): void
    {
        // Filter by action
        if (!empty($filters['action'])) {
            $action = AuditAction::tryFrom($filters['action']);
            if ($action !== null) {
                $query->byAction($action);
            }
        }

        // Filter by auditable type
        if (!empty($filters['auditable_type'])) {
            $type = AuditableType::tryFrom($filters['auditable_type']);
            if ($type !== null) {
                $query->byType($type);
            }
        }

        // Filter by date range
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $start = Carbon::parse($filters['start_date'])->startOfDay();
            $end = Carbon::parse($filters['end_date'])->endOfDay();
            $query->inDateRange($start, $end);
        }

        // Search
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }
    }
}
