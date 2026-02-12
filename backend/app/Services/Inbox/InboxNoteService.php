<?php

declare(strict_types=1);

namespace App\Services\Inbox;

use App\Models\Inbox\InboxInternalNote;
use App\Models\Inbox\InboxItem;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;

final class InboxNoteService extends BaseService
{
    /**
     * List notes for an inbox item.
     *
     * @return Collection<int, InboxInternalNote>
     */
    public function list(InboxItem $item): Collection
    {
        return $item->notes()
            ->with('user')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Create a new internal note.
     *
     * @param array<string, mixed> $data
     */
    public function create(InboxItem $item, User $user, array $data): InboxInternalNote
    {
        return $this->transaction(function () use ($item, $user, $data): InboxInternalNote {
            $note = InboxInternalNote::create([
                'inbox_item_id' => $item->id,
                'user_id' => $user->id,
                'content' => $data['content'],
            ]);

            $this->log('Internal note created', [
                'note_id' => $note->id,
                'inbox_item_id' => $item->id,
                'user_id' => $user->id,
            ]);

            return $note->load('user');
        });
    }

    /**
     * Delete an internal note.
     */
    public function delete(InboxInternalNote $note): void
    {
        $this->transaction(function () use ($note): void {
            $noteId = $note->id;
            $note->delete();

            $this->log('Internal note deleted', [
                'note_id' => $noteId,
            ]);
        });
    }
}
