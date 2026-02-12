<?php

declare(strict_types=1);

namespace App\Services\Feedback;

use App\Data\Feedback\CreateRoadmapItemData;
use App\Data\Feedback\UpdateRoadmapItemData;
use App\Enums\Feedback\RoadmapCategory;
use App\Enums\Feedback\RoadmapStatus;
use App\Models\Feedback\RoadmapItem;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class RoadmapService extends BaseService
{
    /**
     * Get public roadmap grouped by status.
     *
     * @return Collection<string, Collection<int, RoadmapItem>>
     */
    public function getPublicRoadmap(): Collection
    {
        $items = RoadmapItem::query()
            ->public()
            ->whereIn('status', [
                RoadmapStatus::CONSIDERING,
                RoadmapStatus::PLANNED,
                RoadmapStatus::IN_PROGRESS,
                RoadmapStatus::BETA,
                RoadmapStatus::SHIPPED,
            ])
            ->orderBy('priority')
            ->orderByDesc('total_votes')
            ->get();

        // Group by status for display
        return $items->groupBy(fn (RoadmapItem $item) => $item->status->value);
    }

    /**
     * Get a roadmap item by ID.
     *
     * @throws ModelNotFoundException
     */
    public function getItem(string $id): RoadmapItem
    {
        $item = RoadmapItem::with(['linkedFeedback'])
            ->find($id);

        if ($item === null) {
            throw new ModelNotFoundException('Roadmap item not found.');
        }

        return $item;
    }

    /**
     * Get a public roadmap item by ID.
     *
     * @throws ModelNotFoundException
     */
    public function getPublicItem(string $id): RoadmapItem
    {
        $item = RoadmapItem::public()
            ->with(['linkedFeedback'])
            ->find($id);

        if ($item === null) {
            throw new ModelNotFoundException('Roadmap item not found.');
        }

        return $item;
    }

    /**
     * List all roadmap items for admin.
     *
     * @param array<string, mixed> $filters
     */
    public function listAll(array $filters = []): LengthAwarePaginator
    {
        $query = RoadmapItem::query()
            ->with(['linkedFeedback']);

        // Filter by status
        if (!empty($filters['status'])) {
            $status = RoadmapStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->byStatus($status);
            }
        }

        // Filter by category
        if (!empty($filters['category'])) {
            $category = RoadmapCategory::tryFrom($filters['category']);
            if ($category !== null) {
                $query->byCategory($category);
            }
        }

        // Filter by quarter
        if (!empty($filters['quarter'])) {
            $query->byQuarter($filters['quarter']);
        }

        // Filter by visibility
        if (isset($filters['is_public'])) {
            $query->where('is_public', (bool) $filters['is_public']);
        }

        // Search
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
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
     * Create a new roadmap item.
     */
    public function create(CreateRoadmapItemData $data): RoadmapItem
    {
        return $this->transaction(function () use ($data) {
            $item = RoadmapItem::create([
                'title' => $data->title,
                'description' => $data->description,
                'detailed_description' => $data->detailed_description,
                'category' => $data->category,
                'status' => $data->status,
                'quarter' => $data->target_quarter,
                'target_date' => $data->target_date,
                'is_public' => $data->is_public,
                'linked_feedback_count' => 0,
                'total_votes' => 0,
                'progress_percentage' => 0,
            ]);

            $this->log('Roadmap item created', [
                'roadmap_item_id' => $item->id,
            ]);

            return $item;
        });
    }

    /**
     * Update a roadmap item.
     */
    public function update(RoadmapItem $item, UpdateRoadmapItemData $data): RoadmapItem
    {
        return $this->transaction(function () use ($item, $data) {
            $updateData = [];

            if ($data->title !== null) {
                $updateData['title'] = $data->title;
            }
            if ($data->description !== null) {
                $updateData['description'] = $data->description;
            }
            if ($data->detailed_description !== null) {
                $updateData['detailed_description'] = $data->detailed_description;
            }
            if ($data->category !== null) {
                $updateData['category'] = $data->category;
            }
            if ($data->status !== null) {
                $updateData['status'] = $data->status;
            }
            if ($data->target_quarter !== null) {
                $updateData['quarter'] = $data->target_quarter;
            }
            if ($data->target_date !== null) {
                $updateData['target_date'] = $data->target_date;
            }
            if ($data->progress_percentage !== null) {
                $updateData['progress_percentage'] = max(0, min(100, $data->progress_percentage));
            }
            if ($data->is_public !== null) {
                $updateData['is_public'] = $data->is_public;
            }

            if (!empty($updateData)) {
                $item->update($updateData);
            }

            $this->log('Roadmap item updated', [
                'roadmap_item_id' => $item->id,
            ]);

            return $item->fresh(['linkedFeedback']);
        });
    }

    /**
     * Update roadmap item status.
     *
     * @throws ValidationException
     */
    public function updateStatus(RoadmapItem $item, RoadmapStatus $status): RoadmapItem
    {
        if (!$item->status->canTransitionTo($status)) {
            throw ValidationException::withMessages([
                'status' => ['Cannot transition from ' . $item->status->label() . ' to ' . $status->label()],
            ]);
        }

        $item->status = $status;

        // Auto-set shipped date if transitioning to shipped
        if ($status === RoadmapStatus::SHIPPED) {
            $item->shipped_date = now();
            $item->progress_percentage = 100;
        }

        $item->save();

        $this->log('Roadmap item status updated', [
            'roadmap_item_id' => $item->id,
            'new_status' => $status->value,
        ]);

        return $item->fresh(['linkedFeedback']);
    }

    /**
     * Delete a roadmap item.
     */
    public function delete(RoadmapItem $item): void
    {
        $this->transaction(function () use ($item) {
            // Unlink all feedback
            $item->linkedFeedback()->detach();

            $item->delete();

            $this->log('Roadmap item deleted', [
                'roadmap_item_id' => $item->id,
            ]);
        });
    }
}
