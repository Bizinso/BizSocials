<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evergreen_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('source_category_id')->nullable()->constrained('content_categories')->nullOnDelete();
            $table->json('social_account_ids');
            $table->integer('repost_interval_days')->default(30);
            $table->integer('max_reposts')->default(3);
            $table->json('time_slots')->nullable();
            $table->boolean('content_variation')->default(false);
            $table->dateTime('last_reposted_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'is_active']);
        });

        Schema::create('evergreen_post_pool', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('evergreen_rule_id')->constrained('evergreen_rules')->cascadeOnDelete();
            $table->foreignUuid('post_id')->constrained('posts')->cascadeOnDelete();
            $table->integer('repost_count')->default(0);
            $table->dateTime('next_repost_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['evergreen_rule_id', 'is_active', 'next_repost_at'], 'eg_post_pool_rule_active_next_repost_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evergreen_post_pool');
        Schema::dropIfExists('evergreen_rules');
    }
};
