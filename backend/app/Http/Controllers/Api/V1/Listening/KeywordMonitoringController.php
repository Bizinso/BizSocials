<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Listening;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Listening\MonitoredKeyword;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Listening\KeywordMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class KeywordMonitoringController extends Controller
{
    public function __construct(
        private readonly KeywordMonitoringService $keywordMonitoringService,
    ) {}

    /**
     * List monitored keywords.
     */
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $filters = [
            'per_page' => $request->query('per_page', 15),
        ];

        $keywords = $this->keywordMonitoringService->listKeywords($workspace->id, $filters);

        return $this->paginated($keywords, 'Monitored keywords retrieved successfully');
    }

    /**
     * Create a new monitored keyword.
     */
    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'keyword' => 'required|string|max:200',
            'platforms' => 'nullable|array',
            'platforms.*' => 'string|in:facebook,instagram,twitter,linkedin',
            'notify_on_match' => 'nullable|boolean',
        ]);

        $keyword = $this->keywordMonitoringService->create($workspace->id, $validated);

        return $this->created($keyword, 'Monitored keyword created successfully');
    }

    /**
     * Get a single monitored keyword.
     */
    public function show(Request $request, Workspace $workspace, MonitoredKeyword $monitoredKeyword): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($monitoredKeyword->workspace_id !== $workspace->id) {
            return $this->notFound('Monitored keyword not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        return $this->success($monitoredKeyword, 'Monitored keyword retrieved successfully');
    }

    /**
     * Update a monitored keyword.
     */
    public function update(Request $request, Workspace $workspace, MonitoredKeyword $monitoredKeyword): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($monitoredKeyword->workspace_id !== $workspace->id) {
            return $this->notFound('Monitored keyword not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'keyword' => 'sometimes|string|max:200',
            'platforms' => 'nullable|array',
            'platforms.*' => 'string|in:facebook,instagram,twitter,linkedin',
            'is_active' => 'sometimes|boolean',
            'notify_on_match' => 'sometimes|boolean',
        ]);

        $keyword = $this->keywordMonitoringService->update($monitoredKeyword, $validated);

        return $this->success($keyword, 'Monitored keyword updated successfully');
    }

    /**
     * Delete a monitored keyword.
     */
    public function destroy(Request $request, Workspace $workspace, MonitoredKeyword $monitoredKeyword): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($monitoredKeyword->workspace_id !== $workspace->id) {
            return $this->notFound('Monitored keyword not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $this->keywordMonitoringService->delete($monitoredKeyword);

        return $this->success(null, 'Monitored keyword deleted successfully');
    }

    /**
     * Get mentions for a keyword.
     */
    public function mentions(Request $request, Workspace $workspace, MonitoredKeyword $monitoredKeyword): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($monitoredKeyword->workspace_id !== $workspace->id) {
            return $this->notFound('Monitored keyword not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $filters = [
            'sentiment' => $request->query('sentiment'),
            'platform' => $request->query('platform'),
            'per_page' => $request->query('per_page', 20),
        ];

        $mentions = $this->keywordMonitoringService->getMentions($monitoredKeyword->id, $filters);

        return $this->paginated($mentions, 'Keyword mentions retrieved successfully');
    }

    /**
     * Get sentiment breakdown for a keyword.
     */
    public function sentimentBreakdown(Request $request, Workspace $workspace, MonitoredKeyword $monitoredKeyword): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($monitoredKeyword->workspace_id !== $workspace->id) {
            return $this->notFound('Monitored keyword not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $breakdown = $this->keywordMonitoringService->getSentimentBreakdown($monitoredKeyword->id);

        return $this->success($breakdown, 'Sentiment breakdown retrieved successfully');
    }
}
