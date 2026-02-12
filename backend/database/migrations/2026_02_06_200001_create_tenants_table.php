<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for tenants table.
 *
 * Creates the core tenant entity table for multi-tenant architecture.
 * Tenants represent customer organizations/individuals that subscribe
 * to the platform and own workspaces, users, and content.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Tenant name and unique slug
            $table->string('name');
            $table->string('slug')->unique();

            // Tenant type (TenantType enum)
            $table->string('type', 20);

            // Tenant status (TenantStatus enum)
            $table->string('status', 20)->default('pending');

            // Owner user ID (will be set after first user is created)
            $table->uuid('owner_user_id')->nullable();

            // Foreign key to plan_definitions
            $table->uuid('plan_id')->nullable();

            // Trial period end date
            $table->timestamp('trial_ends_at')->nullable();

            // Tenant-wide configuration settings (JSON)
            $table->json('settings')->nullable();

            // Timestamp when onboarding was completed
            $table->timestamp('onboarding_completed_at')->nullable();

            // Additional metadata (JSON)
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamps();

            // Soft deletes
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('type');
            $table->index('plan_id');
            $table->index('created_at');

            // Foreign keys
            $table->foreign('plan_id')
                ->references('id')
                ->on('plan_definitions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
