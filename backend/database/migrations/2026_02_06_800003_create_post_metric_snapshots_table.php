<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for post_metric_snapshots table.
 *
 * Creates the post_metric_snapshots table which stores periodic
 * engagement metrics for published posts. Each snapshot captures
 * the metrics at a specific point in time.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('post_metric_snapshots', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Foreign key to post target
            $table->uuid('post_target_id');

            // When the metrics were captured
            $table->timestamp('captured_at');

            // Engagement metrics
            $table->integer('likes_count')->nullable();
            $table->integer('comments_count')->nullable();
            $table->integer('shares_count')->nullable();
            $table->integer('impressions_count')->nullable();
            $table->integer('reach_count')->nullable();
            $table->integer('clicks_count')->nullable();

            // Calculated engagement rate (percentage with 4 decimal places)
            $table->decimal('engagement_rate', 8, 4)->nullable();

            // Raw API response for reference
            $table->json('raw_response')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes for common queries
            $table->index('captured_at');
            $table->index(['post_target_id', 'captured_at']);

            // Foreign key to post_targets
            $table->foreign('post_target_id')
                ->references('id')
                ->on('post_targets')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_metric_snapshots');
    }
};
