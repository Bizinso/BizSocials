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
        Schema::create('kb_search_analytics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('search_query', 255);
            $table->string('search_query_normalized', 255);
            $table->integer('results_count');
            $table->uuid('clicked_article_id')->nullable();
            $table->boolean('search_successful')->nullable();
            $table->uuid('user_id')->nullable();
            $table->uuid('tenant_id')->nullable();
            $table->string('session_id', 100)->nullable();
            $table->timestamps();

            // Indexes
            $table->index('search_query_normalized');
            $table->index('results_count');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);

            // Foreign keys
            $table->foreign('clicked_article_id')
                ->references('id')
                ->on('kb_articles')
                ->nullOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kb_search_analytics');
    }
};
