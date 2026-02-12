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
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ticket_number', 20)->unique();
            $table->uuid('tenant_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('requester_email', 255);
            $table->string('requester_name', 100);
            $table->uuid('category_id')->nullable();
            $table->string('subject', 255);
            $table->longText('description');
            $table->string('ticket_type', 20)->default('question');
            $table->string('priority', 10)->default('medium');
            $table->string('status', 20)->default('new');
            $table->string('channel', 15)->default('web_form');
            $table->uuid('assigned_to')->nullable();
            $table->uuid('assigned_team_id')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->boolean('is_sla_breached')->default(false);
            $table->integer('comment_count')->default(0);
            $table->integer('attachment_count')->default(0);
            $table->json('custom_fields')->nullable();
            $table->string('browser_info', 500)->nullable();
            $table->string('page_url', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('ticket_number');
            $table->index('status');
            $table->index('priority');
            $table->index('ticket_type');
            $table->index('tenant_id');
            $table->index('assigned_to');
            $table->index('created_at');
            $table->index('sla_due_at');
            $table->index(['status', 'priority'], 'tickets_status_priority_idx');

            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->nullOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('category_id')
                ->references('id')
                ->on('support_categories')
                ->nullOnDelete();

            $table->foreign('assigned_to')
                ->references('id')
                ->on('super_admin_users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
