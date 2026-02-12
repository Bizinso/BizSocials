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
        Schema::create('feedback_tag_assignments', function (Blueprint $table) {
            $table->uuid('feedback_id');
            $table->uuid('tag_id');
            $table->timestamps();

            // Primary key
            $table->primary(['feedback_id', 'tag_id']);

            // Foreign keys
            $table->foreign('feedback_id')
                ->references('id')
                ->on('feedback')
                ->cascadeOnDelete();

            $table->foreign('tag_id')
                ->references('id')
                ->on('feedback_tags')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_tag_assignments');
    }
};
