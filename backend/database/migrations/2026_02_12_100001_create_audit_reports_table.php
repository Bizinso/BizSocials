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
        Schema::create('audit_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('feature_area', 100);
            $table->json('findings')->nullable();
            $table->json('summary')->nullable();
            $table->json('recommendations')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes for efficient querying
            $table->index('feature_area');
            $table->index('status');
            $table->index(['feature_area', 'status'], 'audit_reports_area_status_idx');
            $table->index('created_at');
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_reports');
    }
};
