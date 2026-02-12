<?php

declare(strict_types=1);

namespace App\Services\Feedback;

use App\Data\Feedback\AddFeedbackCommentData;
use App\Data\Feedback\FeedbackStatsData;
use App\Data\Feedback\SubmitFeedbackData;
use App\Enums\Feedback\FeedbackCategory;
use App\Enums\Feedback\FeedbackSource;
use App\Enums\Feedback\FeedbackStatus;
use App\Enums\Feedback\FeedbackType;
use App\Enums\Feedback\VoteType;
use App\Models\Feedback\Feedback;
use App\Models\Feedback\FeedbackComment;
use App\Models\Feedback\FeedbackVote;
use App\Models\Feedback\RoadmapItem;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final class FeedbackService extends BaseService
{
    /**
     * List public feedback with filters.
     *
     * @param array<string, mixed> $filters
     */
    public function listPublic(array $filters = []): LengthAwarePaginator
    {
        $query = Feedback::query()
            ->with(['comments' => fn ($q) => $q->public()])
            ->whereIn('status', [
                FeedbackStatus::NEW,
                FeedbackStatus::UNDER_REVIEW,
                FeedbackStatus::PLANNED,
                FeedbackStatus::IN_PROGRESS,
                FeedbackStatus::SHIPPED,
            ]);

        // Filter by type
        if (!empty($filters['type'])) {
            $type = FeedbackType::tryFrom($filters['type']);
            if ($type !== null) {
                $query->byType($type);
            }
        }

        // Filter by category
        if (!empty($filters['category'])) {
            $category = FeedbackCategory::tryFrom($filters['category']);
            if ($category !== null) {
                $query->byCategory($category);
            }
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $status = FeedbackStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        // Search
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        // Special sort options
        if ($sortBy === 'popular') {
            $query->topVoted();
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        return $query->paginate($perPage);
    }

    /**
     * Submit new feedback.
     */
    public function submit(SubmitFeedbackData $data, ?User $user = null): Feedback
    {
        return $this->transaction(function () use ($data, $user) {
            $feedback = Feedback::create([
                'user_id' => $user?->id,
                'tenant_id' => $user?->tenant_id,
                'submitter_email' => $data->email ?? $user?->email,
                'submitter_name' => $data->name ?? $user?->name,
                'title' => $data->title,
                'description' => $data->description,
                'feedback_type' => $data->type,
                'category' => $data->category,
                'status' => FeedbackStatus::NEW,
                'source' => FeedbackSource::PORTAL,
                'vote_count' => 0,
            ]);

            // Auto-upvote by the submitter if authenticated
            if ($user !== null) {
                $this->vote($feedback, $user, VoteType::UPVOTE);
                $feedback->refresh();
            }

            $this->log('Feedback submitted', [
                'feedback_id' => $feedback->id,
                'user_id' => $user?->id,
            ]);

            return $feedback;
        });
    }

    /**
     * Vote on feedback.
     *
     * @throws ValidationException
     */
    public function vote(Feedback $feedback, User $user, VoteType $type): FeedbackVote
    {
        return $this->transaction(function () use ($feedback, $user, $type) {
            // Check if user already voted
            $existingVote = FeedbackVote::where('feedback_id', $feedback->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingVote !== null) {
                // If same vote type, throw error
                if ($existingVote->vote_type === $type) {
                    throw ValidationException::withMessages([
                        'vote_type' => ['You have already voted on this feedback.'],
                    ]);
                }

                // Change vote type
                $oldValue = $existingVote->vote_type->value();
                $existingVote->vote_type = $type;
                $existingVote->save();

                // Update vote count (remove old, add new)
                $feedback->vote_count = $feedback->vote_count - $oldValue + $type->value();
                $feedback->save();

                return $existingVote;
            }

            // Create new vote
            $vote = FeedbackVote::create([
                'feedback_id' => $feedback->id,
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'vote_type' => $type,
            ]);

            // Update vote count
            $feedback->vote_count = $feedback->vote_count + $type->value();
            $feedback->save();

            $this->log('Vote cast on feedback', [
                'feedback_id' => $feedback->id,
                'user_id' => $user->id,
                'vote_type' => $type->value,
            ]);

            return $vote;
        });
    }

    /**
     * Remove user's vote from feedback.
     */
    public function removeVote(Feedback $feedback, User $user): void
    {
        $this->transaction(function () use ($feedback, $user) {
            $vote = FeedbackVote::where('feedback_id', $feedback->id)
                ->where('user_id', $user->id)
                ->first();

            if ($vote === null) {
                return;
            }

            // Update vote count
            $feedback->vote_count = $feedback->vote_count - $vote->vote_type->value();
            $feedback->save();

            $vote->delete();

            $this->log('Vote removed from feedback', [
                'feedback_id' => $feedback->id,
                'user_id' => $user->id,
            ]);
        });
    }

    /**
     * Add a comment to feedback.
     */
    public function addComment(
        Feedback $feedback,
        AddFeedbackCommentData $data,
        ?User $user = null
    ): FeedbackComment {
        return $this->transaction(function () use ($feedback, $data, $user) {
            $comment = FeedbackComment::create([
                'feedback_id' => $feedback->id,
                'user_id' => $user?->id,
                'commenter_name' => $data->commenter_name ?? $user?->name ?? 'Anonymous',
                'content' => $data->content,
                'is_internal' => false,
                'is_official_response' => false,
            ]);

            $this->log('Comment added to feedback', [
                'feedback_id' => $feedback->id,
                'comment_id' => $comment->id,
                'user_id' => $user?->id,
            ]);

            return $comment;
        });
    }

    /**
     * Get popular feedback.
     */
    public function getPopular(int $limit = 10): Collection
    {
        return Feedback::query()
            ->with(['comments' => fn ($q) => $q->public()])
            ->topVoted()
            ->whereIn('status', [
                FeedbackStatus::NEW,
                FeedbackStatus::UNDER_REVIEW,
                FeedbackStatus::PLANNED,
            ])
            ->limit($limit)
            ->get();
    }

    /**
     * Get feedback by ID.
     *
     * @throws ModelNotFoundException
     */
    public function get(string $id): Feedback
    {
        $feedback = Feedback::with(['comments' => fn ($q) => $q->public(), 'roadmapItem'])
            ->find($id);

        if ($feedback === null) {
            throw new ModelNotFoundException('Feedback not found.');
        }

        return $feedback;
    }

    /**
     * List all feedback for admin.
     *
     * @param array<string, mixed> $filters
     */
    public function listAll(array $filters = []): LengthAwarePaginator
    {
        $query = Feedback::query()
            ->with(['user', 'comments', 'roadmapItem']);

        // Filter by type
        if (!empty($filters['type'])) {
            $type = FeedbackType::tryFrom($filters['type']);
            if ($type !== null) {
                $query->byType($type);
            }
        }

        // Filter by category
        if (!empty($filters['category'])) {
            $category = FeedbackCategory::tryFrom($filters['category']);
            if ($category !== null) {
                $query->byCategory($category);
            }
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $status = FeedbackStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        // Filter open/closed
        if (!empty($filters['is_open'])) {
            $query->open();
        }
        if (!empty($filters['is_closed'])) {
            $query->closed();
        }

        // Search
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        if ($sortBy === 'popular') {
            $query->topVoted();
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        return $query->paginate($perPage);
    }

    /**
     * Update feedback status.
     *
     * @throws ValidationException
     */
    public function updateStatus(Feedback $feedback, FeedbackStatus $status, ?string $reason = null): Feedback
    {
        if (!$feedback->status->canTransitionTo($status)) {
            throw ValidationException::withMessages([
                'status' => ['Cannot transition from ' . $feedback->status->label() . ' to ' . $status->label()],
            ]);
        }

        $feedback->status = $status;
        $feedback->status_reason = $reason;
        $feedback->save();

        $this->log('Feedback status updated', [
            'feedback_id' => $feedback->id,
            'old_status' => $feedback->getOriginal('status'),
            'new_status' => $status->value,
        ]);

        return $feedback->fresh(['user', 'comments', 'roadmapItem']);
    }

    /**
     * Link feedback to a roadmap item.
     */
    public function linkToRoadmap(Feedback $feedback, RoadmapItem $item): void
    {
        $this->transaction(function () use ($feedback, $item) {
            $feedback->linkToRoadmap($item);

            $this->log('Feedback linked to roadmap item', [
                'feedback_id' => $feedback->id,
                'roadmap_item_id' => $item->id,
            ]);
        });
    }

    /**
     * Get feedback statistics.
     */
    public function getStats(): FeedbackStatsData
    {
        $totalFeedback = Feedback::count();
        $newFeedback = Feedback::new()->count();
        $underReview = Feedback::underReview()->count();
        $planned = Feedback::planned()->count();
        $shipped = Feedback::shipped()->count();
        $declined = Feedback::where('status', FeedbackStatus::DECLINED)->count();

        // Stats by status
        $byStatus = [];
        foreach (FeedbackStatus::cases() as $status) {
            $byStatus[$status->value] = Feedback::where('status', $status)->count();
        }

        // Stats by type
        $byType = [];
        foreach (FeedbackType::cases() as $type) {
            $byType[$type->value] = Feedback::byType($type)->count();
        }

        // Stats by category
        $byCategory = [];
        foreach (FeedbackCategory::cases() as $category) {
            $byCategory[$category->value] = Feedback::byCategory($category)->count();
        }

        return new FeedbackStatsData(
            total_feedback: $totalFeedback,
            new_feedback: $newFeedback,
            under_review: $underReview,
            planned: $planned,
            shipped: $shipped,
            declined: $declined,
            by_status: $byStatus,
            by_type: $byType,
            by_category: $byCategory,
        );
    }
}
