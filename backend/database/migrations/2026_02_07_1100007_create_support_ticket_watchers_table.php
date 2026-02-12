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
        Schema::create('support_ticket_watchers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('user_id')->nullable();
            $table->uuid('admin_id')->nullable();
            $table->string('email', 255)->nullable();
            $table->boolean('notify_on_reply')->default(true);
            $table->boolean('notify_on_status_change')->default(true);
            $table->boolean('notify_on_assignment')->default(false);
            $table->timestamps();

            // Unique constraints
            $table->unique(['ticket_id', 'user_id'], 'watchers_ticket_user_unique');
            $table->unique(['ticket_id', 'admin_id'], 'watchers_ticket_admin_unique');

            // Foreign keys
            $table->foreign('ticket_id')
                ->references('id')
                ->on('support_tickets')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('admin_id')
                ->references('id')
                ->on('super_admin_users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_ticket_watchers');
    }
};
