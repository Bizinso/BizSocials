<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id');
            $table->uuid('whatsapp_phone_number_id');
            $table->string('customer_phone', 20);
            $table->string('customer_name', 200)->nullable();
            $table->string('customer_profile_name', 200)->nullable();
            $table->string('status', 20)->default('active');
            $table->uuid('assigned_to_user_id')->nullable();
            $table->string('assigned_to_team', 100)->nullable();
            $table->string('priority', 10)->default('normal');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_customer_message_at')->nullable();
            $table->timestamp('conversation_expires_at')->nullable();
            $table->boolean('is_within_service_window')->default(false);
            $table->unsignedInteger('message_count')->default(0);
            $table->json('tags')->nullable();
            $table->unsignedInteger('internal_notes_count')->default(0);
            $table->timestamp('sla_breach_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['whatsapp_phone_number_id', 'customer_phone'], 'wa_conv_ph_number_id_cust_phone_unique');
            $table->index(['workspace_id', 'status', 'last_message_at'], 'wa_conv_workspace_status_last_msg_index');
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            $table->foreign('whatsapp_phone_number_id', 'wa_conv_ph_number_id_foreign')->references('id')->on('whatsapp_phone_numbers')->cascadeOnDelete();
            $table->foreign('assigned_to_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};
