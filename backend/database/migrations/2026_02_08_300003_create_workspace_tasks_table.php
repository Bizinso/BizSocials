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
        Schema::create('workspace_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces');
            $table->foreignUuid('post_id')->nullable()->constrained('posts')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignUuid('assigned_to_user_id')->nullable()->constrained('users');
            $table->foreignUuid('created_by_user_id')->constrained('users');
            $table->string('status')->default('todo');
            $table->date('due_date')->nullable();
            $table->string('priority')->default('medium');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_tasks');
    }
};
