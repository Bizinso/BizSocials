<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_library_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('folder_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->string('file_name');
            $table->string('original_name');
            $table->string('mime_type');
            $table->bigInteger('file_size')->unsigned();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->text('url');
            $table->text('thumbnail_url')->nullable();
            $table->string('alt_text')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('duration')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'folder_id']);
            $table->index(['workspace_id', 'mime_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_library_items');
    }
};
