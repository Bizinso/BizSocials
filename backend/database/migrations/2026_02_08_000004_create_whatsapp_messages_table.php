<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->string('wamid', 200)->unique()->nullable();
            $table->string('direction', 10);
            $table->string('type', 30);
            $table->text('content_text')->nullable();
            $table->json('content_payload')->nullable();
            $table->text('media_url')->nullable();
            $table->string('media_mime_type', 100)->nullable();
            $table->unsignedInteger('media_file_size')->nullable();
            $table->uuid('template_id')->nullable();
            $table->uuid('sent_by_user_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('status_updated_at')->nullable();
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('platform_timestamp');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->foreign('conversation_id')->references('id')->on('whatsapp_conversations')->cascadeOnDelete();
            $table->foreign('sent_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
