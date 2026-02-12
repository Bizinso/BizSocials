<?php

declare(strict_types=1);

namespace App\Services\Feedback;

use App\Data\Feedback\CreateReleaseNoteData;
use App\Data\Feedback\SubscribeChangelogData;
use App\Data\Feedback\UpdateReleaseNoteData;
use App\Enums\Feedback\ChangeType;
use App\Enums\Feedback\ReleaseNoteStatus;
use App\Enums\Feedback\ReleaseType;
use App\Models\Feedback\ChangelogSubscription;
use App\Models\Feedback\ReleaseNote;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ReleaseNoteService extends BaseService
{
    /**
     * List published release notes.
     *
     * @param array<string, mixed> $filters
     */
    public function listPublished(array $filters = []): LengthAwarePaginator
    {
        $query = ReleaseNote::query()
            ->published()
            ->with(['items'])
            ->recent();

        // Filter by type
        if (!empty($filters['type'])) {
            $type = ReleaseType::tryFrom($filters['type']);
            if ($type !== null) {
                $query->byType($type);
            }
        }

        // Search
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('version', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('content', 'like', '%' . $filters['search'] . '%');
            });
        }

        $perPage = min((int) ($filters['per_page'] ?? 10), 50);

        return $query->paginate($perPage);
    }

    /**
     * Get a release note by slug (version-based).
     *
     * @throws ModelNotFoundException
     */
    public function getBySlug(string $slug): ReleaseNote
    {
        // Convert slug to version (v1-2-3 -> 1.2.3)
        $version = ltrim(str_replace('-', '.', $slug), 'v');

        $note = ReleaseNote::published()
            ->with(['items'])
            ->where('version', $version)
            ->first();

        if ($note === null) {
            throw new ModelNotFoundException('Release note not found.');
        }

        return $note;
    }

    /**
     * Get a release note by ID.
     *
     * @throws ModelNotFoundException
     */
    public function get(string $id): ReleaseNote
    {
        $note = ReleaseNote::with(['items'])->find($id);

        if ($note === null) {
            throw new ModelNotFoundException('Release note not found.');
        }

        return $note;
    }

    /**
     * Subscribe to changelog updates.
     */
    public function subscribe(SubscribeChangelogData $data): ChangelogSubscription
    {
        return $this->transaction(function () use ($data) {
            // Check for existing subscription
            $existing = ChangelogSubscription::forEmail($data->email)->first();

            if ($existing !== null) {
                // Reactivate if inactive
                if (!$existing->is_active) {
                    $existing->resubscribe();
                }

                // Update preferences
                $existing->update([
                    'notify_major' => $data->notify_major,
                    'notify_minor' => $data->notify_minor,
                    'notify_patch' => $data->notify_patch,
                ]);

                return $existing;
            }

            $subscription = ChangelogSubscription::create([
                'email' => $data->email,
                'notify_major' => $data->notify_major,
                'notify_minor' => $data->notify_minor,
                'notify_patch' => $data->notify_patch,
                'is_active' => true,
            ]);

            $this->log('Changelog subscription created', [
                'email' => $data->email,
            ]);

            return $subscription;
        });
    }

    /**
     * Unsubscribe from changelog updates using token/email.
     *
     * @throws ModelNotFoundException
     */
    public function unsubscribe(string $email): void
    {
        $subscription = ChangelogSubscription::forEmail($email)->first();

        if ($subscription === null) {
            throw new ModelNotFoundException('Subscription not found.');
        }

        $subscription->unsubscribe();

        $this->log('Changelog unsubscribed', [
            'email' => $email,
        ]);
    }

    /**
     * List all release notes for admin.
     *
     * @param array<string, mixed> $filters
     */
    public function listAll(array $filters = []): LengthAwarePaginator
    {
        $query = ReleaseNote::query()
            ->with(['items']);

        // Filter by status
        if (!empty($filters['status'])) {
            $status = ReleaseNoteStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        // Filter by type
        if (!empty($filters['type'])) {
            $type = ReleaseType::tryFrom($filters['type']);
            if ($type !== null) {
                $query->byType($type);
            }
        }

        // Search
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('version', 'like', '%' . $filters['search'] . '%');
            });
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }

    /**
     * Create a new release note.
     */
    public function create(CreateReleaseNoteData $data): ReleaseNote
    {
        return $this->transaction(function () use ($data) {
            $note = ReleaseNote::create([
                'version' => $data->version,
                'version_name' => $data->version_name,
                'title' => $data->title,
                'summary' => $data->summary,
                'content' => $data->content,
                'content_format' => 'markdown',
                'release_type' => $data->release_type,
                'status' => ReleaseNoteStatus::DRAFT,
                'is_public' => true,
            ]);

            // Add items if provided
            if (!empty($data->items)) {
                foreach ($data->items as $index => $itemData) {
                    $changeType = ChangeType::tryFrom($itemData['change_type'] ?? 'improvement');
                    $note->addItem(
                        $itemData['title'],
                        $changeType ?? ChangeType::IMPROVEMENT,
                        $itemData['description'] ?? null,
                    );
                }
            }

            $this->log('Release note created', [
                'release_note_id' => $note->id,
                'version' => $data->version,
            ]);

            return $note->fresh(['items']);
        });
    }

    /**
     * Update a release note.
     */
    public function update(ReleaseNote $note, UpdateReleaseNoteData $data): ReleaseNote
    {
        return $this->transaction(function () use ($note, $data) {
            $updateData = [];

            if ($data->version !== null) {
                $updateData['version'] = $data->version;
            }
            if ($data->title !== null) {
                $updateData['title'] = $data->title;
            }
            if ($data->content !== null) {
                $updateData['content'] = $data->content;
            }
            if ($data->version_name !== null) {
                $updateData['version_name'] = $data->version_name;
            }
            if ($data->summary !== null) {
                $updateData['summary'] = $data->summary;
            }
            if ($data->release_type !== null) {
                $updateData['release_type'] = $data->release_type;
            }

            if (!empty($updateData)) {
                $note->update($updateData);
            }

            // Update items if provided
            if ($data->items !== null) {
                // Remove existing items
                $note->items()->delete();

                // Add new items
                foreach ($data->items as $index => $itemData) {
                    $changeType = ChangeType::tryFrom($itemData['change_type'] ?? 'improvement');
                    $note->addItem(
                        $itemData['title'],
                        $changeType ?? ChangeType::IMPROVEMENT,
                        $itemData['description'] ?? null,
                    );
                }
            }

            $this->log('Release note updated', [
                'release_note_id' => $note->id,
            ]);

            return $note->fresh(['items']);
        });
    }

    /**
     * Publish a release note.
     *
     * @throws ValidationException
     */
    public function publish(ReleaseNote $note): ReleaseNote
    {
        if ($note->isPublished()) {
            throw ValidationException::withMessages([
                'status' => ['Release note is already published.'],
            ]);
        }

        $note->publish();

        $this->log('Release note published', [
            'release_note_id' => $note->id,
            'version' => $note->version,
        ]);

        return $note->fresh(['items']);
    }

    /**
     * Unpublish a release note.
     *
     * @throws ValidationException
     */
    public function unpublish(ReleaseNote $note): ReleaseNote
    {
        if (!$note->isPublished()) {
            throw ValidationException::withMessages([
                'status' => ['Release note is not published.'],
            ]);
        }

        $note->status = ReleaseNoteStatus::DRAFT;
        $note->save();

        $this->log('Release note unpublished', [
            'release_note_id' => $note->id,
        ]);

        return $note->fresh(['items']);
    }

    /**
     * Delete a release note.
     *
     * @throws ValidationException
     */
    public function delete(ReleaseNote $note): void
    {
        if ($note->isPublished()) {
            throw ValidationException::withMessages([
                'status' => ['Cannot delete a published release note. Unpublish it first.'],
            ]);
        }

        $this->transaction(function () use ($note) {
            // Delete items
            $note->items()->delete();

            $note->delete();

            $this->log('Release note deleted', [
                'release_note_id' => $note->id,
            ]);
        });
    }
}
