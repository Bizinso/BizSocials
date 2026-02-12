<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\PlanDefinition;
use App\Services\Billing\RazorpayService;
use Illuminate\Console\Command;

final class SyncRazorpayPlansCommand extends Command
{
    protected $signature = 'razorpay:sync-plans';

    protected $description = 'Sync PlanDefinition records to Razorpay Plans';

    public function handle(RazorpayService $razorpayService): int
    {
        $plans = PlanDefinition::where('is_active', true)->get();

        if ($plans->isEmpty()) {
            $this->warn('No active plan definitions found.');
            return self::SUCCESS;
        }

        $this->info("Found {$plans->count()} active plans. Syncing to Razorpay...");

        foreach ($plans as $plan) {
            $this->line("Processing: {$plan->name}");

            // Sync monthly INR plan
            if ($plan->price_inr_monthly > 0 && !$plan->razorpay_plan_id_inr) {
                try {
                    $rzpPlan = $razorpayService->createPlan($plan, 'monthly', 'INR');
                    $plan->update(['razorpay_plan_id_inr' => $rzpPlan->id]);
                    $this->info("  Monthly INR plan created: {$rzpPlan->id}");
                } catch (\Throwable $e) {
                    $this->error("  Failed to create monthly INR plan: {$e->getMessage()}");
                }
            }

            // Sync yearly INR plan
            if ($plan->price_inr_yearly > 0) {
                try {
                    $rzpPlan = $razorpayService->createPlan($plan, 'yearly', 'INR');
                    $this->info("  Yearly INR plan created: {$rzpPlan->id}");
                } catch (\Throwable $e) {
                    $this->error("  Failed to create yearly INR plan: {$e->getMessage()}");
                }
            }
        }

        $this->info('Razorpay plan sync completed.');

        return self::SUCCESS;
    }
}
