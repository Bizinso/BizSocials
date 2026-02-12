<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for tenant_onboarding table.
 *
 * Creates the table for tracking tenant onboarding progress.
 * This helps guide new tenants through the setup process and
 * tracks their progress through various onboarding steps.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_onboarding', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // One-to-one relationship with tenants
            $table->uuid('tenant_id')->unique();

            // Current step in the onboarding process
            $table->string('current_step', 50);

            // Array of completed step keys (JSON)
            $table->json('steps_completed')->nullable();

            // Onboarding timestamps
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('abandoned_at')->nullable();

            // Step-specific metadata (JSON)
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamps();

            // Foreign key
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_onboarding');
    }
};
