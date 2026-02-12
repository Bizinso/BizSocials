<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_phone_numbers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('whatsapp_business_account_id');
            $table->string('phone_number_id', 100)->unique();
            $table->string('phone_number', 20);
            $table->string('display_name', 200);
            $table->string('verified_name', 200)->nullable();
            $table->string('quality_rating', 20)->default('green');
            $table->string('status', 20)->default('active');
            $table->boolean('is_primary')->default(false);
            $table->string('category', 100)->nullable();
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            $table->string('website', 500)->nullable();
            $table->string('support_email', 255)->nullable();
            $table->text('profile_picture_url')->nullable();
            $table->unsignedInteger('daily_send_count')->default(0);
            $table->unsignedInteger('daily_send_limit')->default(1000);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['whatsapp_business_account_id', 'status'], 'wa_ph_numbers_wba_id_status_index');
            $table->foreign('whatsapp_business_account_id', 'wa_ph_numbers_wba_id_foreign')->references('id')->on('whatsapp_business_accounts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_phone_numbers');
    }
};
