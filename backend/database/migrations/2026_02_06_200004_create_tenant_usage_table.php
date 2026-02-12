<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for tenant_usage table.
 *
 * Creates the table for tracking tenant usage metrics per billing period.
 * This is used for billing calculations, limit enforcement, and analytics.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_usage', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Foreign key to tenants
            $table->uuid('tenant_id');

            // Billing period dates
            $table->date('period_start');
            $table->date('period_end');

            // Metric identification and value
            $table->string('metric_key', 100);
            $table->bigInteger('metric_value')->default(0);

            // Timestamps
            $table->timestamps();

            // Unique constraint for one metric per tenant per period
            $table->unique(
                ['tenant_id', 'period_start', 'metric_key'],
                'tenant_usage_unique'
            );

            // Foreign key
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            // Indexes
            $table->index(['tenant_id', 'metric_key']);
            $table->index(['period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_usage');
    }
};
