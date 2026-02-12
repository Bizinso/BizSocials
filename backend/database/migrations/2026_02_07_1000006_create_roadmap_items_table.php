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
        Schema::create('roadmap_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->longText('detailed_description')->nullable();
            $table->string('category', 30);
            $table->string('status', 20)->default('considering');
            $table->string('quarter', 10)->nullable();
            $table->date('target_date')->nullable();
            $table->date('shipped_date')->nullable();
            $table->string('priority', 20)->default('medium');
            $table->integer('progress_percentage')->default(0);
            $table->boolean('is_public')->default(true);
            $table->integer('linked_feedback_count')->default(0);
            $table->integer('total_votes')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('quarter');
            $table->index('category');
            $table->index(['is_public', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roadmap_items');
    }
};
