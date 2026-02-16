<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for inbox_conversations table.
 *
 * Creates the inbox_conversations table which groups related inbox items
 * into conversation threads for better organization and tracking.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inbox_conversations', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Foreign key to workspace
            $table->uuid('workspace_id');

            // Foreign key to social account
            $table->uuid('social_account_id');

            // Conversation identifier (platform-specific thread ID or generated)
            $table->string('conversation_key', 500);

            // Conversation subject/title (derived from first message or post)
            $table->string('subject')->nullable();

            // Participant information
            $table->string('participant_name');
            $table->string('participant_username', 100)->nullable();
            $table->string('participant_profile_url', 500)->nullable();
            $table->string('participant_avatar_url', 500)->nullable();

            // Conversation metadata
            $table->integer('message_count')->default(0);
            $table->timestamp('first_message_at')->nullable();
            $table->timestamp('last_message_at')->nullable();

            // Status tracking
            $table->string('status', 20)->default('active'); // active, resolved, archived

            // Additional metadata (JSON)
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamps();

            // Unique constraint: one conversation per workspace/account/key
            $table->unique(['workspace_id', 'social_account_id', 'conversation_key'], 'inbox_conversations_unique');

            // Indexes for common queries
            $table->index('status');
            $table->index('last_message_at');
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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbox_conversations');
    }
};
