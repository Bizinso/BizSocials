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
        Schema::create('approval_step_decisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignUuid('workflow_id')->constrained('approval_workflows');
            $table->integer('step_order');
            $table->foreignUuid('approver_user_id')->constrained('users');
            $table->string('decision');
            $table->text('comment')->nullable();
            $table->dateTime('decided_at');
            $table->timestamps();

            $table->index(['post_id', 'workflow_id', 'step_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_step_decisions');
    }
};
