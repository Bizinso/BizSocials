<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

/**
 * TenantOnboarding Model
 *
 * Tracks the onboarding progress for a tenant through
 * the initial setup steps after signing up.
 *
 * @property string $id UUID primary key
 * @property string $tenant_id Parent tenant UUID
 * @property string $current_step Current step in onboarding
 * @property array|null $steps_completed Array of completed step keys
 * @property \Carbon\Carbon $started_at When onboarding started
 * @property \Carbon\Carbon|null $completed_at When onboarding completed
 * @property \Carbon\Carbon|null $abandoned_at When onboarding was abandoned
 * @property array|null $metadata Step-specific metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Tenant $tenant
 */
final class TenantOnboarding extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tenant_onboarding';

    /**
     * All onboarding steps in order.
     */
    public const STEPS = [
        'account_created',
        'email_verified',
        'organization_completed',
        'business_type_selected',
        'profile_completed',
        'plan_selected',
        'payment_completed',
        'first_workspace_created',
        'first_social_account_connected',
        'first_post_created',
        'tour_completed',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'current_step',
        'steps_completed',
        'started_at',
        'completed_at',
        'abandoned_at',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'steps_completed' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'abandoned_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the parent tenant.
     *
     * @return BelongsTo<Tenant, TenantOnboarding>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Complete a step in the onboarding process.
     */
    public function completeStep(string $step): void
    {
        if (! in_array($step, self::STEPS, true)) {
            return;
        }

        $completed = $this->steps_completed ?? [];

        if (! in_array($step, $completed, true)) {
            $completed[] = $step;
            $this->steps_completed = $completed;
        }

        // Update current step to the next uncompleted step
        $nextStep = $this->getNextStep();
        if ($nextStep !== null) {
            $this->current_step = $nextStep;
        }

        $this->save();
    }

    /**
     * Check if a specific step has been completed.
     */
    public function isStepCompleted(string $step): bool
    {
        return in_array($step, $this->steps_completed ?? [], true);
    }

    /**
     * Get the count of completed steps.
     */
    public function getCompletedStepsCount(): int
    {
        return count($this->steps_completed ?? []);
    }

    /**
     * Get the progress percentage.
     */
    public function getProgressPercentage(): float
    {
        $total = count(self::STEPS);

        if ($total === 0) {
            return 0.0;
        }

        return round(($this->getCompletedStepsCount() / $total) * 100, 1);
    }

    /**
     * Check if onboarding is complete.
     */
    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Check if onboarding was abandoned.
     */
    public function isAbandoned(): bool
    {
        return $this->abandoned_at !== null;
    }

    /**
     * Mark onboarding as complete.
     */
    public function markComplete(): void
    {
        $this->completed_at = now();
        $this->save();
    }

    /**
     * Mark onboarding as abandoned.
     */
    public function markAbandoned(): void
    {
        $this->abandoned_at = now();
        $this->save();
    }

    /**
     * Get the next uncompleted step.
     */
    public function getNextStep(): ?string
    {
        $completed = $this->steps_completed ?? [];

        foreach (self::STEPS as $step) {
            if (! in_array($step, $completed, true)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * Get a metadata value using dot notation.
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->metadata ?? [], $key, $default);
    }

    /**
     * Set a metadata value using dot notation.
     */
    public function setMetadata(string $key, mixed $value): void
    {
        $metadata = $this->metadata ?? [];
        Arr::set($metadata, $key, $value);
        $this->metadata = $metadata;
        $this->save();
    }
}
