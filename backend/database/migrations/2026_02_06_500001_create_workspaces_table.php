<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for workspaces table.
 *
 * Creates the workspaces table which represents isolated organizational
 * containers within a tenant. Each workspace is an isolated container
 * for social accounts, posts, and team collaboration.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Foreign key to tenant
            $table->uuid('tenant_id');

            // Workspace name
            $table->string('name', 100);

            // URL-safe unique identifier (unique within tenant)
            $table->string('slug', 100);

            // Optional description
            $table->string('description', 500)->nullable();

            // Workspace status (WorkspaceStatus enum)
            $table->string('status', 20)->default('active');

            // Workspace preferences (JSON)
            $table->json('settings')->nullable();

            // Timestamps
            $table->timestamps();

            // Soft deletes
            $table->softDeletes();

            // Unique constraint: slug unique within tenant
            $table->unique(['tenant_id', 'slug']);

            // Indexes
            $table->index('status');
            $table->index('created_at');

            // Foreign key to tenants
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
        Schema::dropIfExists('workspaces');
    }
};
