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
        Schema::create('kb_article_relations', function (Blueprint $table) {
            $table->uuid('article_id');
            $table->uuid('related_article_id');
            $table->string('relation_type', 20)->default('related');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Primary key
            $table->primary(['article_id', 'related_article_id']);

            // Indexes
            $table->index('relation_type');

            // Foreign keys
            $table->foreign('article_id')
                ->references('id')
                ->on('kb_articles')
                ->cascadeOnDelete();

            $table->foreign('related_article_id')
                ->references('id')
                ->on('kb_articles')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kb_article_relations');
    }
};
