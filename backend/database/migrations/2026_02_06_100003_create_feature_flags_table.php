<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for feature_flags table.
 *
 * Creates the table for feature toggles used in gradual rollout
 * of new features. Supports percentage-based rollout, plan-based
 * access, and tenant-specific overrides.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feature_flags', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Unique feature key (e.g., 'ai.caption_generation')
            $table->string('key', 100)->unique();

            // Human-readable name
            $table->string('name', 255);

            // Description of the feature
            $table->text('description')->nullable();

            // Global enable/disable switch
            $table->boolean('is_enabled')->default(false);

            // Percentage of users for gradual rollout (0-100)
            $table->unsignedTinyInteger('rollout_percentage')->default(0);

            // Array of plan codes that have access (null = all plans)
            $table->json('allowed_plans')->nullable();

            // Array of tenant IDs that have explicit access (null = use normal rules)
            $table->json('allowed_tenants')->nullable();

            // Additional metadata
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamps();

            // Index for quick lookup of enabled features
            $table->index('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
    }
};
