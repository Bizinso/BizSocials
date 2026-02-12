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
        Schema::create('api_access_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->uuid('api_key_id')->nullable();
            $table->string('method', 10);
            $table->string('endpoint', 500);
            $table->integer('status_code');
            $table->integer('response_time_ms')->nullable();
            $table->bigInteger('request_size_bytes')->nullable();
            $table->bigInteger('response_size_bytes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('request_headers')->nullable();
            $table->json('request_params')->nullable();
            $table->text('error_message')->nullable();
            $table->string('request_id', 50)->nullable();
            $table->timestamps();

            // Indexes
            $table->index('tenant_id');
            $table->index('user_id');
            $table->index('api_key_id');
            $table->index('endpoint');
            $table->index('status_code');
            $table->index('created_at');
            $table->index('request_id');

            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->nullOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_access_logs');
    }
};
