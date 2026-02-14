<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\Content\MediaLibraryItem;
use App\Models\User;
use App\Models\Workspace\Workspace;
use App\Services\Content\MediaLibraryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MediaLibraryController extends Controller
{
    public function __construct(
        private readonly MediaLibraryService $mediaLibraryService,
    ) {}

    /**
     * List media library items.
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
            'folder_id' => $request->query('folder_id'),
            'type' => $request->query('type'),
            'search' => $request->query('search'),
            'per_page' => $request->query('per_page', 20),
        ];

        $items = $this->mediaLibraryService->search($workspace->id, $filters);

        return $this->paginated($items, 'Media library items retrieved successfully');
    }

    /**
     * Upload a new file.
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
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,pdf,doc,docx|max:51200',
            'folder_id' => 'nullable|uuid|exists:media_folders,id',
            'alt_text' => 'nullable|string|max:500',
        ]);

        $item = $this->mediaLibraryService->upload(
            $workspace,
            $validated['file'],
            $validated['folder_id'] ?? null,
            $validated['alt_text'] ?? null
        );

        return $this->created($item, 'File uploaded successfully');
    }

    /**
     * Get a single media item.
     */
    public function show(Request $request, Workspace $workspace, MediaLibraryItem $media_library): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($media_library->workspace_id !== $workspace->id) {
            return $this->notFound('Media item not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        return $this->success($media_library->load(['uploadedBy', 'folder']), 'Media item retrieved successfully');
    }

    /**
     * Update a media item.
     */
    public function update(Request $request, Workspace $workspace, MediaLibraryItem $media_library): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($media_library->workspace_id !== $workspace->id) {
            return $this->notFound('Media item not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $validated = $request->validate([
            'alt_text' => 'nullable|string|max:500',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $media_library->update($validated);

        return $this->success($media_library, 'Media item updated successfully');
    }

    /**
     * Delete a media item.
     */
    public function destroy(Request $request, Workspace $workspace, MediaLibraryItem $media_library): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if ($media_library->workspace_id !== $workspace->id) {
            return $this->notFound('Media item not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $this->mediaLibraryService->delete($media_library);

        return $this->success(null, 'Media item deleted successfully');
    }

    /**
     * List folders for the workspace.
     */
    public function folders(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $folders = $workspace->load('mediaFolders')->mediaFolders ?? [];

        return $this->success($folders, 'Folders retrieved successfully');
    }

    /**
     * Create a new folder.
     */
    public function createFolder(Request $request, Workspace $workspace): JsonResponse
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
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|uuid|exists:media_folders,id',
            'color' => 'nullable|string|max:7',
        ]);

        $folder = $this->mediaLibraryService->createFolder(
            $workspace,
            $validated['name'],
            $validated['parent_id'] ?? null,
            $validated['color'] ?? null
        );

        return $this->created($folder, 'Folder created successfully');
    }

    /**
     * Move items to a folder.
     */
    public function moveItems(Request $request, Workspace $workspace): JsonResponse
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
            'item_ids' => 'required|array',
            'item_ids.*' => 'uuid|exists:media_library_items,id',
            'folder_id' => 'nullable|uuid|exists:media_folders,id',
        ]);

        $count = $this->mediaLibraryService->moveItems(
            $validated['item_ids'],
            $validated['folder_id'] ?? null
        );

        return $this->success(['moved_count' => $count], 'Items moved successfully');
    }

    /**
     * Get usage statistics for the workspace.
     */
    public function usageStats(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($workspace->tenant_id !== $user->tenant_id) {
            return $this->notFound('Workspace not found');
        }

        if (!$workspace->hasMember($user->id) && !$user->isAdmin()) {
            return $this->forbidden('You do not have access to this workspace');
        }

        $stats = $this->mediaLibraryService->getUsageStats($workspace->id);

        return $this->success($stats, 'Usage statistics retrieved successfully');
    }
}
