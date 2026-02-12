<?php

declare(strict_types=1);

namespace App\Data\Feedback;

use App\Models\Feedback\Feedback;
use App\Models\User;
use Spatie\LaravelData\Data;

final class FeedbackData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public string $type,
        public string $type_label,
        public ?string $category,
        public ?string $category_label,
        public string $status,
        public string $status_label,
        public int $vote_count,
        public int $comment_count,
        public ?string $submitter_name,
        public ?string $submitter_email,
        public bool $is_anonymous,
        public ?int $user_vote,
        public ?string $roadmap_item_id,
        public string $created_at,
        public string $updated_at,
    ) {}

    /**
     * Create FeedbackData from a Feedback model.
     */
    public static function fromModel(Feedback $feedback, ?User $user = null): self
    {
        $feedback->loadMissing(['comments']);

        // Get user's vote if authenticated
        $userVote = null;
        if ($user !== null) {
            $vote = $feedback->votes()->where('user_id', $user->id)->first();
            if ($vote !== null) {
                $userVote = $vote->vote_type->value();
            }
        }

        return new self(
            id: $feedback->id,
            title: $feedback->title,
            description: $feedback->description,
            type: $feedback->feedback_type->value,
            type_label: $feedback->feedback_type->label(),
            category: $feedback->category?->value,
            category_label: $feedback->category?->label(),
            status: $feedback->status->value,
            status_label: $feedback->status->label(),
            vote_count: $feedback->vote_count,
            comment_count: $feedback->comments->count(),
            submitter_name: $feedback->submitter_name,
            submitter_email: $feedback->user_id === null ? $feedback->submitter_email : null,
            is_anonymous: $feedback->user_id === null,
            user_vote: $userVote,
            roadmap_item_id: $feedback->roadmap_item_id,
            created_at: $feedback->created_at->toIso8601String(),
            updated_at: $feedback->updated_at->toIso8601String(),
        );
    }
}
