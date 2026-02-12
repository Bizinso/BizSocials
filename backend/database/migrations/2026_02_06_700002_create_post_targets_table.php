<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for post_targets table.
 *
 * Creates the post_targets table which links posts to social accounts.
 * Each target represents a post being published to a specific social account
 * and tracks the publishing status and results.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('post_targets', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Foreign key to post
            $table->uuid('post_id');

            // Foreign key to social account
            $table->uuid('social_account_id');

            // Platform code (denormalized from social_account)
            $table->string('platform_code', 20);

            // Content override for this specific platform
            $table->text('content_override')->nullable();

            // Publishing status (PostTargetStatus enum)
            $table->string('status', 20)->default('pending');

            // External platform identifiers
            $table->string('external_post_id', 255)->nullable();
            $table->string('external_post_url', 500)->nullable();

            // When published to this platform
            $table->timestamp('published_at')->nullable();

            // Error information
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();

            // Retry tracking
            $table->integer('retry_count')->default(0);

            // Engagement metrics (JSON)
            $table->json('metrics')->nullable();

            // Timestamps
            $table->timestamps();

            // Unique constraint: one target per post per social account
            $table->unique(['post_id', 'social_account_id']);

            // Indexes
            $table->index('status');
            $table->index('platform_code');

            // Foreign key to posts
            $table->foreign('post_id')
                ->references('id')
                ->on('posts')
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
        Schema::dropIfExists('post_targets');
    }
};
