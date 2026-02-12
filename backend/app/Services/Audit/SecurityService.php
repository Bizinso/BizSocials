<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Data\Audit\SecurityStatsData;
use App\Enums\Audit\SecurityEventType;
use App\Enums\Audit\SecuritySeverity;
use App\Models\Audit\SecurityEvent;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Request;

final class SecurityService extends BaseService
{
    /**
     * Log a security event.
     *
     * @param array<string, mixed> $metadata
     */
    public function logEvent(
        SecurityEventType $type,
        ?User $user = null,
        array $metadata = [],
        ?string $description = null,
    ): SecurityEvent {
        $severity = SecuritySeverity::tryFrom($type->severity()) ?? SecuritySeverity::INFO;

        return SecurityEvent::create([
            'tenant_id' => $user?->tenant_id,
            'user_id' => $user?->id,
            'event_type' => $type,
            'severity' => $severity,
            'description' => $description ?? $type->label(),
            'metadata' => $metadata,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'country_code' => $metadata['country_code'] ?? null,
            'city' => $metadata['city'] ?? null,
            'is_resolved' => false,
        ]);
    }

    /**
     * List security events for a tenant.
     *
     * @param array<string, mixed> $filters
     */
    public function listForTenant(Tenant $tenant, array $filters = []): LengthAwarePaginator
    {
        $query = SecurityEvent::forTenant($tenant->id)
            ->with(['user', 'resolver']);

        $this->applyFilters($query, $filters);

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }

    /**
     * List security events for a user.
     *
     * @param array<string, mixed> $filters
     */
    public function listForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = SecurityEvent::forUser($user->id)
            ->with(['user', 'resolver']);

        $this->applyFilters($query, $filters);

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }

    /**
     * Get high severity events for a tenant.
     */
    public function getHighSeverityEvents(Tenant $tenant, int $limit = 10): Collection
    {
        return SecurityEvent::forTenant($tenant->id)
            ->whereIn('severity', [SecuritySeverity::HIGH, SecuritySeverity::CRITICAL])
            ->unresolved()
            ->recent($limit)
            ->with(['user'])
            ->get();
    }

    /**
     * Get security statistics for a tenant.
     */
    public function getStats(Tenant $tenant): SecurityStatsData
    {
        $baseQuery = SecurityEvent::forTenant($tenant->id);

        $totalEvents = $baseQuery->count();
        $criticalEvents = (clone $baseQuery)->bySeverity(SecuritySeverity::CRITICAL)->count();
        $highEvents = (clone $baseQuery)->bySeverity(SecuritySeverity::HIGH)->count();
        $mediumEvents = (clone $baseQuery)->bySeverity(SecuritySeverity::MEDIUM)->count();
        $unresolvedEvents = (clone $baseQuery)->unresolved()->count();

        // Failed logins in last 24 hours
        $failedLogins24h = SecurityEvent::forTenant($tenant->id)
            ->byType(SecurityEventType::LOGIN_FAILURE)
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->count();

        // Suspicious activities
        $suspiciousActivities = SecurityEvent::forTenant($tenant->id)
            ->byType(SecurityEventType::SUSPICIOUS_ACTIVITY)
            ->unresolved()
            ->count();

        // Events by type
        $eventsByType = [];
        foreach (SecurityEventType::cases() as $type) {
            $count = SecurityEvent::forTenant($tenant->id)
                ->byType($type)
                ->count();
            if ($count > 0) {
                $eventsByType[$type->value] = $count;
            }
        }

        // Events by severity
        $eventsBySeverity = [];
        foreach (SecuritySeverity::cases() as $severity) {
            $count = SecurityEvent::forTenant($tenant->id)
                ->bySeverity($severity)
                ->count();
            $eventsBySeverity[$severity->value] = $count;
        }

        return new SecurityStatsData(
            total_events: $totalEvents,
            critical_events: $criticalEvents,
            high_events: $highEvents,
            medium_events: $mediumEvents,
            failed_logins_24h: $failedLogins24h,
            suspicious_activities: $suspiciousActivities,
            unresolved_events: $unresolvedEvents,
            events_by_type: $eventsByType,
            events_by_severity: $eventsBySeverity,
        );
    }

    /**
     * Apply filters to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder<SecurityEvent> $query
     * @param array<string, mixed> $filters
     */
    private function applyFilters($query, array $filters): void
    {
        // Filter by event type
        if (!empty($filters['event_type'])) {
            $type = SecurityEventType::tryFrom($filters['event_type']);
            if ($type !== null) {
                $query->byType($type);
            }
        }

        // Filter by severity
        if (!empty($filters['severity'])) {
            $severity = SecuritySeverity::tryFrom($filters['severity']);
            if ($severity !== null) {
                $query->bySeverity($severity);
            }
        }

        // Filter by resolved status
        if (isset($filters['is_resolved'])) {
            if ($filters['is_resolved']) {
                $query->where('is_resolved', true);
            } else {
                $query->unresolved();
            }
        }

        // Filter by date range
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $start = Carbon::parse($filters['start_date'])->startOfDay();
            $end = Carbon::parse($filters['end_date'])->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        }

        // Filter by IP address
        if (!empty($filters['ip_address'])) {
            $query->fromIp($filters['ip_address']);
        }
    }
}
