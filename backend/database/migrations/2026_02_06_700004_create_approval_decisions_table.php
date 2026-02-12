<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for approval_decisions table.
 *
 * Creates the approval_decisions table which tracks approval/rejection
 * decisions made on posts. Supports audit trail with historical decisions.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('approval_decisions', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Foreign key to post
            $table->uuid('post_id');

            // User who made the decision
            $table->uuid('decided_by_user_id');

            // Decision type (ApprovalDecisionType enum)
            $table->string('decision', 20);

            // Optional comment explaining the decision
            $table->text('comment')->nullable();

            // Whether this is the active/current decision
            $table->boolean('is_active')->default(true);

            // When the decision was made
            $table->timestamp('decided_at');

            // Timestamps
            $table->timestamps();

            // Index for finding active decisions
            $table->index(['post_id', 'is_active']);

            // Foreign key to posts
            $table->foreign('post_id')
                ->references('id')
                ->on('posts')
                ->cascadeOnDelete();

            // Foreign key to users
            $table->foreign('decided_by_user_id')
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
        Schema::dropIfExists('approval_decisions');
    }
};
