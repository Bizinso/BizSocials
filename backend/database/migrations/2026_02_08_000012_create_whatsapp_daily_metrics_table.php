<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_daily_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('whatsapp_phone_number_id')->constrained('whatsapp_phone_numbers')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('conversations_started')->default(0);
            $table->unsignedInteger('conversations_resolved')->default(0);
            $table->unsignedInteger('messages_sent')->default(0);
            $table->unsignedInteger('messages_delivered')->default(0);
            $table->unsignedInteger('messages_read')->default(0);
            $table->unsignedInteger('messages_failed')->default(0);
            $table->unsignedInteger('templates_sent')->default(0);
            $table->unsignedInteger('campaigns_sent')->default(0);
            $table->unsignedInteger('avg_first_response_seconds')->nullable();
            $table->unsignedInteger('avg_resolution_seconds')->nullable();
            $table->unsignedInteger('block_count')->default(0);
            $table->timestamps();

            $table->unique(['whatsapp_phone_number_id', 'date']);
            $table->index(['workspace_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_daily_metrics');
    }
};
