<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rss_feeds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->text('url');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_schedule')->default(false);
            $table->foreignUuid('category_id')->nullable()->constrained('content_categories')->nullOnDelete();
            $table->dateTime('last_fetched_at')->nullable();
            $table->integer('fetch_interval_hours')->default(6);
            $table->timestamps();

            $table->index(['workspace_id', 'is_active']);
        });

        Schema::create('rss_feed_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rss_feed_id')->constrained('rss_feeds')->cascadeOnDelete();
            $table->string('guid');
            $table->string('title');
            $table->text('link');
            $table->text('description')->nullable();
            $table->text('image_url')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->boolean('is_used')->default(false);
            $table->timestamps();

            $table->unique(['rss_feed_id', 'guid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rss_feed_items');
        Schema::dropIfExists('rss_feeds');
    }
};
