<?php

declare(strict_types=1);

namespace Database\Factories\Platform;

use App\Enums\Platform\PlanCode;
use App\Models\Platform\PlanDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for PlanDefinition model.
 *
 * @extends Factory<PlanDefinition>
 */
final class PlanDefinitionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PlanDefinition>
     */
    protected $model = PlanDefinition::class;

    /**
     * Counter for unique plan codes.
     */
    private static int $codeIndex = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Cycle through plan codes for uniqueness
        $codes = PlanCode::cases();
        $code = $codes[self::$codeIndex % count($codes)];
        self::$codeIndex++;

        $priceInrMonthly = fake()->randomFloat(2, 0, 5000);
        $priceUsdMonthly = $priceInrMonthly / 80; // Approximate conversion

        return [
            'code' => $code,
            'name' => $code->label(),
            'description' => $code->description(),
            'price_inr_monthly' => $priceInrMonthly,
            'price_inr_yearly' => $priceInrMonthly * 12 * 0.8, // 20% discount
            'price_usd_monthly' => round($priceUsdMonthly, 2),
            'price_usd_yearly' => round($priceUsdMonthly * 12 * 0.8, 2),
            'trial_days' => $code === PlanCode::FREE ? 0 : 14,
            'is_active' => true,
            'is_public' => true,
            'sort_order' => $code->tierLevel() + 1,
            'features' => fake()->randomElements([
                '1 User',
                '2 Users',
                '5 Users',
                '10 Users',
                'Unlimited Users',
                '1 Workspace',
                '5 Workspaces',
                '10 Workspaces',
                'Analytics',
                'Advanced Analytics',
                'Priority Support',
                'API Access',
            ], fake()->numberBetween(3, 6)),
            'metadata' => null,
            'razorpay_plan_id_inr' => null,
            'razorpay_plan_id_usd' => null,
        ];
    }

    /**
     * Create a specific plan by code.
     */
    public function code(PlanCode $code): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => $code,
            'name' => $code->label(),
            'sort_order' => $code->tierLevel() + 1,
        ]);
    }

    /**
     * Create a free plan.
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => PlanCode::FREE,
            'name' => 'Free',
            'price_inr_monthly' => 0,
            'price_inr_yearly' => 0,
            'price_usd_monthly' => 0,
            'price_usd_yearly' => 0,
            'trial_days' => 0,
            'sort_order' => 1,
        ]);
    }

    /**
     * Create a starter plan.
     */
    public function starter(): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => PlanCode::STARTER,
            'name' => 'Starter',
            'price_inr_monthly' => 999,
            'price_inr_yearly' => 9590,
            'price_usd_monthly' => 15,
            'price_usd_yearly' => 144,
            'trial_days' => 14,
            'sort_order' => 2,
        ]);
    }

    /**
     * Create a professional plan.
     */
    public function professional(): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => PlanCode::PROFESSIONAL,
            'name' => 'Professional',
            'price_inr_monthly' => 2499,
            'price_inr_yearly' => 23990,
            'price_usd_monthly' => 35,
            'price_usd_yearly' => 336,
            'trial_days' => 14,
            'sort_order' => 3,
        ]);
    }

    /**
     * Create a business plan.
     */
    public function business(): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => PlanCode::BUSINESS,
            'name' => 'Business',
            'price_inr_monthly' => 4999,
            'price_inr_yearly' => 47990,
            'price_usd_monthly' => 70,
            'price_usd_yearly' => 672,
            'trial_days' => 14,
            'sort_order' => 4,
        ]);
    }

    /**
     * Create an enterprise plan.
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => PlanCode::ENTERPRISE,
            'name' => 'Enterprise',
            'price_inr_monthly' => 9999,
            'price_inr_yearly' => 95990,
            'price_usd_monthly' => 150,
            'price_usd_yearly' => 1440,
            'trial_days' => 30,
            'sort_order' => 5,
        ]);
    }

    /**
     * Make the plan inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Make the plan private (not publicly visible).
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_public' => false,
        ]);
    }

    /**
     * Set specific features.
     *
     * @param  array<string>  $features
     */
    public function withFeatures(array $features): static
    {
        return $this->state(fn (array $attributes): array => [
            'features' => $features,
        ]);
    }
}
