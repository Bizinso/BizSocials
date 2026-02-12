<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('platform'); // whatsapp, facebook, instagram, twitter, linkedin
            $table->string('policy_version');
            $table->string('policy_name');
            $table->text('description');
            $table->date('effective_date');
            $table->text('source_url')->nullable();
            $table->json('enforcement_actions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['platform', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_policies');
    }
};
