<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for inbox_items table.
 *
 * Creates the inbox_items table which stores comments and mentions
 * from connected social accounts. Each item represents a piece of
 * engagement that needs to be managed.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inbox_items', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Foreign key to workspace
            $table->uuid('workspace_id');

            // Foreign key to social account
            $table->uuid('social_account_id');

            // Foreign key to post target (if comment on our post)
            $table->uuid('post_target_id')->nullable();

            // Item type (InboxItemType enum: comment, mention)
            $table->string('item_type', 20);

            // Status (InboxItemStatus enum: unread, read, resolved, archived)
            $table->string('status', 20)->default('unread');

            // Platform's comment/mention ID
            $table->string('platform_item_id', 255);

            // Platform's post ID (if applicable)
            $table->string('platform_post_id', 255)->nullable();

            // Author information
            $table->string('author_name');
            $table->string('author_username', 100)->nullable();
            $table->string('author_profile_url', 500)->nullable();
            $table->string('author_avatar_url', 500)->nullable();

            // Content
            $table->text('content_text');

            // When the item was created on the platform
            $table->timestamp('platform_created_at');

            // Assignment tracking
            $table->uuid('assigned_to_user_id')->nullable();
            $table->timestamp('assigned_at')->nullable();

            // Resolution tracking
            $table->timestamp('resolved_at')->nullable();
            $table->uuid('resolved_by_user_id')->nullable();

            // Additional metadata (JSON)
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamps();

            // Unique constraint: one item per social account per platform item
            $table->unique(['social_account_id', 'platform_item_id'], 'inbox_items_unique');

            // Indexes for common queries
            $table->index('status');
            $table->index('item_type');
            $table->index('platform_created_at');
            $table->index(['workspace_id', 'status']);

            // Foreign key to workspaces
            $table->foreign('workspace_id')
                ->references('id')
                ->on('workspaces')
                ->cascadeOnDelete();

            // Foreign key to social_accounts
            $table->foreign('social_account_id')
                ->references('id')
                ->on('social_accounts')
                ->cascadeOnDelete();

            // Foreign key to post_targets
            $table->foreign('post_target_id')
                ->references('id')
                ->on('post_targets')
                ->nullOnDelete();

            // Foreign key for assigned user
            $table->foreign('assigned_to_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Foreign key for resolved by user
            $table->foreign('resolved_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbox_items');
    }
};
