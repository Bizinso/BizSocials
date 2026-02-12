<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Content\EvergreenRule;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\EvergreenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EvergreenController extends Controller
{
    public function __construct(
        private readonly EvergreenService $evergreenService,
    ) {}

    /**
     * List evergreen rules.
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

        $rules = EvergreenRule::forWorkspace($workspace->id)
            ->with(['sourceCategory'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->query('per_page', 15));

        return $this->paginated($rules, 'Evergreen rules retrieved successfully');
    }

    /**
     * Create a new evergreen rule.
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
            'name' => 'required|string|max:200',
            'source_category_id' => 'nullable|uuid|exists:content_categories,id',
            'social_account_ids' => 'required|array',
            'social_account_ids.*' => 'uuid|exists:social_accounts,id',
            'repost_interval_days' => 'required|integer|min:1',
            'max_reposts' => 'required|integer|min:1|max:100',
            'time_slots' => 'nullable|array',
            'content_variation' => 'boolean',
        ]);

        $rule = $this->evergreenService->createRule($workspace->id, $validated);

        return $this->created($rule->load('sourceCategory'), 'Evergreen rule created successfully');
    }

    /**
     * Get a single evergreen rule.
     */
    public function show(Request $request, Workspace $workspace, EvergreenRule $evergreenRule): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($evergreenRule->workspace_id !== $workspace->id) {
            return $this->notFound('Evergreen rule not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        return $this->success($evergreenRule->load(['sourceCategory', 'poolEntries']), 'Evergreen rule retrieved successfully');
    }

    /**
     * Update an evergreen rule.
     */
    public function update(Request $request, Workspace $workspace, EvergreenRule $evergreenRule): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($evergreenRule->workspace_id !== $workspace->id) {
            return $this->notFound('Evergreen rule not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:200',
            'is_active' => 'boolean',
            'source_category_id' => 'nullable|uuid|exists:content_categories,id',
            'social_account_ids' => 'sometimes|array',
            'social_account_ids.*' => 'uuid|exists:social_accounts,id',
            'repost_interval_days' => 'sometimes|integer|min:1',
            'max_reposts' => 'sometimes|integer|min:1|max:100',
            'time_slots' => 'nullable|array',
            'content_variation' => 'boolean',
        ]);

        $rule = $this->evergreenService->updateRule($evergreenRule, $validated);

        return $this->success($rule->load('sourceCategory'), 'Evergreen rule updated successfully');
    }

    /**
     * Delete an evergreen rule.
     */
    public function destroy(Request $request, Workspace $workspace, EvergreenRule $evergreenRule): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($evergreenRule->workspace_id !== $workspace->id) {
            return $this->notFound('Evergreen rule not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $this->evergreenService->deleteRule($evergreenRule);

        return $this->success(null, 'Evergreen rule deleted successfully');
    }

    /**
     * Build the post pool for a rule.
     */
    public function buildPool(Request $request, Workspace $workspace, EvergreenRule $evergreenRule): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($evergreenRule->workspace_id !== $workspace->id) {
            return $this->notFound('Evergreen rule not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $count = $this->evergreenService->buildPool($evergreenRule);

        return $this->success(['posts_added' => $count], 'Pool built successfully');
    }

    /**
     * Get the post pool for a rule.
     */
    public function pool(Request $request, Workspace $workspace, EvergreenRule $evergreenRule): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($evergreenRule->workspace_id !== $workspace->id) {
            return $this->notFound('Evergreen rule not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $pool = $evergreenRule->poolEntries()
            ->with('post')
            ->orderBy('next_repost_at')
            ->paginate($request->query('per_page', 20));

        return $this->paginated($pool, 'Pool entries retrieved successfully');
    }
}
