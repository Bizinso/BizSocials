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
        Schema::create('feedback_votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('feedback_id');
            $table->uuid('user_id')->nullable();
            $table->uuid('tenant_id')->nullable();
            $table->string('voter_email', 255)->nullable();
            $table->string('session_id', 100)->nullable();
            $table->string('vote_type', 10)->default('upvote');
            $table->timestamps();

            // Unique constraints
            $table->unique(['feedback_id', 'user_id'], 'feedback_votes_user_unique');
            $table->unique(['feedback_id', 'session_id'], 'feedback_votes_session_unique');

            // Indexes
            $table->index('feedback_id');

            // Foreign keys
            $table->foreign('feedback_id')
                ->references('id')
                ->on('feedback')
                ->cascadeOnDelete();

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
        Schema::dropIfExists('feedback_votes');
    }
};
