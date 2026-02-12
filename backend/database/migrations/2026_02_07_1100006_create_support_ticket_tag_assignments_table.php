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
        Schema::create('support_ticket_tag_assignments', function (Blueprint $table) {
            $table->uuid('ticket_id');
            $table->uuid('tag_id');
            $table->timestamps();

            // Primary key
            $table->primary(['ticket_id', 'tag_id']);

            // Foreign keys
            $table->foreign('ticket_id')
                ->references('id')
                ->on('support_tickets')
                ->cascadeOnDelete();

            $table->foreign('tag_id')
                ->references('id')
                ->on('support_ticket_tags')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_ticket_tag_assignments');
    }
};
