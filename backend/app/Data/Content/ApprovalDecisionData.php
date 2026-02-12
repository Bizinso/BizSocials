<?php

declare(strict_types=1);

namespace App\Data\Content;

use App\Models\Content\ApprovalDecision;
use Spatie\LaravelData\Data;

final class ApprovalDecisionData extends Data
{
    public function __construct(
        public string $id,
        public string $post_id,
        public string $decided_by_user_id,
        public ?string $decided_by_name,
        public string $decision,
        public ?string $reason,
        public ?string $comment,
        public bool $is_active,
        public string $decided_at,
    ) {}

    /**
     * Create ApprovalDecisionData from an ApprovalDecision model.
     */
    public static function fromModel(ApprovalDecision $decision): self
    {
        return new self(
            id: $decision->id,
            post_id: $decision->post_id,
            decided_by_user_id: $decision->decided_by_user_id,
            decided_by_name: $decision->decidedBy?->name,
            decision: $decision->decision->value,
            reason: $decision->post?->rejection_reason,
            comment: $decision->comment,
            is_active: $decision->is_active,
            decided_at: $decision->decided_at->toIso8601String(),
        );
    }
}
