<?php

declare(strict_types=1);

namespace App\Data\KnowledgeBase;

use App\Models\KnowledgeBase\KBArticleFeedback;
use Spatie\LaravelData\Data;

final class KBFeedbackData extends Data
{
    public function __construct(
        public string $id,
        public string $article_id,
        public string $article_title,
        public bool $is_helpful,
        public ?string $feedback_text,
        public ?string $feedback_category,
        public string $status,
        public ?string $reviewed_by,
        public ?string $reviewed_at,
        public ?string $admin_notes,
        public string $created_at,
    ) {}

    /**
     * Create KBFeedbackData from a KBArticleFeedback model.
     */
    public static function fromModel(KBArticleFeedback $feedback): self
    {
        $feedback->loadMissing(['article', 'reviewedBy']);

        return new self(
            id: $feedback->id,
            article_id: $feedback->article_id,
            article_title: $feedback->article?->title ?? '',
            is_helpful: $feedback->is_helpful,
            feedback_text: $feedback->feedback_text,
            feedback_category: $feedback->feedback_category?->value,
            status: $feedback->status->value,
            reviewed_by: $feedback->reviewedBy?->name,
            reviewed_at: $feedback->reviewed_at?->toIso8601String(),
            admin_notes: $feedback->admin_notes,
            created_at: $feedback->created_at->toIso8601String(),
        );
    }
}
