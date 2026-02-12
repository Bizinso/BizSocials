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
        Schema::create('inbox_internal_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('inbox_item_id')->constrained('inbox_items')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users');
            $table->text('content');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbox_internal_notes');
    }
};
