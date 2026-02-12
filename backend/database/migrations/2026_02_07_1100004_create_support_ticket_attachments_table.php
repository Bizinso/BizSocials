<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('support_ticket_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('comment_id')->nullable();
            $table->string('filename', 255);
            $table->string('original_filename', 255);
            $table->string('file_path', 500);
            $table->string('mime_type', 100);
            $table->string('attachment_type', 20)->default('other');
            $table->bigInteger('file_size');
            $table->uuid('uploaded_by')->nullable();
            $table->boolean('is_inline')->default(false);
            $table->timestamps();

            // Indexes
            $table->index('ticket_id');
            $table->index('comment_id');

            // Foreign keys
            $table->foreign('ticket_id')
                ->references('id')
                ->on('support_tickets')
                ->cascadeOnDelete();

            $table->foreign('comment_id')
                ->references('id')
                ->on('support_ticket_comments')
                ->nullOnDelete();

            $table->foreign('uploaded_by')
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
        Schema::dropIfExists('support_ticket_attachments');
    }
};
