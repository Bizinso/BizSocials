<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for subscriptions table.
 *
 * Creates the subscription tracking table for tenant subscriptions.
 * Subscriptions link tenants to plans with billing cycle and Razorpay integration.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Foreign key to tenant
            $table->uuid('tenant_id');

            // Foreign key to plan
            $table->uuid('plan_id');

            // Subscription status (SubscriptionStatus enum)
            $table->string('status', 20)->default('created');

            // Billing cycle (BillingCycle enum)
            $table->string('billing_cycle', 20);

            // Currency (Currency enum)
            $table->string('currency', 3);

            // Amount per billing cycle
            $table->decimal('amount', 10, 2);

            // Razorpay subscription ID
            $table->string('razorpay_subscription_id', 100)->nullable()->unique();

            // Razorpay customer ID
            $table->string('razorpay_customer_id', 100)->nullable();

            // Current billing period
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();

            // Trial period
            $table->timestamp('trial_start')->nullable();
            $table->timestamp('trial_end')->nullable();

            // Cancellation tracking
            $table->timestamp('cancelled_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);

            // End date (when subscription actually ended)
            $table->timestamp('ended_at')->nullable();

            // Additional metadata (JSON)
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('current_period_end');
            $table->index('razorpay_subscription_id');

            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign('plan_id')
                ->references('id')
                ->on('plan_definitions')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
