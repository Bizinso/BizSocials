<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\Audit\AuditAction;
use App\Enums\Notification\NotificationType;
use App\Enums\Platform\IntegrationStatus;
use App\Enums\Social\SocialAccountStatus;
use App\Enums\Social\SocialPlatform;
use App\Models\Platform\SocialPlatformIntegration;
use App\Models\Platform\SuperAdminUser;
use App\Models\Social\SocialAccount;
use App\Models\Tenant\Tenant;
use App\Services\Audit\AuditLogService;
use App\Services\BaseService;
use App\Services\Notification\NotificationService;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Crypt;

final class IntegrationService extends BaseService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * List all integrations with account stats.
     *
     * @return Collection<int, SocialPlatformIntegration>
     */
    public function list(): Collection
    {
        return SocialPlatformIntegration::with('updatedByAdmin')
            ->orderBy('display_name')
            ->get();
    }

    /**
     * Get integration by provider with stats.
     */
    public function getByProvider(string $provider): ?SocialPlatformIntegration
    {
        return SocialPlatformIntegration::where('provider', $provider)
            ->with('updatedByAdmin')
            ->first();
    }

    /**
     * Create or update an integration.
     *
     * @param array<string, mixed> $data
     * @return array{integration: SocialPlatformIntegration, scope_changes: array<string, array<string, array<string>>>, requires_reauth: bool, affected_accounts: int, affected_tenants: int}
     */
    public function upsert(string $provider, array $data, SuperAdminUser $admin): array
    {
        $integration = SocialPlatformIntegration::where('provider', $provider)->first();
        $isNew = $integration === null;
        $oldValues = [];
        $scopeChanges = [];
        $requiresReauth = false;
        $affectedAccounts = 0;
        $affectedTenants = 0;

        if ($isNew) {
            $integration = new SocialPlatformIntegration();
            $integration->provider = $provider;
            $integration->display_name = $data['display_name'] ?? $this->defaultDisplayName($provider);
            $integration->platforms = $data['platforms'] ?? $this->defaultPlatforms($provider);
            $integration->environment = 'production';
        } else {
            $oldValues = $this->captureOldValues($integration);
        }

        // Update credentials
        if (isset($data['app_id'])) {
            $integration->setAppId($data['app_id']);
        }
        if (isset($data['app_secret'])) {
            $integration->setAppSecret($data['app_secret']);
            $integration->last_rotated_at = now();
        }

        // Update config
        if (isset($data['api_version'])) {
            $integration->api_version = $data['api_version'];
        }
        if (isset($data['redirect_uris'])) {
            $integration->redirect_uris = $data['redirect_uris'];
        } elseif ($isNew) {
            $integration->redirect_uris = $this->defaultRedirectUris($provider);
        }

        // Scope changes — detect additions
        if (isset($data['scopes'])) {
            $oldScopes = $isNew ? [] : ($integration->scopes ?? []);
            if (is_string($oldScopes)) {
                $oldScopes = json_decode($oldScopes, true) ?: [];
            }
            $scopeChanges = $this->computeScopeChanges($oldScopes, $data['scopes']);
            $integration->scopes = $data['scopes'];

            // Check if any scopes were added (requires reauth)
            foreach ($scopeChanges as $platformChanges) {
                if (! empty($platformChanges['added'])) {
                    $requiresReauth = true;
                    break;
                }
            }
        } elseif ($isNew) {
            $integration->scopes = $this->defaultScopes($provider);
        }

        $integration->updated_by = $admin->id;
        $integration->save();

        // Handle scope addition → force reauth on affected accounts
        if ($requiresReauth && ! $isNew) {
            $platformsWithAdditions = [];
            foreach ($scopeChanges as $platform => $changes) {
                if (! empty($changes['added'])) {
                    $platformsWithAdditions[] = $platform;
                }
            }

            if (! empty($platformsWithAdditions)) {
                $result = $this->revokeAccountsForScopeChange($integration, $platformsWithAdditions, $admin);
                $affectedAccounts = $result['accounts'];
                $affectedTenants = $result['tenants'];
            }
        }

        // Audit
        $this->auditLogService->record(
            action: $isNew ? AuditAction::CREATE : AuditAction::SETTINGS_CHANGE,
            auditable: $integration,
            admin: $admin,
            oldValues: empty($oldValues) ? null : $oldValues,
            newValues: $this->captureNewValues($integration, $data),
            description: $isNew
                ? "Integration created: {$integration->display_name}"
                : "Integration updated: {$integration->display_name}",
            metadata: [
                'scope_changes' => $scopeChanges,
                'requires_reauth' => $requiresReauth,
                'affected_accounts' => $affectedAccounts,
            ],
        );

        return [
            'integration' => $integration->fresh('updatedByAdmin'),
            'scope_changes' => $scopeChanges,
            'requires_reauth' => $requiresReauth,
            'affected_accounts' => $affectedAccounts,
            'affected_tenants' => $affectedTenants,
        ];
    }

    /**
     * Verify credentials by calling Meta's debug_token endpoint.
     *
     * @return array{valid: bool, app_name: string|null, error: string|null, verified_at: string}
     */
    public function verifyCredentials(SocialPlatformIntegration $integration): array
    {
        try {
            $client = new Client(['timeout' => 10]);
            $response = $client->get("https://graph.facebook.com/{$integration->api_version}/app", [
                'query' => [
                    'access_token' => $integration->getAppId() . '|' . $integration->getAppSecret(),
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $integration->update(['last_verified_at' => now()]);

            return [
                'valid' => true,
                'app_name' => $body['name'] ?? null,
                'error' => null,
                'verified_at' => now()->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            $integration->update(['last_verified_at' => now()]);

            return [
                'valid' => false,
                'app_name' => null,
                'error' => 'Invalid App ID or App Secret',
                'verified_at' => now()->toIso8601String(),
            ];
        }
    }

    /**
     * Toggle integration enabled/disabled.
     */
    public function toggle(SocialPlatformIntegration $integration, bool $enabled, string $reason, SuperAdminUser $admin): SocialPlatformIntegration
    {
        $oldEnabled = $integration->is_enabled;
        $oldStatus = $integration->status;

        $integration->is_enabled = $enabled;
        $integration->status = $enabled ? IntegrationStatus::ACTIVE : IntegrationStatus::DISABLED;
        $integration->updated_by = $admin->id;
        $integration->save();

        // If disabling — disconnect all affected accounts
        if (! $enabled && $oldEnabled) {
            $accounts = SocialAccount::query()
                ->whereIn('platform', $integration->platforms)
                ->where('status', SocialAccountStatus::CONNECTED)
                ->with('workspace')
                ->get();

            foreach ($accounts as $account) {
                $account->update([
                    'status' => SocialAccountStatus::DISCONNECTED,
                    'disconnected_at' => now(),
                    'metadata' => array_merge($account->metadata ?? [], [
                        'disconnect_reason' => 'integration_disabled',
                        'disconnect_detail' => $reason,
                    ]),
                ]);
            }

            // Notify affected tenants
            $tenantIds = $accounts->pluck('workspace.tenant_id')->filter()->unique();
            $tenants = Tenant::whereIn('id', $tenantIds)->get();
            $platformLabels = implode(' and ', array_map('ucfirst', $integration->platforms));

            foreach ($tenants as $tenant) {
                $this->notificationService->sendToTenant(
                    tenant: $tenant,
                    type: NotificationType::PLATFORM_MAINTENANCE,
                    title: "{$platformLabels} integration temporarily disabled",
                    message: "{$platformLabels} integrations have been temporarily disabled. {$reason}",
                    data: ['platforms' => $integration->platforms, 'reason' => $reason],
                );
            }
        }

        // Audit
        $this->auditLogService->record(
            action: AuditAction::SETTINGS_CHANGE,
            auditable: $integration,
            admin: $admin,
            oldValues: ['is_enabled' => $oldEnabled, 'status' => $oldStatus->value],
            newValues: ['is_enabled' => $enabled, 'status' => $integration->status->value],
            description: $enabled
                ? "Integration enabled: {$integration->display_name}"
                : "Integration disabled: {$integration->display_name}",
            metadata: ['reason' => $reason],
        );

        return $integration->fresh('updatedByAdmin');
    }

    /**
     * Get token health stats for an integration.
     *
     * @return array{summary: array<string, array<string, int>>, accounts: \Illuminate\Contracts\Pagination\LengthAwarePaginator}
     */
    public function getHealth(SocialPlatformIntegration $integration, array $filters = []): array
    {
        $platforms = $integration->platforms;
        $summary = [];

        foreach ($platforms as $platformValue) {
            $query = SocialAccount::where('platform', $platformValue);

            $summary[$platformValue] = [
                'total' => (clone $query)->count(),
                'connected' => (clone $query)->where('status', SocialAccountStatus::CONNECTED)->count(),
                'expiring' => (clone $query)->where('status', SocialAccountStatus::CONNECTED)
                    ->whereNotNull('token_expires_at')
                    ->where('token_expires_at', '<=', now()->addDays(7))
                    ->where('token_expires_at', '>', now())
                    ->count(),
                'expired' => (clone $query)->where('status', SocialAccountStatus::TOKEN_EXPIRED)->count(),
                'revoked' => (clone $query)->where('status', SocialAccountStatus::REVOKED)->count(),
                'disconnected' => (clone $query)->where('status', SocialAccountStatus::DISCONNECTED)->count(),
            ];
        }

        // Paginated account list
        $accountQuery = SocialAccount::query()
            ->whereIn('platform', $platforms)
            ->with(['workspace:id,name,tenant_id', 'workspace.tenant:id,name'])
            ->orderByDesc('created_at');

        if (! empty($filters['platform'])) {
            $accountQuery->where('platform', $filters['platform']);
        }
        if (! empty($filters['status'])) {
            $accountQuery->where('status', $filters['status']);
        }
        if (! empty($filters['tenant_id'])) {
            $accountQuery->whereHas('workspace', function ($q) use ($filters): void {
                $q->where('tenant_id', $filters['tenant_id']);
            });
        }

        $perPage = min((int) ($filters['per_page'] ?? 25), 100);

        return [
            'summary' => $summary,
            'accounts' => $accountQuery->paginate($perPage),
        ];
    }

    /**
     * Get audit log for a specific integration.
     */
    public function getAuditLog(SocialPlatformIntegration $integration, int $perPage = 25): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->auditLogService->listForAuditablePaginated($integration, $perPage);
    }

    // ── Private helpers ────────────────────────────────────────

    /**
     * @return array<string, array<string, array<string>>>
     */
    private function computeScopeChanges(array $oldScopes, array $newScopes): array
    {
        $changes = [];

        $allPlatforms = array_unique(array_merge(array_keys($oldScopes), array_keys($newScopes)));

        foreach ($allPlatforms as $platform) {
            $old = $oldScopes[$platform] ?? [];
            $new = $newScopes[$platform] ?? [];
            $added = array_values(array_diff($new, $old));
            $removed = array_values(array_diff($old, $new));

            if (! empty($added) || ! empty($removed)) {
                $changes[$platform] = [
                    'added' => $added,
                    'removed' => $removed,
                ];
            }
        }

        return $changes;
    }

    /**
     * @return array{accounts: int, tenants: int}
     */
    private function revokeAccountsForScopeChange(SocialPlatformIntegration $integration, array $platforms, SuperAdminUser $admin): array
    {
        $accounts = SocialAccount::query()
            ->whereIn('platform', $platforms)
            ->whereIn('status', [SocialAccountStatus::CONNECTED, SocialAccountStatus::TOKEN_EXPIRED])
            ->with('workspace')
            ->get();

        foreach ($accounts as $account) {
            $account->update([
                'status' => SocialAccountStatus::REVOKED,
                'metadata' => array_merge($account->metadata ?? [], [
                    'revoke_reason' => 'scope_upgrade',
                    'revoked_at' => now()->toIso8601String(),
                    'revoked_by_admin' => $admin->id,
                ]),
            ]);
        }

        $tenantIds = $accounts->pluck('workspace.tenant_id')->filter()->unique();
        $tenants = Tenant::whereIn('id', $tenantIds)->get();
        $platformLabels = implode(' and ', array_map('ucfirst', $platforms));

        foreach ($tenants as $tenant) {
            $this->notificationService->sendToTenant(
                tenant: $tenant,
                type: NotificationType::PLATFORM_REAUTH_REQUIRED,
                title: "{$platformLabels} permissions updated",
                message: "New permissions are required for {$platformLabels}. Please reconnect your accounts to enable new features.",
                data: ['platforms' => $platforms, 'change_type' => 'scope_addition'],
            );
        }

        return [
            'accounts' => $accounts->count(),
            'tenants' => $tenantIds->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function captureOldValues(SocialPlatformIntegration $integration): array
    {
        return [
            'app_id_masked' => $integration->getMaskedAppId(),
            'api_version' => $integration->api_version,
            'scopes' => $integration->scopes,
            'is_enabled' => $integration->is_enabled,
            'status' => $integration->status->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function captureNewValues(SocialPlatformIntegration $integration, array $data): array
    {
        $values = [];
        if (isset($data['app_id'])) {
            $values['app_id_masked'] = $integration->getMaskedAppId();
        }
        if (isset($data['app_secret'])) {
            $values['app_secret'] = '••••••••';
        }
        if (isset($data['api_version'])) {
            $values['api_version'] = $data['api_version'];
        }
        if (isset($data['scopes'])) {
            $values['scopes'] = $data['scopes'];
        }

        return $values;
    }

    private function defaultDisplayName(string $provider): string
    {
        return match ($provider) {
            'meta' => 'Meta (Facebook + Instagram)',
            default => ucfirst($provider),
        };
    }

    /**
     * @return array<string>
     */
    private function defaultPlatforms(string $provider): array
    {
        return match ($provider) {
            'meta' => ['facebook', 'instagram'],
            default => [$provider],
        };
    }

    /**
     * @return array<string, string>
     */
    private function defaultRedirectUris(string $provider): array
    {
        $baseUrl = config('app.url', 'http://localhost');

        return match ($provider) {
            'meta' => [
                'facebook' => $baseUrl . '/api/v1/oauth/facebook/callback',
                'instagram' => $baseUrl . '/api/v1/oauth/instagram/callback',
            ],
            default => [],
        };
    }

    /**
     * @return array<string, array<string>>
     */
    private function defaultScopes(string $provider): array
    {
        return match ($provider) {
            'meta' => [
                'facebook' => SocialPlatform::FACEBOOK->oauthScopes(),
                'instagram' => SocialPlatform::INSTAGRAM->oauthScopes(),
            ],
            default => [],
        };
    }
}
