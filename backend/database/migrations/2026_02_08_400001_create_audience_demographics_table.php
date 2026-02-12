<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audience_demographics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('social_account_id');
            $table->date('snapshot_date');
            $table->json('age_ranges')->nullable();
            $table->json('gender_split')->nullable();
            $table->json('top_countries')->nullable();
            $table->json('top_cities')->nullable();
            $table->integer('follower_count')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->unique(['social_account_id', 'snapshot_date']);

            $table->foreign('social_account_id')->references('id')->on('social_accounts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audience_demographics');
    }
};
