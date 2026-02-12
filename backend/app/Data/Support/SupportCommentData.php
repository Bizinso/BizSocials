<?php

declare(strict_types=1);

namespace App\Data\Support;

use App\Models\Support\SupportTicketComment;
use Spatie\LaravelData\Data;

final class SupportCommentData extends Data
{
    public function __construct(
        public string $id,
        public string $ticket_id,
        public string $comment_type,
        public string $content,
        public bool $is_internal,
        public string $author_type,
        public ?string $author_id,
        public string $author_name,
        public ?string $author_email,
        public string $created_at,
    ) {}

    /**
     * Create SupportCommentData from a SupportTicketComment model.
     */
    public static function fromModel(SupportTicketComment $comment): self
    {
        $comment->loadMissing(['user', 'admin']);

        // Determine author type
        $authorType = 'system';
        $authorId = null;

        if ($comment->admin_id !== null) {
            $authorType = 'admin';
            $authorId = $comment->admin_id;
        } elseif ($comment->user_id !== null) {
            $authorType = 'user';
            $authorId = $comment->user_id;
        }

        return new self(
            id: $comment->id,
            ticket_id: $comment->ticket_id,
            comment_type: $comment->comment_type->value,
            content: $comment->content,
            is_internal: $comment->is_internal,
            author_type: $authorType,
            author_id: $authorId,
            author_name: $comment->getAuthorName(),
            author_email: $comment->getAuthorEmail(),
            created_at: $comment->created_at->toIso8601String(),
        );
    }
}
