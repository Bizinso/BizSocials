<?php

declare(strict_types=1);

namespace App\Services\Inbox;

use App\Models\Inbox\SavedReply;
use App\Models\Workspace\Workspace;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class SavedReplyService extends BaseService
{
    /**
     * List saved replies for a workspace.
     *
     * @param array<string, mixed> $filters
     */
    public function list(Workspace $workspace, array $filters = []): LengthAwarePaginator
    {
        $query = SavedReply::forWorkspace($workspace->id);

        if (!empty($filters['category'])) {
            $query->forCategory($filters['category']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);
        $perPage = min($perPage, 100);

        return $query->orderBy('title')->paginate($perPage);
    }

    /**
     * Create a new saved reply.
     *
     * @param array<string, mixed> $data
     */
    public function create(Workspace $workspace, array $data): SavedReply
    {
        return $this->transaction(function () use ($workspace, $data): SavedReply {
            $reply = SavedReply::create([
                'workspace_id' => $workspace->id,
                'title' => $data['title'],
                'content' => $data['content'],
                'shortcut' => $data['shortcut'] ?? null,
                'category' => $data['category'] ?? null,
            ]);

            $this->log('Saved reply created', [
                'reply_id' => $reply->id,
                'workspace_id' => $workspace->id,
            ]);

            return $reply;
        });
    }

    /**
     * Update an existing saved reply.
     *
     * @param array<string, mixed> $data
     */
    public function update(SavedReply $reply, array $data): SavedReply
    {
        return $this->transaction(function () use ($reply, $data): SavedReply {
            $reply->update($data);

            $this->log('Saved reply updated', [
                'reply_id' => $reply->id,
            ]);

            return $reply->fresh() ?? $reply;
        });
    }

    /**
     * Delete a saved reply.
     */
    public function delete(SavedReply $reply): void
    {
        $this->transaction(function () use ($reply): void {
            $reply->delete();

            $this->log('Saved reply deleted', [
                'reply_id' => $reply->id,
            ]);
        });
    }

    /**
     * Increment the usage count for a saved reply.
     */
    public function incrementUsage(SavedReply $reply): SavedReply
    {
        $reply->increment('usage_count');

        $this->log('Saved reply usage incremented', [
            'reply_id' => $reply->id,
            'usage_count' => $reply->usage_count + 1,
        ]);

        return $reply->fresh() ?? $reply;
    }
}
