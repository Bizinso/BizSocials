<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Platform;

use App\Enums\Social\SocialAccountStatus;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Admin\ForceReauthRequest;
use App\Http\Requests\Admin\ToggleIntegrationRequest;
use App\Http\Requests\Admin\UpdateIntegrationRequest;
use App\Models\Platform\SocialPlatformIntegration;
use App\Models\Social\SocialAccount;
use App\Services\Admin\IntegrationService;
use App\Services\Social\ForceReauthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminIntegrationController extends Controller
{
    public function __construct(
        private readonly IntegrationService $integrationService,
        private readonly ForceReauthorizationService $forceReauthService,
    ) {}

    /**
     * List all platform integrations.
     * GET /admin/integrations
     */
    public function index(): JsonResponse
    {
        $integrations = $this->integrationService->list();

        $data = $integrations->map(fn (SocialPlatformIntegration $i) => $this->formatListItem($i));

        return $this->success($data, 'Integrations retrieved successfully');
    }

    /**
     * Get integration detail.
     * GET /admin/integrations/{provider}
     */
    public function show(string $provider): JsonResponse
    {
        $integration = $this->integrationService->getByProvider($provider);

        if ($integration === null) {
            return $this->error('Integration not found', 404);
        }

        return $this->success($this->formatDetail($integration), 'Integration retrieved successfully');
    }

    /**
     * Create or update integration.
     * PUT /admin/integrations/{provider}
     */
    public function update(string $provider, UpdateIntegrationRequest $request): JsonResponse
    {
        $admin = $request->user();

        // Only SUPER_ADMIN can change app_secret
        if ($request->has('app_secret') && ! $admin->canManageAdmins()) {
            return $this->error('Only super admins can update the app secret', 403);
        }

        $result = $this->integrationService->upsert(
            provider: $provider,
            data: $request->validated(),
            admin: $admin,
        );

        $response = $this->formatDetail($result['integration']);
        $response['meta'] = [
            'scope_changes' => $result['scope_changes'],
            'requires_reauth' => $result['requires_reauth'],
            'affected_accounts' => $result['affected_accounts'],
            'affected_tenants' => $result['affected_tenants'],
        ];

        return $this->success($response, 'Integration updated successfully');
    }

    /**
     * Verify integration credentials.
     * POST /admin/integrations/{provider}/verify
     */
    public function verify(string $provider): JsonResponse
    {
        $integration = $this->integrationService->getByProvider($provider);

        if ($integration === null) {
            return $this->error('Integration not found', 404);
        }

        $result = $this->integrationService->verifyCredentials($integration);

        return $this->success($result, $result['valid'] ? 'Credentials are valid' : 'Credentials verification failed');
    }

    /**
     * Force re-authorization for affected accounts.
     * POST /admin/integrations/{provider}/force-reauth
     */
    public function forceReauth(string $provider, ForceReauthRequest $request): JsonResponse
    {
        $integration = $this->integrationService->getByProvider($provider);

        if ($integration === null) {
            return $this->error('Integration not found', 404);
        }

        $admin = $request->user();

        // Only SUPER_ADMIN and ADMIN can force reauth
        if (! $admin->hasWriteAccess()) {
            return $this->error('Insufficient permissions', 403);
        }

        $result = $this->forceReauthService->execute(
            integration: $integration,
            platforms: $request->validated('platforms'),
            reason: $request->validated('reason'),
            admin: $admin,
            notifyTenants: $request->boolean('notify_tenants', true),
        );

        $result['platforms'] = $request->validated('platforms');

        return $this->success($result, 'Forced re-authorization executed');
    }

    /**
     * Get token health stats.
     * GET /admin/integrations/{provider}/health
     */
    public function health(string $provider, Request $request): JsonResponse
    {
        $integration = $this->integrationService->getByProvider($provider);

        if ($integration === null) {
            return $this->error('Integration not found', 404);
        }

        $health = $this->integrationService->getHealth($integration, $request->query());

        // Transform account data
        $accounts = $health['accounts']->through(function (SocialAccount $account) {
            return [
                'id' => $account->id,
                'tenant_id' => $account->workspace?->tenant_id,
                'tenant_name' => $account->workspace?->tenant?->name ?? 'Unknown',
                'workspace_id' => $account->workspace_id,
                'workspace_name' => $account->workspace?->name ?? 'Unknown',
                'platform' => $account->platform->value,
                'account_name' => $account->account_name,
                'status' => $account->status->value,
                'token_expires_at' => $account->token_expires_at?->toIso8601String(),
                'connected_at' => $account->connected_at?->toIso8601String(),
                'last_refreshed_at' => $account->last_refreshed_at?->toIso8601String(),
            ];
        });

        return $this->success([
            'summary' => $health['summary'],
            'accounts' => $accounts,
        ], 'Health data retrieved successfully');
    }

    /**
     * Toggle integration enabled/disabled.
     * POST /admin/integrations/{provider}/toggle
     */
    public function toggle(string $provider, ToggleIntegrationRequest $request): JsonResponse
    {
        $integration = $this->integrationService->getByProvider($provider);

        if ($integration === null) {
            return $this->error('Integration not found', 404);
        }

        $admin = $request->user();
        $updated = $this->integrationService->toggle(
            integration: $integration,
            enabled: $request->boolean('enabled'),
            reason: $request->validated('reason', ''),
            admin: $admin,
        );

        return $this->success([
            'provider' => $updated->provider,
            'is_enabled' => $updated->is_enabled,
            'status' => $updated->status->value,
        ], $updated->is_enabled ? 'Integration enabled' : 'Integration disabled');
    }

    /**
     * Get audit log for integration.
     * GET /admin/integrations/{provider}/audit-log
     */
    public function auditLog(string $provider, Request $request): JsonResponse
    {
        $integration = $this->integrationService->getByProvider($provider);

        if ($integration === null) {
            return $this->error('Integration not found', 404);
        }

        $perPage = min((int) $request->query('per_page', 25), 100);
        $logs = $this->integrationService->getAuditLog($integration, $perPage);

        return $this->success($logs, 'Audit log retrieved successfully');
    }

    // ── Formatters ─────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function formatListItem(SocialPlatformIntegration $i): array
    {
        $stats = [];
        foreach ($i->platforms as $platformValue) {
            $query = SocialAccount::where('platform', $platformValue);
            $stats[$platformValue] = [
                'connected' => (clone $query)->where('status', SocialAccountStatus::CONNECTED)->count(),
                'expiring' => (clone $query)->where('status', SocialAccountStatus::CONNECTED)
                    ->whereNotNull('token_expires_at')
                    ->where('token_expires_at', '<=', now()->addDays(7))
                    ->where('token_expires_at', '>', now())
                    ->count(),
                'expired' => (clone $query)->where('status', SocialAccountStatus::TOKEN_EXPIRED)->count(),
                'revoked' => (clone $query)->where('status', SocialAccountStatus::REVOKED)->count(),
            ];
        }

        return [
            'id' => $i->id,
            'provider' => $i->provider,
            'display_name' => $i->display_name,
            'platforms' => $i->platforms,
            'is_enabled' => $i->is_enabled,
            'status' => $i->status->value,
            'api_version' => $i->api_version,
            'has_credentials' => $i->app_id_encrypted !== null,
            'last_verified_at' => $i->last_verified_at?->toIso8601String(),
            'last_rotated_at' => $i->last_rotated_at?->toIso8601String(),
            'account_stats' => $stats,
            'updated_at' => $i->updated_at->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDetail(SocialPlatformIntegration $i): array
    {
        return [
            'id' => $i->id,
            'provider' => $i->provider,
            'display_name' => $i->display_name,
            'platforms' => $i->platforms,
            'app_id_masked' => $i->getMaskedAppId(),
            'has_secret' => $i->app_secret_encrypted !== null,
            'redirect_uris' => $i->redirect_uris,
            'api_version' => $i->api_version,
            'scopes' => $i->scopes,
            'is_enabled' => $i->is_enabled,
            'status' => $i->status->value,
            'environment' => $i->environment,
            'last_verified_at' => $i->last_verified_at?->toIso8601String(),
            'last_rotated_at' => $i->last_rotated_at?->toIso8601String(),
            'updated_by' => $i->updatedByAdmin ? [
                'id' => $i->updatedByAdmin->id,
                'name' => $i->updatedByAdmin->name,
            ] : null,
            'created_at' => $i->created_at->toIso8601String(),
            'updated_at' => $i->updated_at->toIso8601String(),
        ];
    }
}
