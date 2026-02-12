<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for users table.
 *
 * Creates the tenant users table. This replaces Laravel's default users table
 * with a multi-tenant aware implementation. Users belong to tenants and have
 * roles within their tenant organization.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Foreign key to tenant
            $table->uuid('tenant_id');

            // User email (unique within tenant)
            $table->string('email');

            // Password (nullable for SSO users)
            $table->string('password')->nullable();

            // User display name
            $table->string('name', 100);

            // Avatar URL
            $table->string('avatar_url', 500)->nullable();

            // Phone number
            $table->string('phone', 20)->nullable();

            // User timezone (overrides tenant timezone if set)
            $table->string('timezone', 50)->nullable();

            // Preferred language
            $table->string('language', 10)->default('en');

            // User status (UserStatus enum)
            $table->string('status', 20)->default('pending');

            // Role within tenant (TenantRole enum)
            $table->string('role_in_tenant', 20);

            // Email verification timestamp
            $table->timestamp('email_verified_at')->nullable();

            // Last login timestamp
            $table->timestamp('last_login_at')->nullable();

            // Last activity timestamp
            $table->timestamp('last_active_at')->nullable();

            // MFA settings
            $table->boolean('mfa_enabled')->default(false);
            $table->string('mfa_secret')->nullable();

            // User preferences (JSON)
            $table->json('settings')->nullable();

            // Remember token for persistent login
            $table->string('remember_token', 100)->nullable();

            // Timestamps
            $table->timestamps();

            // Soft deletes
            $table->softDeletes();

            // Unique constraint: email unique within tenant
            $table->unique(['tenant_id', 'email']);

            // Indexes
            $table->index('email');
            $table->index('status');
            $table->index('role_in_tenant');
            $table->index('last_active_at');

            // Foreign key to tenants
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });

        // Add owner_user_id foreign key to tenants table
        Schema::table('tenants', function (Blueprint $table): void {
            $table->foreign('owner_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove foreign key from tenants table first
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropForeign(['owner_user_id']);
        });

        Schema::dropIfExists('users');
    }
};
