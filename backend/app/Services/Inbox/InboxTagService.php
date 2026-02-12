<?php

declare(strict_types=1);

namespace App\Services\Inbox;

use App\Models\Inbox\InboxItem;
use App\Models\Inbox\InboxItemTag;
use App\Models\Workspace\Workspace;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

final class InboxTagService extends BaseService
{
    /**
     * List tags for a workspace.
     *
     * @param array<string, mixed> $filters
     */
    public function list(Workspace $workspace, array $filters = []): LengthAwarePaginator
    {
        $query = InboxItemTag::forWorkspace($workspace->id);

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        $perPage = (int) ($filters['per_page'] ?? 50);
        $perPage = min($perPage, 100);

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Create a new tag.
     *
     * @param array<string, mixed> $data
     */
    public function create(Workspace $workspace, array $data): InboxItemTag
    {
        return $this->transaction(function () use ($workspace, $data): InboxItemTag {
            $tag = InboxItemTag::create([
                'workspace_id' => $workspace->id,
                'name' => $data['name'],
                'color' => $data['color'] ?? '#6B7280',
            ]);

            $this->log('Inbox tag created', [
                'tag_id' => $tag->id,
                'workspace_id' => $workspace->id,
            ]);

            return $tag;
        });
    }

    /**
     * Update an existing tag.
     *
     * @param array<string, mixed> $data
     */
    public function update(InboxItemTag $tag, array $data): InboxItemTag
    {
        return $this->transaction(function () use ($tag, $data): InboxItemTag {
            $tag->update($data);

            $this->log('Inbox tag updated', [
                'tag_id' => $tag->id,
            ]);

            return $tag->fresh() ?? $tag;
        });
    }

    /**
     * Delete a tag.
     */
    public function delete(InboxItemTag $tag): void
    {
        $this->transaction(function () use ($tag): void {
            $tag->delete();

            $this->log('Inbox tag deleted', [
                'tag_id' => $tag->id,
            ]);
        });
    }

    /**
     * Attach a tag to an inbox item.
     *
     * @throws ValidationException
     */
    public function attachToItem(InboxItem $item, InboxItemTag $tag): void
    {
        if ($item->workspace_id !== $tag->workspace_id) {
            throw ValidationException::withMessages([
                'tag' => ['Tag does not belong to the same workspace.'],
            ]);
        }

        if (!$item->tags()->where('tag_id', $tag->id)->exists()) {
            $item->tags()->attach($tag->id);

            $this->log('Tag attached to inbox item', [
                'inbox_item_id' => $item->id,
                'tag_id' => $tag->id,
            ]);
        }
    }

    /**
     * Detach a tag from an inbox item.
     */
    public function detachFromItem(InboxItem $item, InboxItemTag $tag): void
    {
        $item->tags()->detach($tag->id);

        $this->log('Tag detached from inbox item', [
            'inbox_item_id' => $item->id,
            'tag_id' => $tag->id,
        ]);
    }
}
