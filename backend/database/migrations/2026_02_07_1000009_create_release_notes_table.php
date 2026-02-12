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
        Schema::create('release_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('version', 20);
            $table->string('version_name', 100)->nullable();
            $table->string('title', 255);
            $table->text('summary')->nullable();
            $table->longText('content');
            $table->string('content_format', 10)->default('markdown');
            $table->string('release_type', 10);
            $table->string('status', 20)->default('draft');
            $table->boolean('is_public')->default(true);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('version');
            $table->index('status');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('release_notes');
    }
};
