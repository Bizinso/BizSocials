<?php

declare(strict_types=1);

namespace App\Data\Feedback;

use App\Models\Feedback\FeedbackComment;
use Spatie\LaravelData\Data;

final class FeedbackCommentData extends Data
{
    public function __construct(
        public string $id,
        public string $feedback_id,
        public string $content,
        public string $author_name,
        public bool $is_official_response,
        public string $created_at,
    ) {}

    /**
     * Create FeedbackCommentData from a FeedbackComment model.
     */
    public static function fromModel(FeedbackComment $comment): self
    {
        return new self(
            id: $comment->id,
            feedback_id: $comment->feedback_id,
            content: $comment->content,
            author_name: $comment->getAuthorName(),
            is_official_response: $comment->is_official_response,
            created_at: $comment->created_at->toIso8601String(),
        );
    }
}
