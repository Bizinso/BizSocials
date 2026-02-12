<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inbox;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Inbox\InboxContact;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Inbox\InboxContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InboxContactController extends Controller
{
    public function __construct(
        private readonly InboxContactService $contactService,
    ) {}

    /**
     * List contacts for a workspace.
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
            'platform' => $request->query('platform'),
            'search' => $request->query('search'),
            'per_page' => $request->query('per_page', 20),
            'sort_by' => $request->query('sort_by', 'last_seen_at'),
            'sort_dir' => $request->query('sort_dir', 'desc'),
        ];

        $contacts = $this->contactService->list($workspace, $filters);

        return $this->paginated($contacts, 'Inbox contacts retrieved successfully');
    }

    /**
     * Create a new contact.
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
            'platform' => 'required|string|max:255',
            'platform_user_id' => 'required|string|max:255',
            'display_name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:5000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
        ]);

        $contact = $this->contactService->create($workspace, $validated);

        return $this->created($contact->toArray(), 'Inbox contact created successfully');
    }

    /**
     * Show a single contact.
     */
    public function show(Request $request, Workspace $workspace, InboxContact $inboxContact): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($inboxContact->workspace_id !== $workspace->id) {
            return $this->notFound('Contact not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        return $this->success($inboxContact->toArray(), 'Inbox contact retrieved successfully');
    }

    /**
     * Update a contact.
     */
    public function update(Request $request, Workspace $workspace, InboxContact $inboxContact): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($inboxContact->workspace_id !== $workspace->id) {
            return $this->notFound('Contact not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'display_name' => 'sometimes|string|max:255',
            'username' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:5000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
        ]);

        $contact = $this->contactService->update($inboxContact, $validated);

        return $this->success($contact->toArray(), 'Inbox contact updated successfully');
    }

    /**
     * Delete a contact.
     */
    public function destroy(Request $request, Workspace $workspace, InboxContact $inboxContact): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($inboxContact->workspace_id !== $workspace->id) {
            return $this->notFound('Contact not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $this->contactService->delete($inboxContact);

        return $this->noContent();
    }
}
