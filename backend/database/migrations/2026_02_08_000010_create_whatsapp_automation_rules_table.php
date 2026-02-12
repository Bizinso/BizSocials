<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_automation_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->string('trigger_type'); // new_conversation, keyword_match, outside_business_hours, no_response_timeout
            $table->json('trigger_conditions')->nullable();
            $table->string('action_type'); // auto_reply, assign_user, assign_team, add_tag, send_template
            $table->json('action_params')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->unsignedInteger('execution_count')->default(0);
            $table->timestamps();

            $table->index(['workspace_id', 'is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_automation_rules');
    }
};
