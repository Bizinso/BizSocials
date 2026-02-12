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
        Schema::create('watermark_presets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // image or text
            $table->string('image_path')->nullable();
            $table->string('text')->nullable();
            $table->string('position')->default('bottom-right');
            $table->integer('opacity')->default(50);
            $table->integer('scale')->default(20);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['workspace_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watermark_presets');
    }
};
