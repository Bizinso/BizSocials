<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id');
            $table->uuid('created_by_user_id');

            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('report_type', 50); // performance, engagement, growth, custom

            $table->date('date_from');
            $table->date('date_to');
            $table->json('social_account_ids')->nullable();
            $table->json('metrics')->nullable();
            $table->json('filters')->nullable();

            $table->string('status', 20)->default('pending');
            $table->string('file_path', 500)->nullable();
            $table->string('file_format', 10)->default('pdf');
            $table->bigInteger('file_size_bytes')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'created_at']);
            $table->index('status');

            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_reports');
    }
};
