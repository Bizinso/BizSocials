<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for user_sessions table.
 *
 * Creates the session tracking table for users. Stores active sessions
 * with device information, location, and expiration tracking.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Foreign key to user
            $table->uuid('user_id');

            // Hashed session token
            $table->string('token_hash');

            // IP address (IPv6 support)
            $table->string('ip_address', 45)->nullable();

            // User agent string
            $table->text('user_agent')->nullable();

            // Device type (DeviceType enum)
            $table->string('device_type', 20)->nullable();

            // Geolocation data (JSON)
            $table->json('location')->nullable();

            // Last activity timestamp
            $table->timestamp('last_active_at');

            // Session expiration timestamp
            $table->timestamp('expires_at');

            // Created timestamp (no updated_at for sessions)
            $table->timestamp('created_at');

            // Indexes
            $table->index('token_hash');
            $table->index('expires_at');
            $table->index('last_active_at');

            // Foreign key to users
            $table->foreign('user_id')
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
        Schema::dropIfExists('user_sessions');
    }
};
