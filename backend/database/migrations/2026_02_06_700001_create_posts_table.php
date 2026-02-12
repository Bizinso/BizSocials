<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for posts table.
 *
 * Creates the posts table which stores social media post content.
 * Posts are the core content unit with support for multi-platform publishing,
 * scheduling, and approval workflows.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Foreign key to workspace
            $table->uuid('workspace_id');

            // User who created the post
            $table->uuid('created_by_user_id');

            // Post content
            $table->text('content_text')->nullable();

            // Platform-specific content variations (JSON)
            $table->json('content_variations')->nullable();

            // Status (PostStatus enum)
            $table->string('status', 20)->default('draft');

            // Post type (PostType enum)
            $table->string('post_type', 20)->default('standard');

            // Scheduling
            $table->timestamp('scheduled_at')->nullable();
            $table->string('scheduled_timezone', 50)->nullable();

            // Publishing timestamps
            $table->timestamp('published_at')->nullable();
            $table->timestamp('submitted_at')->nullable();

            // Hashtags array (JSON)
            $table->json('hashtags')->nullable();

            // Mentions array (JSON)
            $table->json('mentions')->nullable();

            // Link attachment
            $table->string('link_url', 500)->nullable();
            $table->json('link_preview')->nullable();

            // First comment to post after publishing
            $table->text('first_comment')->nullable();

            // Rejection reason (when status is rejected)
            $table->text('rejection_reason')->nullable();

            // Additional metadata (JSON)
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamps();

            // Soft deletes
            $table->softDeletes();

            // Indexes for common queries
            $table->index('status');
            $table->index('scheduled_at');
            $table->index('published_at');
            $table->index('created_at');

            // Compound indexes for workspace filtering
            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'scheduled_at']);

            // Foreign key to workspaces
            $table->foreign('workspace_id')
                ->references('id')
                ->on('workspaces')
                ->cascadeOnDelete();

            // Foreign key to users
            $table->foreign('created_by_user_id')
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
        Schema::dropIfExists('posts');
    }
};
