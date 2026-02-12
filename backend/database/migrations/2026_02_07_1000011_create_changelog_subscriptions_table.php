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
        Schema::create('changelog_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email', 255);
            $table->uuid('user_id')->nullable();
            $table->uuid('tenant_id')->nullable();
            $table->boolean('notify_major')->default(true);
            $table->boolean('notify_minor')->default(true);
            $table->boolean('notify_patch')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            // Unique constraint
            $table->unique('email');

            // Indexes
            $table->index('is_active');

            // Foreign keys
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('changelog_subscriptions');
    }
};
