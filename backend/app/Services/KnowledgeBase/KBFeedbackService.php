<?php

declare(strict_types=1);

namespace App\Services\KnowledgeBase;

use App\Data\KnowledgeBase\SubmitFeedbackData;
use App\Enums\KnowledgeBase\KBFeedbackCategory;
use App\Enums\KnowledgeBase\KBFeedbackStatus;
use App\Models\KnowledgeBase\KBArticle;
use App\Models\KnowledgeBase\KBArticleFeedback;
use App\Models\Platform\SuperAdminUser;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class KBFeedbackService extends BaseService
{
    /**
     * Submit feedback for an article.
     */
    public function submitFeedback(KBArticle $article, SubmitFeedbackData $data, ?string $ipAddress = null): KBArticleFeedback
    {
        return $this->transaction(function () use ($article, $data, $ipAddress) {
            // Parse feedback category
            $feedbackCategory = null;
            if ($data->category !== null) {
                $feedbackCategory = KBFeedbackCategory::tryFrom($data->category);
            }

            $feedback = KBArticleFeedback::create([
                'article_id' => $article->id,
                'is_helpful' => $data->is_helpful,
                'feedback_text' => $data->comment,
                'feedback_category' => $feedbackCategory,
                'ip_address' => $ipAddress,
                'session_id' => session()->getId(),
                'status' => KBFeedbackStatus::PENDING,
            ]);

            // Update article helpful/not helpful counts
            if ($data->is_helpful) {
                $article->recordHelpfulVote();
            } else {
                $article->recordNotHelpfulVote();
            }

            $this->log('Feedback submitted', [
                'feedback_id' => $feedback->id,
                'article_id' => $article->id,
                'is_helpful' => $data->is_helpful,
            ]);

            return $feedback;
        });
    }

    /**
     * List feedback for an article.
     */
    public function listForArticle(KBArticle $article): Collection
    {
        return KBArticleFeedback::forArticle($article->id)
            ->with(['reviewedBy'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * List pending feedback for admin review.
     *
     * @param array<string, mixed> $filters
     */
    public function listPending(array $filters = []): LengthAwarePaginator
    {
        $query = KBArticleFeedback::pending()
            ->with(['article', 'reviewedBy'])
            ->orderByDesc('created_at');

        // Filter by article
        if (!empty($filters['article_id'])) {
            $query->where('article_id', $filters['article_id']);
        }

        // Filter by is_helpful
        if (isset($filters['is_helpful'])) {
            $query->where('is_helpful', (bool) $filters['is_helpful']);
        }

        // Filter by feedback category
        if (!empty($filters['category'])) {
            $category = KBFeedbackCategory::tryFrom($filters['category']);
            if ($category !== null) {
                $query->withCategory($category);
            }
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage);
    }

    /**
     * List all feedback for admin.
     *
     * @param array<string, mixed> $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = KBArticleFeedback::with(['article', 'reviewedBy'])
            ->orderByDesc('created_at');

        // Filter by status
        if (!empty($filters['status'])) {
            $status = KBFeedbackStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        // Filter by article
        if (!empty($filters['article_id'])) {
            $query->where('article_id', $filters['article_id']);
        }

        // Filter by is_helpful
        if (isset($filters['is_helpful'])) {
            $query->where('is_helpful', (bool) $filters['is_helpful']);
        }

        // Filter by feedback category
        if (!empty($filters['category'])) {
            $category = KBFeedbackCategory::tryFrom($filters['category']);
            if ($category !== null) {
                $query->withCategory($category);
            }
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage);
    }

    /**
     * Resolve feedback (mark as reviewed).
     */
    public function resolve(KBArticleFeedback $feedback, SuperAdminUser $admin, ?string $notes = null): KBArticleFeedback
    {
        $feedback->markAsReviewed($admin->id, $notes);

        $this->log('Feedback resolved', [
            'feedback_id' => $feedback->id,
            'admin_id' => $admin->id,
        ]);

        return $feedback->fresh(['article', 'reviewedBy']);
    }

    /**
     * Mark feedback as actioned.
     */
    public function action(KBArticleFeedback $feedback, SuperAdminUser $admin, ?string $notes = null): KBArticleFeedback
    {
        $feedback->markAsActioned($admin->id, $notes);

        $this->log('Feedback actioned', [
            'feedback_id' => $feedback->id,
            'admin_id' => $admin->id,
        ]);

        return $feedback->fresh(['article', 'reviewedBy']);
    }

    /**
     * Dismiss feedback.
     */
    public function dismiss(KBArticleFeedback $feedback, SuperAdminUser $admin, ?string $notes = null): KBArticleFeedback
    {
        $feedback->dismiss($admin->id, $notes);

        $this->log('Feedback dismissed', [
            'feedback_id' => $feedback->id,
            'admin_id' => $admin->id,
        ]);

        return $feedback->fresh(['article', 'reviewedBy']);
    }
}
