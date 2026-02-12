<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_aggregates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id');
            $table->uuid('social_account_id')->nullable();
            $table->date('date');
            $table->string('period_type', 10); // daily, weekly, monthly

            // Aggregate metrics
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('reach')->default(0);
            $table->unsignedBigInteger('engagements')->default(0);
            $table->unsignedBigInteger('likes')->default(0);
            $table->unsignedBigInteger('comments')->default(0);
            $table->unsignedBigInteger('shares')->default(0);
            $table->unsignedBigInteger('saves')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('video_views')->default(0);
            $table->unsignedBigInteger('posts_count')->default(0);
            $table->decimal('engagement_rate', 8, 4)->default(0);

            // Follower metrics
            $table->unsignedBigInteger('followers_start')->default(0);
            $table->unsignedBigInteger('followers_end')->default(0);
            $table->integer('followers_change')->default(0);

            $table->timestamps();

            $table->unique(['workspace_id', 'social_account_id', 'date', 'period_type'], 'analytics_unique');
            $table->index(['workspace_id', 'date']);
            $table->index(['social_account_id', 'date']);

            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            $table->foreign('social_account_id')->references('id')->on('social_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_aggregates');
    }
};
