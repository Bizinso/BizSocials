<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for user_invitations table.
 *
 * Creates the invitation table for team building. Invitations are sent
 * to email addresses with a token for accepting and joining a tenant.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_invitations', function (Blueprint $table): void {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Foreign key to tenant
            $table->uuid('tenant_id');

            // Invitee email address
            $table->string('email');

            // Role to assign when accepted (TenantRole enum)
            $table->string('role_in_tenant', 20);

            // Pre-configured workspace memberships (JSON)
            $table->json('workspace_memberships')->nullable();

            // Foreign key to inviting user
            $table->uuid('invited_by');

            // Unique invitation token
            $table->string('token', 100)->unique();

            // Invitation status (InvitationStatus enum)
            $table->string('status', 20)->default('pending');

            // Invitation expiration timestamp
            $table->timestamp('expires_at');

            // Timestamp when invitation was accepted
            $table->timestamp('accepted_at')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'email']);
            $table->index('status');
            $table->index('expires_at');

            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign('invited_by')
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
        Schema::dropIfExists('user_invitations');
    }
};
