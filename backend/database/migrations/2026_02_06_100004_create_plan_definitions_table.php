<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for plan_definitions table.
 *
 * Creates the table for subscription plan definitions.
 * Stores pricing, features, and metadata for each plan tier
 * (FREE, STARTER, PROFESSIONAL, BUSINESS, ENTERPRISE).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plan_definitions', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Unique plan code (FREE, STARTER, PROFESSIONAL, BUSINESS, ENTERPRISE)
            $table->string('code', 50)->unique();

            // Human-readable plan name
            $table->string('name', 100);

            // Plan description
            $table->text('description')->nullable();

            // Pricing in INR
            $table->decimal('price_inr_monthly', 10, 2);
            $table->decimal('price_inr_yearly', 10, 2);

            // Pricing in USD
            $table->decimal('price_usd_monthly', 10, 2);
            $table->decimal('price_usd_yearly', 10, 2);

            // Trial period in days (0 = no trial)
            $table->unsignedSmallInteger('trial_days')->default(0);

            // Plan visibility flags
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true);

            // Display order
            $table->unsignedSmallInteger('sort_order')->default(0);

            // Feature list for display (array of strings)
            $table->json('features');

            // Additional metadata
            $table->json('metadata')->nullable();

            // Payment gateway plan IDs
            $table->string('razorpay_plan_id_inr', 100)->nullable();
            $table->string('razorpay_plan_id_usd', 100)->nullable();

            // Timestamps
            $table->timestamps();

            // Composite index for listing active public plans
            $table->index(['is_active', 'is_public', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_definitions');
    }
};
