<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keyword_mentions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('keyword_id')->constrained('monitored_keywords')->cascadeOnDelete();
            $table->string('platform');
            $table->string('platform_item_id')->nullable();
            $table->string('author_name')->nullable();
            $table->text('content_text')->nullable();
            $table->string('sentiment')->default('unknown');
            $table->text('url')->nullable();
            $table->dateTime('platform_created_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['keyword_id', 'sentiment']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keyword_mentions');
    }
};
