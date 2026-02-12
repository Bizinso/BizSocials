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
        Schema::create('inbox_automation_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->string('trigger_type');
            $table->json('trigger_conditions')->nullable();
            $table->string('action_type');
            $table->json('action_params')->nullable();
            $table->integer('priority')->default(0);
            $table->integer('execution_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbox_automation_rules');
    }
};
