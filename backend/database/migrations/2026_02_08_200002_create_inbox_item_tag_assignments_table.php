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
        Schema::create('inbox_item_tag_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('inbox_item_id')->constrained('inbox_items')->cascadeOnDelete();
            $table->foreignUuid('tag_id')->constrained('inbox_item_tags')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['inbox_item_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbox_item_tag_assignments');
    }
};
