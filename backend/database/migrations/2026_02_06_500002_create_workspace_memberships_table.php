<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for workspace_memberships table.
 *
 * Creates the workspace_memberships table which is a join entity linking
 * users to workspaces with their assigned role. This enables team collaboration
 * within workspaces with role-based access control.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workspace_memberships', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Foreign key to workspace
            $table->uuid('workspace_id');

            // Foreign key to user
            $table->uuid('user_id');

            // Role within workspace (WorkspaceRole enum)
            $table->string('role', 20);

            // When the user joined the workspace
            $table->timestamp('joined_at');

            // Timestamps
            $table->timestamps();

            // Unique constraint: one membership per user per workspace
            $table->unique(['workspace_id', 'user_id']);

            // Indexes
            $table->index('role');
            $table->index('user_id');

            // Foreign key to workspaces
            $table->foreign('workspace_id')
                ->references('id')
                ->on('workspaces')
                ->cascadeOnDelete();

            // Foreign key to users
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_memberships');
    }
};
