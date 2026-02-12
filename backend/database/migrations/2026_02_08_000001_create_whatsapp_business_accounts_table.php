<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_business_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('meta_business_account_id', 100);
            $table->string('waba_id', 100)->unique();
            $table->string('name', 200);
            $table->string('status', 30)->default('pending_verification');
            $table->string('quality_rating', 20)->default('unknown');
            $table->string('messaging_limit_tier', 20)->default('tier_1k');
            $table->text('access_token_encrypted');
            $table->string('webhook_verify_token', 100);
            $table->json('webhook_subscribed_fields')->nullable();
            $table->timestamp('compliance_accepted_at')->nullable();
            $table->uuid('compliance_accepted_by_user_id')->nullable();
            $table->boolean('is_marketing_enabled')->default(false);
            $table->text('suspended_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('compliance_accepted_by_user_id', 'wba_compliance_user_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_business_accounts');
    }
};
