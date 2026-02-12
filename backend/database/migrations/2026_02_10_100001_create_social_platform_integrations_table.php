<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_platform_integrations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('provider', 50)->unique();
            $table->string('display_name', 100);
            $table->json('platforms');
            $table->text('app_id_encrypted');
            $table->text('app_secret_encrypted');
            $table->json('redirect_uris');
            $table->string('api_version', 10);
            $table->json('scopes');
            $table->boolean('is_enabled')->default(true);
            $table->string('status', 20)->default('active');
            $table->string('environment', 20)->default('production');
            $table->string('webhook_verify_token', 255)->nullable();
            $table->text('webhook_secret_encrypted')->nullable();
            $table->json('rate_limit_config')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('last_rotated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('updated_by')
                ->references('id')
                ->on('super_admin_users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_platform_integrations');
    }
};
