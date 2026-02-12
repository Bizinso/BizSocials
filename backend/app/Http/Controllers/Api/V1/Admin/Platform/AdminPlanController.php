<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Platform;

use App\Data\Admin\CreatePlanData;
use App\Data\Admin\PlanData;
use App\Data\Admin\UpdatePlanData;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\Platform\PlanDefinition;
use App\Services\Admin\PlanService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class AdminPlanController extends Controller
{
    public function __construct(
        private readonly PlanService $planService,
    ) {}

    /**
     * List all plans.
     * GET /admin/plans
     */
    public function index(): JsonResponse
    {
        $plans = $this->planService->list();

        $transformedItems = $plans->map(
            fn (PlanDefinition $plan) => PlanData::fromModel($plan)->toArray()
        );

        return $this->success($transformedItems, 'Plans retrieved successfully');
    }

    /**
     * Get a specific plan.
     * GET /admin/plans/{plan}
     */
    public function show(string $plan): JsonResponse
    {
        try {
            $planModel = $this->planService->get($plan);

            return $this->success(
                PlanData::fromModel($planModel)->toArray(),
                'Plan retrieved successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Plan not found');
        }
    }

    /**
     * Create a new plan.
     * POST /admin/plans
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = CreatePlanData::from($request->all());
            $plan = $this->planService->create($data);

            return $this->created(
                PlanData::fromModel($plan)->toArray(),
                'Plan created successfully'
            );
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        }
    }

    /**
     * Update a plan.
     * PUT /admin/plans/{plan}
     */
    public function update(Request $request, string $plan): JsonResponse
    {
        try {
            $planModel = $this->planService->get($plan);

            $data = UpdatePlanData::from($request->all());
            $updatedPlan = $this->planService->update($planModel, $data);

            return $this->success(
                PlanData::fromModel($updatedPlan)->toArray(),
                'Plan updated successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Plan not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        }
    }

    /**
     * Delete a plan.
     * DELETE /admin/plans/{plan}
     */
    public function destroy(string $plan): JsonResponse
    {
        try {
            $planModel = $this->planService->get($plan);
            $this->planService->delete($planModel);

            return $this->success(null, 'Plan deleted successfully');
        } catch (ModelNotFoundException) {
            return $this->notFound('Plan not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        }
    }

    /**
     * Update plan limits.
     * PUT /admin/plans/{plan}/limits
     */
    public function updateLimits(Request $request, string $plan): JsonResponse
    {
        try {
            $planModel = $this->planService->get($plan);

            $validated = $request->validate([
                'limits' => 'required|array',
                'limits.*' => 'required|integer|min:-1',
            ]);

            $updatedPlan = $this->planService->updateLimits($planModel, $validated['limits']);

            return $this->success(
                PlanData::fromModel($updatedPlan)->toArray(),
                'Plan limits updated successfully'
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('Plan not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        }
    }
}
