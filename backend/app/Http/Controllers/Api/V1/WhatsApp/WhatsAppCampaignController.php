<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Data\WhatsApp\WhatsAppCampaignData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\WhatsApp\CreateCampaignRequest;
use App\Http\Requests\WhatsApp\ScheduleCampaignRequest;
use App\Models\WhatsApp\WhatsAppCampaign;
use App\Models\Workspace\Workspace;
use App\Services\WhatsApp\WhatsAppCampaignService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WhatsAppCampaignController extends Controller
{
    public function __construct(
        private readonly WhatsAppCampaignService $campaignService,
    ) {}

    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $campaigns = $this->campaignService->listForWorkspace($workspace->id, [
            'status' => $request->query('status'),
            'search' => $request->query('search'),
            'per_page' => $request->query('per_page', 15),
        ]);

        return $this->paginated($campaigns, 'Campaigns retrieved successfully');
    }

    public function store(CreateCampaignRequest $request, Workspace $workspace): JsonResponse
    {
        $campaign = $this->campaignService->create(
            $workspace,
            $request->user()->id,
            $request->validated(),
        );

        return $this->created(WhatsAppCampaignData::fromModel($campaign));
    }

    public function show(Workspace $workspace, WhatsAppCampaign $campaign): JsonResponse
    {
        return $this->success(WhatsAppCampaignData::fromModel($campaign->load(['template', 'createdBy'])));
    }

    public function update(CreateCampaignRequest $request, Workspace $workspace, WhatsAppCampaign $campaign): JsonResponse
    {
        $updated = $this->campaignService->update($campaign, $request->validated());

        return $this->success(WhatsAppCampaignData::fromModel($updated));
    }

    public function destroy(Workspace $workspace, WhatsAppCampaign $campaign): JsonResponse
    {
        $campaign->delete();

        return $this->noContent();
    }

    public function buildAudience(Workspace $workspace, WhatsAppCampaign $campaign): JsonResponse
    {
        $count = $this->campaignService->buildAudience($campaign);

        return $this->success(['recipients_count' => $count]);
    }

    public function schedule(ScheduleCampaignRequest $request, Workspace $workspace, WhatsAppCampaign $campaign): JsonResponse
    {
        $this->campaignService->schedule($campaign, Carbon::parse($request->validated('scheduled_at')));

        return $this->success(WhatsAppCampaignData::fromModel($campaign->refresh()));
    }

    public function send(Workspace $workspace, WhatsAppCampaign $campaign): JsonResponse
    {
        $this->campaignService->send($campaign);

        return $this->success(WhatsAppCampaignData::fromModel($campaign->refresh()));
    }

    public function cancel(Workspace $workspace, WhatsAppCampaign $campaign): JsonResponse
    {
        $this->campaignService->cancel($campaign);

        return $this->success(WhatsAppCampaignData::fromModel($campaign->refresh()));
    }

    public function stats(Workspace $workspace, WhatsAppCampaign $campaign): JsonResponse
    {
        return $this->success($this->campaignService->getStats($campaign));
    }

    public function validate(Workspace $workspace, WhatsAppCampaign $campaign): JsonResponse
    {
        return $this->success($this->campaignService->validateAudience($campaign));
    }
}
