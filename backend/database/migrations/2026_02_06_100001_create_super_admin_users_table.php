<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for super_admin_users table.
 *
 * Creates the table for platform administrators (Bizinso team members).
 * These users have access to the super admin panel for managing
 * all tenants, configurations, and platform settings.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('super_admin_users', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Authentication fields
            $table->string('email', 255)->unique();
            $table->string('password', 255);

            // Profile fields
            $table->string('name', 100);

            // Role and status
            // SUPER_ADMIN, ADMIN, SUPPORT, VIEWER
            $table->string('role', 20);
            // ACTIVE, INACTIVE, SUSPENDED
            $table->string('status', 20)->default('ACTIVE');

            // Login tracking
            $table->timestamp('last_login_at')->nullable();

            // Multi-factor authentication
            $table->boolean('mfa_enabled')->default(false);
            $table->string('mfa_secret', 255)->nullable();

            // Laravel authentication
            $table->rememberToken();

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('super_admin_users');
    }
};
