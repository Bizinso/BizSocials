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
        Schema::create('kb_article_tags', function (Blueprint $table) {
            $table->uuid('article_id');
            $table->uuid('tag_id');
            $table->timestamps();

            // Primary key
            $table->primary(['article_id', 'tag_id']);

            // Foreign keys
            $table->foreign('article_id')
                ->references('id')
                ->on('kb_articles')
                ->cascadeOnDelete();

            $table->foreign('tag_id')
                ->references('id')
                ->on('kb_tags')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kb_article_tags');
    }
};
