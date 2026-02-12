<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Data\WhatsApp\WhatsAppBusinessAccountData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\WhatsApp\OnboardWhatsAppRequest;
use App\Http\Requests\WhatsApp\UpdateBusinessProfileRequest;
use App\Models\WhatsApp\WhatsAppBusinessAccount;
use App\Services\WhatsApp\WhatsAppAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WhatsAppAccountController extends Controller
{
    public function __construct(
        private readonly WhatsAppAccountService $accountService,
    ) {}

    public function onboard(OnboardWhatsAppRequest $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $waba = $this->accountService->onboard(
            $tenant,
            $request->validated('meta_access_token'),
        );

        return $this->created(
            WhatsAppBusinessAccountData::fromModel($waba),
            'WhatsApp Business Account connected successfully',
        );
    }

    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $accounts = WhatsAppBusinessAccount::forTenant($tenant->id)
            ->with('phoneNumbers')
            ->get();

        return $this->success(
            WhatsAppBusinessAccountData::collection($accounts),
        );
    }

    public function show(WhatsAppBusinessAccount $account): JsonResponse
    {
        $account->load(['phoneNumbers', 'complianceAcceptedBy']);

        return $this->success(
            WhatsAppBusinessAccountData::fromModel($account),
        );
    }

    public function updateProfile(UpdateBusinessProfileRequest $request, WhatsAppBusinessAccount $account): JsonResponse
    {
        $phone = $account->phoneNumbers()->where('is_primary', true)->firstOrFail();

        $this->accountService->updateBusinessProfile($phone, $request->validated());

        return $this->success(null, 'Business profile updated');
    }

    public function acceptCompliance(Request $request, WhatsAppBusinessAccount $account): JsonResponse
    {
        $this->accountService->acceptCompliance($account, $request->user());

        return $this->success(null, 'Compliance accepted');
    }
}
