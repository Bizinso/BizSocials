<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for social_accounts table.
 *
 * Creates the social_accounts table which stores connected social media accounts
 * with OAuth credentials. Each social account belongs to a workspace and stores
 * encrypted tokens for posting to social platforms.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Foreign key to workspace
            $table->uuid('workspace_id');

            // Platform identifier (SocialPlatform enum)
            $table->string('platform', 20);

            // Platform-specific account identifier
            $table->string('platform_account_id', 100);

            // Display name of the account
            $table->string('account_name');

            // Username/handle (e.g., @username)
            $table->string('account_username', 100)->nullable();

            // Profile image URL
            $table->string('profile_image_url', 500)->nullable();

            // Account status (SocialAccountStatus enum)
            $table->string('status', 20)->default('connected');

            // Encrypted OAuth access token
            $table->text('access_token_encrypted');

            // Encrypted OAuth refresh token (optional)
            $table->text('refresh_token_encrypted')->nullable();

            // Token expiration timestamp
            $table->timestamp('token_expires_at')->nullable();

            // User who connected this account
            $table->uuid('connected_by_user_id');

            // When the account was connected
            $table->timestamp('connected_at');

            // When tokens were last refreshed
            $table->timestamp('last_refreshed_at')->nullable();

            // When the account was disconnected
            $table->timestamp('disconnected_at')->nullable();

            // Platform-specific metadata (JSON)
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamps();

            // Unique constraint: one platform account per workspace
            $table->unique(
                ['workspace_id', 'platform', 'platform_account_id'],
                'social_accounts_unique'
            );

            // Indexes for common queries
            $table->index('platform');
            $table->index('status');
            $table->index('token_expires_at');

            // Foreign key to workspaces
            $table->foreign('workspace_id')
                ->references('id')
                ->on('workspaces')
                ->cascadeOnDelete();

            // Foreign key to users
            $table->foreign('connected_by_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
