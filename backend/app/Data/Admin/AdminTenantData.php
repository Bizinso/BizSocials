<?php

declare(strict_types=1);

namespace App\Data\Admin;

use App\Models\Tenant\Tenant;
use Spatie\LaravelData\Data;

final class AdminTenantData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public string $type,
        public string $type_label,
        public string $status,
        public string $status_label,
        public ?string $plan_id,
        public ?string $plan_name,
        public int $user_count,
        public int $workspace_count,
        public ?string $trial_ends_at,
        public ?string $suspended_at,
        public ?string $suspension_reason,
        public bool $onboarding_completed,
        public string $created_at,
        public string $updated_at,
    ) {}

    /**
     * Create AdminTenantData from a Tenant model.
     */
    public static function fromModel(Tenant $tenant): self
    {
        $tenant->loadMissing(['plan']);

        // Get counts
        $userCount = $tenant->users()->count();
        $workspaceCount = $tenant->workspaces()->count();

        // Get suspension info from metadata
        $metadata = $tenant->metadata ?? [];
        $suspendedAt = $metadata['suspended_at'] ?? null;
        $suspensionReason = $metadata['suspension_reason'] ?? null;

        return new self(
            id: $tenant->id,
            name: $tenant->name,
            slug: $tenant->slug,
            type: $tenant->type->value,
            type_label: $tenant->type->label(),
            status: $tenant->status->value,
            status_label: $tenant->status->label(),
            plan_id: $tenant->plan_id,
            plan_name: $tenant->plan?->name,
            user_count: $userCount,
            workspace_count: $workspaceCount,
            trial_ends_at: $tenant->trial_ends_at?->toIso8601String(),
            suspended_at: $suspendedAt,
            suspension_reason: $suspensionReason,
            onboarding_completed: $tenant->hasCompletedOnboarding(),
            created_at: $tenant->created_at->toIso8601String(),
            updated_at: $tenant->updated_at->toIso8601String(),
        );
    }
}
