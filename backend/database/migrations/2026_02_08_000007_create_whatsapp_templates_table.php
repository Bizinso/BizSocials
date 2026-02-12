<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('whatsapp_phone_number_id')->constrained('whatsapp_phone_numbers')->cascadeOnDelete();
            $table->string('meta_template_id')->nullable();
            $table->string('name');
            $table->string('language', 10)->default('en');
            $table->string('category'); // marketing, utility, authentication
            $table->string('status')->default('draft'); // draft, pending_approval, approved, rejected, disabled, paused
            $table->text('rejection_reason')->nullable();
            $table->string('header_type')->default('none'); // none, text, image, video, document
            $table->text('header_content')->nullable();
            $table->text('body_text');
            $table->string('footer_text', 60)->nullable();
            $table->json('buttons')->nullable();
            $table->json('sample_values')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->unique(['whatsapp_phone_number_id', 'name', 'language'], 'wa_tpl_ph_number_id_name_lang_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
