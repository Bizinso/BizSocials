<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hashtag_performance', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id');
            $table->string('hashtag');
            $table->string('platform');
            $table->integer('usage_count')->default(0);
            $table->decimal('avg_reach', 12, 2)->default(0);
            $table->decimal('avg_engagement', 12, 2)->default(0);
            $table->decimal('avg_impressions', 12, 2)->default(0);
            $table->dateTime('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'hashtag', 'platform']);

            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hashtag_performance');
    }
};
