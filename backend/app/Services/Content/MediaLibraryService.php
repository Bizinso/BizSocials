<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Content\MediaFolder;
use App\Models\Content\MediaLibraryItem;
use App\Models\Workspace\Workspace;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class MediaLibraryService extends BaseService
{
    /**
     * Upload a file to the media library.
     */
    public function upload(
        Workspace $workspace,
        UploadedFile $file,
        ?string $folderId = null,
        ?string $altText = null
    ): MediaLibraryItem {
        return $this->transaction(function () use ($workspace, $file, $folderId, $altText) {
            $disk = config('filesystems.default');
            $originalName = $file->getClientOriginalName();
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = 'media/' . $workspace->id . '/' . $fileName;

            // Store file
            Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

            // Get file info
            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();
            $url = Storage::disk($disk)->url($path);

            // Get image dimensions if image
            $width = null;
            $height = null;
            if (str_starts_with($mimeType, 'image/')) {
                $imageSize = getimagesize($file->getRealPath());
                if ($imageSize !== false) {
                    [$width, $height] = $imageSize;
                }
            }

            $item = MediaLibraryItem::create([
                'workspace_id' => $workspace->id,
                'uploaded_by_user_id' => auth()->id(),
                'folder_id' => $folderId,
                'file_name' => $fileName,
                'original_name' => $originalName,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'disk' => $disk,
                'path' => $path,
                'url' => $url,
                'alt_text' => $altText,
                'width' => $width,
                'height' => $height,
            ]);

            $this->log('Media file uploaded', ['item_id' => $item->id]);

            return $item;
        });
    }

    /**
     * Create a new folder.
     */
    public function createFolder(
        Workspace $workspace,
        string $name,
        ?string $parentId = null,
        ?string $color = null
    ): MediaFolder {
        $slug = Str::slug($name);

        $folder = MediaFolder::create([
            'workspace_id' => $workspace->id,
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug,
            'color' => $color,
            'sort_order' => 0,
        ]);

        $this->log('Media folder created', ['folder_id' => $folder->id]);

        return $folder;
    }

    /**
     * Move items to a target folder.
     *
     * @param array<string> $itemIds
     */
    public function moveItems(array $itemIds, ?string $targetFolderId = null): int
    {
        $count = MediaLibraryItem::whereIn('id', $itemIds)
            ->update(['folder_id' => $targetFolderId]);

        $this->log('Media items moved', [
            'count' => $count,
            'target_folder_id' => $targetFolderId,
        ]);

        return $count;
    }

    /**
     * Tag items.
     *
     * @param array<string> $itemIds
     * @param array<string> $tags
     */
    public function tagItems(array $itemIds, array $tags): int
    {
        $count = 0;

        foreach ($itemIds as $itemId) {
            $item = MediaLibraryItem::find($itemId);
            if ($item !== null) {
                $existingTags = $item->tags ?? [];
                $mergedTags = array_unique(array_merge($existingTags, $tags));
                $item->update(['tags' => $mergedTags]);
                $count++;
            }
        }

        $this->log('Media items tagged', ['count' => $count]);

        return $count;
    }

    /**
     * Search media library with filters.
     *
     * @param array<string, mixed> $filters
     */
    public function search(string $workspaceId, array $filters = []): LengthAwarePaginator
    {
        $query = MediaLibraryItem::forWorkspace($workspaceId)
            ->with(['uploadedBy', 'folder']);

        // Filter by folder
        if (!empty($filters['folder_id'])) {
            $query->inFolder($filters['folder_id']);
        }

        // Filter by type
        if (!empty($filters['type'])) {
            $query->ofType($filters['type']);
        }

        // Search
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);
        $perPage = min($perPage, 100);

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Delete a media item.
     */
    public function delete(MediaLibraryItem $item): bool
    {
        // Delete file from storage
        if (Storage::disk($item->disk)->exists($item->path)) {
            Storage::disk($item->disk)->delete($item->path);
        }

        $deleted = $item->delete();

        $this->log('Media item deleted', ['item_id' => $item->id]);

        return $deleted;
    }

    /**
     * Track usage of a media item.
     */
    public function trackUsage(MediaLibraryItem $item): void
    {
        $item->trackUsage();

        $this->log('Media usage tracked', [
            'item_id' => $item->id,
            'usage_count' => $item->usage_count,
        ]);
    }

    /**
     * Get usage statistics for a workspace.
     *
     * @return array<string, mixed>
     */
    public function getUsageStats(string $workspaceId): array
    {
        $items = MediaLibraryItem::forWorkspace($workspaceId)->get();

        return [
            'total_items' => $items->count(),
            'total_size' => $items->sum('file_size'),
            'total_usage' => $items->sum('usage_count'),
            'by_type' => [
                'images' => $items->filter(fn($item) => $item->isImage())->count(),
                'videos' => $items->filter(fn($item) => $item->isVideo())->count(),
                'documents' => $items->filter(fn($item) => $item->isDocument())->count(),
            ],
            'most_used' => $items->sortByDesc('usage_count')->take(10)->values(),
            'recently_used' => $items->whereNotNull('last_used_at')
                ->sortByDesc('last_used_at')
                ->take(10)
                ->values(),
        ];
    }
}
