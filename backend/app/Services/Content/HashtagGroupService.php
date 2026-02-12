<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Content\HashtagGroup;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class HashtagGroupService extends BaseService
{
    /**
     * List hashtag groups for a workspace.
     *
     * @param array<string, mixed> $filters
     */
    public function list(string $workspaceId, array $filters = []): LengthAwarePaginator
    {
        $query = HashtagGroup::forWorkspace($workspaceId);

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = min($perPage, 100);

        return $query
            ->orderBy('usage_count', 'desc')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Create a new hashtag group.
     *
     * @param array<string, mixed> $data
     */
    public function create(string $workspaceId, array $data): HashtagGroup
    {
        $group = HashtagGroup::create([
            'workspace_id' => $workspaceId,
            'name' => $data['name'],
            'hashtags' => $data['hashtags'],
            'description' => $data['description'] ?? null,
            'usage_count' => 0,
        ]);

        $this->log('Hashtag group created', ['group_id' => $group->id]);

        return $group;
    }

    /**
     * Update a hashtag group.
     *
     * @param array<string, mixed> $data
     */
    public function update(HashtagGroup $group, array $data): HashtagGroup
    {
        $group->update($data);

        $this->log('Hashtag group updated', ['group_id' => $group->id]);

        return $group;
    }

    /**
     * Delete a hashtag group.
     */
    public function delete(HashtagGroup $group): bool
    {
        $deleted = $group->delete();

        $this->log('Hashtag group deleted', ['group_id' => $group->id]);

        return $deleted;
    }
}
