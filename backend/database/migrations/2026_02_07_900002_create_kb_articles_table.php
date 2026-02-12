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
        Schema::create('kb_articles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('category_id');
            $table->string('title', 255);
            $table->string('slug', 255);
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('content_format', 20)->default('markdown');
            $table->string('featured_image', 500)->nullable();
            $table->string('video_url', 500)->nullable();
            $table->integer('video_duration')->nullable();
            $table->string('article_type', 30);
            $table->string('difficulty_level', 20)->default('beginner');
            $table->string('status', 20)->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_public')->default(true);
            $table->string('visibility', 20)->default('all');
            $table->json('allowed_plans')->nullable();
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->json('meta_keywords')->nullable();
            $table->integer('version')->default(1);
            $table->uuid('author_id');
            $table->uuid('last_edited_by')->nullable();
            $table->integer('view_count')->default(0);
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            // Unique constraint
            $table->unique(['category_id', 'slug'], 'kb_articles_category_slug_unique');

            // Indexes
            $table->index('status');
            $table->index('article_type');
            $table->index('difficulty_level');
            $table->index(['is_featured', 'status']);
            $table->index('published_at');
            $table->index('view_count');

            // Foreign keys
            $table->foreign('category_id')
                ->references('id')
                ->on('kb_categories')
                ->cascadeOnDelete();

            $table->foreign('author_id')
                ->references('id')
                ->on('super_admin_users')
                ->cascadeOnDelete();

            $table->foreign('last_edited_by')
                ->references('id')
                ->on('super_admin_users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kb_articles');
    }
};
