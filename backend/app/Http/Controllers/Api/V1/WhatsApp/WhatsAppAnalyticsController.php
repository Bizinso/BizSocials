<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Workspace\Workspace;
use App\Services\WhatsApp\WhatsAppAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WhatsAppAnalyticsController extends Controller
{
    public function __construct(
        private readonly WhatsAppAnalyticsService $analyticsService,
    ) {}

    public function inboxHealth(Workspace $workspace): JsonResponse
    {
        return $this->success($this->analyticsService->getInboxHealth($workspace->id));
    }

    public function marketingPerformance(Request $request, Workspace $workspace): JsonResponse
    {
        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        return $this->success($this->analyticsService->getMarketingPerformance($workspace->id, $from, $to));
    }

    public function complianceHealth(Workspace $workspace): JsonResponse
    {
        return $this->success($this->analyticsService->getComplianceHealth($workspace->id));
    }

    public function agentProductivity(Workspace $workspace): JsonResponse
    {
        return $this->success($this->analyticsService->getAgentProductivity($workspace->id));
    }
}
