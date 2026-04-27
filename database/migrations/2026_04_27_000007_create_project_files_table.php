<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id')->index();
            $table->uuid('user_id');
            $table->string('original_name', 500);
            $table->text('storage_path');
            $table->string('file_format', 20);
            $table->string('mime_type', 100)->nullable();
            $table->bigInteger('file_size_bytes')->nullable();
            $table->string('skeleton_store', 20)->default('filesystem');
            $table->text('skeleton_path')->nullable();
            $table->binary('skeleton_blob')->nullable();
            $table->integer('word_count')->default(0);
            $table->integer('segment_count')->default(0);
            $table->integer('translated_count')->default(0);
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('export_path')->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_files');
    }
};
