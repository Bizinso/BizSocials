<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Data\Admin\CreateFeatureFlagData;
use App\Data\Admin\UpdateFeatureFlagData;
use App\Models\Platform\FeatureFlag;
use App\Models\Tenant\Tenant;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final class FeatureFlagService extends BaseService
{
    /**
     * List all feature flags.
     *
     * @return Collection<int, FeatureFlag>
     */
    public function list(): Collection
    {
        return FeatureFlag::orderBy('key')->get();
    }

    /**
     * Get a feature flag by ID.
     *
     * @throws ModelNotFoundException
     */
    public function get(string $id): FeatureFlag
    {
        $flag = FeatureFlag::find($id);

        if ($flag === null) {
            throw new ModelNotFoundException('Feature flag not found.');
        }

        return $flag;
    }

    /**
     * Get a feature flag by key.
     *
     * @throws ModelNotFoundException
     */
    public function getByKey(string $key): FeatureFlag
    {
        $flag = FeatureFlag::where('key', $key)->first();

        if ($flag === null) {
            throw new ModelNotFoundException('Feature flag not found.');
        }

        return $flag;
    }

    /**
     * Create a new feature flag.
     *
     * @throws ValidationException
     */
    public function create(CreateFeatureFlagData $data): FeatureFlag
    {
        return $this->transaction(function () use ($data) {
            // Check if key already exists
            $existingFlag = FeatureFlag::where('key', $data->key)->first();
            if ($existingFlag !== null) {
                throw ValidationException::withMessages([
                    'key' => ['A feature flag with this key already exists.'],
                ]);
            }

            $flag = FeatureFlag::create([
                'key' => $data->key,
                'name' => $data->name,
                'description' => $data->description,
                'is_enabled' => $data->is_enabled,
                'rollout_percentage' => $data->rollout_percentage,
                'allowed_plans' => $data->allowed_plans,
                'allowed_tenants' => $data->allowed_tenants,
                'metadata' => $data->metadata,
            ]);

            $this->log('Feature flag created', [
                'flag_id' => $flag->id,
                'key' => $data->key,
            ]);

            return $flag;
        });
    }

    /**
     * Update a feature flag.
     */
    public function update(FeatureFlag $flag, UpdateFeatureFlagData $data): FeatureFlag
    {
        return $this->transaction(function () use ($flag, $data) {
            $updateData = [];

            if ($data->name !== null) {
                $updateData['name'] = $data->name;
            }

            if ($data->description !== null) {
                $updateData['description'] = $data->description;
            }

            if ($data->is_enabled !== null) {
                $updateData['is_enabled'] = $data->is_enabled;
            }

            if ($data->rollout_percentage !== null) {
                $updateData['rollout_percentage'] = $data->rollout_percentage;
            }

            if ($data->allowed_plans !== null) {
                $updateData['allowed_plans'] = $data->allowed_plans;
            }

            if ($data->allowed_tenants !== null) {
                $updateData['allowed_tenants'] = $data->allowed_tenants;
            }

            if ($data->metadata !== null) {
                $currentMetadata = $flag->metadata ?? [];
                $updateData['metadata'] = array_merge($currentMetadata, $data->metadata);
            }

            if (!empty($updateData)) {
                $flag->update($updateData);
            }

            $this->log('Feature flag updated', [
                'flag_id' => $flag->id,
                'key' => $flag->key,
                'updates' => array_keys($updateData),
            ]);

            return $flag->fresh();
        });
    }

    /**
     * Toggle a feature flag.
     */
    public function toggle(FeatureFlag $flag): FeatureFlag
    {
        return $this->transaction(function () use ($flag) {
            $flag->is_enabled = !$flag->is_enabled;
            $flag->save();

            $this->log('Feature flag toggled', [
                'flag_id' => $flag->id,
                'key' => $flag->key,
                'is_enabled' => $flag->is_enabled,
            ]);

            return $flag;
        });
    }

    /**
     * Delete a feature flag.
     */
    public function delete(FeatureFlag $flag): void
    {
        $this->transaction(function () use ($flag) {
            $flagId = $flag->id;
            $flagKey = $flag->key;

            $flag->delete();

            $this->log('Feature flag deleted', [
                'flag_id' => $flagId,
                'key' => $flagKey,
            ]);
        });
    }

    /**
     * Check if a feature is enabled for a tenant.
     */
    public function isEnabled(string $key, ?Tenant $tenant = null): bool
    {
        $flag = FeatureFlag::where('key', $key)->first();

        if ($flag === null) {
            return false;
        }

        // If no tenant context, just return global enabled status
        if ($tenant === null) {
            return $flag->is_enabled;
        }

        $planCode = $tenant->plan?->code->value ?? 'free';

        return $flag->isEnabledForTenant($tenant->id, $planCode);
    }

    /**
     * Add a tenant to the allowed tenants list.
     */
    public function addAllowedTenant(FeatureFlag $flag, string $tenantId): FeatureFlag
    {
        return $this->transaction(function () use ($flag, $tenantId) {
            $allowedTenants = $flag->allowed_tenants ?? [];

            if (!in_array($tenantId, $allowedTenants, true)) {
                $allowedTenants[] = $tenantId;
                $flag->allowed_tenants = $allowedTenants;
                $flag->save();
            }

            $this->log('Tenant added to feature flag allowed list', [
                'flag_id' => $flag->id,
                'tenant_id' => $tenantId,
            ]);

            return $flag;
        });
    }

    /**
     * Remove a tenant from the allowed tenants list.
     */
    public function removeAllowedTenant(FeatureFlag $flag, string $tenantId): FeatureFlag
    {
        return $this->transaction(function () use ($flag, $tenantId) {
            $allowedTenants = $flag->allowed_tenants ?? [];

            $allowedTenants = array_values(array_filter(
                $allowedTenants,
                fn ($id) => $id !== $tenantId
            ));

            $flag->allowed_tenants = empty($allowedTenants) ? null : $allowedTenants;
            $flag->save();

            $this->log('Tenant removed from feature flag allowed list', [
                'flag_id' => $flag->id,
                'tenant_id' => $tenantId,
            ]);

            return $flag;
        });
    }

    /**
     * Add a plan to the allowed plans list.
     */
    public function addAllowedPlan(FeatureFlag $flag, string $planCode): FeatureFlag
    {
        return $this->transaction(function () use ($flag, $planCode) {
            $allowedPlans = $flag->allowed_plans ?? [];

            if (!in_array($planCode, $allowedPlans, true)) {
                $allowedPlans[] = $planCode;
                $flag->allowed_plans = $allowedPlans;
                $flag->save();
            }

            $this->log('Plan added to feature flag allowed list', [
                'flag_id' => $flag->id,
                'plan_code' => $planCode,
            ]);

            return $flag;
        });
    }

    /**
     * Remove a plan from the allowed plans list.
     */
    public function removeAllowedPlan(FeatureFlag $flag, string $planCode): FeatureFlag
    {
        return $this->transaction(function () use ($flag, $planCode) {
            $allowedPlans = $flag->allowed_plans ?? [];

            $allowedPlans = array_values(array_filter(
                $allowedPlans,
                fn ($code) => $code !== $planCode
            ));

            $flag->allowed_plans = empty($allowedPlans) ? null : $allowedPlans;
            $flag->save();

            $this->log('Plan removed from feature flag allowed list', [
                'flag_id' => $flag->id,
                'plan_code' => $planCode,
            ]);

            return $flag;
        });
    }
}
