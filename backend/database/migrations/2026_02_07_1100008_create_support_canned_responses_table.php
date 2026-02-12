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
        Schema::create('support_canned_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 100);
            $table->string('shortcut', 50)->nullable()->unique();
            $table->longText('content');
            $table->string('category', 20)->default('general');
            $table->uuid('created_by');
            $table->boolean('is_shared')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('shortcut');
            $table->index('category');
            $table->index('created_by');

            // Foreign keys
            $table->foreign('created_by')
                ->references('id')
                ->on('super_admin_users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_canned_responses');
    }
};
