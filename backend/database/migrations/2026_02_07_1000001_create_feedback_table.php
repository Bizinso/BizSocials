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
        Schema::create('feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('submitter_email', 255)->nullable();
            $table->string('submitter_name', 100)->nullable();
            $table->string('title', 255);
            $table->text('description');
            $table->string('feedback_type', 30);
            $table->string('category', 30)->nullable();
            $table->string('user_priority', 20)->default('important');
            $table->text('business_impact')->nullable();
            $table->string('admin_priority', 20)->nullable();
            $table->string('effort_estimate', 5)->nullable();
            $table->string('status', 20)->default('new');
            $table->text('status_reason')->nullable();
            $table->integer('vote_count')->default(0);
            $table->uuid('roadmap_item_id')->nullable();
            $table->uuid('duplicate_of_id')->nullable();
            $table->string('source', 20)->default('portal');
            $table->json('browser_info')->nullable();
            $table->string('page_url', 500)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('feedback_type');
            $table->index('category');
            $table->index(['vote_count', 'id'], 'feedback_votes_idx');
            $table->index('tenant_id');
            $table->index('created_at');

            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->nullOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('duplicate_of_id')
                ->references('id')
                ->on('feedback')
                ->nullOnDelete();

            $table->foreign('reviewed_by')
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
        Schema::dropIfExists('feedback');
    }
};
