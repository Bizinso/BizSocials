<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\WhatsApp;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\WhatsApp\AccountRiskAlert;
use App\Models\WhatsApp\WhatsAppBusinessAccount;
use App\Models\WhatsApp\WhatsAppPhoneNumber;
use App\Services\WhatsApp\WhatsAppAccountService;
use App\Services\WhatsApp\WhatsAppGovernanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WhatsAppAdminController extends Controller
{
    public function __construct(
        private readonly WhatsAppGovernanceService $governanceService,
        private readonly WhatsAppAccountService $accountService,
    ) {}

    /**
     * List all WhatsApp Business Accounts across all tenants.
     * GET /admin/whatsapp/accounts
     */
    public function listAllAccounts(Request $request): JsonResponse
    {
        $accounts = $this->governanceService->listAllAccounts($request->only([
            'status', 'quality_rating', 'per_page',
        ]));

        return $this->paginated($accounts);
    }

    /**
     * Get full details for a WhatsApp Business Account.
     * GET /admin/whatsapp/accounts/{account}
     */
    public function getAccountDetail(WhatsAppBusinessAccount $account): JsonResponse
    {
        $detail = $this->governanceService->getAccountDetail($account);

        return $this->success($detail, 'Account detail retrieved');
    }

    /**
     * Force-suspend a WhatsApp Business Account.
     * POST /admin/whatsapp/accounts/{account}/suspend
     */
    public function suspendAccount(WhatsAppBusinessAccount $account, Request $request): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $this->accountService->suspendAccount($account, $request->input('reason'));

        return $this->success(['id' => $account->id], 'Account suspended');
    }

    /**
     * Reactivate a suspended WhatsApp Business Account.
     * POST /admin/whatsapp/accounts/{account}/reactivate
     */
    public function reactivateAccount(WhatsAppBusinessAccount $account): JsonResponse
    {
        $this->governanceService->reactivateAccount($account);

        return $this->success(['id' => $account->id], 'Account reactivated');
    }

    /**
     * Disable marketing for a WhatsApp Business Account.
     * POST /admin/whatsapp/accounts/{account}/disable-marketing
     */
    public function disableMarketing(WhatsAppBusinessAccount $account): JsonResponse
    {
        $this->governanceService->disableMarketing($account);

        return $this->success(['id' => $account->id], 'Marketing disabled');
    }

    /**
     * Enable marketing for a WhatsApp Business Account.
     * POST /admin/whatsapp/accounts/{account}/enable-marketing
     */
    public function enableMarketing(WhatsAppBusinessAccount $account): JsonResponse
    {
        $this->governanceService->enableMarketing($account);

        return $this->success(['id' => $account->id], 'Marketing enabled');
    }

    /**
     * Override daily send limit for a phone number.
     * POST /admin/whatsapp/phone-numbers/{phone}/override-rate-limit
     */
    public function overrideRateLimit(WhatsAppPhoneNumber $phone, Request $request): JsonResponse
    {
        $request->validate(['daily_send_limit' => 'required|integer|min:100|max:100000']);

        $this->governanceService->overrideRateLimit($phone, (int) $request->input('daily_send_limit'));

        return $this->success(['id' => $phone->id], 'Rate limit overridden');
    }

    /**
     * View compliance consent logs for a WhatsApp Business Account.
     * GET /admin/whatsapp/accounts/{account}/consent-logs
     */
    public function viewConsentLogs(WhatsAppBusinessAccount $account): JsonResponse
    {
        $logs = $this->governanceService->getConsentLogs($account);

        return $this->success($logs, 'Consent logs retrieved');
    }

    /**
     * List all risk alerts across all tenants.
     * GET /admin/whatsapp/alerts
     */
    public function listAlerts(Request $request): JsonResponse
    {
        $alerts = $this->governanceService->listAlerts($request->only([
            'severity', 'alert_type', 'resolved', 'waba_id', 'per_page',
        ]));

        return $this->paginated($alerts);
    }

    /**
     * Acknowledge a risk alert.
     * POST /admin/whatsapp/alerts/{alert}/acknowledge
     */
    public function acknowledgeAlert(AccountRiskAlert $alert): JsonResponse
    {
        $alert->acknowledge($this->getUser());

        return $this->success(['id' => $alert->id], 'Alert acknowledged');
    }

    private function getUser(): \App\Models\User
    {
        /** @var \App\Models\User */
        return auth()->user();
    }
}
