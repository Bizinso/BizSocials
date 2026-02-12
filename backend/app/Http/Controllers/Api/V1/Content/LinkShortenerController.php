<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Content\ShortLink;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\LinkShortenerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LinkShortenerController extends Controller
{
    public function __construct(
        private readonly LinkShortenerService $linkShortenerService,
    ) {}

    /**
     * List short links.
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

        $links = ShortLink::forWorkspace($workspace->id)
            ->with(['createdBy'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->query('per_page', 15));

        return $this->paginated($links, 'Short links retrieved successfully');
    }

    /**
     * Create a new short link.
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
            'original_url' => 'required|url|max:2048',
            'title' => 'nullable|string|max:200',
            'custom_alias' => 'nullable|alpha_dash|max:30|unique:short_links,custom_alias',
            'utm_source' => 'nullable|string|max:100',
            'utm_medium' => 'nullable|string|max:100',
            'utm_campaign' => 'nullable|string|max:100',
            'utm_term' => 'nullable|string|max:100',
            'utm_content' => 'nullable|string|max:100',
        ]);

        $link = $this->linkShortenerService->shorten(
            $workspace->id,
            $user->id,
            $validated['original_url'],
            $validated
        );

        return $this->created($link->load('createdBy'), 'Short link created successfully');
    }

    /**
     * Get a single short link.
     */
    public function show(Request $request, Workspace $workspace, ShortLink $shortLink): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($shortLink->workspace_id !== $workspace->id) {
            return $this->notFound('Short link not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        return $this->success($shortLink->load('createdBy'), 'Short link retrieved successfully');
    }

    /**
     * Delete a short link.
     */
    public function destroy(Request $request, Workspace $workspace, ShortLink $shortLink): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($shortLink->workspace_id !== $workspace->id) {
            return $this->notFound('Short link not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $shortLink->delete();

        return $this->success(null, 'Short link deleted successfully');
    }

    /**
     * Get statistics for a short link.
     */
    public function stats(Request $request, Workspace $workspace, ShortLink $shortLink): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($shortLink->workspace_id !== $workspace->id) {
            return $this->notFound('Short link not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $stats = $this->linkShortenerService->getStats($shortLink);

        return $this->success($stats, 'Statistics retrieved successfully');
    }
}
