<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Analytics;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Workspace\Workspace;
use App\Services\Analytics\HashtagTrackingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HashtagTrackingController extends Controller
{
    public function __construct(
        private readonly HashtagTrackingService $hashtagService,
    ) {}

    /**
     * List hashtag performance entries with filters.
     * GET /api/v1/workspaces/{workspace}/hashtag-tracking
     *
     * Query parameters:
     * - platform: Filter by platform (optional)
     * - search: Search hashtag name (optional)
     * - sort_by: Sort field (default: avg_engagement)
     * - sort_dir: Sort direction (default: desc)
     * - per_page: Items per page (default: 20)
     *
     * @throws AuthorizationException
     */
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $filters = $request->only(['platform', 'search', 'sort_by', 'sort_dir', 'per_page']);
        $hashtags = $this->hashtagService->list($workspace->id, $filters);

        return $this->paginated($hashtags, 'Hashtag performance retrieved successfully');
    }

    /**
     * Get top performing hashtags.
     * GET /api/v1/workspaces/{workspace}/hashtag-tracking/top
     *
     * Query parameters:
     * - platform: Filter by platform (optional)
     * - limit: Number of results (default: 20)
     *
     * @throws AuthorizationException
     */
    public function topHashtags(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $platform = $request->query('platform');
        $limit = min((int) $request->query('limit', 20), 50);

        $topHashtags = $this->hashtagService->getTopHashtags($workspace->id, $platform, $limit);

        return $this->success($topHashtags, 'Top hashtags retrieved successfully');
    }

    /**
     * Get hashtag suggestions for a platform.
     * GET /api/v1/workspaces/{workspace}/hashtag-tracking/suggestions
     *
     * Query parameters:
     * - platform: Platform identifier (required)
     *
     * @throws AuthorizationException
     */
    public function suggestions(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $request->validate([
            'platform' => 'required|string|max:50',
        ]);

        $suggestions = $this->hashtagService->getSuggestions(
            $workspace->id,
            $request->query('platform')
        );

        return $this->success($suggestions, 'Hashtag suggestions retrieved successfully');
    }
}
