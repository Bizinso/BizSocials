<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to add conversation_id to inbox_items table.
 *
 * Links inbox items to conversations for thread grouping.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inbox_items', function (Blueprint $table): void {
            // Add conversation_id column
            $table->uuid('conversation_id')->nullable()->after('post_target_id');

            // Add index for conversation queries
            $table->index('conversation_id');

            // Foreign key to inbox_conversations
            $table->foreign('conversation_id')
                ->references('id')
                ->on('inbox_conversations')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inbox_items', function (Blueprint $table): void {
            $table->dropForeign(['conversation_id']);
            $table->dropIndex(['conversation_id']);
            $table->dropColumn('conversation_id');
        });
    }
};
