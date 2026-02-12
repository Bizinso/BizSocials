<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for plan_limits table.
 *
 * Creates the table for storing limits/quotas for each plan.
 * Each limit is identified by a key (e.g., 'max_workspaces')
 * and has a numeric value (-1 indicates unlimited).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plan_limits', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Reference to plan_definitions
            $table->uuid('plan_id');

            // Limit identifier (e.g., 'max_workspaces', 'max_users')
            $table->string('limit_key', 100);

            // Limit value (-1 = unlimited)
            $table->integer('limit_value');

            // Timestamps
            $table->timestamps();

            // Foreign key to plan_definitions
            $table->foreign('plan_id')
                ->references('id')
                ->on('plan_definitions')
                ->onDelete('cascade');

            // Ensure each plan has only one entry per limit key
            $table->unique(['plan_id', 'limit_key']);

            // Index for looking up limits by key
            $table->index('limit_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_limits');
    }
};
