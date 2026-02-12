<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('user_id');
            $table->uuid('workspace_id')->nullable();

            $table->string('activity_type', 50);
            $table->string('activity_category', 50);
            $table->string('resource_type', 50)->nullable();
            $table->uuid('resource_id')->nullable();

            $table->string('page_url', 500)->nullable();
            $table->string('referrer_url', 500)->nullable();
            $table->string('session_id', 100)->nullable();

            $table->string('device_type', 20)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();

            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index(['tenant_id', 'user_id', 'created_at']);
            $table->index(['workspace_id', 'created_at']);
            $table->index(['activity_type', 'created_at']);
            $table->index('session_id');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
    }
};
