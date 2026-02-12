<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Integration;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Integration\WebhookEndpoint;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Integration\WebhookDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WebhookEndpointController extends Controller
{
    public function __construct(
        private readonly WebhookDispatchService $webhookService,
    ) {}

    /**
     * List webhook endpoints for a workspace.
     * GET /api/v1/workspaces/{workspace}/webhook-endpoints
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

        $endpoints = $this->webhookService->listEndpoints($workspace->id, $filters);

        return $this->paginated($endpoints, 'Webhook endpoints retrieved successfully');
    }

    /**
     * Create a new webhook endpoint.
     * POST /api/v1/workspaces/{workspace}/webhook-endpoints
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
            'url' => 'required|url|max:2048',
            'events' => 'required|array|min:1',
            'events.*' => 'required|string|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        $endpoint = $this->webhookService->create($workspace->id, $validated);

        return $this->created($endpoint, 'Webhook endpoint created successfully');
    }

    /**
     * Show a specific webhook endpoint.
     * GET /api/v1/workspaces/{workspace}/webhook-endpoints/{webhookEndpoint}
     */
    public function show(Request $request, Workspace $workspace, WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($webhookEndpoint->workspace_id !== $workspace->id) {
            return $this->notFound('Webhook endpoint not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        return $this->success($webhookEndpoint, 'Webhook endpoint retrieved successfully');
    }

    /**
     * Update a webhook endpoint.
     * PUT /api/v1/workspaces/{workspace}/webhook-endpoints/{webhookEndpoint}
     */
    public function update(Request $request, Workspace $workspace, WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($webhookEndpoint->workspace_id !== $workspace->id) {
            return $this->notFound('Webhook endpoint not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'url' => 'sometimes|url|max:2048',
            'events' => 'sometimes|array|min:1',
            'events.*' => 'required|string|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        $endpoint = $this->webhookService->update($webhookEndpoint, $validated);

        return $this->success($endpoint, 'Webhook endpoint updated successfully');
    }

    /**
     * Delete a webhook endpoint.
     * DELETE /api/v1/workspaces/{workspace}/webhook-endpoints/{webhookEndpoint}
     */
    public function destroy(Request $request, Workspace $workspace, WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($webhookEndpoint->workspace_id !== $workspace->id) {
            return $this->notFound('Webhook endpoint not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $this->webhookService->delete($webhookEndpoint);

        return $this->noContent();
    }

    /**
     * List deliveries for a webhook endpoint.
     * GET /api/v1/workspaces/{workspace}/webhook-endpoints/{webhookEndpoint}/deliveries
     */
    public function deliveries(Request $request, Workspace $workspace, WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($webhookEndpoint->workspace_id !== $workspace->id) {
            return $this->notFound('Webhook endpoint not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $filters = [
            'per_page' => $request->query('per_page', 20),
        ];

        $deliveries = $this->webhookService->getDeliveries($webhookEndpoint->id, $filters);

        return $this->paginated($deliveries, 'Webhook deliveries retrieved successfully');
    }

    /**
     * Send a test webhook to an endpoint.
     * POST /api/v1/workspaces/{workspace}/webhook-endpoints/{webhookEndpoint}/test
     */
    public function test(Request $request, Workspace $workspace, WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($webhookEndpoint->workspace_id !== $workspace->id) {
            return $this->notFound('Webhook endpoint not found');
        }

        if (! $workspace->hasMember($user->id) && ! $user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $delivery = $this->webhookService->testEndpoint($webhookEndpoint);

        return $this->success($delivery, 'Test webhook sent successfully');
    }
}
