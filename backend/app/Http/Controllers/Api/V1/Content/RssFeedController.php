<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Content\RssFeed;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\RssFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RssFeedController extends Controller
{
    public function __construct(
        private readonly RssFeedService $rssFeedService,
    ) {}

    /**
     * List RSS feeds.
     */
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $filters = [
            'per_page' => $request->query('per_page', 15),
        ];

        $feeds = $this->rssFeedService->listFeeds($workspace->id, $filters);

        return $this->paginated($feeds, 'RSS feeds retrieved successfully');
    }

    /**
     * Create a new RSS feed.
     */
    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'url' => 'required|url|max:500',
            'name' => 'required|string|max:200',
            'category_id' => 'nullable|uuid|exists:content_categories,id',
        ]);

        $feed = $this->rssFeedService->addFeed(
            $workspace->id,
            $validated['url'],
            $validated['name'],
            $validated['category_id'] ?? null
        );

        return $this->created($feed->load('category'), 'RSS feed created successfully');
    }

    /**
     * Get a single RSS feed.
     */
    public function show(Request $request, Workspace $workspace, RssFeed $rssFeed): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($rssFeed->workspace_id !== $workspace->id) {
            return $this->notFound('RSS feed not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        return $this->success($rssFeed->load('category'), 'RSS feed retrieved successfully');
    }

    /**
     * Delete an RSS feed.
     */
    public function destroy(Request $request, Workspace $workspace, RssFeed $rssFeed): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($rssFeed->workspace_id !== $workspace->id) {
            return $this->notFound('RSS feed not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $this->rssFeedService->delete($rssFeed);

        return $this->success(null, 'RSS feed deleted successfully');
    }

    /**
     * Get items from an RSS feed.
     */
    public function items(Request $request, Workspace $workspace, RssFeed $rssFeed): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($rssFeed->workspace_id !== $workspace->id) {
            return $this->notFound('RSS feed not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $filters = [
            'is_used' => $request->query('is_used'),
            'per_page' => $request->query('per_page', 20),
        ];

        $items = $this->rssFeedService->listItems($rssFeed->id, $filters);

        return $this->paginated($items, 'RSS feed items retrieved successfully');
    }

    /**
     * Fetch items from an RSS feed.
     */
    public function fetch(Request $request, Workspace $workspace, RssFeed $rssFeed): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($rssFeed->workspace_id !== $workspace->id) {
            return $this->notFound('RSS feed not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $count = $this->rssFeedService->fetchItems($rssFeed);

        return $this->success(['new_items' => $count], 'RSS feed fetched successfully');
    }
}
