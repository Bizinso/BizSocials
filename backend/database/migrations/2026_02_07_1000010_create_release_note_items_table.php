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
        Schema::create('release_note_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('release_note_id');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('change_type', 20);
            $table->uuid('roadmap_item_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('release_note_id');

            // Foreign keys
            $table->foreign('release_note_id')
                ->references('id')
                ->on('release_notes')
                ->cascadeOnDelete();

            $table->foreign('roadmap_item_id')
                ->references('id')
                ->on('roadmap_items')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('release_note_items');
    }
};
