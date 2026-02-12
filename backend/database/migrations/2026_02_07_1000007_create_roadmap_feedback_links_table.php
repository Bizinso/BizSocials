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
        Schema::create('roadmap_feedback_links', function (Blueprint $table) {
            $table->uuid('roadmap_item_id');
            $table->uuid('feedback_id');
            $table->timestamps();

            // Primary key
            $table->primary(['roadmap_item_id', 'feedback_id']);

            // Foreign keys
            $table->foreign('roadmap_item_id')
                ->references('id')
                ->on('roadmap_items')
                ->cascadeOnDelete();

            $table->foreign('feedback_id')
                ->references('id')
                ->on('feedback')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roadmap_feedback_links');
    }
};
