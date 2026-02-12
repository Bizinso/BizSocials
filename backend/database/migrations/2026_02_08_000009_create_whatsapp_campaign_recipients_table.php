<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_campaign_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('campaign_id')->constrained('whatsapp_campaigns')->cascadeOnDelete();
            $table->foreignUuid('opt_in_id')->constrained('whatsapp_opt_ins')->cascadeOnDelete();
            $table->string('phone_number');
            $table->string('customer_name')->nullable();
            $table->json('template_params')->nullable();
            $table->string('status')->default('pending'); // pending, sent, delivered, read, failed
            $table->string('wamid')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();

            $table->index(['campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_campaign_recipients');
    }
};
