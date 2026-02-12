<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_change_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('platform_policy_id')->constrained('platform_policies')->cascadeOnDelete();
            $table->string('change_type'); // rate_limit_change, category_deprecated, new_requirement, policy_update
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->text('description');
            $table->dateTime('notified_at')->nullable();
            $table->timestamps();

            $table->index('platform_policy_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_change_logs');
    }
};
