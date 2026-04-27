<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('segments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('file_id')->index();
            $table->uuid('project_id')->index();
            $table->integer('segment_number');
            $table->text('source_text');
            $table->text('target_text')->nullable();
            $table->jsonb('source_tags')->default('[]');
            $table->jsonb('target_tags')->default('[]');
            $table->string('status', 20)->default('untranslated');
            $table->integer('word_count')->default(0);
            $table->integer('char_count')->default(0);
            $table->smallInteger('tm_match_percent')->nullable();
            $table->string('tm_match_origin', 20)->nullable();
            $table->text('context_before')->nullable();
            $table->text('context_after')->nullable();
            $table->text('note')->nullable();
            $table->boolean('locked')->default(false);
            $table->boolean('bookmarked')->default(false);
            $table->timestamps();

            $table->unique(['file_id', 'segment_number']);
            $table->foreign('file_id')->references('id')->on('project_files')->cascadeOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->index(['file_id', 'status']);
            $table->index(['file_id', 'segment_number']);
            $table->index('status');
        });

        // GIN trigram index for concordance search
        DB::statement('CREATE INDEX segments_source_trgm ON segments USING GIN (source_text gin_trgm_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('segments');
    }
};
