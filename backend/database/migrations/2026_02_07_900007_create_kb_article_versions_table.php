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
        Schema::create('kb_article_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('article_id');
            $table->integer('version');
            $table->string('title', 255);
            $table->longText('content');
            $table->text('change_summary')->nullable();
            $table->uuid('changed_by');
            $table->timestamps();

            // Unique constraint
            $table->unique(['article_id', 'version'], 'kb_article_versions_unique');

            // Indexes
            $table->index('article_id');

            // Foreign keys
            $table->foreign('article_id')
                ->references('id')
                ->on('kb_articles')
                ->cascadeOnDelete();

            $table->foreign('changed_by')
                ->references('id')
                ->on('super_admin_users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kb_article_versions');
    }
};
