<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Table name and columns are hardcoded in catframework/translation-memory PostgresTranslationMemory.
// Do not rename this table or alter these column names without updating that package.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_units', function (Blueprint $table) {
            $table->id();
            $table->uuid('tm_id')->index();
            $table->string('source_lang', 20);
            $table->string('target_lang', 20);
            $table->text('source_text');
            $table->text('target_text');
            $table->text('source_segment');
            $table->text('target_segment');
            $table->text('source_text_normalized');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('last_used_at')->nullable();
            $table->string('created_by')->nullable();
            $table->jsonb('metadata')->default('{}');

            $table->unique(['tm_id', 'source_lang', 'target_lang', 'source_text_normalized']);
            $table->foreign('tm_id')->references('id')->on('translation_memories')->cascadeOnDelete();
        });

        // GIN trigram index on normalized source — required by D32 pg_trgm pre-filter
        DB::statement('CREATE INDEX tm_units_source_trgm ON tm_units USING GIN (source_text_normalized gin_trgm_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_units');
    }
};
