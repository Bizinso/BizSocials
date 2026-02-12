<?php

declare(strict_types=1);

namespace App\Services\Inbox;

use App\Models\Inbox\InboxContact;
use App\Models\Workspace\Workspace;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class InboxContactService extends BaseService
{
    /**
     * List contacts for a workspace.
     *
     * @param array<string, mixed> $filters
     */
    public function list(Workspace $workspace, array $filters = []): LengthAwarePaginator
    {
        $query = InboxContact::forWorkspace($workspace->id);

        if (!empty($filters['platform'])) {
            $query->forPlatform($filters['platform']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);
        $perPage = min($perPage, 100);

        $sortBy = $filters['sort_by'] ?? 'last_seen_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }

    /**
     * Create a new contact.
     *
     * @param array<string, mixed> $data
     */
    public function create(Workspace $workspace, array $data): InboxContact
    {
        return $this->transaction(function () use ($workspace, $data): InboxContact {
            $contact = InboxContact::create([
                'workspace_id' => $workspace->id,
                'platform' => $data['platform'],
                'platform_user_id' => $data['platform_user_id'],
                'display_name' => $data['display_name'],
                'username' => $data['username'] ?? null,
                'avatar_url' => $data['avatar_url'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'notes' => $data['notes'] ?? null,
                'tags' => $data['tags'] ?? null,
                'first_seen_at' => $data['first_seen_at'] ?? now(),
                'last_seen_at' => $data['last_seen_at'] ?? now(),
            ]);

            $this->log('Inbox contact created', [
                'contact_id' => $contact->id,
                'workspace_id' => $workspace->id,
            ]);

            return $contact;
        });
    }

    /**
     * Update an existing contact.
     *
     * @param array<string, mixed> $data
     */
    public function update(InboxContact $contact, array $data): InboxContact
    {
        return $this->transaction(function () use ($contact, $data): InboxContact {
            $contact->update($data);

            $this->log('Inbox contact updated', [
                'contact_id' => $contact->id,
            ]);

            return $contact->fresh() ?? $contact;
        });
    }

    /**
     * Delete a contact.
     */
    public function delete(InboxContact $contact): void
    {
        $this->transaction(function () use ($contact): void {
            $contact->delete();

            $this->log('Inbox contact deleted', [
                'contact_id' => $contact->id,
            ]);
        });
    }

    /**
     * Find or create a contact by platform and platform user ID.
     *
     * @param array<string, mixed> $data
     */
    public function findOrCreate(Workspace $workspace, array $data): InboxContact
    {
        $contact = InboxContact::forWorkspace($workspace->id)
            ->where('platform', $data['platform'])
            ->where('platform_user_id', $data['platform_user_id'])
            ->first();

        if ($contact !== null) {
            $contact->incrementInteraction();

            return $contact;
        }

        return $this->create($workspace, $data);
    }
}
