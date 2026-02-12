<?php

declare(strict_types=1);

namespace App\Data\Audit;

use App\Models\Audit\DataDeletionRequest;
use Spatie\LaravelData\Data;

final class DataDeletionRequestData extends Data
{
    public function __construct(
        public string $id,
        public string $user_id,
        public string $status,
        public ?array $data_categories,
        public ?string $reason,
        public bool $requires_approval,
        public ?string $approved_by,
        public ?string $approved_at,
        public ?string $scheduled_for,
        public ?string $completed_at,
        public ?array $deletion_summary,
        public ?string $failure_reason,
        public string $created_at,
    ) {}

    /**
     * Create DataDeletionRequestData from a DataDeletionRequest model.
     */
    public static function fromModel(DataDeletionRequest $request): self
    {
        return new self(
            id: $request->id,
            user_id: $request->user_id ?? $request->requested_by,
            status: $request->status->value,
            data_categories: $request->data_categories,
            reason: $request->reason,
            requires_approval: $request->requires_approval,
            approved_by: $request->approved_by,
            approved_at: $request->approved_at?->toIso8601String(),
            scheduled_for: $request->scheduled_for?->toIso8601String(),
            completed_at: $request->completed_at?->toIso8601String(),
            deletion_summary: $request->deletion_summary,
            failure_reason: $request->failure_reason,
            created_at: $request->created_at->toIso8601String(),
        );
    }
}
