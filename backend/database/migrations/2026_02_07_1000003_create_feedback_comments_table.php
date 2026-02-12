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
        Schema::create('feedback_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('feedback_id');
            $table->uuid('user_id')->nullable();
            $table->uuid('admin_id')->nullable();
            $table->string('commenter_name', 100)->nullable();
            $table->text('content');
            $table->boolean('is_internal')->default(false);
            $table->boolean('is_official_response')->default(false);
            $table->timestamps();

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
        Schema::dropIfExists('feedback_comments');
    }
};
