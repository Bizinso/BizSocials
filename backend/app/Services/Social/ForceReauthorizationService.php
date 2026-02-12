<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Enums\Audit\AuditAction;
use App\Enums\Notification\NotificationType;
use App\Enums\Social\SocialAccountStatus;
use App\Models\Platform\SocialPlatformIntegration;
use App\Models\Platform\SuperAdminUser;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Services\Audit\AuditLogService;
use App\Services\BaseService;
use App\Services\Notification\NotificationService;

final class ForceReauthorizationService extends BaseService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Force re-authorization for all accounts on specified platforms.
     *
     * @param array<string> $platforms Platform values (e.g. ['facebook', 'instagram'])
     * @return array{accounts_revoked: int, tenants_affected: int, tenants_notified: int}
     */
    public function execute(
        SocialPlatformIntegration $integration,
        array $platforms,
        string $reason,
        SuperAdminUser $admin,
        bool $notifyTenants = true,
    ): array {
        // 1. Find all affected accounts (connected or token_expired)
        $accounts = SocialAccount::query()
            ->whereIn('platform', $platforms)
            ->whereIn('status', [
                SocialAccountStatus::CONNECTED,
                SocialAccountStatus::TOKEN_EXPIRED,
            ])
            ->with('workspace')
            ->get();

        // 2. Revoke each account
        foreach ($accounts as $account) {
            $account->update([
                'status' => SocialAccountStatus::REVOKED,
                'metadata' => array_merge($account->metadata ?? [], [
                    'revoke_reason' => 'admin_forced_reauth',
                    'revoke_detail' => $reason,
                    'revoked_at' => now()->toIso8601String(),
                    'revoked_by_admin' => $admin->id,
                ]),
            ]);
        }

        // 3. Identify affected tenants via workspace → tenant_id
        $tenantIds = $accounts
            ->pluck('workspace.tenant_id')
            ->filter()
            ->unique()
            ->values();

        // 4. Notify tenants
        $tenantsNotified = 0;
        if ($notifyTenants && $tenantIds->isNotEmpty()) {
            $tenants = Tenant::whereIn('id', $tenantIds)->get();
            $platformLabels = implode(' and ', array_map('ucfirst', $platforms));

            foreach ($tenants as $tenant) {
                $this->notificationService->sendToTenant(
                    tenant: $tenant,
                    type: NotificationType::PLATFORM_REAUTH_REQUIRED,
                    title: 'Action Required: Reconnect your social accounts',
                    message: "Your {$platformLabels} accounts have been disconnected: {$reason}. Please reconnect your accounts.",
                    data: ['platforms' => $platforms, 'reason' => $reason],
                );
                $tenantsNotified++;
            }
        }

        // 5. Audit
        $this->auditLogService->record(
            action: AuditAction::SETTINGS_CHANGE,
            auditable: $integration,
            admin: $admin,
            description: "Forced re-authorization: {$reason}",
            metadata: [
                'platforms' => $platforms,
                'accounts_revoked' => $accounts->count(),
                'tenants_affected' => $tenantIds->count(),
                'reason' => $reason,
            ],
        );

        return [
            'accounts_revoked' => $accounts->count(),
            'tenants_affected' => $tenantIds->count(),
            'tenants_notified' => $tenantsNotified,
        ];
    }
}
