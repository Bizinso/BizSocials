<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Data\Admin\CreatePlanData;
use App\Data\Admin\UpdatePlanData;
use App\Enums\Platform\PlanCode;
use App\Models\Billing\Subscription;
use App\Models\Platform\PlanDefinition;
use App\Models\Platform\PlanLimit;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PlanService extends BaseService
{
    /**
     * List all plans.
     *
     * @return Collection<int, PlanDefinition>
     */
    public function list(): Collection
    {
        return PlanDefinition::with(['limits'])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get a plan by ID.
     *
     * @throws ModelNotFoundException
     */
    public function get(string $id): PlanDefinition
    {
        $plan = PlanDefinition::with(['limits'])->find($id);

        if ($plan === null) {
            throw new ModelNotFoundException('Plan not found.');
        }

        return $plan;
    }

    /**
     * Create a new plan.
     *
     * @throws ValidationException
     */
    public function create(CreatePlanData $data): PlanDefinition
    {
        return $this->transaction(function () use ($data) {
            // Normalize code to uppercase for case-insensitive matching
            $normalizedCode = strtoupper($data->code);
            $planCode = PlanCode::tryFrom($normalizedCode);
            if ($planCode === null) {
                throw ValidationException::withMessages([
                    'code' => ['Invalid plan code. Must be one of: ' . implode(', ', array_column(PlanCode::cases(), 'value'))],
                ]);
            }

            $existingPlan = PlanDefinition::where('code', $planCode)->first();
            if ($existingPlan !== null) {
                throw ValidationException::withMessages([
                    'code' => ['A plan with this code already exists.'],
                ]);
            }

            $plan = PlanDefinition::create([
                'code' => $planCode,
                'name' => $data->name,
                'description' => $data->description,
                'is_active' => $data->is_active,
                'is_public' => $data->is_public,
                'price_inr_monthly' => $data->price_inr_monthly,
                'price_inr_yearly' => $data->price_inr_yearly,
                'price_usd_monthly' => $data->price_usd_monthly,
                'price_usd_yearly' => $data->price_usd_yearly,
                'trial_days' => $data->trial_days,
                'sort_order' => $data->sort_order,
                'features' => $data->features ?? [],
                'metadata' => $data->metadata,
                'razorpay_plan_id_inr' => $data->razorpay_plan_id_inr,
                'razorpay_plan_id_usd' => $data->razorpay_plan_id_usd,
            ]);

            // Create limits if provided
            if ($data->limits !== null) {
                foreach ($data->limits as $key => $value) {
                    if (PlanLimit::isValidLimitKey($key)) {
                        PlanLimit::create([
                            'plan_id' => $plan->id,
                            'limit_key' => $key,
                            'limit_value' => $value,
                        ]);
                    }
                }
            }

            $this->log('Plan created', [
                'plan_id' => $plan->id,
                'code' => $data->code,
            ]);

            return $plan->fresh(['limits']);
        });
    }

    /**
     * Update a plan.
     */
    public function update(PlanDefinition $plan, UpdatePlanData $data): PlanDefinition
    {
        return $this->transaction(function () use ($plan, $data) {
            $updateData = [];

            if ($data->name !== null) {
                $updateData['name'] = $data->name;
            }

            if ($data->description !== null) {
                $updateData['description'] = $data->description;
            }

            if ($data->is_active !== null) {
                $updateData['is_active'] = $data->is_active;
            }

            if ($data->is_public !== null) {
                $updateData['is_public'] = $data->is_public;
            }

            if ($data->price_inr_monthly !== null) {
                $updateData['price_inr_monthly'] = $data->price_inr_monthly;
            }

            if ($data->price_inr_yearly !== null) {
                $updateData['price_inr_yearly'] = $data->price_inr_yearly;
            }

            if ($data->price_usd_monthly !== null) {
                $updateData['price_usd_monthly'] = $data->price_usd_monthly;
            }

            if ($data->price_usd_yearly !== null) {
                $updateData['price_usd_yearly'] = $data->price_usd_yearly;
            }

            if ($data->trial_days !== null) {
                $updateData['trial_days'] = $data->trial_days;
            }

            if ($data->sort_order !== null) {
                $updateData['sort_order'] = $data->sort_order;
            }

            if ($data->features !== null) {
                $updateData['features'] = $data->features;
            }

            if ($data->metadata !== null) {
                $currentMetadata = $plan->metadata ?? [];
                $updateData['metadata'] = array_merge($currentMetadata, $data->metadata);
            }

            if ($data->razorpay_plan_id_inr !== null) {
                $updateData['razorpay_plan_id_inr'] = $data->razorpay_plan_id_inr;
            }

            if ($data->razorpay_plan_id_usd !== null) {
                $updateData['razorpay_plan_id_usd'] = $data->razorpay_plan_id_usd;
            }

            if (!empty($updateData)) {
                $plan->update($updateData);
            }

            $this->log('Plan updated', [
                'plan_id' => $plan->id,
                'updates' => array_keys($updateData),
            ]);

            return $plan->fresh(['limits']);
        });
    }

    /**
     * Delete a plan.
     *
     * @throws ValidationException
     */
    public function delete(PlanDefinition $plan): void
    {
        $this->transaction(function () use ($plan) {
            // Check if plan has active subscriptions
            $activeSubscriptions = Subscription::where('plan_id', $plan->id)
                ->where('status', 'active')
                ->count();

            if ($activeSubscriptions > 0) {
                throw ValidationException::withMessages([
                    'plan' => ["Cannot delete plan with {$activeSubscriptions} active subscription(s)."],
                ]);
            }

            // Delete plan limits first
            $plan->limits()->delete();

            // Delete the plan
            $plan->delete();

            $this->log('Plan deleted', [
                'plan_id' => $plan->id,
                'code' => $plan->code->value,
            ]);
        });
    }

    /**
     * Update plan limits.
     *
     * @param array<string, int> $limits
     */
    public function updateLimits(PlanDefinition $plan, array $limits): PlanDefinition
    {
        return $this->transaction(function () use ($plan, $limits) {
            foreach ($limits as $key => $value) {
                if (!PlanLimit::isValidLimitKey($key)) {
                    continue;
                }

                $limit = $plan->limits()->where('limit_key', $key)->first();

                if ($limit !== null) {
                    $limit->update(['limit_value' => $value]);
                } else {
                    PlanLimit::create([
                        'plan_id' => $plan->id,
                        'limit_key' => $key,
                        'limit_value' => $value,
                    ]);
                }
            }

            $this->log('Plan limits updated', [
                'plan_id' => $plan->id,
                'limits' => array_keys($limits),
            ]);

            return $plan->fresh(['limits']);
        });
    }

    /**
     * Get the count of active subscriptions for a plan.
     */
    public function getSubscriptionCount(PlanDefinition $plan): int
    {
        return Subscription::where('plan_id', $plan->id)
            ->where('status', 'active')
            ->count();
    }
}
