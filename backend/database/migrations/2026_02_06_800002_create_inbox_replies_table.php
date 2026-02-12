<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for inbox_replies table.
 *
 * Creates the inbox_replies table which stores replies sent to
 * inbox items (comments). Each reply tracks the response sent
 * to the social platform.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inbox_replies', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Foreign key to inbox item
            $table->uuid('inbox_item_id');

            // Foreign key to user who sent the reply
            $table->uuid('replied_by_user_id');

            // Reply content (max 1000 chars as per spec)
            $table->text('content_text');

            // Platform's reply ID (null until sent successfully)
            $table->string('platform_reply_id', 255)->nullable();

            // When the reply was sent
            $table->timestamp('sent_at');

            // Failure tracking
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();

            // Timestamps
            $table->timestamps();

            // Index for common queries
            $table->index('sent_at');

            // Foreign key to inbox_items
            $table->foreign('inbox_item_id')
                ->references('id')
                ->on('inbox_items')
                ->cascadeOnDelete();

            // Foreign key to users
            $table->foreign('replied_by_user_id')
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
        Schema::dropIfExists('inbox_replies');
    }
};
