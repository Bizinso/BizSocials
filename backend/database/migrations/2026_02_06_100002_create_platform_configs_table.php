<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for platform_configs table.
 *
 * Creates the table for global platform configuration settings.
 * These settings control platform-wide behavior and can be managed
 * by super admin users.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('platform_configs', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Configuration key (unique identifier)
            $table->string('key', 100)->unique();

            // Configuration value stored as JSON
            $table->json('value');

            // Category for grouping
            // general, features, integrations, limits, notifications, security
            $table->string('category', 50);

            // Human-readable description
            $table->text('description')->nullable();

            // Flag for sensitive data (should be masked in UI)
            $table->boolean('is_sensitive')->default(false);

            // Track who last updated this config
            $table->uuid('updated_by')->nullable();

            // Timestamps
            $table->timestamps();

            // Foreign key to super_admin_users
            $table->foreign('updated_by')
                ->references('id')
                ->on('super_admin_users')
                ->onDelete('set null');

            // Index for category filtering
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_configs');
    }
};
