<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for post_media table.
 *
 * Creates the post_media table which stores media attachments for posts.
 * Each media item can be an image, video, GIF, or document.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('post_media', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Foreign key to post
            $table->uuid('post_id');

            // Media type (MediaType enum)
            $table->string('type', 20);

            // File information
            $table->string('file_name', 255);
            $table->bigInteger('file_size')->nullable(); // Bytes
            $table->string('mime_type', 100)->nullable();

            // Storage location
            $table->string('storage_path', 500);
            $table->string('cdn_url', 500)->nullable();
            $table->string('thumbnail_url', 500)->nullable();

            // Media dimensions (JSON: {width, height})
            $table->json('dimensions')->nullable();

            // Duration for videos (in seconds)
            $table->integer('duration_seconds')->nullable();

            // Accessibility
            $table->string('alt_text', 500)->nullable();

            // Ordering
            $table->integer('sort_order')->default(0);

            // Processing status (MediaProcessingStatus enum)
            $table->string('processing_status', 20)->default('pending');

            // Additional metadata (JSON)
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('processing_status');
            $table->index(['post_id', 'sort_order']);

            // Foreign key to posts
            $table->foreign('post_id')
                ->references('id')
                ->on('posts')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_media');
    }
};
